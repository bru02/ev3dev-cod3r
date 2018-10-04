/*
 * Gnikrap is a simple scripting environment for the Lego Mindstrom EV3
 * Copyright (C) 2014-2015 Jean BENECH
 *
 * Gnikrap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Gnikrap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Gnikrap.  If not, see <http://www.gnu.org/licenses/>.
 */


// Manage the interaction with the server on the EV3 brick
function EV3BrickServer(appContext) {
  'use strict';

  var self = this;
  { // Init
    self.context = appContext; // The application context
    self.ws = undefined; // undefined <=> no connection with the EV3 brick
    self.xSensorStream = { // Manage the xSensor stream
      sensors: {},
      timeoutID: undefined
    };
    self.XSENSOR_STREAM_FREQUENCY = 50; // in ms => Maximum of 20 message by second by xSensor
  }

  self.initialize = function () {
    if ("WebSocket" in window) {
      var wsURI = "ws://" + location.host + "/ws/gnikrap/script";
      try {
        self.ws = new WebSocket(wsURI);
        self.ws.onopen = function (evt) { self.__onWSOpen(evt); };
        self.ws.onclose = function (evt) { self.__onWSClose(evt); };
        self.ws.onmessage = function (evt) { self.__onWSMessage(evt); };
        self.ws.onerror = function (evt) { self.__onWSError(evt); };
      } catch (ex) {
        console.warn("Fail to create websocket for: '" + wsURI + "'");
        self.context.messageLogVM.addMessage(true, i18n.t("ev3brick.errors.ev3ConnectionFailed", { cansedBy: ex }));
        self.WSReconnection();
      }
    }
    else {
      self.context.messageLogVM.addMessage(true, i18n.t("ev3brick.errors.websocketNotSupported"));
    }
  };

  self.__onWSOpen = function (evt) {
    self.context.messageLogVM.addMessage(false, i18n.t("ev3brick.ev3ConnectionOk"));
  };

  self.__onWSMessage = function (evt) {
    var received_msg = evt.data;
    var received_data = JSON.parse(received_msg);
    var msgType = received_data.msgTyp;
    console.log("Message received: " + received_msg);

    if (msgType == "ScriptException" || msgType == "Exception") {
      if (received_data.code == "SCRIPT_ALREADY_RUNNING") {
        // Ask confirmation in order to stop the script
        bootbox.confirm(i18n.t("ev3brick.confirmStopScriptAlreadyRunning"), function (result) {
          if (result) {
            self.context.navigationBarVM.doRunScript(true);
          } // else cancel
        });
      }
      else {
        self.context.messageLogVM.addMessage(true, i18n.t("server.errors." + received_data.code, received_data.params));
      }
    } else if (msgType == "InfoCoded") {
      self.context.messageLogVM.addMessage(false, i18n.t("server.messages." + received_data.code, received_data.params));
    } else {
      // Default: Assume this is a text message
      self.context.messageLogVM.addMessage(false, received_data.txt);
    }
  };

  self.__onWSClose = function (evt) {
    self.context.messageLogVM.addMessage(true, i18n.t("ev3brick.errors.ev3ConnectionNok"));
    self.WSReconnection();
  };

  self.__onWSError = function (evt) {
    // Does nothing, onError seems redundant with onClose, see http://www.w3.org/TR/websockets/#feedback-from-the-protocol
  };

  self.WSReconnection = function () {
    self.WSClose();
    setTimeout(self.initialize, 15000); // Run once in 15s
  };

  // Close the websocket (if initialized)
  self.WSClose = function () {
    if (self.ws) {
      if (self.ws.readyState == 0 || self.ws.readyState == 1) { // CONNECTING or OPEN - See https://developer.mozilla.org/en-US/docs/Web/API/WebSocket#Ready_state_constants
        try {
          self.ws.close();
        } catch (ex) {
          console.warn("Fail to close the websocket - " + JSON.stringify(ex));
        }
      } // else: CLOSED or CLOSING => No need to close again
      self.ws = undefined;
    }
  };

  // Send a message to the websocket (if opened)
  // Returns true if sent, false otherwise
  self.WSSend = function (message) {
    if (self.ws && (self.ws.readyState == 1)) { // OPEN
      try {
        self.ws.send(message);
        return true;
      } catch (ex) {
        console.log("Fail to send a message - " + JSON.stringify(ex));
        self.WSReconnection();
      }
    } else {
      console.log("Can't send a message because the ws isn't initialized or isn't opened - " + JSON.stringify(message));
    }
    return false;
  };

  self.runScript = function (scriptCode, stopRunningScript) {
    if (scriptCode) {
      var jsonMsg = JSON.stringify({
        act: "runScript",
        sLang: "javascript",
        sText: scriptCode,
        sFStop: stopRunningScript
      });
      // console.log("runScript - " + jsonMsg);
      if (self.WSSend(jsonMsg) == false) {
        self.context.messageLogVM.addMessage(true, i18n.t("ev3brick.errors.cantRunScriptEV3ConnectionNok"));
      }
    }
  };

  self.stopScript = function () {
    var jsonMsg = JSON.stringify({
      act: "stopScript"
    });
    if (self.WSSend(jsonMsg) == false) {
      self.context.messageLogVM.addMessage(true, i18n.t("ev3brick.errors.cantStopScriptEV3ConnectionNok"));
    }
  };

  self.shutdownBrick = function () {
    var jsonMsg = JSON.stringify({
      act: "shutdownBrick"
    });
    if (self.WSSend(jsonMsg) == false) {
      self.context.messageLogVM.addMessage(true, i18n.t("ev3brick.errors.cantDoSomethingEV3ConnectionNok", { "action": "shutdownBrick" }));
    }
  };

  self.stopGnikrap = function () {
    var jsonMsg = JSON.stringify({
      act: "stopGnikrap"
    });
    if (self.WSSend(jsonMsg) == false) {
      self.context.messageLogVM.addMessage(true, i18n.t("ev3brick.errors.cantDoSomethingEV3ConnectionNok", { "action": "stopGnikrap" }));
    }
  };
}