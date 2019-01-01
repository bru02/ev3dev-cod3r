function SaveAsDialogViewModel(appContext) {
  'use strict';

  var self = this;
  (function () { // Init
    self.context = appContext; // The application context
    self.folder = ko.observable("/");
    self.fileName = ko.observable(localStorage['script'] || false);
    self.fileName.subscribe(() => {
      localStorage['script'] = self.fileName()
    })
    self.folderDialog = undefined;
  })();

  self.display = function () {
    $('#saveAsModal').modal('show');
  };

  self.hide = function () {
    $('#saveAsModal').modal('hide');
  };

  self.changeFolder = function () {
    PopupCenter("/manage?save=1", "Pick a folder", 600, 600);
    window.addEventListener('message', function (e) {
      e = JSON.parse(e.data);
      if (!e['dir'] || e.origin !== location.origin) return;
      self.folder(e['dir']);
    })
  }

  self.onSave = function () {
    let path = self.folder() + self.fileName();
    $.post("/bridge.py", {
      action: "createFile",
      item: path
    }, function (e) {
      try {
        e = JSON.parse(e);
      } catch (e) {
        self.context.messageLogVM.addError('Failed to create file!');
        return;
      }
      self.context.messageLogVM.addMessage(e.result.success ? 'success' : 'danger', e.result.error || "Saved file!")
    });
    self.context.fileName(path);
    self.hide();
  };
}


function ScriptEditorTabViewModel(appContext) {
  'use strict';

  var self = this;
  (function () { // Init
    self.context = appContext; // The application context
    self.isJs = ko.observable(self.context.settings.lang == 'js')
    var mode = ko.observable("Split");
    self.editor = window.dbl = new DoubleEditor($('#scriptEditorTab'), { lang: "python", editor: mode })
    self.editor.setCodeLang(self.context.settings.lang == "js" ? "javascript" : "python")
    self.split = Split([".blockpy-blocks", ".blockpy-text"], {
      cursor: 'row-resize', onDrag: () => {
        Blockly.svgResize(self.ScriptEditorTabViewModel.editor.blockly);
      },
      sizes: [60, 40]
    });
    self.editor.codeMirror.on('change', function () {
      self.context.isSaved(false);
    })
    self.context.events.changeSettings.add(function (keyChanged, newValue) {
      if ("lang" == keyChanged) {
        self.onSaveScript();
        self.editor.codeMirror.setValue("");
        self.isJs(newValue == 'js')
        if (self.isJs()) { self.editor.setMode('Text'); }
        self.editor.setCodeLang(newValue == "js" ? "javascript" : "python")
      }
    });
  })();

  self.setModeToBlocks = function () {
    if (!self.isJs()) {
      self.editor.setMode("Blocks");
    }
  }
  self.setModeToText = function () {
    self.editor.setMode("Text");
  }
  self.setModeToSplit = function () {
    if (!self.isJs()) {
      self.editor.setMode("Split");
    }
  }
  self.onClearScript = function () {
    if (self.editor) {
      bootbox.confirm(i18n.t("scriptEditorTab.clearScriptModal.title"), function (result) {
        if (result) {
          self.editor.codeMirror.setValue("");
        }
      });
    } else {
      console.log("Cannot clear script, self.editor is not set");
    }
  };
  self.onLoadScript = function () {
    PopupCenter("/manage?load=1", "Pick a file", 600, 600);
    window.addEventListener('message', function (e) {
      e = JSON.parse(e.data);
      if (!e['dir'] || e.origin !== location.origin) return;
      self.context.fileName(e['dir']);
      self.loadScript();
    })

  }
  self.loadScript = function () {
    if (self.context.fileName()) {
      $.post("/bridge.py", {
        action: "getContent",
        item: self.context.fileName()
      }, function (e) {
        try {
          e = JSON.parse(e);
        } catch (e) {
          self.context.messageLogVM.addError(i18n.t("scriptEditorTab.errors.cantLoadScriptFile", { filename: self.context.fileName(), causedBy: "ERR_BAD_RESPONSE" }));
          return;
        }
        self.codeMirror.setValue(e.result)
        self.context.messageLogVM.addMessage(e.result.success == false ? 'danger' : 'success', i18n.t("scriptEditorTab.errors.cantLoadScriptFile", { filename: self.context.fileName(), causedBy: e.result.error }) || "Loaded file!")
        localStorage['script'] = self.context.fileName()
      });
    }
  }
  self.onSaveScript = function () {
    if (!!self.context.fileName()) {
      let val = self.editor.codeMirror.getValue();
      if (!val.startsWith("#!/usr/bin/env python3")) {
        val = "#!/usr/bin/env python3\n\r" + val;
      }
      $.post("/bridge.py", {
        action: "edit",
        content: val,
        item: self.context.fileName()
      }, function (e) {
        try {
          e = JSON.parse(e);
        } catch (e) {
          self.context.messageLogVM.addError(i18n.t("scriptEditorTab.errors.cantSaveScriptFile", { filename: self.context.fileName(), causedBy: "ERR_BAD_RESPONSE" }));
          return;
        }
        let s = e.result.success == false;
        self.context.messageLogVM.addMessage(s ? 'danger' : 'success', i18n.t(`scriptEditorTab.${s ? "errors.cantSaveScriptFile" : "scriptSuccessfullySaved"}`, { filename: self.context.fileName(), causedBy: e.result.error }))
      });
    } else {
      self.context.saveAsVM.display();
    }
    self.context.isSaved(true);
  };
}