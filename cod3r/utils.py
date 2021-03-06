# *-* coding: utf-8 *-*

import os
import pwd
import re
import struct
import subprocess
from sys import version_info, platform
import time
import datetime
import json
import shutil
import uuid
import stat
import zipfile
from collections import namedtuple
from logging import getLogger

log = getLogger('cod3r')


def get_hex_ip_port(remote):
    ip, port = remote
    if ip.startswith('::ffff:'):
        ip = ip[len('::ffff:'):]
    splits = ip.split('.')
    if ':' not in ip and len(splits) == 4:
        # Must be an ipv4
        return '%02X%02X%02X%02X:%04X' % (
            int(splits[3]),
            int(splits[2]),
            int(splits[1]),
            int(splits[0]),
            int(port)
        )
    try:
        import ipaddress
    except ImportError:
        print('Please install ipaddress backport for ipv6 user detection')
        return ''

    # Endian reverse:
    ipv6_parts = ipaddress.IPv6Address(ip).exploded.split(':')
    for i in range(0, 8, 2):
        ipv6_parts[i], ipv6_parts[i + 1] = (
            ipv6_parts[i + 1][2:] + ipv6_parts[i + 1][:2],
            ipv6_parts[i][2:] + ipv6_parts[i][:2])

    return ''.join(ipv6_parts) + ':%04X' % port


def parse_cert(cert):
    user = None

    for elt in cert['subject']:
        user = dict(elt).get('commonName', None)
        if user:
            break

    return user


class User(object):
    def __init__(self, uid=None, name=None):
        if uid is None and not name:
            uid = os.getuid()
        if uid is not None:
            self.pw = pwd.getpwuid(uid)
        else:
            self.pw = pwd.getpwnam(name)
        if self.pw is None:
            raise LookupError('Unknown user')

    @property
    def uid(self):
        return self.pw.pw_uid

    @property
    def gid(self):
        return self.pw.pw_gid

    @property
    def name(self):
        return self.pw.pw_name

    @property
    def dir(self):
        return self.pw.pw_dir

    @property
    def shell(self):
        return self.pw.pw_shell

    @property
    def root(self):
        return self.uid == 0

    def __eq__(self, other):
        if other is None:
            return False
        return self.uid == other.uid

    def __repr__(self):
        return "%s [%r]" % (self.name, self.uid)


class Socket(object):

    def __init__(self, socket):
        sn = socket.getsockname()
        self.local_addr = sn[0]
        self.local_port = sn[1]
        try:
            pn = socket.getpeername()
            self.remote_addr = pn[0]
            self.remote_port = pn[1]
        except Exception:
            log.debug("Can't get peer name", exc_info=True)
            self.remote_addr = '???'
            self.remote_port = 0
        self.user = None
        self.env = {}

        if not self.local:
            return

        # If there is procfs, get as much info as we can
        if os.path.exists('/proc/net'):
            try:
                line = get_procfs_socket_line(get_hex_ip_port(pn[:2]))
                self.user = User(uid=int(line[7]))
                self.env = get_socket_env(line[9], self.user)
            except Exception:
                log.debug('procfs was no good, aight', exc_info=True)

        if self.user is None:
            # Try with lsof
            try:
                self.user = User(name=get_lsof_socket_line(
                    self.remote_addr, self.remote_port)[1])
            except Exception:
                log.debug('lsof was no good', exc_info=True)

    @property
    def local(self):
        return (self.remote_addr in ['127.0.0.1', '::1', '::ffff:127.0.0.1'] or
                self.local_addr == self.remote_addr)

    def __repr__(self):
        return '<Socket L: %s:%d R: %s:%d User: %r>' % (
            self.local_addr, self.local_port,
            self.remote_addr, self.remote_port,
            self.user)


