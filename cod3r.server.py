#!/usr/bin/env python
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

import tornado.options
import tornado.ioloop
import tornado.httpserver
try:
    from tornado_systemd import SystemdHTTPServer as HTTPServer
except ImportError:
    from tornado.httpserver import HTTPServer

import logging
import uuid
import ssl
import getpass
import os
import shutil
import stat
import socket
import sys

tornado.options.define("debug", default=True, help="Debug mode")
tornado.options.define("more", default=True,
                       help="Debug mode with more verbosity")
tornado.options.define("unminified", default=True,
                       help="Use the unminified js (for development only)")
tornado.options.define("host", default=socket.gethostbyname(
    socket.gethostname()), help="Server host")
tornado.options.define("port", default=12345, type=int, help="Server port")
tornado.options.define("keepalive_interval", default=30, type=int,
                       help="Interval between ping packets sent from server "
                       "to client (in seconds)")
tornado.options.define("one_shot", default=False,
                       help="Run a one-shot instance. Quit at term close")
tornado.options.define("shell", help="Shell to execute at login")
tornado.options.define("motd", default='motd', help="Path to the motd file.")
tornado.options.define("cmd",
                       help="Command to run instead of shell, f.i.: 'ls -l'")
tornado.options.define("unsecure", default=True,
                       help="Don't use ssl not recommended")
tornado.options.define("i_hereby_declare_i_dont_want_any_security_whatsoever",
                       default=False,
                       help="Remove all security and warnings. There are some "
                       "use cases for that. Use this if you really know what "
                       "you are doing.")
tornado.options.define("login", default=True,
                       help="Use login screen at start")
tornado.options.define("pam_profile", default="", type=str,
                       help="When --login=True provided and running as ROOT, "
                       "use PAM with the specified PAM profile for "
                       "authentication and then execute the user's default "
                       "shell. Will override --shell.")
tornado.options.define("force_unicode_width",
                       default=True,
                       help="Force all unicode characters to the same width."
                       "Useful for avoiding layout mess.")
tornado.options.define("ssl_version", default=None,
                       help="SSL protocol version")
tornado.options.define("generate_certs", default=False,
                       help="Generate cod3r certificates")
tornado.options.define("generate_current_user_pkcs", default=False,
                       help="Generate current user pfx for client "
                       "authentication")
tornado.options.define("generate_user_pkcs", default='',
                       help="Generate user pfx for client authentication "
                       "(Must be root to create for another user)")
tornado.options.define("uri_root_path", default='',
                       help="Sets the servier root path: "
                       "example.com/<uri_root_path>/static/")


if os.getuid() == 0:
    ev = os.getenv('XDG_CONFIG_DIRS', '/etc')
else:
    ev = os.getenv(
        'XDG_CONFIG_HOME', os.path.join(
            os.getenv('HOME', os.path.expanduser('~')),
            '.config'))

cod3r_dir = os.path.join(ev, 'cod3r')
conf_file = os.path.join(cod3r_dir, 'cod3r.conf')
ssl_dir = os.path.join(cod3r_dir, 'ssl')

tornado.options.define("conf", default=conf_file,
                       help="cod3r configuration file. "
                       "Contains the same options as command line.")

tornado.options.define("ssl_dir", default="./",
                       help="Force SSL directory location")

# Do it once to get the conf path
tornado.options.parse_command_line()

if os.path.exists(tornado.options.options.conf):
    tornado.options.parse_config_file(tornado.options.options.conf)

# Do it again to overwrite conf with args
tornado.options.parse_command_line()

# For next time, create them a conf file from template.
# Need to do this after parsing options so we do not trigger
# code import for cod3r module, in case that code is
# dependent on the set of parsed options.
if not os.path.exists(conf_file):
    try:
        import cod3r
        shutil.copy(
            os.path.join(
                os.path.abspath(os.path.dirname(cod3r.__file__)),
                'cod3r.conf.default'), conf_file)
        print('cod3r.conf installed in %s' % conf_file)
    except:
        pass

options = tornado.options.options

for logger in ('tornado.access', 'tornado.application',
               'tornado.general', 'cod3r'):
    level = logging.WARNING
    if options.debug:
        level = logging.INFO
        if options.more:
            level = logging.DEBUG
    logging.getLogger(logger).setLevel(level)

log = logging.getLogger('cod3r')

host = options.host
port = options.port

if options.i_hereby_declare_i_dont_want_any_security_whatsoever:
    options.unsecure = True


if not os.path.exists(options.ssl_dir):
    os.makedirs(options.ssl_dir)


def to_abs(file):
    return os.path.join(options.ssl_dir, file)


