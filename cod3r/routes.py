# *-* coding: utf-8 *-*
# This file is part of cod3r
#
# cod3r Copyright(C) 2015-2017 Florian Mounier
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


import json
import os
import struct
import sys
import time
from collections import defaultdict
from mimetypes import guess_type
import datetime
import shutil
import uuid
import stat
import zipfile
import tornado.web
import tornado.escape
import tornado.options
import tornado.process
import tornado.web
import tornado.websocket
import cod3r.utils as utils
from cod3r import Route, url, wrappers
from cod3r.terminal import Terminal
import mimetypes

@url(r'/(?:index.html)?')
class Index(Route):
    def get(self):
        return self.render('index.html')

@url(r'/frame.html')
class Frame(Route):
    def get(self):
        return self.render('frame.html')

@url(r'/manage')
class Manage(Route):
    def get(self):
        return self.render('manage.html')


@url(r'/shell(?:user/(.+))?/?(?:wd/(.+))?/?(?:session/(.+))?')
class Shell(Route):
    def get(self, user, path, session):
        user = self.request.query_arguments.get(
            'user', [b''])[0].decode('utf-8')
        if not tornado.options.options.unsecure and user:
            raise tornado.web.HTTPError(400)
        return self.render(
            'shell.html', session=session or str(uuid.uuid4()))


@url(r'/theme/([^/]+)/style.css')
class Theme(Route):

    def get(self, theme):
        self.log.info('Getting style')
        base_dir = self.get_theme_dir(theme)

        style = None
        for ext in ['css']:
            probable_style = os.path.join(base_dir, 'style.%s' % ext)
            if os.path.exists(probable_style):
                style = probable_style

        if not style:
            raise tornado.web.HTTPError(404)
            
        with open(style, 'r') as myfile:
            css=myfile.read().replace('\n', '')
        self.log.debug('Style ok')
        self.set_header("Content-Type", "text/css")
        self.write(css)
        self.finish()


@url(r'/theme/([^/]+)/(.+)')
class ThemeStatic(Route):
    def get(self, theme, name):
        if '..' in name:
            raise tornado.web.HTTPError(403)

        base_dir = self.get_theme_dir(theme)

        fn = os.path.normpath(os.path.join(base_dir, name))
        if not fn.startswith(base_dir):
            raise tornado.web.HTTPError(403)

        if os.path.exists(fn):
            type = guess_type(fn)[0]
            if type is None:
                # Fallback if there's no mimetypes on the system
                type = {
                    'png': 'image/png',
                    'jpg': 'image/jpeg',
                    'jpeg': 'image/jpeg',
                    'gif': 'image/gif',
                    'woff': 'application/font-woff',
                    'ttf': 'application/x-font-ttf'
                }.get(fn.split('.')[-1], 'text/plain')

            self.set_header("Content-Type", type)
            with open(fn, 'rb') as s:
                while True:
                    data = s.read(16384)
                    if data:
                        self.write(data)
                    else:
                        break
            self.finish()
        raise tornado.web.HTTPError(404)


class KeptAliveWebSocketHandler(tornado.websocket.WebSocketHandler):
    keepalive_timer = None

    def open(self, *args, **kwargs):
        self.keepalive_timer = tornado.ioloop.PeriodicCallback(
            self.send_ping, tornado.options.options.keepalive_interval * 1000)
        self.keepalive_timer.start()

    def send_ping(self):
        t = int(time.time())
        frame = struct.pack('<I', t)  # A ping frame based on time
        self.log.info("Sending ping frame %s" % t)
        try:
            self.ping(frame)
        except tornado.websocket.WebSocketClosedError:
            self.keepalive_timer.stop()

    def on_close(self):
        if self.keepalive_timer is not None:
            self.keepalive_timer.stop()