# Portable way to get the user, if lsof is installed
def get_lsof_socket_line(addr, port):
    # May want to make this into a dictionary in the future...
    regex = "\w+\s+(?P<pid>\d+)\s+(?P<user>\w+).*\s" \
            "(?P<laddr>.*?):(?P<lport>\d+)->(?P<raddr>.*?):(?P<rport>\d+)"
    output = subprocess.check_output(['lsof', '-Pni']).decode('utf-8')
    lines = output.split('\n')
    for line in lines:
        # Look for local address with peer port
        match = re.findall(regex, line)
        if len(match):
            match = match[0]
            if int(match[5]) == port:
                return match
    raise Exception("Couldn't find a match!")


# Linux only socket line get
def get_procfs_socket_line(hex_ip_port):
    fn = None
    if len(hex_ip_port) == 13:  # ipv4
        fn = '/proc/net/tcp'
    elif len(hex_ip_port) == 37:  # ipv6
        fn = '/proc/net/tcp6'
    if not fn:
        return
    try:
        with open(fn) as k:
            lines = k.readlines()
        for line in lines:
            # Look for local address with peer port
            if line.split()[1] == hex_ip_port:
                # We got the socket
                return line.split()
    except Exception:
        log.debug('getting socket %s line fail' % fn, exc_info=True)


# Linux only browser environment far fetch
def get_socket_env(inode, user):
    for pid in os.listdir("/proc/"):
        if not pid.isdigit():
            continue
        try:
            with open('/proc/%s/cmdline' % pid) as c:
                command = c.read().split('\x00')
                executable = command[0].split('/')[-1]
                if executable in ('sh', 'bash', 'zsh'):
                    executable = command[1].split('/')[-1]
                if executable in [
                        'gnome-session',
                        'gnome-session-binary',
                        'startkde',
                        'startdde',
                        'xfce4-session']:
                    with open('/proc/%s/status' % pid) as e:
                        uid = None
                        for line in e.read().splitlines():
                            parts = line.split('\t')
                            if parts[0] == 'Uid:':
                                uid = int(parts[1])
                                break
                        if not uid or uid != user.uid:
                            continue

                    with open('/proc/%s/environ' % pid) as e:
                        keyvals = e.read().split('\x00')
                        env = {}
                        for keyval in keyvals:
                            if '=' in keyval:
                                key, val = keyval.split('=', 1)
                                env[key] = val
                        return env
        except Exception:
            continue

    for pid in os.listdir("/proc/"):
        if not pid.isdigit():
            continue
        for fd in os.listdir("/proc/%s/fd/" % pid):
            lnk = "/proc/%s/fd/%s" % (pid, fd)
            if not os.path.islink(lnk):
                continue
            if 'socket:[%s]' % inode == os.readlink(lnk):
                with open('/proc/%s/status' % pid) as s:
                    for line in s.readlines():
                        if line.startswith('PPid:'):
                            with open('/proc/%s/environ' %
                                      line[len('PPid:'):].strip()) as e:
                                keyvals = e.read().split('\x00')
                                env = {}
                                for keyval in keyvals:
                                    if '=' in keyval:
                                        key, val = keyval.split('=', 1)
                                        env[key] = val
                                return env


utmp_struct = struct.Struct('hi32s4s32s256shhiii4i20s')


if version_info[0] == 2:
    b = lambda x: x
else:
    def b(x):
        if isinstance(x, str):
            return x.encode('utf-8')
        return x


def get_utmp_file():
    for file in (
            '/var/run/utmp',
            '/var/adm/utmp',
            '/var/adm/utmpx',
            '/etc/utmp',
            '/etc/utmpx',
            '/var/run/utx.active'):
        if os.path.exists(file):
            return file


def get_wtmp_file():
    for file in (
            '/var/log/wtmp',
            '/var/adm/wtmp',
            '/var/adm/wtmpx',
            '/var/run/utx.log'):
        if os.path.exists(file):
            return file


