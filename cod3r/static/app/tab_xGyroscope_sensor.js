// Model to manage the Gyroscope x-Sensor
class GyroscopeSensorTabViewModel {
  constructor(appContext) {
    // Init
    this.context = appContext; // The application context
    this.sensorName = ko.observable("xGyro");
    this.isStarted = ko.observable(false);
    this.axisOrientation = 0;
    this.xAxisValue = ko.observable("");
    this.yAxisValue = ko.observable("");
    this.zAxisValue = ko.observable("");
    this.xAxisValue.extend({ rateLimit: 100 }); // Accept lower refresh rate
    this.yAxisValue.extend({ rateLimit: 100 }); // Accept lower refresh rate
    this.zAxisValue.extend({ rateLimit: 100 }); // Accept lower refresh rate
    // Is device orientation supported
    if (!window.DeviceOrientationEvent) {
      // Should not be possible to be here if not allowed (should have already been checked while adding tabs)
      console.log("Device orientation not supported !");
    }
    this.onSetAxis = () => {
      var wo = window.orientation;
      if (wo == undefined) { // Browser don't support orientation
        this.__askAxisOrientationFull();
      }
      else {
        if (wo == -180) {
          wo = 180;
        }
        var woLabel = i18n.t("gyroSensorTab.axisOrientation.o" + wo);
        bootbox.dialog({
          title: i18n.t("gyroSensorTab.setAxisDialogLight.title"),
          message: i18n.t("gyroSensorTab.setAxisDialogLight.message", { "axisOrientation": woLabel }),
          buttons: {
            cancel: {
              label: i18n.t("gyroSensorTab.setAxisDialogLight.cancel"),
              className: "btn-default",
              callback: function () { }
            },
            ok: {
              label: i18n.t("gyroSensorTab.setAxisDialogLight.ok"),
              className: "btn-primary",
              callback: function () {
                this.__setAxisOrientation(wo);
              }
            },
            fullChoice: {
              label: i18n.t("gyroSensorTab.setAxisDialogLight.fullChoice"),
              className: "btn-primary",
              callback: function () {
                this.__askAxisOrientationFull();
              }
            }
          }
        });
      }
    }
    this.onStart = () => {
      this.isStarted(!this.isStarted());
      if (this.isStarted()) {
        if (window.DeviceOrientationEvent) {
          console.log("Register DeviceOrientationEvent...");
          this.__resetXValue();
          window.addEventListener('deviceorientation', this.deviceOrientationHandler, false);
          //window.addEventListener('devicemotion', this.deviceMotionHandler, false);
          this.__sendXValue();
        }
      }
      else {
        if (window.DeviceOrientationEvent) {
          console.log("Remove DeviceOrientationEvent...");
          window.removeEventListener('deviceorientation', this.deviceOrientationHandler, false);
          //window.removeEventListener('devicemotion', this.deviceMotionHandler, false);
        }
        this.__resetXValue();
        this.__sendXValue();
      }
    }
  }
  __resetXValue() {
    // EV3 sensor values: angle degree and rate in degree/s
    this.xValue = {
      isStarted: undefined,
      x: { angle: 0 },
      y: { angle: 0 },
      z: { angle: 0 } //, rate: 0.0}
    };
  }
  deviceOrientationHandler(eventData) {
    var xv = this.xValue;
    xv.x.angle = Math.round(eventData.beta);
    xv.y.angle = Math.round(eventData.gamma);
    xv.z.angle = Math.round(eventData.alpha);
    if ((this.axisOrientation == 90) || (this.axisOrientation == -90)) { // Invert X and Y
      var t = xv.y.angle;
      xv.y.angle = xv.x.angle;
      xv.x.angle = t;
    }
    if ((this.axisOrientation == 180) || (this.axisOrientation == -90)) {
      xv.y.angle *= -1;
    }
    if ((this.axisOrientation == 180) || (this.axisOrientation == 90)) {
      xv.x.angle *= -1;
    }
    this.__sendXValue();
  };
  /*
  function deviceMotionHandler(eventData) {
    var acceleration = eventData.acceleration || // can acceleration be undefined on some hardware
                        eventData.accelerationIncludingGravity;
    if(acceleration != undefined) {
      if (Math.abs(this.axisOrientation) == 90) {
        // Invert X and Y
        this.xValue.y.rate = round2dec(acceleration.x);
        this.xValue.x.rate = round2dec(acceleration.y);
      } else {
        // Use device default
        this.xValue.x.rate = round2dec(acceleration.x);
        this.xValue.y.rate = round2dec(acceleration.y);
      }
      // TODO: Does we need to change the sign for acceleration ?
      this.xValue.z.rate = round2dec(acceleration.z);
      this.__sendXValue();
    }
  }*/
  __sendXValue() {
    this.xValue.isStarted = this.isStarted();
    this.context.ev3BrickServer.streamXSensorValue(this.sensorName(), "Gyr1", this.xValue);
    // Also display value to GUI
    this.xAxisValue("x: " + JSON.stringify(this.xValue.x));
    this.yAxisValue("y: " + JSON.stringify(this.xValue.y));
    this.zAxisValue("z: " + JSON.stringify(this.xValue.z));
  };
  __setAxisOrientation(orientation) {
    this.axisOrientation = orientation;
  }
  __askAxisOrientationFull() {
    bootbox.dialog({
      title: i18n.t("gyroSensorTab.setAxisDialogFull.title"),
      message: i18n.t("gyroSensorTab.setAxisDialogFull.message"),
      buttons: {
        cancel: {
          label: i18n.t("gyroSensorTab.setAxisDialogFull.cancel"),
          className: "btn-default",
          callback: function () { }
        },
        landscapeLeft: {
          label: i18n.t("gyroSensorTab.setAxisDialogFull.landscapeLeft"),
          className: "btn-primary",
          callback: function () {
            this.__setAxisOrientation(90);
          }
        },
        landscapeRight: {
          label: i18n.t("gyroSensorTab.setAxisDialogFull.landscapeRight"),
          className: "btn-primary",
          callback: function () {
            this.__setAxisOrientation(-90);
          }
        },
        portrait: {
          label: i18n.t("gyroSensorTab.setAxisDialogFull.portrait"),
          className: "btn-primary",
          callback: function () {
            this.__setAxisOrientation(0);
          }
        },
        reversePortrait: {
          label: i18n.t("gyroSensorTab.setAxisDialogFull.reversePortrait"),
          className: "btn-primary",
          callback: function () {
            this.__setAxisOrientation(180);
          }
        }
      }
    });
  }
}