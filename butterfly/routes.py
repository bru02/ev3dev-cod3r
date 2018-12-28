# *-* coding: utf-8 *-*
# This file is part of butterfly
#
# butterfly Copyright(C) 2015-2017 Florian Mounier
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
from butterfly import Route, url, utils
from butterfly.terminal import Terminal
import mimetypes

def u(s):
    if sys.version_info[0] == 2:
        return s.decode('utf-8')
    return s


def timestamp_to_str(timestamp, format_str='%Y-%m-%d %I:%M:%S'):
    date = datetime.datetime.fromtimestamp(timestamp)
    return date.strftime(format_str)


def filemode(mode):
    is_dir = 'd' if stat.S_ISDIR(mode) else '-'
    dic = {'7': 'rwx', '6': 'rw-', '5': 'r-x', '4': 'r--', '0': '---'}
    perm = str(oct(mode)[-3:])
    return is_dir + ''.join(dic.get(x, x) for x in perm)


def get_file_information(path):
    fstat = os.stat(path)
    if stat.S_ISDIR(fstat.st_mode):
        ftype = 'dir'
    else:
        ftype = 'file'

    fsize = fstat.st_size
    ftime = timestamp_to_str(fstat.st_mtime)
    fmode = filemode(fstat.st_mode)

    return ftype, fsize, ftime, fmode


def change_permissions_recursive(path, mode):
    for root, dirs, files in os.walk(path, topdown=False):
        for d in [os.path.join(root, d) for d in dirs]:
            os.chmod(d, mode)
        for f in [os.path.join(root, f) for f in files]:
            os.chmod(f, mode)


