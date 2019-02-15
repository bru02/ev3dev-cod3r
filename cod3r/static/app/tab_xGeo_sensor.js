// Model to manage the GPS/Geo x-Sensor
class GeoSensorTabViewModel {
    constructor(appContext) {
        // Init
        this.context = appContext; // The application context
        this.sensorName = ko.observable("xGeo");
        this.isStarted = ko.observable(false);
        this.timestamp = ko.observable("");
        this.latitude = ko.observable("");
        this.longitude = ko.observable("");
        this.accuracy = ko.observable("");
        this.altitude = ko.observable("");
        this.altitudeAccuracy = ko.observable("");
        // Is device orientation supported
        if (!navigator.geolocation) {
            // Should not be possible to be here if not allowed (should have already been checked while adding tabs)
            console.log("Geolocation not supported !");
        }
    }
    __resetXValue() {
        this.xValue = {
            isStarted: undefined,
            timestamp: 0,
            latitude: 0,
            longitude: 0,
            accuracy: 0,
            // Optional field (depends on the hardware/device capabilities)
            altitude: 0,
            altitudeAccuracy: 0
        };
    }
    __sendXValue() {
        this.xValue.isStarted = this.isStarted();
        this.context.ev3BrickServer.streamXSensorValue(this.sensorName(), "Geo1", this.xValue);
        // Also display value to GUI
        this.timestamp(this.xValue.timestamp + (this.xValue.timestamp != 0 ? " - " + new Date(this.xValue.timestamp).toLocaleString() : ""));
        this.latitude(this.xValue.latitude);
        this.longitude(this.xValue.longitude);
        this.accuracy(this.xValue.accuracy);
        this.altitude(this.xValue.altitude);
        this.altitudeAccuracy(this.xValue.altitudeAccuracy);
    }
    watchPositionHandler(position) {
        if (this.isStarted()) { // Workaround: In some version of Firefox, the geolocation.clearWatch don't unregister the callback <=> avoid flooding the EV3 brick
            this.xValue.timestamp = position.timestamp;
            this.xValue.latitude = position.coords.latitude;
            this.xValue.longitude = position.coords.longitude;
            this.xValue.accuracy = position.coords.accuracy;
            this.xValue.altitude = position.coords.altitude;
            this.xValue.altitudeAccuracy = position.coords.altitudeAccuracy;
            //this.xValue.heading = position.coords.heading; // Heading can be computed (or use the gyro compass)
            //this.xValue.speed = position.coords.speed; // Speed can be computed
            this.__sendXValue();
        }
    }
    watchPositionErrorHandler(error) {
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
        this.context.messageLogVM.addError(errorMsg);
    };
    onStart() {
        this.isStarted(!this.isStarted());
        if (this.isStarted()) {
            if (navigator.geolocation) {
                console.log("Register geolocation callback...");
                this.__resetXValue();
                var geo_options = {
                    enableHighAccuracy: true,
                    maximumAge: 30000,
                    timeout: 27000
                };
                this.watchID = navigator.geolocation.watchPosition(this.watchPositionHandler, this.watchPositionErrorHandler, geo_options);
                console.log("  ... id of callback is: " + this.watchID);
                this.__sendXValue();
            }
        }
        else {
            if (navigator.geolocation) {
                console.log("Unregister geolocation for callback: " + this.watchID);
                if (this.watchID) {
                    navigator.geolocation.clearWatch(this.watchID);
                    this.watchID = undefined;
                }
            }
            this.__resetXValue();
            this.__sendXValue();
        }
    };
}