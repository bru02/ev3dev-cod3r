// Model that manage the "Settings" dialog
function SettingsViewModel(appContext) {
  'use strict';

  var self = this;
  (function () { // Init
    self.context = appContext; // The application context
    self.language = ko.observable("");
    self.lang = ko.observable("js");
  })();

  self.display = function () {
    // Initialize the values
    self.language(self.context.settings.language);
    self.lang(self.context.settings.lang);

    $('#settingsModal').modal('show');
  };

  self.hide = function () {
    $('#settingsModal').modal('hide');
  };

  self.onSave = function () {
    self.context.settings.update({
      language: self.language(),
      lang: self.lang()
    });

    self.hide();
  };
}