UTmp = namedtuple(
    'UTmp',
    ['type', 'pid', 'line', 'id', 'user', 'host',
     'exit0', 'exit1', 'session',
     'sec', 'usec', 'addr0', 'addr1', 'addr2', 'addr3', 'unused'])


def utmp_line(id, type, pid, fd, user, host, ts):
    return UTmp(
        type,  # Type, 7 : user process
        pid,  # pid
        b(fd),  # line
        b(id),  # id
        b(user),  # user
        b(host),  # host
        0,  # exit 0
        0,  # exit 1
        0,  # session
        int(ts),  # sec
        int(10 ** 6 * (ts - int(ts))),  # usec
        0,  # addr 0
        0,  # addr 1
        0,  # addr 2
        0,  # addr 3
        b('')  # unused
    )


def add_user_info(id, fd, pid, user, host):
    # Freebsd format is not yet supported.
    # Please submit PR
    if platform != 'linux':
        return
    utmp = utmp_line(id, 7, pid, fd, user, host, time.time())
    for kind, file in {
            'utmp': get_utmp_file(),
            'wtmp': get_wtmp_file()}.items():
        if not file:
            continue
        try:
            with open(file, 'rb+') as f:
                s = f.read(utmp_struct.size)
                while s:
                    entry = UTmp(*utmp_struct.unpack(s))
                    if kind == 'utmp' and entry.id == utmp.id:
                        # Same id recycling
                        f.seek(f.tell() - utmp_struct.size)
                        f.write(utmp_struct.pack(*utmp))
                        break
                    s = f.read(utmp_struct.size)
                else:
                    f.write(utmp_struct.pack(*utmp))
        except Exception:
            log.debug('Unable to write utmp info to ' + file, exc_info=True)


def rm_user_info(id, pid):
    if platform != 'linux':
        return
    utmp = utmp_line(id, 8, pid, '', '', '', time.time())
    for kind, file in {
            'utmp': get_utmp_file(),
            'wtmp': get_wtmp_file()}.items():
        if not file:
            continue
        try:
            with open(file, 'rb+') as f:
                s = f.read(utmp_struct.size)
                while s:
                    entry = UTmp(*utmp_struct.unpack(s))
                    if entry.id == utmp.id:
                        if kind == 'utmp':
                            # Same id closing
                            f.seek(f.tell() - utmp_struct.size)
                            f.write(utmp_struct.pack(*utmp))
                            break
                        else:
                            utmp = utmp_line(
                                id, 8, pid, entry.line, entry.user, '',
                                time.time())

                    s = f.read(utmp_struct.size)
                else:
                    f.write(utmp_struct.pack(*utmp))

        except Exception:
            log.debug('Unable to update utmp info to ' + file, exc_info=True)


class AnsiColors(object):
    colors = {
        'black': 30,
        'red': 31,
        'green': 32,
        'yellow': 33,
        'blue': 34,
        'magenta': 35,
        'cyan': 36,
        'white': 37
    }

    def __getattr__(self, key):
        bold = True
        if key.startswith('light_'):
            bold = False
            key = key[len('light_'):]
        if key in self.colors:
            return '\x1b[%d%sm' % (
                self.colors[key],
                ';1' if bold else '')
        if key == 'reset':
            return '\x1b[0m'
        return ''


ansi_colors = AnsiColors()

