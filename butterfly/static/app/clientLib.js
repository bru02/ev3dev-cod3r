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
function sendIntent() {
    let fn = arguments.shift();
    let args = arguments;
    EV3BrickServer.WSSend({ fn, args, });
}
class Speaker {
    constructor() {
        this.volume = 100;
    }
    speak(txt) {
        sendIntent("speak", txt, volume);
    }
    tone() {
        sendIntent("tone", txt, volume);
    }
}
class Ev3 {
    constructor() {
        this.battery = new Battery();
        this.screen = new Screen();
        this.motors = new Motors();
        this.sensors = new Sensors();
        this.lights = new Lights();
        this.buttons = new Buttons();
        this.speaker = new Speaker();
    }
}