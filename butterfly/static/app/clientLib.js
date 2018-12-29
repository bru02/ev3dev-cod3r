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
                type: "text",
                multible,
            }]
        }
    },
    battery: {

    },
    screen: {
        textPixels: {
            args: [
                {
                    type: "text"
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
                    type: "text",
                    default: "black"
                },
                {
                    type: "boolean",
                    default: true
                },
                {
                    type: "text",
                    default: ""
                },
            ]
        },
        textGrid: {
            args: [
                {
                    type: "text"
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
                    type: "text",
                    default: "black"
                },
                {
                    type: "boolean",
                    default: true
                },
                {
                    type: "text",
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
                    type: "text",
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
                    type: "text",
                    default: "black"

                },
                {
                    type: "text",
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
                    type: "number"
                },
                {
                    type: "text",
                    default: "black"

                },
                {
                    type: "text",
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
                    type: "text",
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
                    type: "text"
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
                    type: "text"
                },
                {
                    type: "number"
                },
                {
                    type: "number"
                },
            ]
        }
    },
    leds: {
        setColor: {
            args: [
                {
                    type: "text",
                    val: ['LEFT', 'RIGHT', 'BOTH']
                },
                {
                    type: ["text", "array"]
                }
            ]
        }
    },
    global: {
        print: {
            args: [{
                type: "text",
                multible,
            }]
        },
        wrap: {
            args: [
                {
                    type: "text"
                },
                {
                    type: "number"
                }
            ]
        }
    }
};
for ([key, val] in fns) {
    if (key == "global") {
        t = window
    } else {
        window[key] = {};
        t = window[key];
    }
    for ([name, props] in val) {
        t[name] = function () {
            $(props).each(function (e, i) {
                if (arguments[i]) {
                    let type = typeof arguments[i];
                    let res = true;
                    if (Array.isArray(e['type'])) {
                        $(e['type']).each(e => {
                            res = type !== e['type']
                            if (!res) return false;
                        });
                    } else {
                        res = type !== e['type']
                    }
                    if (res) {
                        context.MessageLogViewModel.addError("Argument type mismatch for function " + namespace + "." + name + " argument " + i + ".");
                    }
                } else {
                    if (e['default'] !== null) {
                        arguments[i] = e['default']
                    } else {
                        context.MessageLogViewModel.addError("Too few arguments supplied to function " + namespace + "." + name + ".");

                    }
                }
            });
            return new Promise((resolve, reject) => {

                context.EV3BrickServer.doWSSend({
                    'act': 'ufn',
                    'namespace': key,
                    fn: name,
                    args: arguments
                })
                context.EV3BrickServer.ws.addEventListener('message', function (msg) {
                    msg = JSON.parse(msg);
                    if (msg['err']) {
                        reject(msg['err']);
                        MessageLogViewModel.addError(msg['err']);
                    } else {
                        resolve(msg['res'])
                    }
                })
            })
        }
    }
}
leds.off = function () {
    leds.setColor('both', [0, 0])
}
window.sleep = function sleep(s) {
    return new Promise(resolve => setTimeout(resolve, s * 500));
}