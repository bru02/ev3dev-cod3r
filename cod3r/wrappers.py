from textwrap import wrap
from PIL import Image
from io import BytesIO
from ev3dev2.display import Display
from ev3dev2.sound import Sound
from ev3dev2.led import Leds
from ev3dev2.motor import LargeMotor, MediumMotor, MoveSteering, MoveTank, OUTPUT_A, OUTPUT_B, OUTPUT_C, OUTPUT_D
from ev3dev2.button import Button
from ev3dev2.sensor.lego import TouchSensor, InfraredSensor, ColorSensor, GyroSensor, UltrasonicSensor
from ev3dev2.sensor import INPUT_1, INPUT_2, INPUT_3, INPUT_4
from sys import stderr

def get_motor_class(isM):
    if(isM):
        return MediumMotor
    else:
        return LargeMotor

def get_output(p):
    port = str(p).upper()
    if(port=="A"):
        return OUTPUT_A
    if(port=="B"):
        return OUTPUT_B
    if(port=="C"):
        return OUTPUT_C
    if(port=="D"):
        return OUTPUT_D        


def get_input(p):
    port = int(float(p))
    if(port==1):
        return INPUT_1
    if(port==2):
        return INPUT_2
    if(port==3):
        return INPUT_3
    if(port==4):
        return INPUT_4        

class apiWrapper:
    def __init__(self):
        self.ev3 = ev3Wrapper()
        self.scope = globalWrapper()
        self.screen = screenWrapper()
        self.led = ledWrapper()
        self.sound = soundWrapper()
        self.motor = motorWrapper()
        self.touchSensor = touchSensorWrapper()
        self.colorSensor = colorSensorWrapper()
        self.gyroSensor = gyroSensorWrapper()
        self.ultraSonicSensor = ultraSonicSensorWrapper()
        self.button = buttonWrapper()

class ev3Wrapper:
    def isOk(self, args):
        return True
    
    def log(self, *args):
        txt="" 
        for arg in args:
            print(str(arg), file=stderr)
            txt += "\r\n " + str(arg)

        return {'txt': txt} 

class globalWrapper:
    def wrap(self, text, width):
        return wrap(text, width)

    def print(self, args):
        for arg in args:
            print(str(arg))

class screenWrapper:
    def __init__(self):
        self.lcd = Display()

    def textPixels(self, txt, x, y, color = 'black', font = None, clear = False):
        self.lcd.text_pixels(txt, clear_screen=clear, x=x, y=y, text_color=color, font=font or None)

    def line(self, x1,y1,x2,y2,w = 1, color = 'black', clear = True):
        self.lcd.line(clear_screen=clear, x1=x1, y1=y1, x2=x2, y2=y2, line_color=color, width=w)
        

    def circle(self, x, y, r, color = 'white', borderColor = 'black', clear = True):
        self.lcd.circle(clear_screen=clear, x=x, y=y, radius=r, fill_color=color, outline_color=borderColor)

    def point(self, x, y, color = 'black', clear = True):
        self.lcd.point(clear_screen=clear, x=x, y=y, point_color=color)
        

    def rect(self, x,y,w,h,color = 'white', borderColor = 'black', clear = True):
        self.lcd.rectangle(clear_screen=clear, x=x, y=y, width=w+x, height=h+y, fill_color=color, outline_color=borderColor)
        
    def imageFromFile(self, file, x = 0, y = 0):
        logo = Image.open(file)
        self.lcd.image.paste(logo, (x,y))
        

    def imageFromString(self, txt, x = 0, y = 0):
        import re
        image_data = re.sub('^data:image/.+;base64,', '', txt).decode('base64')
        im = Image.open(BytesIO(image_data))
        self.lcd.image.paste(im, (x,y))
        

    def update(self, args):
        self.lcd.update()
        

    def clear(self, args):
        self.lcd.clear()
        
class ledWrapper:
    def __init__(self):
        self.leds = Leds()

    def leds_setColor(self, addr, color):
        addr = str(addr).upper()
        if(addr == "BOTH"):
            self.leds.set_color('LEFT', color)
            self.leds.set_color('RIGHT', color)
        else:
            self.leds.set_color(addr, color)
             
class soundWrapper:
    def __init__(self):
        self.spk = Sound()

    def beep(self, args):
        self.spk.beep()
        

    def tone(self, freq, time = None):
        if(time):
            self.spk.play_tone(freq, time)
        else:
            self.spk.tone(freq)
        

    def play(self, file):
        self.spk.play(file)
        
    def speak(self, text, amplitude = 200, speed = 130, lang = 'en-gb'):
        opts = '-a '+ str(amplitude)+' -s '+str(speed)+' -v'+str(lang)
        self.spk.speak(text, espeak_opts=opts)

