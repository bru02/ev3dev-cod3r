/*
 * cod3r is a simple scripting environment for the Lego Mindstrom EV3
 * Copyright (C) 2014-2016 Jean BENECH
 *
 * cod3r is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * cod3r is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with cod3r.  If not, see <http://www.gnu.org/licenses/>.
 */
// Model to manage the keyboard x-Sensor
class KeyboardSensorTabViewModel {
  constructor(appContext) {
    // Init
    this.context = appContext; // The application context
    // Data for the View
    this.buttons = [];
    this.isStarted = ko.observable(false);
    this.sensorName = ko.observable("xTouch");
    this.keyboardFilename = undefined;
    for (var i = 0; i < 4; i++) {
      this.buttons[i] = [];
      for (var j = 0; j < 6; j++) {
        this.buttons[i].push({
          //row: i, col: j,
          name: ko.observable(""),
          actions: [],
          isDisabled: ko.observable(false),
          isPressed: false
        });
      }
    }
    // In order to be able to manage multi-touch, bind events on the top level keyboard element, with jQuery delegate
    // (Don't find a better way to do it with 'standard' button )
    $("#xTouchButtons").on("mousedown touchstart", "button", function (event) {
      var btn = ko.dataFor(this);
      this.OnButtonPressed(btn);
      $(this).addClass("active");
      this.style.color = "red";
      return false;
    });
    $("#xTouchButtons").on("mouseup mouseout touchend", "button", function (event) {
      var btn = ko.dataFor(this);
      this.OnButtonRelease(btn);
      $(this).removeClass("active");
      this.style.removeProperty("color");
      return false;
    });
    // Register events
    this.context.events.resize.add(function (workAreaHeight, usefullWorkAreaHeight) {
      this.doResize(workAreaHeight, usefullWorkAreaHeight);
    });
  }
  onStart() {
    this.isStarted(!this.isStarted());
    // Switch the button status according to the mode & button content
    this.buttons.forEach(function (e0) {
      e0.forEach(function (e1) {
        e1.isDisabled(this.isStarted() && (e1.name().length == 0));
        e1.isPressed = false;
      });
    });
    this.NotifyStateChanged(true);
  }
  OnButtonPressed(btn) {
    if (this.isStarted()) {
      btn.isPressed = true;
      this.NotifyStateChanged(false);
    }
    else {
      bootbox.prompt({
        title: i18n.t('keyboardSensorTab.configureKeyboardButtonModal.title'),
        value: btn.name(),
        callback: function (result) {
          if (result) {
            btn.actions = this.__splitNameToActions(result);
            btn.name(this.__buildNameFromActions(btn.actions));
          } // else, cancel clicked
        }
      });
    }
  }
  OnButtonRelease(btn) {
    if (btn.isPressed) {
      btn.isPressed = false;
      this.NotifyStateChanged(false);
    } // else, useless event
  };
  __splitNameToActions(name) {
    return name.trim().split(",")
      .map(function (e) { return e.trim(); })
      .filter(function (e) { return e.length > 0; });
  }
  __buildNameFromActions(actions) {
    return actions.reduce(function (val, elt) {
      return (val.length == 0 ? val : val + ", ") + elt;
    }, "");
  }
  NotifyStateChanged(sendIfNotStarted) {
    if (this.isStarted() || sendIfNotStarted) {
      // Notify the list of actions triggered
      var xValue = {
        isStarted: this.isStarted(),
        touchs: {}
      };
      if (this.isStarted()) {
        this.buttons.forEach(function (e0) {
          e0.forEach(function (e1) {
            if (e1.isPressed) {
              e1.actions.forEach(function (a) {
                var btn = xValue.touchs[a];
                xValue.touchs[a] = (btn == undefined ? 1 : btn + 1);
              });
              // Array.prototype.push.apply(xValue.touchs, e1.actions);
            }
          });
        });
      }
      this.context.ev3BrickServer.sendXSensorValue(this.sensorName(), "Tch1", xValue);
    }
  }
  onResetKeyboard() {
    bootbox.confirm(i18n.t("keyboardSensorTab.resetKeyboardModal.title"), function (result) {
      if (result) {
        this.ResetKeyboard();
      }
    });
  }
  ResetKeyboard() {
    this.buttons.forEach(function (e0) {
      e0.forEach(function (e1) {
        e1.name("");
        e1.actions = [];
        e1.isDisabled(false);
        e1.isPressed = false;
      });
    });
  }
  onLoadKeyboard() {
    this.context.manageFilesVM.display(this.loadKeyboardFile, function () { return "/rest/xkeyboardfiles/"; }, function (filename) { return "/rest/xkeyboardfiles/" + filename; });
  };
  loadKeyboardFile(filename) {
    this.keyboardFilename = undefined;
    console.log("Try loading keyboard: '" + filename + "'");
    $.ajax({
      url: "/rest/xkeyboardfiles/" + filename,
      success: function (data, status) {
        console.log("Keyboard downloaded from server: '" + filename + "'");
        var keyboardFile = JSON.parse(data);
        this.loadFromJSON(keyboardFile.content);
        if (filename.indexOf("__") != 0) { // Not read-only => memorize the filename
          this.keyboardFilename = filename;
        }
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        // XMLHttpRequest.status: HTTP response code
        this.context.messageLogVM.addError(i18n.t("keyboardSensorTab.errors.cantLoadKeyboardFile", { "filename": filename, causedBy: ("" + XMLHttpRequest.status + " - " + errorThrown) }));
      }
    });
  }
  loadFromJSON(json) {
    var keyboard = JSON.parse(json);
    if (keyboard && keyboard.version && keyboard.version == 1) {
      this.ResetKeyboard();
      this.sensorName(keyboard.sensorName);
      keyboard.buttons.forEach(function (line, idxLine) {
        line.forEach(function (btn, idxBtn) {
          this.buttons[idxLine][idxBtn].name(this.__buildNameFromActions(btn.actions));
          this.buttons[idxLine][idxBtn].actions = btn.actions;
        });
      });
    }
    else {
      // XMLHttpRequest.status: HTTP response code
      this.context.messageLogVM.addError(i18n.t("keyboardSensorTab.errors.invalidKeyboadFile", { "version: ": (keyboard ? keyboard.version : "undefined") }));
    }
  }
  saveToJSON() {
    var keyboard = {};
    keyboard.version = 1;
    keyboard.sensorName = this.sensorName();
    keyboard.buttons = [];
    this.buttons.forEach(function (line) {
      var lineToSave = [];
      line.forEach(function (btn) {
        lineToSave.push({
          name: btn.name,
          actions: btn.actions
        });
      });
      keyboard.buttons.push(lineToSave);
    });
    return JSON.stringify(keyboard);
  }
  onSaveKeyboard() {
    bootbox.prompt({
      title: i18n.t('keyboardSensorTab.saveKeyboardModal.title'),
      value: (this.keyboardFilename ? this.keyboardFilename : ""),
      callback: function (result) {
        if (result && (result.trim().lenght != 0)) {
          var filename = result.trim();
          console.log("Save keyboard: '" + filename + "'");
          $.ajax({
            url: "/rest/xkeyboardfiles/" + filename,
            content: "application/json",
            data: JSON.stringify({
              name: filename,
              content: this.saveToJSON()
            }),
            type: "PUT",
            success: function (data, status) {
              this.keyboardFilename = filename;
              this.context.messageLogVM.addSuccess(i18n.t("keyboardSensorTab.keyboardSuccessfullySaved", { "filename": filename }));
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
              this.context.messageLogVM.addError(i18n.t("keyboardSensorTab.errors.cantSaveKeyboardFile", { "filename": filename, causedBy: ("" + XMLHttpRequest.status + " - " + errorThrown) }));
            }
          });
        } // else: cancel clicked
      }
    });
  }
  doResize(workAreaHeight, usefullWorkAreaHeight) {
    $('.xkeyboard-touch').css('height', Math.round(Math.max(45, Math.min(window.innerWidth / 6, // Max height for better display for devices in portrait mode 
      (usefullWorkAreaHeight - 10) / 4))).toString() + 'px');
  };
}