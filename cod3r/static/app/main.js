// Basic checks for "browser compatibility"
//
// Note: Don't perform this check in jQuery .ready() callback as version 2.x of jQuery don't have compatibility with some 'old' browser.
//       Don't use i18n as it doesn't work on some 'old' browser (eg. IE8)
if (!('WebSocket' in window &&
  'matchMedia' in window)) { // A minimal level of css for bootstrap
  alert("Cod3r can't run in this browser, consider using a more recent browser.\nThe page will be automatically closed.");
  window.close();
}


var context = { // The application context - used as a basic dependency-injection mechanism
  // Define events that are used internally in the application (See https://api.jquery.com/category/callbacks-object/).
  events: {
    // cod3r resized event.
    // Params: workAreaHeight, usefullWorkAreaHeight
    resize: $.Callbacks('unique'),
    // Settings (configuration change event. 
    // Params: keyChanged, newValue
    // Be carefull, on 'language' translations may not have been reloaded, use 'languageReloaded' instead
    changeSettings: $.Callbacks('unique'),
    // Language reloaded event (the i18n has been updated with the new language).
    // Params: none
    languageReloaded: $.Callbacks('unique'),
    // The displayed tab has changed
    // Params: tabName, isVisible
    tabDisplayedChanged: $.Callbacks('unique')
  }
};

// Define a settings object
class cod3rSettings {
  constructor() {
    this.STORAGE_SETTINGS = "cod3r_settings";
    this.JSON_FIELDS = ['language', 'lang'];
    // Load 
    var loaded = localStorage[this.STORAGE_SETTINGS];
    console.log("Settings loaded on local storage: " + loaded);
    if (loaded) {
      try {
        var newSettings = JSON.parse(loaded);
        for (var key in newSettings) {
          if (this.JSON_FIELDS.indexOf(key) != -1) {
            this[key] = newSettings[key];
          }
        }
      }
      catch (ex) {
        console.log("Error: " + ex + " on settings loaded: " + loaded);
      }
    }
    // Language
    if (!this.language) {
      var language_complete = navigator.language.split("-");
      this.language = (language_complete[0]) == "fr" ? "fr" : "en";
    }
    // ProgrammingStyle
    if (!this.lang) {
      this.lang = "js";
    }
    // Override with url param if exist
    var param = Utils.getUrlParameter("programmingLanguage");
    if (param && (param == "js" || param == "py")) {
      this.lang = param;
    }
  }

  update(newSettings) {
    var needSave = false;
    for (var key in newSettings) {
      if (this[key] && this[key] != newSettings[key]) {
        this[key] = newSettings[key];
        context.events.changeSettings.fire(key, this[key]);
        needSave = true;
      }
    }
    if (needSave) {
      localStorage[this.STORAGE_SETTINGS] = JSON.stringify(this, this.JSON_FIELDS);
    }
  }
}

context.settings = new cod3rSettings(context);

// Initialization of the application
$(document).ready(function () {
  'use strict';

  // Translation
  i18n.init({ fallbackLng: 'en', lng: context.settings.language }, function () {
    context.settings.language = i18n.lng(); // Set language really used    
    // Technical objects
    context.compatibility = compatibility;
    context.fileName = ko.observable("")
    context.isSaved = ko.observable(true)

    context.ev3BrickServer = new EV3BrickServer(context);
    context.navigationBarVM = new NavigationBarViewModel(context);
    context.messageLogVM = new MessageLogViewModel(context);
    // Tabs
    context.scriptEditorTabVM = new ScriptEditorTabViewModel(context);
    context.keyboardSensorTabVM = new KeyboardSensorTabViewModel(context);
    context.gyroscopeSensorTabVM = new GyroscopeSensorTabViewModel(context);
    context.videoSensorTabVM = new VideoSensorTabViewModel(context);
    context.geoSensorTabVM = new GeoSensorTabViewModel(context);
    // Dialogs
    context.settingsVM = new SettingsViewModel(context);
    context.importImagesVM = new ImportImagesViewModel(context);
    context.saveAsVM = new SaveAsDialogViewModel(context);

    // Knockout bindings
    ko.applyBindings(context.navigationBarVM, $("#navigationBar")[0]);
    ko.applyBindings(context.messageLogVM, $("#messageLog")[0]);
    // Tabs
    ko.applyBindings(context.scriptEditorTabVM, $("#scriptEditorTab")[0]);
    ko.applyBindings(context.keyboardSensorTabVM, $("#keyboardSensorTab")[0]);
    ko.applyBindings(context.gyroscopeSensorTabVM, $("#gyroSensorTab")[0]);
    ko.applyBindings(context.videoSensorTabVM, $("#videoSensorTab")[0]);
    ko.applyBindings(context.geoSensorTabVM, $("#geoSensorTab")[0]);
    // Dialogs
    ko.applyBindings(context.settingsVM, $("#settingsModal")[0]);
    ko.applyBindings(context.importImagesVM, $("#importImagesModal")[0]);
    ko.applyBindings(context.saveAsVM, $("#saveAsModal")[0]);

    // Other initialization
    context.ev3BrickServer.initialize(); // WebSocket connection with the server

    // Register config events to update translation if needed
    context.events.changeSettings.add(function (keyChanged, newValue) {
      if ("language" == keyChanged) {
        i18n.setLng(context.settings.language, function (t) {
          $(".i18n").i18n();
          context.events.languageReloaded.fire();
        });
      }
    });
    if (context.fileName()) context.scriptEditorTabVM.loadScript();


    // Publish events for settings
    context.events.changeSettings.fire("language", context.settings.language);


    // Register windows events for editor auto-resize
    window.onresize = function () {
      var workAreaHeight = window.innerHeight - 60; // Should be synchronized with body.padding-top (in css/HTML)
      var usefullWorkAreaHeight = workAreaHeight - 35; // Also remove the button bar
      context.events.resize.fire(workAreaHeight, usefullWorkAreaHeight);
    };
    $(window).resize();

    // Register windows events for keyboard shortcuts
    document.onkeydown = function (e) {
      if (e.ctrlKey) {
        if (e.keyCode == 83) { // Ctrl+S
          e.preventDefault();
          e.stopPropagation();
          if (e.shiftKey) context.saveAsVM.display()
          context.scriptEditorTabVM.onSaveScript();
          return false;
        }
      }
    };
    // Register windows event to ask confirmation while the user leave the page (avoid loosing scripts)
    window.onbeforeunload = function () {
      if (!context.isSaved()) return "";
    };
    clientLib.init(context.ev3BrickServer.message, i18n.t);
  });
});
$(document).ready(function () {
  $('[data-toggle=offcanvas]').click(function () {
    $("#sidebar").toggleClass("collapsed");
    $(".row-offcanvas").toggleClass("active");
    $("#content").toggleClass("col-md-12 col-md-8").one('transitionend', function () {
      Blockly.svgResize(context.scriptEditorTabVM.editor.blockly);
    });
  });
});