@url(r'/ctl/session/(?P<session>[^/]+)')
class TermCtlWebSocket(Route, KeptAliveWebSocketHandler):
    sessions = defaultdict(list)
    sessions_secure_users = {}

    def open(self, session):
        super(TermCtlWebSocket, self).open(session)
        self.session = session
        self.closed = False
        self.log.info('Websocket /ctl opened %r' % self)

    def create_terminal(self):
        socket = utils.Socket(self.ws_connection.stream.socket)
        user = self.request.query_arguments.get(
            'user', [b''])[0].decode('utf-8')
        path = self.request.query_arguments.get(
            'path', [b''])[0].decode('utf-8')
        secure_user = None

        if not tornado.options.options.unsecure:
            user = utils.parse_cert(
                self.ws_connection.stream.socket.getpeercert())
            assert user, 'No user in certificate'
            try:
                user = utils.User(name=user)
            except LookupError:
                raise Exception('Invalid user in certificate')

            # Certificate authed user
            secure_user = user

        elif socket.local and socket.user == utils.User() and not user:
            # Local to local returning browser user
            secure_user = socket.user
        elif user:
            try:
                user = utils.User(name=user)
            except LookupError:
                raise Exception('Invalid user')

        if secure_user:
            user = secure_user
            if self.session in self.sessions and self.session in (
                    self.sessions_secure_users):
                if user.name != self.sessions_secure_users[self.session]:
                    # Restrict to authorized users
                    raise tornado.web.HTTPError(403)
            else:
                self.sessions_secure_users[self.session] = user.name

        self.sessions[self.session].append(self)

        terminal = Terminal.sessions.get(self.session)
        # Handling terminal session
        if terminal:
            TermWebSocket.last.write_message(terminal.history)
            # And returning, we don't want another terminal
            return

        # New session, opening terminal
        terminal = Terminal(
            user, path, self.session, socket,
            self.request.full_url().replace('/ctl/', '/'), self.render_string,
            TermWebSocket.broadcast)

        terminal.pty()
        self.log.info('Openning session %s for secure user %r' % (
            self.session, user))

    @classmethod
    def broadcast(cls, session, message, emitter=None):
        for wsocket in cls.sessions[session]:
            try:
                if wsocket != emitter:
                    wsocket.write_message(message)
            except Exception:
                wsocket.log.exception('Error on broadcast')
                wsocket.close()

    def on_message(self, message):
        cmd = json.loads(message)
        if cmd['cmd'] == 'open':
            self.create_terminal()
        else:
            try:
                Terminal.sessions[self.session].ctl(cmd)
            except Exception:
                # FF strange bug
                pass
        self.broadcast(self.session, message, self)

    def on_close(self):
        super(TermCtlWebSocket, self).on_close()
        if self.closed:
            return
        self.closed = True
        self.log.info('Websocket /ctl closed %r' % self)
        if self in self.sessions[self.session]:
            self.sessions[self.session].remove(self)

        if tornado.options.options.one_shot or (
                getattr(self.application, 'systemd', False) and
                not sum([
                    len(wsockets)
                    for session, wsockets in self.sessions.items()])):
            sys.exit(0)


@url(r'/ws/session/(?P<session>[^/]+)'
     '(?:/user/(?P<user>[^/]+))?/?')
class TermWebSocket(Route, KeptAliveWebSocketHandler):
    # List of websockets per session
    sessions = defaultdict(list)

    # Last is kept for session shared history send
    last = None

    # Session history
    history = {}

    def open(self, session="", user=None, path=""):
        super(TermWebSocket, self).open(session)

        self.set_nodelay(True)
        self.session = session
        self.closed = False
        self.sessions[session].append(self)
        self.user = user if user else None
        self.__class__.last = self
        self.log.info('Websocket /ws opened %r' % self)

    @classmethod
    def close_session(cls, session):
        wsockets = (cls.sessions.get(session, []) +
                    TermCtlWebSocket.sessions.get(session, []))
        for wsocket in wsockets:
            wsocket.on_close()

            wsocket.close()

        if session in cls.sessions:
            del cls.sessions[session]
        if session in TermCtlWebSocket.sessions_secure_users:
            del TermCtlWebSocket.sessions_secure_users[session]
        if session in TermCtlWebSocket.sessions:
            del TermCtlWebSocket.sessions[session]

    @classmethod
    def broadcast(cls, session, message, emitter=None):
        if message is None:
            cls.close_session(session)
            return

        wsockets = cls.sessions.get(session)
        for wsocket in wsockets:
            try:
                if wsocket != emitter:
                    wsocket.write_message(message)
            except Exception:
                wsocket.log.exception('Error on broadcast')
                wsocket.close()

    def on_message(self, message):
        Terminal.sessions[self.session].write(message)

    def on_close(self):
        super(TermWebSocket, self).on_close()
        if self.closed:
            return
        self.closed = True
        self.log.info('Websocket /ws closed %r' % self)
        self.sessions[self.session].remove(self)