ca, ca_key, cert, cert_key, pkcs12 = map(to_abs, [
    'cod3r_ca.crt', 'cod3r_ca.key',
    'cod3r_%s.crt', 'cod3r_%s.key',
    '%s.p12'])


def fill_fields(subject):
    subject.C = 'WW'
    subject.O = 'cod3r'
    subject.OU = 'cod3r Terminal'
    subject.ST = 'World Wide'
    subject.L = 'Terminal'


def write(file, content):
    with open(file, 'wb') as fd:
        fd.write(content)
    print('Writing %s' % file)


def read(file):
    print('Reading %s' % file)
    with open(file, 'rb') as fd:
        return fd.read()


def b(s):
    return s.encode('utf-8')


if (options.generate_current_user_pkcs or
        options.generate_user_pkcs):
    from cod3r import utils
    try:
        current_user = utils.User()
    except Exception:
        current_user = None

    from OpenSSL import crypto
    if not all(map(os.path.exists, [ca, ca_key])):
        print('Please generate certificates using --generate-certs before')
        sys.exit(1)

    if options.generate_current_user_pkcs:
        user = current_user.name
    else:
        user = options.generate_user_pkcs

    if user != current_user.name and current_user.uid != 0:
        print('Cannot create certificate for another user with '
              'current privileges.')
        sys.exit(1)

    ca_cert = crypto.load_certificate(crypto.FILETYPE_PEM, read(ca))
    ca_pk = crypto.load_privatekey(crypto.FILETYPE_PEM, read(ca_key))

    client_pk = crypto.PKey()
    client_pk.generate_key(crypto.TYPE_RSA, 2048)

    client_cert = crypto.X509()
    client_cert.set_version(2)
    client_cert.get_subject().CN = user
    fill_fields(client_cert.get_subject())
    client_cert.set_serial_number(uuid.uuid4().int)
    client_cert.gmtime_adj_notBefore(0)  # From now
    client_cert.gmtime_adj_notAfter(315360000)  # to 10y
    client_cert.set_issuer(ca_cert.get_subject())  # Signed by ca
    client_cert.set_pubkey(client_pk)
    client_cert.sign(client_pk, 'sha512')
    client_cert.sign(ca_pk, 'sha512')

    pfx = crypto.PKCS12()
    pfx.set_certificate(client_cert)
    pfx.set_privatekey(client_pk)
    pfx.set_ca_certificates([ca_cert])
    pfx.set_friendlyname(('%s cert for cod3r' % user).encode('utf-8'))

    while True:
        password = getpass.getpass('\nPKCS12 Password (can be blank): ')
        password2 = getpass.getpass('Verify Password (can be blank): ')
        if password == password2:
            break
        print('Passwords do not match.')

    print('')
    write(pkcs12 % user, pfx.export(password.encode('utf-8')))
    os.chmod(pkcs12 % user, stat.S_IRUSR | stat.S_IWUSR)  # 0o600 perms
    sys.exit(0)

if options.unsecure:
    ssl_opts = None
else:
    if not all(map(os.path.exists, [cert % host, cert_key % host, ca])):
        print("Unable to find cod3r certificate for host %s" % host)
        print(cert % host)
        print(cert_key % host)
        print(ca)
        print("Can't run cod3r without certificate.\n")
        print("Either generate them using --generate-certs --host=host "
              "or run as --unsecure (NOT RECOMMENDED)\n")
        print("For more information go to http://paradoxxxzero.github.io/"
              "2014/03/21/cod3r-with-ssl-auth.html\n")
        sys.exit(1)

    ssl_opts = {
        'certfile': cert % host,
        'keyfile': cert_key % host,
        'ca_certs': ca,
        'cert_reqs': ssl.CERT_REQUIRED
    }
    if options.ssl_version is not None:
        if not hasattr(
                ssl, 'PROTOCOL_%s' % options.ssl_version):
            print(
                "Unknown SSL protocol %s" %
                options.ssl_version)
            sys.exit(1)
        ssl_opts['ssl_version'] = getattr(
            ssl, 'PROTOCOL_%s' % options.ssl_version)
from cod3r import application
application.cod3r_dir = cod3r_dir
log.info('Starting server')
http_server = HTTPServer(application, ssl_options=ssl_opts)
http_server.listen(port, address=host)

if getattr(http_server, 'systemd', False):
    os.environ.pop('LISTEN_PID')
    os.environ.pop('LISTEN_FDS')

log.info('Starting loop')

ioloop = tornado.ioloop.IOLoop.instance()

if port == 0:
    port = list(http_server._sockets.values())[0].getsockname()[1]

url = "http%s://%s:%d/%s" % (
    "s" if not options.unsecure else "", host, port,
    (options.uri_root_path.strip('/') + '/') if options.uri_root_path else ''
)

log.warn('cod3r started at: %s' % url)

ioloop.start()
