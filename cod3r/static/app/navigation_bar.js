// Model to manage the navigation bar actions
class NavigationBarViewModel {
  constructor(appContext) {
    this.context = appContext; // The application context
    this.workAreaItems = ko.observableArray();
    this.running = ko.observable(false);
    this.running.subscribe(() => {
      $('#runBtn').toggleClass('btn-warning', this.running()).toggleClass('btn-success', !this.running()).find('span').toggleClass('glyphicon-play', !this.running()).toggleClass('glyphicon-stop', this.running());
    });
    this._runner = null;
    this.btnScript = {
      name: "SCRIPT_EDITOR",
      data_i18n: "workArea.scriptEditorTab",
      tabId: "scriptEditorTab",
      active: ko.observable(true)
    };
    this.btnKeyboard = {
      name: "xKEYBOARD",
      data_i18n: "workArea.keyboardSensorTab",
      tabId: "keyboardSensorTab",
      active: ko.observable(false)
    };
    this.btnGyro = {
      name: "xGYRO",
      data_i18n: "workArea.gyroSensorTab",
      tabId: "gyroSensorTab",
      active: ko.observable(false)
    };
    this.btnVideo = {
      name: "xVIDEO",
      data_i18n: "workArea.videoSensorTab",
      tabId: "videoSensorTab",
      active: ko.observable(false)
    };
    this.btnGeo = {
      name: "xGEO",
      data_i18n: "workArea.geoSensorTab",
      tabId: "geoSensorTab",
      active: ko.observable(false)
    };
    this.workAreaItems.removeAll();
    this.workAreaItems.push(this.btnScript);
    this.workAreaItems.push(this.btnKeyboard);
    if (location.protocol == "https:") {
      if (window.DeviceOrientationEvent) {
        this.workAreaItems.push(this.btnGyro);
      } // else: Don't show xGyro, not supported by the browser
      if (this.context.compatibility.isUserMediaSupported()) {
        this.workAreaItems.push(this.btnVideo);
      } // else: Don't show xVideo, video/WebCam not supported by the browser
      if (navigator.geolocation) {
        this.workAreaItems.push(this.btnGeo);
      } // else: Don't show xGeo, GPS not supported by the browser
    }
    this.onShowWorkAreaItem(this.btnScript);
  }
  __collapseNavbar() {
    if ($("#collapser").css("display") != "none") {
      $("#collapser").click();
    }
  }
  onShowWorkAreaItem(workAreaItem) {
    // Set the active item in the model and on screen
    var items = this.workAreaItems(); // return a regular array
    for (var i = 0; i < items.length; i++) {
      items[i].active(items[i].tabId == workAreaItem.tabId);
      $("#" + items[i].tabId).toggleClass("active", items[i].active());
      this.context.events.tabDisplayedChanged.fire(items[i].tabId, items[i].active());
    }
    this.__collapseNavbar();
  }
  // Auto collapse navbar while collapse feature is enabled (screen width is < 768)
  onRunScript() {
    if (this._runner)
      this._runner.kill();
    if (this.running()) {
      this._runner = null;
    }
    else {
      var value = (this.context.scriptEditorTabVM ? this.context.scriptEditorTabVM.editor.codeMirror.getValue() : null);
      if (value) {
        if (this.context.settings.lang == "js") {
          this._runner = new Runner(value, function (data) { return this.appContext.ev3BrickServer.message(data) }, function (err) {
            this._runner = null;
            this.running(false);
            this.context.messageLogVM.addError(`Failed to run code: ${err}`);
          }, function () {
            this._runner = null;
            this.running(false);
            this.context.messageLogVM.addSuccess('Successfully ran code!');
          });
        }
      }
    }
    this.running(!this.running());
  }
  onDisplayAbout() {
    $('#aboutModal').modal("show");
    this.__collapseNavbar();
  }
  onFullScreen() {
    this.context.compatibility.toggleFullScreen();
    this.__collapseNavbar();
  }
  onStopcod3r() {
    bootbox.dialog({
      title: i18n.t("navigationBar.confirmStopcod3r.title"),
      message: i18n.t("navigationBar.confirmStopcod3r.message"),
      buttons: {
        cancel: {
          label: i18n.t("navigationBar.confirmStopcod3r.cancel"),
          className: "btn-primary",
          callback: function () { }
        },
        stopcod3r: {
          label: i18n.t("navigationBar.confirmStopcod3r.stopcod3r"),
          className: "btn-default",
          callback: function () {
            this.context.ev3BrickServer.stopcod3r();
          }
        },
        shutdownBrick: {
          label: i18n.t("navigationBar.confirmStopcod3r.shutdownBrick"),
          className: "btn-default",
          callback: function () {
            this.context.ev3BrickServer.shutdownBrick();
          }
        }
      }
    });
  }
  onDisplaySettings() {
    this.context.settingsVM.display();
    this.__collapseNavbar();
  }
  onDisplayImportImages() {
    this.context.importImagesVM.display();
    this.__collapseNavbar();
  }
}