def u(s):
    if version_info[0] == 2:
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
    def __init__(self, root='/home/robot/'):
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
                return {'result': {'success': False, 'error': 'Invalid path'}}

            shutil.move(src, dst)
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def copy(self, request):
        try:
            items = request['items']
            path = os.path.abspath(self.root + request['newPath'])
            if len(items) == 1 and 'singleFilename' in request:
                src = os.path.abspath(self.root + items[0])
                if not (os.path.exists(src) and src.startswith(self.root) and path.startswith(self.root)):
                    return {'result': {'success': False, 'error': 'File not found'}}

                shutil.copy(src, path)
            else:
                for item in items:
                    src = os.path.abspath(self.root + item)
                    if not (os.path.exists(src) and src.startswith(self.root) and path.startswith(self.root)):
                        return {'result': {'success': False, 'error': 'Invalid path'}}

                    shutil.copy(src, path)
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def move(self, request):
        try:
            items = request['items']
            path = os.path.abspath(self.root + request['newPath'])
            for item in items:
                src = os.path.abspath(self.root + item)
                if not (os.path.exists(src) and src.startswith(self.root) and path.startswith(self.root)):
                    return {'result': {'success': False, 'error': 'Invalid path'}}

                shutil.move(src, path)
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def remove(self, request):
        try:
            items = request['items']
            for item in items:
                path = os.path.abspath(self.root + item)
                if not (os.path.exists(path) and path.startswith(self.root)):
                    return {'result': {'success': False, 'error': 'Invalid path'}}

                if os.path.isdir(path):
                    shutil.rmtree(path)
                else:
                    os.remove(path)
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def edit(self, request):
        try:
            path = os.path.abspath(self.root + request['item'])
            if not path.startswith(self.root):
                return {'result': {'success': False, 'error': 'Invalid path'}}

            content = request['content']
            with open(path, 'w') as f:
                f.write(content)
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def getContent(self, request):
        try:
            path = os.path.abspath(self.root + request['item'])
            if not path.startswith(self.root):
                return {'result': {'success': False, 'error': 'Invalid path'}}

            with open(path, 'r') as f:
                content = f.read()
        except Exception as e:
            content = str(e)

        return {'result': content}

    def createFolder(self, request):
        try:
            path = os.path.abspath(self.root + request['newPath'])
            if not path.startswith(self.root):
                return {'result': {'success': False, 'error': 'Invalid path'}}

            os.makedirs(path)
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def changePermissions(self, request):
        try:
            items = request['items']
            permissions = int(request['perms'], 8)
            recursive = request['recursive']
            print('recursive: {}, type: {}'.format(recursive, type(recursive)))
            for item in items:
                path = os.path.abspath(self.root + item)
                if not (os.path.exists(path) and path.startswith(self.root)):
                    return {'result': {'success': False, 'error': 'Invalid path'}}

                if recursive == True:
                    change_permissions_recursive(path, permissions)
                else:
                    os.chmod(path, permissions)
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def compress(self, request):
        try:
            items = request['items']
            path = os.path.abspath(os.path.join(self.root + request['destination'], request['compressedFilename']))
            if not path.startswith(self.root):
                return {'result': {'success': False, 'error': 'Invalid path'}}

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
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def extract(self, request):
        try:
            src = os.path.abspath(self.root + request['item'])
            dst = os.path.abspath(self.root + request['destination'])
            if not (os.path.isfile(src) and src.startswith(self.root) and dst.startswith(self.root)):
                return {'result': {'success': False, 'error': 'Invalid path'}}

            zip_file = zipfile.ZipFile(src, 'r')
            zip_file.extractall(dst)
            zip_file.close()
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

    def upload(self, handler):
        try:
            destination = handler.get_body_argument('destination', default='/')[1:]
            for name in handler.request.files:
                fileinfo = handler.request.files[name][0]
                filename = fileinfo['filename']
                path = os.path.abspath(os.path.join(self.root, destination, filename))
                if not path.startswith(self.root):
                    return {'result': {'success': False, 'error': 'Invalid path'}}
                with open(path, 'wb') as f:
                    f.write(fileinfo['body'])
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}

        return {'result': {'success': True, 'error': ''}}

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
    def createFile(self, request):
        path = os.path.abspath(self.root + request['item'])
        if not path.startswith(self.root):
            return {'result': {'success': False, 'error': 'Invalid path'}}
        try:
            from pathlib import Path
            filename = Path(path)
            filename.touch(exist_ok=True) 
            return {'result': {'success': True, 'error': ''}}
        except Exception as e:
            return {'result': {'success': False, 'error': str(e)}}