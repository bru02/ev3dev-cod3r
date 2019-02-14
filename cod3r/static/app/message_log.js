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
class MessageLogViewModel {
  constructor(appContext) {
    // Init
    this.context = appContext; // The application context
    this.messages = ko.observableArray();
    this.messages.extend({ rateLimit: 200 }); // Accept lower refresh rate
    this.keepOnlyLastMessages = ko.observable(true);
    this.MESSAGES_TO_KEEP = 15;
    // Register events
    this.context.events.resize.add(function (workAreaHeight, usefullWorkAreaHeight) {
      this.doResize(workAreaHeight, usefullWorkAreaHeight);
    });
  }
  addMessage(type, message) {
    function doAddMessage(type, message, count) {
      this.messages.unshift({
        "time": new Date().toLocaleTimeString(),
        "cssClass": "list-group-item-" + type,
        type,
        "text": message,
        "count": count
      });
      this.KeepOnlyLastMessages();
    }
    // Manage the message count
    var m0 = (this.messages().length > 0 ? this.messages()[0] : undefined);
    if (m0 && (m0.type == type) && (m0.text == message)) {
      this.messages.shift();
      doAddMessage(type, message, m0.count + 1);
    }
    else {
      doAddMessage(type, message, 1);
    }
  }
  addError(msg) {
    this.addMessage('danger', msg);
  }
  addInfo(msg) {
    this.addMessage('info', msg);
  }
  addSuccess(msg) {
    this.addMessage('success', msg);
  }
  onResetMessages() {
    this.messages.removeAll();
  }
  onKeepOnlyLastMessages() {
    this.keepOnlyLastMessages(!this.keepOnlyLastMessages());
    this.KeepOnlyLastMessages();
  }
  KeepOnlyLastMessages() {
    if (this.keepOnlyLastMessages()) {
      this.messages.splice(this.MESSAGES_TO_KEEP); // Keep the first n messages
    }
  }
  doResize(workAreaHeight, usefullWorkAreaHeight) {
    this.MESSAGES_TO_KEEP = Math.max(15, Math.round(usefullWorkAreaHeight / 40)); // 40 is a bit more than the height of a single line message
    this.KeepOnlyLastMessages();
  }
}