class motorWrapper:
    def on(self, ports, spd = False, isMedium = None):
        if(isMedium is None):
            m = get_motor_class(spd)
            m = m()
            m.on(speed=ports)
        else:
            if(isinstance(ports, list)):
                arr = ports
            else:
                import re
                arr = re.compile(r"\+|\,").split(ports)
            
            for p in arr:
                m = get_motor_class(isMedium)
                m = m(get_output(p))
                m.on(speed=spd)

    def off(self, ports, isMedium = None):
        if(isMedium is not None):
            if(isinstance(ports, list)):
                arr = ports
            else:
                import re
                arr = re.compile(r"\+|\,").split(ports)
            
            for p in arr:
                m = get_motor_class(isMedium)
                m = m(get_output(p))
                m.off()
        else:
            m = get_motor_class(ports)
            m = m()
            m.off()

    def turn(self, ports, spd, rots = False, isMedium=None):
        if(isMedium is not None):
            if(isinstance(ports, list)):
                arr = ports
            else:
                import re
                arr = re.compile(r"\+|\,").split(ports)

            for p in arr:
                m = get_motor_class(isMedium)
                m = m(get_output(p))
                m.on_for_rotations(rots, spd, brake=True, block=True)
        else:
            m = get_motor_class(rots)
            m.on_for_rotations(spd, ports, brake=True, block=True)

    def waitUntilNotMoving(self, port = None, isMedium = None):
        if(isMedium is not None):
            m = get_motor_class(isMedium)
            m = m(port)
            m.wait_until_not_moving()
        else:
            m = get_motor_class(port)
            m = m()
            m.wait_until_not_moving()

    def steer(self, ports, spd, direction, amount = 0, isMedium = False):
        if(isinstance(ports, list)):
            arr = ports
        else:
            import re
            arr = re.compile(r"\+|\,").split(ports)

        steer_pair = MoveSteering(get_output(arr[0]), get_output(arr[1]), motor_class=get_motor_class(isMedium))
        if(amount == 0):
                    steer_pair.on(steering=direction, speed=spd)
        else:
            steer_pair.on_for_rotations(steering=direction, speed=spd, rotations=amount)

class touchSensorWrapper:
    def isPressed(self, port = None):
        if(port):
            ts = TouchSensor(get_input(port))
        else:
            ts = TouchSensor()
        return ts.is_pressed

    def waitForPress(self, port = None):
        if(port):
            ts = TouchSensor(get_input(port))
        else:
            ts = TouchSensor()
        ts.wait_for_pressed()

    def waitForRelease(self, port = None):
        if(port):
            ts = TouchSensor(get_input(port))
        else:
            ts = TouchSensor()
        ts.wait_for_released()

    def waitForBump(self, port = None):
        if(port):
            ts = TouchSensor(get_input(port))
        else:
            ts = TouchSensor()
        ts.wait_for_bump()

class colorSensorWrapper:
    def raw(self, port = None):
        if(port):
            cs = ColorSensor(get_input(port))
        else:
            cs = ColorSensor()
        return  cs.raw

    def rgb(self, port = None):
        if(port):
            cs = ColorSensor(get_input(port))
        else:
            cs = ColorSensor()
        return  cs.rgb

    def color(self, port = None):
        if(port):
            cs = ColorSensor(get_input(port))
        else:
            cs = ColorSensor()
        return  cs.color

    def colorName(self, port = None):
        if(port):
            cs = ColorSensor(get_input(port))
        else:
            cs = ColorSensor()
        return  cs.color_name

    def reflectedLightIntensity(self, port = None):
        if(port):
            cs = ColorSensor(get_input(port))
        else:
            cs = ColorSensor()
        return  cs.reflected_light_intensity
    
    def ambientLightIntensity(self, port = None):
        if(port):
            cs = ColorSensor(get_input(port))
        else:
            cs = ColorSensor()
        return  cs.ambient_light_intensity

    def calibrateWhite(self, port = None):
        if(port):
            cs = ColorSensor(get_input(port))
        else:
            cs = ColorSensor()
        return  cs.calibrate_white

class gyroSensorWrapper:
    def rate(self, port = None):
        if(port):
            gs = GyroSensor(get_input(port))
        else:
            gs = GyroSensor()
        return  gs.rate

    def angle(self, port = None):
        if(port):
            gs = GyroSensor(get_input(port))
        else:
            gs = GyroSensor()
        return gs.angle

    def angleAndRate(self, port = None):
        if(port):
            gs = GyroSensor(get_input(port))
        else:
            gs = GyroSensor()
        return gs.angle_and_rate

    def waitUntilAngleIsChangedBy(self, port, angle = None):
        if(angle):
            gs = GyroSensor(get_input(port))
            gs.wait_until_angle_changed_by(angle)
        else:
            gs = GyroSensor()
            gs.wait_until_angle_changed_by(port)

class ultraSonicSensorWrapper:
    def distanceCM(self, port = None):
        if(port):
            us = UltrasonicSensor(get_input(port))
        else:
            us = UltrasonicSensor()
        return  us.distance_centimeters

    def distanceInch(self, port = None):
        if(port):
            us = UltrasonicSensor(get_input(port))
        else:
            us = UltrasonicSensor()
        return us.distance_inches

class buttonWrapper:
    def __init__(self):
        self.btn = Button()

    def waitForBump(self, pos):
        self.btn.wait_for_bump(pos)

    def waitForPress(self, pos):
        self.btn.wait_for_pressed(pos)

    def waitForRelease(self, pos):
        self.btn.wait_for_released(pos)

    def any(self):
        return self.btn.any()

    def process(self):
        self.btn.process()

    def left(self):
        return self.btn.left

    def right(self):
        return self.btn.right

    def up(self):
        return self.btn.up

    def down(self):
        return self.btn.down

    def enter(self):
        return self.btn.enter

    def backspace(self):
        return self.btn.backspace

    def buttonsPressed(self):
        return self.btn.buttons_pressed