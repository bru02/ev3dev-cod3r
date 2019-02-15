// Manage the interaction with the server on the EV3 brick
class EV3BrickServer {
  constructor(appContext) {
    this.context = appContext; // The application context
    this.ws = undefined; // undefined <=> no connection with the EV3 brick
    this.xSensorStream = { // Manage the xSensor stream
      sensors: {},
      timeoutID: undefined
    };
    this.XSENSOR_STREAM_FREQUENCY = 50; // in ms => Maximum of 20 message by second by xSensor
  }

  initialize() {
    if ("WebSocket" in window) {
      var wsURI = location.protocol.replace('http', 'ws') + "//" + location.host + "/connect",
        self = this;
      try {
        this.ws = new WebSocket(wsURI);
        this.ws.onopen = function (evt) { self.__onWSOpen(evt); };
        this.ws.onclose = function (evt) { self.__onWSClose(evt); };
        this.ws.onmessage = function (evt) { self.__onWSMessage(evt); };
        this.ws.onerror = function (evt) { self.__onWSError(evt); };
      } catch (ex) {
        console.warn("Fail to create websocket for: '" + wsURI + "'");
        this.context.messageLogVM.addError(i18n.t("ev3brick.errors.ev3ConnectionFailed", { cansedBy: ex }));
        this.WSReconnection();
      }
    }
    else {
      this.context.messageLogVM.addError(i18n.t("ev3brick.errors.websocketNotSupported"));
    }
  };

  __onWSOpen(evt) {
    this.context.messageLogVM.addSuccess(i18n.t("ev3brick.ev3ConnectionOk"));
  };

  __onWSMessage(evt) {
    var received_msg = evt.data;
    var received_data = JSON.parse(received_msg);
    var msgType = received_data.msgTyp;
    console.log("Message received: " + received_msg);

    if (msgType == "ScriptException" || msgType == "Exception") {
      this.context.messageLogVM.addError(i18n.t("server.errors." + received_data.code, received_data.params));
    } else if (msgType == "InfoCoded") {
      this.context.messageLogVM.addInfo(i18n.t("server.messages." + received_data.code, received_data.params));
    } else if (!('res' in received_data || 'id' in received_data || 'btnPressed' in received_data)) {
      // Default: Assume this is a text message
      this.context.messageLogVM.addInfo(received_data.txt);
    }
  };

  __onWSClose(evt) {
    this.context.messageLogVM.addError(i18n.t("ev3brick.errors.ev3ConnectionNok"));
    this.WSReconnection();
  };

  __onWSError(evt) {
    // Does nothing, onError seems redundant with onClose, see http://www.w3.org/TR/websockets/#feedback-from-the-protocol
  };

  WSReconnection() {
    this.WSClose();
    setTimeout(this.initialize, 15000); // Run once in 15s
  };

  // Close the websocket (if initialized)
  WSClose() {
    if (this.ws) {
      if (this.ws.readyState == 0 || this.ws.readyState == 1) { // CONNECTING or OPEN - See https://developer.mozilla.org/en-US/docs/Web/API/WebSocket#Ready_state_constants
        try {
          this.ws.close();
        } catch (ex) {
          console.warn("Fail to close the websocket - " + JSON.stringify(ex));
        }
      } // else: CLOSED or CLOSING => No need to close again
      this.ws = undefined;
    }
  };

  // Send a message to the websocket (if opened)
  // Returns true if sent, false otherwise
  WSSend(message) {
    let msg = message;
    if (this.ws && (this.ws.readyState == 1)) { // OPEN
      try {
        if (message['err']) {
          delete message['err']
        }
        if (typeof (message) !== "string") {
          message = JSON.stringify(message);
        }
        this.ws.send(message);
        return true;
      } catch (ex) {
        console.log("Fail to send a message - " + JSON.stringify(ex));
        this.WSReconnection();
        return false;
      }
    } else {
      console.log("Can't send a message because the ws isn't initialized or isn't opened - " + message);
    }
    this.context.messageLogVM.addError(i18n.t(`ev3brick.errors.${msg['err'] || 'cantDoSomethingEV3ConnectionNok'}`, {
      action:
        msg['act']
    }));

    return false;
  };

  runScript(scriptCode) {
    if (scriptCode) {
      this.WSSend({
        act: "runScript",
        sLang: "javascript",
        sText: scriptCode,
        err: "cantRunScriptEV3ConnectionNok"
      })
    }
  };

  stopScript() {
    this.WSSend({
      act: "stopScript",
      err: "cantStopScriptEV3ConnectionNok"
    })
  };

  shutdownBrick() {
    this.WSSend({
      act: "shutdownBrick"
    })
  };

  stopcod3r() {
    this.WSSend({
      act: "stopCod3r"
    })
  }

  async message(data = {}) {
    return new Promise((resolve, reject) => {
      let id = Utils.generateUUID();
      if (this.WSSend($.fn.extend(data, {
        id,
      }))) {
        this.ws.addEventListener('message', function cb(msg) {
          try {
            msg = JSON.parse(msg.data);
          } catch (e) {
            return;
          }
          if (msg.id !== id) return;
          this.ws.removeEventListener('message', cb);
          if (msg.err) {
            reject(msg.err);
            this.appContext.messageLogVM.addError(msg.err);
          } else {
            resolve(msg.res);
          }
        });
      }
    })
  }
}