class SaveAsDialogViewModel {
  constructor(appContext) {

    this.context = appContext; // The application context
    this.folder = ko.observable("/");
    this.fileName = ko.observable(localStorage['script'] || false);
    this.fileName.subscribe(() => {
      localStorage['script'] = this.fileName();
    });
    this.folderDialog = undefined;
    this.onSave = () => {
      let path = this.folder() + this.fileName();
      $.post("/bridge.py", {
        action: "createFile",
        item: path
      }, (e) => {
        this.context.messageLogVM.addMessage(e.result.success ? 'success' : 'danger', e.result.error || "Saved file!");
      }, 'json');
      this.context.fileName(path);
      this.hide();
    }
    this.changeFolder = () => {
      PopupCenter("/manage?save=1", "Pick a folder", 600, 600);
      let cb = (e) => {
        if (e.origin == location.origin) {
          e = JSON.parse(e.data);
          if ('dir' in e) {
            removeEventListener('message', cb)
            this.folder(e['dir']);
          }
        }
      };
      addEventListener('message', cb);
    }
  }
  display() {
    $('#saveAsModal').modal('show');
  }
  hide() {
    $('#saveAsModal').modal('hide');
  };
}

class ScriptEditorTabViewModel {
  constructor(appContext) {
    this.context = appContext; // The application context
    let isJs = this.context.settings.lang == 'js';
    this.isJs = ko.observable(isJs);
    var mode = ko.observable("Split");
    this.editor = new DoubleEditor($('#scriptEditorTab'), { lang: isJs ? "javascript" : "python", editor: mode });
    this.split = Split([".blockpy-blocks", ".blockpy-text"], {
      cursor: 'row-resize', onDrag: () => {
        Blockly.svgResize(this.editor.blockly);
      },
      sizes: [60, 40]
    });
    this.editor.codeMirror.on('change', () => {
      this.context.isSaved(false);
    });
    if (isJs) {
      this.editor.setMode('Text');
    }
    this.context.events.changeSettings.add((keyChanged, newValue) => {
      if ("lang" == keyChanged) {
        this.onSaveScript();
        this.editor.codeMirror.setValue("");
        let isJs = newValue == 'js';
        this.isJs(isJs);
        if (isJs) {
          this.editor.setMode('Text');
        }
        this.editor.setCodeLang(isJs ? "javascript" : "python");
      }
    });
    this.onClearScript = () => {
      if (this.editor) {
        bootbox.confirm(i18n.t("scriptEditorTab.clearScriptModal.title"), (result) => {
          if (result) {
            this.editor.codeMirror.setValue("");
          }
        });
      }
      else {
        console.log("Cannot clear script, this.editor is not set");
      }
    };
    this.onLoadScript = () => {
      PopupCenter("/manage?load=1", "Pick a file", 600, 600);
      let cb = (e) => {
        if (e.origin == location.origin) {
          e = JSON.parse(e.data);
          if ('dir' in e) {
            this.context.fileName(e['dir']);
            this.loadScript();
            removeEventListener('message', cb);
          }
        }
      };
      addEventListener('message', cb);
    }
    this.onSaveScript = () => {
      if (!!this.context.fileName()) {
        let val = this.editor.codeMirror.getValue();
        if (!this.isJs() && !val.startsWith("#!/usr/bin python3")) {
          val = "#!/usr/bin python3\n\r" + val;
        }
        $.post("/bridge.py", {
          action: "edit",
          content: val,
          item: this.context.fileName()
        }, (e) => {
          let s = e.result.success == false;
          this.context.messageLogVM.addMessage(s ? 'danger' : 'success', i18n.t(`scriptEditorTab.${s ? "errors.cantSaveScriptFile" : "scriptSuccessfullySaved"}`, { filename: this.context.fileName(), causedBy: e.result.error }));
        }, 'json');
      }
      else {
        this.context.saveAsVM.display();
      }
      this.context.isSaved(true);
    }
    this.setModeToBlocks = () => {
      if (!this.isJs()) {
        this.editor.setMode("Blocks");
      }
    };
    this.setModeToText = () => {
      this.editor.setMode("Text");
    };
    this.setModeToSplit = () => {
      if (!this.isJs()) {
        this.editor.setMode("Split");
      }
    };
  }


  loadScript() {
    if (this.context.fileName()) {
      $.post("/bridge.py", {
        action: "getContent",
        item: this.context.fileName()
      }, (e) => {
        this.editor.codeMirror.setValue(e.result);
        this.context.messageLogVM.addError(i18n.t("scriptEditorTab.errors.cantLoadScriptFile", { filename: this.context.fileName(), causedBy: e.result.error }));
        localStorage['script'] = this.context.fileName();
      }, 'json');
    }
  };
}