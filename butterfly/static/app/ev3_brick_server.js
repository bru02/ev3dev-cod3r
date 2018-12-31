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
      var wsURI = location.protocol.replace('http', 'ws') + "//" + location.host + "/connect";
      try {
        self.ws = new WebSocket(wsURI);
        self.ws.onopen = function (evt) { self.__onWSOpen(evt); };
        self.ws.onclose = function (evt) { self.__onWSClose(evt); };
        self.ws.onmessage = function (evt) { self.__onWSMessage(evt); };
        self.ws.onerror = function (evt) { self.__onWSError(evt); };
      } catch (ex) {
        console.warn("Fail to create websocket for: '" + wsURI + "'");
        self.context.messageLogVM.addError(i18n.t("ev3brick.errors.ev3ConnectionFailed", { cansedBy: ex }));
        self.WSReconnection();
      }
    }
    else {
      self.context.messageLogVM.addError(i18n.t("ev3brick.errors.websocketNotSupported"));
    }
  };

  self.__onWSOpen = function (evt) {
    self.context.messageLogVM.addSuccess(i18n.t("ev3brick.ev3ConnectionOk"));
  };

  self.__onWSMessage = function (evt) {
    var received_msg = evt.data;
    var received_data = JSON.parse(received_msg);
    var msgType = received_data.msgTyp;
    console.log("Message received: " + received_msg);

    if (msgType == "ScriptException" || msgType == "Exception") {
      self.context.messageLogVM.addError(i18n.t("server.errors." + received_data.code, received_data.params));
    } else if (msgType == "InfoCoded") {
      self.context.messageLogVM.addInfo(i18n.t("server.messages." + received_data.code, received_data.params));
    } else {
      // Default: Assume this is a text message
      self.context.messageLogVM.addInfo(received_data.txt);
    }
  };

  self.__onWSClose = function (evt) {
    self.context.messageLogVM.addError(i18n.t("ev3brick.errors.ev3ConnectionNok"));
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
    let msg = message;
    if (self.ws && (self.ws.readyState == 1)) { // OPEN
      try {
        if (message['err']) {
          delete message['err']
        }
        if (typeof (message) !== "string") {
          message = JSON.stringify(message);
        }
        self.ws.send(message);
        return true;
      } catch (ex) {
        console.log("Fail to send a message - " + JSON.stringify(ex));
        self.WSReconnection();
        return false;
      }
    } else {
      console.log("Can't send a message because the ws isn't initialized or isn't opened - " + message);
    }
    self.context.messageLogVM.addError(i18n.t(`ev3brick.errors.${msg['err'] || 'cantDoSomethingEV3ConnectionNok'}`, {
      action:
        msg['act']
    }));

    return false;
  };

  self.runScript = function (scriptCode) {
    if (scriptCode) {
      self.WSSend({
        act: "runScript",
        sLang: "javascript",
        sText: scriptCode,
        err: "cantRunScriptEV3ConnectionNok"
      })
    }
  };

  self.stopScript = function () {
    self.WSSend({
      act: "stopScript",
      err: "cantStopScriptEV3ConnectionNok"
    })
  };

  self.shutdownBrick = function () {
    self.WSSend({
      act: "shutdownBrick"
    })
  };

  self.stopcod3r = function () {
    self.WSSend({
      act: "stopCod3r"
    })
  }
}