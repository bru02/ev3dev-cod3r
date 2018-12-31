class xGeo {
    constructor() {
        if (!navigator.geolocation) {
            console.error("Geolocation not supported !");
        }
        this.options = { // TODO tune parameters ?
            enableHighAccuracy: true,
            maximumAge: 30000,
            timeout: 27000
        };
    }
    errorHandler(error) {
        var errorMsg;
        switch (error.code) {
            case error.TIMEOUT:
                errorMsg = i18n.t("geoSensorTab.errors.timeout", { "detail": error.message });
                break;
            case error.PERMISSION_DENIED:
                errorMsg = i18n.t("geoSensorTab.errors.permissionDenied", { "detail": error.message });
                break;
            case error.POSITION_UNAVAILABLE:
                errorMsg = i18n.t("geoSensorTab.errors.positionUnavailable", { "detail": error.message });
                break;
            // case error.UNKNOWN_ERROR: // Use default
            default:
                errorMsg = i18n.t("geoSensorTab.errors.unknownError", { "detail": error.message });
                break;
        }
        console.error(errorMsg);
    }

    getLocation(fn) {
        if (!navigator.geolocation) return null;
        self.watchID = navigator.geolocation.getCurrentPosition(function (p) {
            let c = p.coords;
            fn(c.latitude, c.longitude, c.accuracy, p.timestamp, p);
        }, this.errorHandler, this.options);
    }
    toStreet(lat, lon, fn) {
        $.getJSON(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`, function (obj) {
            fn(obj.display_name);
        });
    }
    latlonToXY(lat, lon, z) {
        var latRad,
            n,
            xTile,
            yTile;

        latRad = lat * Math.PI / 180;
        n = Math.pow(2, z);
        xTile = parseInt(n * ((lon + 180) / 360));
        yTile = parseInt(n * (1 - (Math.log(Math.tan(latRad) + (1 / Math.cos(latRad))) / Math.PI)) / 2);
        return { x: xTile, y: yTile, z, }
    }
}
const multible = "multible";
const fns = {
    ev3: {
        isOk: {},
        log: {
            args: [{
                type: "string",
                multible,
            }]
        }
    },
    screen: {
        textPixels: {
            args: [
                {
                    type: "string"
                },
                {
                    type: "number",
                    default: 0

                },
                {
                    type: "number",
                    default: 0
                },
                {
                    type: "string",
                    default: "black"
                },
                {
                    type: "boolean",
                    default: true
                },
                {
                    type: "string",
                    default: ""
                },
            ]
        },
        textGrid: {
            args: [
                {
                    type: "string"
                },
                {
                    type: "number",
                    default: 0

                },
                {
                    type: "number",
                    default: 0
                },
                {
                    type: "string",
                    default: "black"
                },
                {
                    type: "boolean",
                    default: true
                },
                {
                    type: "string",
                    default: ""
                },
            ]
        },
        line: {
            args: [
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "string",
                    default: "black"

                },
                {
                    type: "boolean",
                    default: true

                },
            ]
        },
        circle: {
            args: [
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "string",
                    default: "black"

                },
                {
                    type: "string",
                    default: "black"

                },
                {
                    type: "boolean",
                    default: true

                },
            ]
        },
        rect: {
            args: [
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "string",
                    default: "black"

                },
                {
                    type: "string",
                    default: "black"

                },
                {
                    type: "boolean",
                    default: true

                },
            ]
        },
        point: {
            args: [
                {
                    type: "number"
                },
                {
                    type: "number"
                },
                {
                    type: "string",
                    default: "black"
                },
                {
                    type: "boolean",
                    default: true
                }
            ]
        },
        imageFromFile: {
            args: [
                {
                    type: "string"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
            ]
        },
        imageFromString: {
            args: [
                {
                    type: "string"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
            ]
        },
        clear: {},
        update: {}
    },
    leds: {
        setColor: {
            args: [
                {
                    type: "string",
                    val: ['LEFT', 'RIGHT', 'BOTH']
                },
                {
                    type: ["string", "array"]
                }
            ]
        }
    },
    sound: {
        beep: {},
        tone: {
            args: [
                {
                    type: ['number', 'array']
                }
            ]
        },
        play: {
            args: [
                {
                    type: "string"
                }
            ]
        },
        speak: {
            args: [
                {
                    type: "string"
                },
                {
                    type: "number",
                    default: 200
                },
                {
                    type: "number",
                    default: 130
                },
                ,
                {
                    type: "string",
                    default: "en-gb"
                }
            ]
        }
    },
    motors: {
        on: {
            args: [
                {
                    type: ["array", "string"]
                },
                {
                    type: "number"
                },
                {
                    type: "boolean",
                    default: false
                }
            ]
        },
        off: {
            args: [
                {
                    type: ["array", "string"]
                },
                {
                    type: "boolean",
                    default: false
                }
            ]
        },
        turn: {
            args: [
                {
                    type: ["array", "string"]
                },
                {
                    type: "number"
                },
                {
                    type: "number",
                    default: 100
                },
                {
                    type: "boolean",
                    default: false
                },
            ]
        },
        waitUntilNotMoving: {
            args: [
                {
                    type: "string"
                },
                {
                    type: "boolean",
                    default: false
                },
            ]
        },
        steer: {
            args: [
                {
                    type: ["string", "array"]
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                }, {
                    type: "number",
                    default: 0
                }, {
                    type: "boolean",
                    default: false
                },
            ]
        }
    },
    touchSensor: {
        isPressed: {},
        waitForPress: {},
        waitForRelease: {},
        waitForBump: {},
    },
    gyroSensor: {
        rate: {},
        angle: {},
        angleAndRate: {},
        waitUntilAngleIsChangedBy: {},
    },
    ultraSonicSensor: {
        distanceCM: {},
        distanceInch: {},
    },
    colorSensor: {
        raw: {},
        rgb: {},
        color: {},
        colorName: {},
        reflectedLightIntensity: {},
        ambientLightIntensity: {},
        calibrateWhite: {},
    },
    infraredSensor: {
        proximity: {},
        heading: {},
        distance: {},
        headingAndDistance: {},
        beacon: {},
        buttonsPressed: {}
    },
    button: {
        waitForPress: {
            args: [
                {
                    type: ["string", "array"]
                }
            ]
        },
        waitForRelease: {
            args: [
                {
                    type: ["string", "array"]
                }
            ]
        },
        waitForBump: {
            args: [
                {
                    type: ["string", "array"]
                }
            ]
        },
        any: {},
        process: {},
        backspace: {},
        left: {},
        right: {},
        top: {},
        bottom: {},
        enter: {},
        buttonsPressed: {}
    },
    global: {
        print: {
            args: [{
                type: "string",
                multible,
            }]
        },
        wrap: {
            args: [
                {
                    type: "string"
                },
                {
                    type: "number"
                }
            ]
        }
    }
};
window.evalContext = {};
for (let [key, val] of Object.entries(fns)) {
    if (key == "global") {
        t = window = evalContext
    } else {
        window[key] = evalContext[key] = {};
        t = window[key] = evalContext[key];
    }
    for (let [name, props] of Object.entries(val)) {
        t[name] = async function () {
            let args = Array.from(arguments);
            $(props.args).each(function (i, e) {
                if (args[i]) {
                    let type = typeof args[i];
                    let res = true;
                    if (Array.isArray(e[i].type)) {
                        $(e[i]['type']).each((j, e) => {
                            res = type !== e[i]['type'][j]
                            if (!res) return false;
                        });
                    } else {
                        res = type !== e[i].type
                    }
                    if (res) {
                        context.messageLogVM.addError("Argument type mismatch for function " + key + "." + name + " argument " + i + ".");
                    }
                } else {
                    if ('default' in e) {
                        args[i] = e.default
                    } else {
                        context.messageLogVM.addError("Too few arguments supplied to function " + key + "." + name + ".");

                    }
                }
            });
            return new Promise((resolve, reject) => {
                let id = Utils.generateUUID();
                if (context.ev3BrickServer.WSSend({
                    'act': 'ufn',
                    fn: key + "_" + name,
                    args,
                    id,
                })) {
                    function cb(msg) {
                        try {
                            msg = JSON.parse(msg.data);
                        } catch (e) {
                            return;
                        }
                        if (msg.id !== id) return;
                        context.ev3BrickServer.ws.removeEventListener('message', cb)
                        if (msg.err) {
                            reject(msg.err);
                            MessageLogViewModel.addError(msg.err);
                        } else {
                            resolve(msg.res)
                        }
                    }
                    context.ev3BrickServer.ws.addEventListener('message', cb)
                }
            })
        }
    }
}

evalContext.leds.off = leds.off = function () {
    return leds.setColor('BOTH', [0, 0])
}
infraredSensor.distanceCM = function () {
    return infraredSensor.proximity.call(this, arguments).then(e => {
        return e * 0.7
    })
}
var cbs = {
    back: [],
    left: [],
    right: [],
    top: [],
    bottom: [],
    middle: [],
};
var listener = false;
evalContext.button.on = button.on = function (pos, fn) {
    if (cbs[pos]) {
        cbs[pos].push(fn);
        if (!listener) {
            listener = true;
            context.ev3BrickServer.ws.addEventListener('message', function (e) {
                try {
                    e = JSON.parse(e.data)
                    if (e.btnPressed && cbs[e.grp]) {
                        $(cbs[e.grp]).each((i, e) => e())
                    }
                } catch (e) { }
            })
        }
    }
}
evalContext.button.off = button.off = function (pos, fn) {
    if (cbs[pos]) {
        return cbs[pos].filter(function (ele) {
            return ele != fn;
        });
    }
}
evalContext.sleep = window.sleep = function sleep(s) {
    return new Promise(resolve => setTimeout(resolve, s * 500));
}