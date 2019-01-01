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
    if (self._runner) self._runner.disconnect();
    if (self.running()) {
      self._runner = null;
    } else {
      var value = (self.context.scriptEditorTabVM ? self.context.scriptEditorTabVM.editor.codeMirror.getValue() : null);
      if (value) {
        if (self.context.settings.lang == "js") {
          self._runner = new jailed.DynamicPlugin(`(function(window){let fns=${JSON.stringify(fns)}, send=application.remote.send,add=application.remote.add,rem=application.remote.rem,id=application.remote.id,err=application.remote.err;function each(b,c){for(let d=0;d<b.length&&!!c(d,b[d]);d++);}for(let[a,b]of Object.entries(fns)){"global"==a?t=window:(window[a]={},t=window[a]);for(let[c,d]of Object.entries(b))t[c]=async function(){let f=Array.from(arguments);return each(d.args,function(g,h){if(f[g]){let k=typeof f[g],l=!0;Array.isArray(h.type)?each(h.type,(m,n)=>{if(l=l&&k!==n,!l)return!1}):l=k!==h.type,l&&err("Argument type mismatch for function "+a+"."+c+" argument "+g+".")}else"default"in h?f[g]=h.default:err("Too few arguments supplied to function "+a+"."+c+".")}),new Promise((g,h)=>{let k=k();if(send({act:"ufn",fn:a+"_"+c,args:f,id:k})){function l(m){try{m=JSON.parse(m.data)}catch(n){return}m.id!==k||(rem("message",l),m.err?(h(m.err),err(m.err)):g(m.res))}add("message",l)}})}}leds.off=function(){return leds.setColor("BOTH",[0,0])},infraredSensor.distanceCM=function(){return infraredSensor.proximity.call(this,arguments).then(a=>{return 0.7*a})};var cbs={backspace:[],left:[],right:[],up:[],down:[],enter:[]},listener=!1;button.on=function(a,b){cbs[a]&&(cbs[a].push(b),!listener&&(listener=!0,add("message",function(c){try{c=JSON.parse(c.data),c.btnPressed&&cbs[c.grp]&&each(cbs[c.grp],(d,f)=>f())}catch(d){}})))},button.off=function(a,b){if(cbs[a])return cbs[a].filter(function(c){return c!=b})},window.sleep=function(b){return new Promise(c=>setTimeout(c,500*b))};})(this);${value}`, {
            send: self.context.ev3BrickServer.WSSend,
            add: self.context.ev3BrickServer.ws.addEventListener,
            rem: self.context.ev3BrickServer.removeEventListener,
            err: self.context.messageLogVM.addError,
            id: Utils.generateUUID(),
          });
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