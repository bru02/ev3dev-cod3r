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


// Model that manage the message log view
function MessageLogViewModel(appContext) { // appContext not used for MessageLog
  'use strict';

  var self = this;
  { // Init
    self.context = appContext; // The application context
    self.messages = ko.observableArray();
    self.messages.extend({ rateLimit: 200 }); // Accept lower refresh rate
    self.keepOnlyLastMessages = ko.observable(true);
    self.MESSAGES_TO_KEEP = 15;

    // Register events
    self.context.events.resize.add(function (workAreaHeight, usefullWorkAreaHeight) {
      self.doResize(workAreaHeight, usefullWorkAreaHeight);
    });
  }

  self.addMessage = function (type, message) {
    function doAddMessage(type, message, count) {
      self.messages.unshift({
        "time": new Date().toLocaleTimeString(),
        "cssClass": "list-group-item-" + type,
        type,
        "text": message,
        "count": count
      });
      self.KeepOnlyLastMessages();
    }

    // Manage the message count
    var m0 = (self.messages().length > 0 ? self.messages()[0] : undefined);
    if (m0 && (m0.type == type) && (m0.text == message)) {
      self.messages.shift();
      doAddMessage(type, message, m0.count + 1);
    } else {
      doAddMessage(type, message, 1);
    }
  };
  self.addError = function (msg) {
    self.addMessage('danger', msg)
  }
  self.addInfo = function (msg) {
    self.addMessage('info', msg)
  }
  self.addSuccess = function (msg) {
    self.addMessage('success', msg)
  }

  self.onResetMessages = function () {
    self.messages.removeAll();
  };

  self.onKeepOnlyLastMessages = function () {
    self.keepOnlyLastMessages(!self.keepOnlyLastMessages());
    self.KeepOnlyLastMessages();
  };

  self.KeepOnlyLastMessages = function () {
    if (self.keepOnlyLastMessages()) {
      self.messages.splice(self.MESSAGES_TO_KEEP); // Keep the first n messages
    }
  };

  self.doResize = function (workAreaHeight, usefullWorkAreaHeight) {
    self.MESSAGES_TO_KEEP = Math.max(15, Math.round(usefullWorkAreaHeight / 40)); // 40 is a bit more than the height of a single line message
    self.KeepOnlyLastMessages();
  };
}
