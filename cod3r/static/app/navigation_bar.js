// Model to manage the navigation bar actions
function NavigationBarViewModel(appContext) {
  'use strict';

  var self = this;
  self.context = appContext; // The application context
  self.workAreaItems = ko.observableArray();
  self.running = ko.observable(false);
  self.running.subscribe(() => {
    $('#runBtn').toggleClass('btn-warning', self.running()).toggleClass('btn-success', !self.running()).find('span').toggleClass('glyphicon-play', !self.running()).toggleClass('glyphicon-stop', self.running());
  })
  self._runner = null;
  self.btnScript = {
    name: "SCRIPT_EDITOR",
    data_i18n: "workArea.scriptEditorTab",
    tabId: "scriptEditorTab",
    active: ko.observable(true)
  };

  self.btnKeyboard = {
    name: "xKEYBOARD",
    data_i18n: "workArea.keyboardSensorTab",
    tabId: "keyboardSensorTab",
    active: ko.observable(false)
  };

  self.btnGyro = {
    name: "xGYRO",
    data_i18n: "workArea.gyroSensorTab",
    tabId: "gyroSensorTab",
    active: ko.observable(false)
  };

  self.btnVideo = {
    name: "xVIDEO",
    data_i18n: "workArea.videoSensorTab",
    tabId: "videoSensorTab",
    active: ko.observable(false)
  };

  self.btnGeo = {
    name: "xGEO",
    data_i18n: "workArea.geoSensorTab",
    tabId: "geoSensorTab",
    active: ko.observable(false)
  };
  self.workAreaItems.removeAll();
  self.workAreaItems.push(self.btnScript);
  self.workAreaItems.push(self.btnKeyboard);
  if (location.protocol == "https:") {
    if (window.DeviceOrientationEvent) {
      self.workAreaItems.push(self.btnGyro);
    } // else: Don't show xGyro, not supported by the browser
    if (self.context.compatibility.isUserMediaSupported()) {
      self.workAreaItems.push(self.btnVideo);
    } // else: Don't show xVideo, video/WebCam not supported by the browser
    if (navigator.geolocation) {
      self.workAreaItems.push(self.btnGeo);
    } // else: Don't show xGeo, GPS not supported by the browser
  }
  self.__collapseNavbar = function () {
    if ($("#collapser").css("display") != "none") {
      $("#collapser").click();
    }
  };
  self.onShowWorkAreaItem = function (workAreaItem) {
    // Set the active item in the model and on screen
    var items = self.workAreaItems(); // return a regular array
    for (var i = 0; i < items.length; i++) {
      items[i].active(items[i].tabId == workAreaItem.tabId);
      $("#" + items[i].tabId).toggleClass("active", items[i].active());
      self.context.events.tabDisplayedChanged.fire(items[i].tabId, items[i].active());
    }
    self.__collapseNavbar();
  };
  self.onShowWorkAreaItem(self.btnScript);


  // Auto collapse navbar while collapse feature is enabled (screen width is < 768)


  self.onRunScript = function () {
    /*     <button class="btn btn-warning navbar-btn" data-bind="click: onStopScript">
       <span class="glyphicon glyphicon-stop"></span> <span class="i18n" data-i18n="navigationBar.stop">STOP</span></button>*/
    if (self._runner) self._runner.disconnect();
    if (self.running()) {
      self._runner = null;
    } else {
      var value = (self.context.scriptEditorTabVM ? self.context.scriptEditorTabVM.editor.codeMirror.getValue() : null);
      if (value) {
        if (self.context.settings.lang == "js") {
          self._runner = new jailed.DynamicPlugin(`(function(self){Object.assign(self, ${JSON.stringify(evalContext).replace(/(\"FUNK\_START|FUNK\_END\")/g, "")})})(this);${value}`);
          self._runner.whenFailed(function () {
            self._runner = null;
            self.running(false);
            self.context.messageLogVM.addError('Failed to run code!');
          })
          self._runner.whenDisconnected(function () {
            self._runner = null;
            self.running(false);
          })
          self._runner.whenDone(function () {
            self._runner = null;
            self.running(false);
            self.context.messageLogVM.addSuccess('Successfully ran code!');
          })
        }
      }
    }
    self.running(!self.running());
  };
  self.onDisplayAbout = function () {
    $('#aboutModal').modal("show");
    self.__collapseNavbar();
  };

  self.onFullScreen = function () {
    self.context.compatibility.toggleFullScreen();
    self.__collapseNavbar();
  };

  self.onStopcod3r = function () {
    bootbox.dialog({
      title: i18n.t("navigationBar.confirmStopcod3r.title"),
      message: i18n.t("navigationBar.confirmStopcod3r.message"),
      buttons: {
        cancel: {
          label: i18n.t("navigationBar.confirmStopcod3r.cancel"),
          className: "btn-primary",
          callback: function () { /* Cancel */ }
        },
        stopcod3r: {
          label: i18n.t("navigationBar.confirmStopcod3r.stopcod3r"),
          className: "btn-default",
          callback: function () {
            self.context.ev3BrickServer.stopcod3r();
          }
        },
        shutdownBrick: {
          label: i18n.t("navigationBar.confirmStopcod3r.shutdownBrick"),
          className: "btn-default",
          callback: function () {
            self.context.ev3BrickServer.shutdownBrick();
          }
        }
      }
    });
  };

  self.onDisplaySettings = function () {
    self.context.settingsVM.display();
    self.__collapseNavbar();
  };

  self.onDisplayImportImages = function () {
    self.context.importImagesVM.display();
    self.__collapseNavbar();
  }
};