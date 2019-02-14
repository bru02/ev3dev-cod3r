// Model that manage the "Settings" dialog
class SettingsViewModel {
  constructor(appContext) {
    this.context = appContext; // The application context
    this.language = ko.observable("");
    this.lang = ko.observable("js");
  }

  display() {
    // Initialize the values
    this.language(this.context.settings.language);
    this.lang(this.context.settings.lang);
    $('#settingsModal').modal('show');
  }

  hide() {
    $('#settingsModal').modal('hide');
  }

  onSave() {
    this.context.settings.update({
      language: this.language(),
      lang: this.lang()
    });
    this.hide();
  }
}