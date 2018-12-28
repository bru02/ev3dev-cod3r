#!/usr/bin/env python3
from ev3dev2.display import Display
from ev3dev2.button import Button
from ev3dev2.sound import Sound
from ev3dev2.led import Leds
from ev3dev2.sensor.lego import *
from ev3dev2.motor import *
import math
import tornado.web
import tornado.httpserver
import tornado.ioloop
import tornado.websocket
import tornado.options
import json

from time import sleep, time
from PIL import Image
from io import BytesIO
from threading import Thread
import re

btn = Button()
sound = Sound()
lcd = Display()
leds = Leds()
lm = LargeMotor()
updated = False


def imgFromBase64(str, x=0, y=0):
    image_data = re.sub('^data:image/.+;base64,', '', str).decode('base64')
    im = Image.open(BytesIO(image_data))
    lcd.image.paste(im, (x, y))
    updated = True


def imgFromFile(path, x=0, y=0):
    im = Image.open(path)
    lcd.image.paste(im, (x, y))
    updated = True


def beep():
    sound.beep()


def tone(tone):
    sound.play_tone(tone)


def sequence(seq):
    sound.tone(seq)


def playFile(path):
    sound.play(path)


def talk(txt):
    sound.speak(txt)


def ledsOff():
    leds.all_off()


def setRight(c):
    leds.set_color('RIGHT', c)


def setLeft(c):
    leds.set_color('LEFT', c)


def setBoth(c):
    setLeft(c)
    setRight(c)


def tick():
    if(updated):
        lcd.update()


LISTEN_PORT = 8000
LISTEN_ADDRESS = '0.0.0.0'


class ChannelHandler(tornado.websocket.WebSocketHandler):
    """
    Handler that handles a websocket channel
    """
    @classmethod
    def urls(cls):
        return [
            (r'/ws/control', cls, {}),  # Route/Handler/kwargs
        ]

    def initialize(self):
        self.channel = None

    def open(self, channel):
        """
        Client opens a websocket
        """
        self.channel = channel

    def on_message(self, message):
        if message == "ping":
            self.write_message("pong")
            return "pong"
        obj = json.load(message)

        ret = globals()[obj["name"]](obj["arg"])
        if ret is None:
            return
        ret = json.dumps(ret)
        self.write_message(ret)
        """
        Message received on channel
        """

    def on_close(self):
        """
        Channel is closed
        """

    def check_origin(self, origin):
        """
        Override the origin check if needed
        """
        return True


def main(opts):
    # Create tornado application and supply URL routes
    app = tornado.web.Application(ChannelHandler.urls())

    # Setup HTTP Server
    http_server = tornado.httpserver.HTTPServer(app)
    http_server.listen(LISTEN_PORT, LISTEN_ADDRESS)

    # Start IO/Event loop
    tornado.ioloop.IOLoop.instance().start()


if __name__ == '__main__':
    main()
