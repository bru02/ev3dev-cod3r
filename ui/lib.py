from ev3dev.ev3 import *
from PIL import Image
from io import BytesIO
import re

lcd = Screen()
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
    Sound.beep().wait()


def tone():
    Sound.tone(1500, 2000).wait()


def playFile(path):
    Sound.play(path).wait()


def talk(txt):
    Sound.speak(txt).wait()
