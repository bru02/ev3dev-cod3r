/*
 * Gnikrap is a simple scripting environment for the Lego Mindstrom EV3
 * Copyright (C) 2014-2017 Jean BENECH
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
function SaveAsDialogViewModel(appContext) {
  'use strict';

  var self = this;
  (function () { // Init
    self.context = appContext; // The application context
    self.folder = ko.observable("/");
    self.fileName = ko.observable("/script.py");
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
      e = JSON.parse(e);
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
    self.context.fileName.update(path);
    self.hide();
  };
}


function ScriptEditorTabViewModel(appContext) {
  'use strict';

  var self = this;
  (function () { // Init
    self.context = appContext; // The application context
    var mode = ko.observable("Split");
    self.editor = window.dbl = new DoubleEditor($('#scriptEditorTab'), { lang: "python", editor: mode })
    window.spl = Split([".blockpy-blocks", ".blockpy-text"], {
      cursor: 'row-resize', onDrag: () => {
        Blockly.svgResize(dbl.blockly);
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
        if (newValue == "js") self.editor.setMode('text');
        self.editor.setCodeLang(newValue == "js" ? "javascript" : "python")
      }
    });
  })();
  self.isJs = function () {
    return self.context.settings.lang == 'js';
  }
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
      e = JSON.parse(e);
      self.context.fileName(e['dir']);
      self.loadScript();
    })

  }
  self.loadScript = function () {
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
  self.onSaveScript = function () {
    if (self.context.fileName) {
      let val = self.editor.codeMirror.getValue();
      if (!val.startsWith("#!/usr/bin/env python3")) {
        val = "#!/usr/bin/env python3\n\r" + val;
      }
      $.post("/bridge.py", {
        action: "edit",
        content: val,
        item: self.context.fileName
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