class FileManager:
    def __init__(self, root='/'):
        self.root = os.path.abspath(root)

    def list(self, request):
        path = os.path.abspath(self.root + request['path'])
        if not os.path.exists(path) or not path.startswith(self.root):
            return {'result': ''}

        files = []
        for fname in sorted(os.listdir(path)):
            if fname.startswith('.'):
                continue

            fpath = os.path.join(path, fname)

            try:
                ftype, fsize, ftime, fmode = get_file_information(fpath)
            except Exception as e:
                continue

            files.append({
                'name': fname,
                'rights': fmode,
                'size': fsize,
                'date': ftime,
                'type': ftype,
            })

        return {'result': files}

    def rename(self, request):
        try:
            src = os.path.abspath(self.root + request['item'])
            dst = os.path.abspath(self.root + request['newItemPath'])
            print('rename {} {}'.format(src, dst))
            if not (os.path.exists(src) and src.startswith(self.root) and dst.startswith(self.root)):
                return {'result': {'success': 'false', 'error': 'Invalid path'}}

            shutil.move(src, dst)
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def copy(self, request):
        try:
            items = request['items']
            path = os.path.abspath(self.root + request['newPath'])
            if len(items) == 1 and 'singleFilename' in request:
                src = os.path.abspath(self.root + items[0])
                if not (os.path.exists(src) and src.startswith(self.root) and path.startswith(self.root)):
                    return {'result': {'success': 'false', 'error': 'File not found'}}

                shutil.copy(src, path)
            else:
                for item in items:
                    src = os.path.abspath(self.root + item)
                    if not (os.path.exists(src) and src.startswith(self.root) and path.startswith(self.root)):
                        return {'result': {'success': 'false', 'error': 'Invalid path'}}

                    shutil.copy(src, path)
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def move(self, request):
        try:
            items = request['items']
            path = os.path.abspath(self.root + request['newPath'])
            for item in items:
                src = os.path.abspath(self.root + item)
                if not (os.path.exists(src) and src.startswith(self.root) and path.startswith(self.root)):
                    return {'result': {'success': 'false', 'error': 'Invalid path'}}

                shutil.move(src, path)
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def remove(self, request):
        try:
            items = request['items']
            for item in items:
                path = os.path.abspath(self.root + item)
                if not (os.path.exists(path) and path.startswith(self.root)):
                    return {'result': {'success': 'false', 'error': 'Invalid path'}}

                if os.path.isdir(path):
                    shutil.rmtree(path)
                else:
                    os.remove(path)
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def edit(self, request):
        try:
            path = os.path.abspath(self.root + request['item'])
            if not path.startswith(self.root):
                return {'result': {'success': 'false', 'error': 'Invalid path'}}

            content = request['content']
            with open(path, 'w') as f:
                f.write(content)
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def getContent(self, request):
        try:
            path = os.path.abspath(self.root + request['item'])
            if not path.startswith(self.root):
                return {'result': {'success': 'false', 'error': 'Invalid path'}}

            with open(path, 'r') as f:
                content = f.read()
        except Exception as e:
            content = e.message

        return {'result': content}

    def createFolder(self, request):
        try:
            path = os.path.abspath(self.root + request['newPath'])
            if not path.startswith(self.root):
                return {'result': {'success': 'false', 'error': 'Invalid path'}}

            os.makedirs(path)
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def changePermissions(self, request):
        try:
            items = request['items']
            permissions = int(request['perms'], 8)
            recursive = request['recursive']
            print('recursive: {}, type: {}'.format(recursive, type(recursive)))
            for item in items:
                path = os.path.abspath(self.root + item)
                if not (os.path.exists(path) and path.startswith(self.root)):
                    return {'result': {'success': 'false', 'error': 'Invalid path'}}

                if recursive == 'true':
                    change_permissions_recursive(path, permissions)
                else:
                    os.chmod(path, permissions)
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def compress(self, request):
        try:
            items = request['items']
            path = os.path.abspath(os.path.join(self.root + request['destination'], request['compressedFilename']))
            if not path.startswith(self.root):
                return {'result': {'success': 'false', 'error': 'Invalid path'}}

            zip_file = zipfile.ZipFile(path, 'w', zipfile.ZIP_DEFLATED)
            for item in items:
                path = os.path.abspath(self.root + item)
                if not (os.path.exists(path) and path.startswith(self.root)):
                    continue

                if os.path.isfile(path):
                    zip_file.write(path, item)
                else:
                    zip_file.write(path, arcname=os.path.basename(path))

                    for root, dirs, files in os.walk(path):
                        if root != path:
                            zip_file.write(root, arcname=os.path.relpath(root, os.path.dirname(path)))
                        for filename in files:
                            zip_file.write(os.path.join(root, filename), arcname=os.path.join(os.path.relpath(root, os.path.dirname(path)), filename))                

            zip_file.close()
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def extract(self, request):
        try:
            src = os.path.abspath(self.root + request['item'])
            dst = os.path.abspath(self.root + request['destination'])
            if not (os.path.isfile(src) and src.startswith(self.root) and dst.startswith(self.root)):
                return {'result': {'success': 'false', 'error': 'Invalid path'}}

            zip_file = zipfile.ZipFile(src, 'r')
            zip_file.extractall(dst)
            zip_file.close()
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def upload(self, handler):
        try:
            destination = handler.get_body_argument('destination', default='/')[1:]
            for name in handler.request.files:
                fileinfo = handler.request.files[name][0]
                filename = fileinfo['filename']
                path = os.path.abspath(os.path.join(self.root, destination, filename))
                if not path.startswith(self.root):
                    return {'result': {'success': 'false', 'error': 'Invalid path'}}
                with open(path, 'wb') as f:
                    f.write(fileinfo['body'])
        except Exception as e:
            return {'result': {'success': 'false', 'error': e.message}}

        return {'result': {'success': 'true', 'error': ''}}

    def download(self, path):
        path = os.path.abspath(self.root + path)
        content = ''
        if path.startswith(self.root) and os.path.isfile(path):
            try:
                with open(path, 'rb') as f:
                    content = f.read()
            except Exception as e:
                pass
        return content

    def downloadMultiple(self, items, filename):
        temp_zip_filename = str(uuid.uuid4())
        zipfile_path = os.path.abspath(os.path.join(self.root, temp_zip_filename))
        zip_file = zipfile.ZipFile(zipfile_path, 'w', zipfile.ZIP_DEFLATED)
        for item in items:
            path = os.path.abspath(self.root + item)
            if not (os.path.exists(path) and path.startswith(self.root)):
                continue

            if os.path.isfile(path):
                zip_file.write(path, item)
            else:
                pass
        zip_file.close()     

        content = ''
        f = open(zipfile_path,'rb')
        content = f.read()
        f.close()
        os.remove(zipfile_path)
        return content


@url(r'/(?:user/(.+))?/?(?:wd/(.+))?/?(?:session/(.+))?')
class Index(Route):
    def get(self, user, path, session):
        user = self.request.query_arguments.get(
            'user', [b''])[0].decode('utf-8')
        if not tornado.options.options.unsecure and user:
            raise tornado.web.HTTPError(400)
        return self.render(
            'index.html', session=session or str(uuid4()))


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
class WSHandler(tornado.websocket.WebSocketHandler):
    def open(self):
        print('new connection')

    def on_message(self, message):
        print('message received:  %s' % message)
        data = tornado.escape.json_decode(message)
        # Reverse Message and send it back
        print('sending back message: %s' % message[::-1])
        self.write_message(message[::-1])

    def on_close(self):
        print('connection closed')

    def check_origin(self, origin):
        return True

@url('/bridge.py')
class FileManagerHandler(tornado.web.RequestHandler):
    def initialize(self, root='/'):
        self.filemanager = FileManager(root)

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