@url(r'/sessions/list.json')
class SessionsList(Route):
    """Get the theme list"""

    def get(self):
        if tornado.options.options.unsecure:
            raise tornado.web.HTTPError(403)

        cert = self.request.get_ssl_certificate()
        user = utils.parse_cert(cert)

        if not user:
            raise tornado.web.HTTPError(403)

        self.set_header('Content-Type', 'application/json')
        self.write(tornado.escape.json_encode({
            'sessions': sorted(
                TermWebSocket.sessions),
            'user': user
        }))


@url(r'/themes/list.json')
class ThemesList(Route):
    """Get the theme list"""

    def get(self):

        if os.path.exists(self.themes_dir):
            themes = [
                theme
                for theme in os.listdir(self.themes_dir)
                if os.path.isdir(os.path.join(self.themes_dir, theme)) and
                not theme.startswith('.')]
        else:
            themes = []

        if os.path.exists(self.builtin_themes_dir):
            builtin_themes = [
                'built-in-%s' % theme
                for theme in os.listdir(self.builtin_themes_dir)
                if os.path.isdir(os.path.join(
                    self.builtin_themes_dir, theme)) and
                not theme.startswith('.')]
        else:
            builtin_themes = []

        self.set_header('Content-Type', 'application/json')
        self.write(tornado.escape.json_encode({
            'themes': sorted(themes),
            'builtin_themes': sorted(builtin_themes),
            'dir': self.themes_dir
        }))
@url('/connect')
class WSHandler(Route, KeptAliveWebSocketHandler):
    def open(self):
        super(WSHandler, self).open()
        self.wrapper = utils.apiWrapper()
        def change(changed_buttons): 
            print('These buttons changed state: ' + str(changed_buttons))
            for btn, state in changed_buttons.__dict__.items():
                if(state == True):
                    self.write_message("{\"pos\":\"%s\"}" % btn)

        self.wrapper.button.btn.on_change = change
        print('new connection')

    def on_message(self, message):
        print('message received:  %s' % message)
        try:
            data = tornado.escape.json_decode(message)
        except Exception as e:
            print(str(e))
            pass
        act = data['act']
        msg = {}
        if(act == "ufn"):
            if 'ns' in data and hasattr(self.wrapper, data['ns']):
                wrapper = getattr(self.wrapper, data['ns'])
                if 'fn' in data and hasattr(wrapper, data['fn']):
                    method = getattr(wrapper, data['fn'])
                    try:
                        res = method(*data['args']) or None
                        if(type(res) is dict):
                            msg = res
                        else:
                            msg = {'res': res}
                    except Exception as e:
                        msg = {'err': str(e)}

        if(act == "shutdownBrick" or act == "stopCod3r"):
            self.write_message('{"txt":"Bye"}')
            self.close()
            tornado.ioloop.IOLoop.instance().stop()
            if(act is "shutdownBrick"):
                import os
                os.system('sudo shutdown')
            exit()

        if('id' in data):
            msg['id'] = data['id']
        msg = json.dumps(msg)
        print('sending back message: %s' % msg)
        self.write_message(msg)

    def on_close(self):
        super(WSHandler, self).on_close()
        print('connection closed')

    def check_origin(self, origin):
        return True

@url('/bridge.py')
class FileManagerHandler(tornado.web.RequestHandler):
    def initialize(self, root='/home/robot/'):
        self.filemanager = utils.FileManager(root)

    def get(self):

        action = self.get_query_argument('action', '')
        path = self.get_query_argument('path', '')
        items = self.get_query_arguments('items')
        toFilename = self.get_query_argument('toFilename', '')

        if action == 'download' and path:
            result = self.filemanager.download(path)
            self.write(result)
            
            file_name = os.path.basename(path)
            self.set_header('Content-Type', 'application/force-download')
            self.set_header('Content-Disposition', 'attachment; filename=%s' % file_name) 
        elif action == 'downloadMultiple' and len(items) > 0 and toFilename:
            result = self.filemanager.downloadMultiple(items,toFilename)
            self.write(result)
            
            file_name = os.path.basename(toFilename)
            self.set_header('Content-Type', 'application/force-download')
            self.set_header('Content-Disposition', 'attachment; filename=%s' % file_name) 
        else:
            pass

    def post(self):
        if self.request.headers.get('Content-Type').find('multipart/form-data') >= 0:
            result = self.filemanager.upload(self)
            self.write(json.dumps(result))
        else:
            try:
                request = tornado.escape.json_decode(self.request.body)
                if 'action' in request and hasattr(self.filemanager, request['action']):
                    method = getattr(self.filemanager, request['action'])
                    result = method(request)
                    self.write(json.dumps(result))
            except ValueError:
                pass