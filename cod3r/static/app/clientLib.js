var clientLib = (function (window) {
    return {
        init: function (msg, t) {
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
                            errorMsg = t("geoSensorTab.errors.timeout", { "detail": error.message });
                            break;
                        case error.PERMISSION_DENIED:
                            errorMsg = t("geoSensorTab.errors.permissionDenied", { "detail": error.message });
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = t("geoSensorTab.errors.positionUnavailable", { "detail": error.message });
                            break;
                        // case error.UNKNOWN_ERROR: // Use default
                        default:
                            errorMsg = t("geoSensorTab.errors.unknownError", { "detail": error.message });
                            break;
                    }
                    throw new Error(errorMsg);
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

            const multible = "multible",
                fns = {
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
                                    type: "string",
                                    name: 'Text',
                                    desc: 'The string that you want to display'
                                },
                                {
                                    type: "number",
                                    default: 0,
                                    name: 'X',
                                    desc: 'The X position of the text',
                                    min: 0,
                                    max: 177
                                },
                                {
                                    type: "number",
                                    default: 0,
                                    name: 'Y',
                                    desc: 'The Y position of the text',
                                    min: 0,
                                    max: 127
                                },
                                {
                                    type: "string",
                                    default: "black",
                                    name: 'Color',
                                    desc: 'The color of the text',
                                    canBe: ['black', 'white']
                                },
                                {
                                    type: "boolean",
                                    default: true,
                                    name: 'Clear screen',
                                    desc: 'Wether to clear the screen before drawing the text or nah'
                                },
                                {
                                    type: "string",
                                    default: null,
                                    name: 'Font',
                                    desc: 'The font of the text'
                                },
                            ]
                        },
                        textGrid: {
                            args: [
                                {
                                    type: "string",
                                    name: 'Text',
                                    desc: 'The string that you want to display'
                                },
                                {
                                    type: "number",
                                    default: 0,
                                    name: 'X',
                                    desc: 'The X position of the text',
                                    min: 0,
                                    max: 22

                                },
                                {
                                    type: "number",
                                    default: 0,
                                    name: 'Y',
                                    desc: 'The Y position of the text',
                                    min: 0,
                                    max: 12
                                },
                                {
                                    type: "string",
                                    default: "black",
                                    name: 'Color',
                                    desc: 'The color of the text',
                                    canBe: ['black', 'white']
                                },
                                {
                                    type: "boolean",
                                    default: true,
                                    name: 'Clear screen',
                                    desc: 'Wether to clear the screen before drawing the text or nah'
                                },
                                {
                                    type: "string",
                                    default: null,
                                    name: 'Font',
                                    desc: 'The font of the text'
                                },
                            ]
                        },
                        line: {
                            args: [
                                {
                                    type: "number",
                                    name: 'x1'
                                },
                                {
                                    type: "number",
                                    name: 'y1'
                                },
                                {
                                    type: "number",
                                    name: 'x2'
                                },
                                {
                                    type: "number",
                                    name: 'y2'
                                },
                                {
                                    type: "number",
                                    name: 'line width'
                                },
                                {
                                    type: "string",
                                    default: "black",
                                    name: 'Color',
                                    canBe: ['white', 'black']
                                },
                                {
                                    type: "boolean",
                                    default: true,
                                    name: 'Clear screen'
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
                        up: {},
                        down: {},
                        enter: {},
                        buttonsPressed: {}
                    },
                    scope: {
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
            for (let key in fns) {
                let val = fns[key];
                if (key == "scope") {
                    t = window
                } else {
                    window[key] = {};
                    t = window[key]
                }
                for (let name in val) {
                    let props = val[name];
                    t[name] = async function () {
                        let args = Array.from(arguments);
                        $(props.args).each(function (i, e) {
                            if (args[i]) {
                                let type = Array.isArray(args[i]) ? "array" : typeof args[i],
                                    res = true;
                                if (Array.isArray(e.type)) {
                                    $(e['type']).each((j, e) => {
                                        res = res && type !== e;
                                        if (!res) return false;
                                    });
                                } else {
                                    res = type !== e.type;
                                }
                                if (res) {
                                    throw new Error("Argument type mismatch for function " + key + "." + name + " argument " + i + ".");
                                }
                            } else {
                                if ('default' in e) {
                                    args[i] = e.default;
                                } else {
                                    throw new Error("Too few arguments supplied to function " + key + "." + name + ".");
                                }
                            }
                        });
                        return msg({
                            act: 'ufn',
                            ns: key,
                            fn: name,
                            args,
                        });
                    }
                }
            }

            leds.off = function () {
                return leds.setColor('BOTH', [0, 0]);
            }
            infraredSensor.distanceCM = function () {
                return infraredSensor.proximity.call(this, arguments).then(e => {
                    return e * 0.7;
                });
            }
            // var cbs = {
            //     backspace: [],
            //     left: [],
            //     right: [],
            //     up: [],
            //     down: [],
            //     enter: [],
            // };
            // var listener = false;
            // button.on = function (pos, fn) {
            //     if (cbs[pos]) {
            //         cbs[pos].push(fn);
            //         if (!listener) {
            //             listener = true;
            //             context.ev3BrickServer.ws.addEventListener('message', function (e) {
            //                 try {
            //                     e = JSON.parse(e.data);
            //                     if (e.btnPressed && cbs[e.pos]) {
            //                         $(cbs[e.pos]).each((i, e) => e());
            //                     }
            //                 } catch (e) { }
            //             });
            //         }
            //     }
            // }
            // button.off = function (pos, fn) {
            //     if (cbs[pos]) {
            //         return cbs[pos].filter(function (ele) {
            //             return ele != fn;
            //         });
            //     }
            // }
            window.sleep = function (s) {
                return new Promise(resolve => setTimeout(resolve, s * 1000));
            }
            window.xGeo = xGeo;
        }
    }
})(this);