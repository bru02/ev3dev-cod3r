window.pyConv = new PythonToBlocks();
window.jsConv = {
    convertSourceToCodeBlock: function (s) {
        //return Blocklify.JavaScript.importer.codeToDom(s, 'atomic')
    },
    convertSource: function (s) {
        //return Blockly.Xml.domToText(Blocklify.JavaScript.importer.codeToDom(s, 'atomic'))
    }
}
function DoubleEditor(tag, settings) {

    this.tag = tag;

    // This tool is what actually converts text to blocks!
    this.converter = null;
    this.prevCode = "";
    // HTML DOM accessors
    this.blockTag = tag.find(".blockpy-blocks");
    this.blocklyDiv = this.blockTag.find(".blockly-div");
    this.textTag = tag.find(".blockpy-text");
    this.textSidebarTag = this.textTag.find(".blockpy-text-sidebar");

    // Blockly and CodeMirror instances
    this.blockly = null;
    this.codeMirror = null;
    // The updateStack keeps track of whether an update is percolating, to prevent duplicate update events.
    this.silenceBlock = false;
    this.silenceBlockTimer = null;
    this.silenceText = false;
    this.silenceModel = 0;
    this.blocksFailed = false;
    this.blocksFailedTimeout = null;

    // Hack to prevent chrome errors. Forces audio to load on demand.
    // See: https://github.com/google/blockly/issues/299
    Blockly.WorkspaceSvg.prototype.preloadAudio_ = function () { };

    // Keep track of the toolbox width
    this.blocklyToolboxWidth = 0;

    // Initialize subcomponents
    this.initText();
    this.initBlockly();
    this.setCodeLang(settings.lang);

    this.triggerOnChange = null;
    var editor = this,
        firstEdit = true;

    // Handle mode switching
    settings.editor.subscribe((e) => {
        editor.setMode(e)
    });
    editor.setMode(settings.editor())


    // Handle Upload mode turned on
    /*this.main.model.assignment.upload.subscribe((uploadsMode) => {
        if (uploadsMode) {
            editor.setMode('Text');
        }
    });*/

    // Have to force a manual block update
    // this.updateText();
    this.updateBlocksFromModel();
    this.updateTextFromModel();

}

/**
 * Initializes the Blockly instance (handles all the blocks). This includes
 * attaching a number of ChangeListeners that can keep the internal code
 * representation updated and enforce type checking.
 */
DoubleEditor.prototype.initBlockly = function () {

    this.blockly = Blockly.inject(this.blocklyDiv[0],
        {
            "path": "static/lib/blockly-20160417/",
            "scrollbars": true,
            "readOnly": false,
            "zoom": { "enabled": false },
            "oneBasedIndex": false,
            "comments": false,
            "toolbox": this.updateToolbox(false)
        });
    // Register model changer
    var editor = this;
    this.blockly.addChangeListener((evt) => {
        //editor.main.components.feedback.clearEditorErrors();
        editor.blockly.highlightBlock(null);
        editor.updateBlocks();
    });

    // Force the proper window size
    this.blockly.resize();
    // Keep the toolbox width set
    this.blocklyToolboxWidth = this.getToolbarWidth();

    // Enable static type checking!
    /*
    this.blockly.addChangeListener(function() {
        if (!editor.main.model.settings.disable_variable_types()) {
            var variables = editor.main.components.engine.analyzeVariables()
            editor.blockly.getAllBlocks().filter(function(r) {return r.type == 'variables_get'}).forEach(function(block) {
                var name = block.inputList[0].fieldRow[0].value_;
                if (name in variables) {
                    var type = variables[name];

                    if (type.type == "Num") {
                        block.setOutput(true, "Number");
                    } else if (type.type == "List") {
                        block.setOutput(true, "Array");
                    } else if (type.type == "Str") {
                        block.setOutput(true, "String");
                    } else {
                        block.setOutput(true, null);
                    }
                }
            })
        }
    });
    */


};

/**
 * Retrieves the current width of the Blockly Toolbox, unless
 * we're in read-only mode (when there is no toolbox).
 * @returns {Number} The current width of the toolbox.
 */
DoubleEditor.prototype.getToolbarWidth = function () {
    return (this.blockly.toolbox_ && this.blockly.toolbox_.width) || 0;

};
DoubleEditor.prototype.setCode = function (code) {
    var pc = this.prevCode;
    this.prevCode = code;
    return pc !== code;

};
/**
 * Initializes the CodeMirror instance. This handles text editing (with syntax highlighting)
 * and also attaches a listener for change events to update the internal code represntation.
 */
DoubleEditor.prototype.initText = function () {
    var codeMirrorDiv = this.textTag.find('textarea')[0];
    this.codeMirror = CodeMirror.fromTextArea(codeMirrorDiv, {
        mode: {
            name: "python",
            version: 3,
            singleLineStringErrors: false
        },
        showCursorWhenSelecting: true,
        lineNumbers: true,
        firstLineNumber: 1,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        matchBrackets: true,
        extraKeys: {
            "Tab": "indentMore",
            "Shift-Tab": "indentLess"
        },
    });
    // Register model changer
    var editor = this;
    this.codeMirror.on("change", function () {
        //editor.main.components.feedback.clearEditorErrors();
        editor.updateText()
        editor.unhighlightLines();
    });

    // Ensure that it fills the editor area
    this.codeMirror.setSize(null, "100%");

};


/**
 * Makes the module available in the availableModules multi-select menu by adding
 * it to the list.
 *
 * @param {String} name - The name of the module (human-friendly version, as opposed to the slug) to be added.
 */
DoubleEditor.prototype.addAvailableModule = function (name) {

    this.availableModules.multiSelect("addOption", {
        "value": name,
        "text": name
    });
    this.availableModules.multiSelect("select", name);

};
DoubleEditor.prototype.setCodeLang = function (lang) {
    this.converter = lang == "python" ? window.pyConv : window.jsConv;
    let opts;
    if (lang == "python") {
        this.updateToolbox(true);
        opts = {
            mode: {
                name: "python",
                version: 3,
                singleLineStringErrors: false
            },
            showCursorWhenSelecting: true,
            lineNumbers: true,
            firstLineNumber: 1,
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false,
            matchBrackets: true,
            extraKeys: {
                "Tab": "indentMore",
                "Shift-Tab": "indentLess"
            },
        }

    } else {
        opts = {
            mode: { name: "javascript", globalVars: true },
            gutters: ["CodeMirror-lint-markers"],
            lint: true,
            showCursorWhenSelecting: true,
            lineNumbers: true,
            firstLineNumber: 1,
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false,
            matchBrackets: true,
            extraKeys: {
                "Tab": "indentMore",
                "Shift-Tab": "indentLess",
                "Ctrl-Space": "autocomplete"
            },
        };

    }
    for (let [key, val] of Object.entries(opts)) {
        this.codeMirror.setOption(key, val)
    }
};

/**
 * Hides the Text tab, which involves shrinking it and hiding its CodeMirror too.
 */
DoubleEditor.prototype.hideSplitMenu = function () {

    this.hideTextMenu();
    this.hideBlockMenu();

};

/**
 * Shows the Text tab, which requires restoring its height, showing AND refreshing
 * the CodeMirror instance.
 */
DoubleEditor.prototype.showSplitMenu = function () {
    this.showBlockMenu();
    this.showTextMenu();

    this.textTag.css("width", "calc(40% - 5px)");
    this.blockTag.css("width", "calc(60% - 5px)");
    this.textSidebarTag.css("width", "0px");
    this.blocklyDiv.css("width", "");
    this.textTag.addClass("col-md-6");
    this.blockTag.addClass("col-md-6");
    Blockly.svgResize(this.blockly);
};

/**
 * Hides the Text tab, which involves shrinking it and hiding its CodeMirror too.
 */
DoubleEditor.prototype.hideTextMenu = function () {

    this.textTag.css("height", "0%");
    $(this.codeMirror.container).hide();
    this.textSidebarTag.hide();
    this.textTag.hide();

};

/**
 * Shows the Text tab, which requires restoring its height, showing AND refreshing
 * the CodeMirror instance.
 */
DoubleEditor.prototype.showTextMenu = function () {

    this.textTag.show();
    // Adjust height
    this.textTag.css("height", "450px");
    this.textTag.css("width", "100%");
    // Show CodeMirror
    $(this.codeMirror.container).show();
    // CodeMirror doesn't know its changed size
    this.codeMirror.refresh();

    // Resize sidebar
    let codemirrorGutterWidth = $(".ace-gutters").width(),
        sideBarWidth = this.blocklyToolboxWidth - codemirrorGutterWidth - 2;
    this.textSidebarTag.css("width", `${sideBarWidth}px`);
    this.textSidebarTag.show();
    this.textTag.removeClass("col-md-6");

};

/**
 * Hides the Block tab, which involves shrinking it and hiding the Blockly instance.
 */
DoubleEditor.prototype.hideBlockMenu = function () {

    this.blocklyToolboxWidth = this.getToolbarWidth();
    this.blockTag.css("height", "0px").css("width", "0px");
    this.blocklyDiv.css("width", "0px");
    this.blockly.setVisible(false);

};

/**
 * Shows the Block tab, which involves restoring its height and showing the Blockly instance.
 */
DoubleEditor.prototype.showBlockMenu = function () {

    this.blockTag.css("height", "100%");
    this.blockTag.css("width", "100%");
    this.blocklyDiv.css("width", "100%");
    this.blockly.resize();
    this.blockly.setVisible(true);
    this.blockTag.removeClass("col-md-6");
    Blockly.svgResize(this.blockly);

};
/**
 * Sets the current editor mode to Text, hiding the other menus.
 * Also forces the text side to update.
 */
DoubleEditor.prototype.setModeToText = function () {

    this.hideBlockMenu();
    this.showTextMenu();
    // Update the text model from the blocks

};

/**
 * Sets the current editor mode to Blocks, hiding the other menus.
 * Also forces the block side to update.
 * There is a chance this could fail, if the text side is irredeemably
 * awful. So then the editor bounces back to the text side.
 */
DoubleEditor.prototype.setModeToBlocks = function () {

    this.hideTextMenu();
    this.showBlockMenu();
    if (this.blocksFailed !== false) {

        this.showConversionError();
        /*let main = this.main;
        main.model.settings.editor("Text");
        setTimeout(() => {
            main.components.toolbar.tags.mode_set_text.click();
        }, 0);*/

    }
    // Update the blocks model from the text
    /*
    success = this.updateBlocksFromModel();
    if (!success) {
        var main = this.main;
        main.components.editor.updateTextFromModel();
        main.model.settings.editor("Text");
        setTimeout(function() {
            main.components.toolbar.tags.mode_set_text.click();
        }, 0);
    }*/

};

/**
 * Sets the current editor mode to Split mode, hiding the other menus.
 */
DoubleEditor.prototype.setModeToSplit = function () {

    this.hideTextMenu();
    this.hideBlockMenu();
    this.showSplitMenu();
    if (this.blocksFailed !== false) {

        this.showConversionError();

    }

};




/**
 * Dispatch method to set the mode to the given argument.
 * If the mode is invalid, an editor error is reported. If the
 *
 * @param {String} mode - The new mode to set to ("Blocks", "Text", or "Instructor")
 */
DoubleEditor.prototype.setMode = function (mode) {

    // Dispatch according to new mode
    if (mode == "Blocks") {

        this.setModeToBlocks();

    } else if (mode == "Text") {

        this.setModeToText();

    } else if (mode == "Split") {

        this.setModeToSplit();

    }

};

/**
 * Actually changes the value of the CodeMirror instance
 *
 * @param {String} code - The new code for the CodeMirror
 */
DoubleEditor.prototype.setText = function (code) {

    if (code == undefined || code.trim() == "") {

        this.codeMirror.setValue("\n");

    } else {

        this.codeMirror.setValue(code);

    }
    // Ensure that we maintain proper highlighting
    this.refreshHighlight();

};

DoubleEditor.prototype.showConversionError = function () {

    let error = this.blocksFailed;
    //this.main.components.feedback.convertSkulptSyntax(error);
    try {
        var convertedError = Sk.ffi.remapToJs(error.args);
        console.log(convertedError);
        var codeLine = '.';
        if (convertedError.length > 3 && convertedError[4]) {
            codeLine = ', where it says:<br><code>' + convertedError[4] + '</code>';
        }
        this.editorError(error, "Syntacs error at line " + convertedError[2] + codeLine, convertedError[2]);
    } catch (e) {
        console.error(e);
    }

};
DoubleEditor.prototype.editorError = function (original, message, line) {
    original = this.prettyPrintError(original);
    //this.title.html("Editor Error");
    //this.original.show().html(original);
    //this.body.html(message);
    context.messageLogVM.addError("Python Error: " + original + "\n" + message, 1);
    this.highlightError(line - 1);
}
DoubleEditor.prototype.prettyPrintError = function (error) {
    if (typeof error === "string") {
        return error;
    } else {
        // A weird skulpt thing?
        console.error(error);
        if (error.tp$str !== undefined) {
            return error.tp$str().v;
        } else {
            return "" + error.name + ": " + error.message;
        }
    }
}
DoubleEditor.prototype.setBlocks = function (python_code) {

    let xml_code = "";
    if (python_code !== "" && python_code !== undefined && python_code.trim().charAt(0) !== "<") {

        let result = this.converter.convertSource(python_code);
        xml_code = result.xml;
        window.clearTimeout(this.blocksFailedTimeout);
        if (result.error !== null) {

            this.blocksFailed = result.error;
            let editor = this;
            this.blocksFailedTimeout = window.setTimeout(() => {

                editor.showConversionError();

            }, 500);

        } else {

            this.blocksFailed = false;
            //this.main.components.feedback.clearEditorErrors();

        }

    }
    let error_code = this.converter.convertSourceToCodeBlock(python_code),
        errorXml = Blockly.Xml.textToDom(error_code);
    if (python_code == "" || python_code == undefined || python_code.trim() == "") {

        this.blockly.clear();

    } else if (xml_code !== "" && xml_code !== undefined) {

        let blocklyXml = Blockly.Xml.textToDom(xml_code);
        try {

            this.setBlocksFromXml(blocklyXml);

        } catch (e) {

            console.error(e);
            this.setBlocksFromXml(errorXml);

        }

    } else {

        this.setBlocksFromXml(errorXml);

    }
    Blockly.Events.disable();
    this.blockly.align();
    Blockly.Events.enable();
    if (this.previousLine !== null) {

        this.refreshBlockHighlight(this.previousLine);

    }

};

DoubleEditor.prototype.clearDeadBlocks = function () {

    let all_blocks = this.blockly.getAllBlocks();
    all_blocks.forEach((elem) => {
        if (!Blockly.Python[elem.type]) {
            elem.dispose(true);
        }
    });

};

/**
 * Attempts to update the model for the current code file from the
 * block workspace. Might be prevented if an update event was already
 * percolating.
 */
DoubleEditor.prototype.updateBlocks = function () {

    if (!this.silenceBlock) {

        try {

            var newCode = Blockly.Python.workspaceToCode(this.blockly);

        } catch (e) {

            this.clearDeadBlocks();

        }
        // Update Model
        this.silenceModel = 2;
        let changed = this.setCode(newCode);  //this.main.setCode(newCode);
        if (!changed) {

            this.silenceModel = 0;

        } else {

            // Update Text
            this.silenceText = true;
            this.setText(newCode);

        }

    }

};

/**
 * Attempts to update the model for the current code file from the
 * text editor. Might be prevented if an update event was already
 * percolating. Also unhighlights any lines.
 */
var timerGuard = null;
DoubleEditor.prototype.updateText = function () {

    if (!(this.silenceText || this.converter == window.jsConv)) {

        let newCode = this.codeMirror.getValue();
        // Update Model
        this.silenceModel = 2;
        this.setCode(newCode);
        // Update Blocks
        this.silenceBlock = true;
        this.setBlocks(newCode);
        this.unhighlightLines();
        this.resetBlockSilence();

    }
    this.silenceText = false;

};

/**
 * Resets the silenceBlock after a short delay
 */
DoubleEditor.prototype.resetBlockSilence = function () {

    let editor = this;
    if (editor.silenceBlockTimer != null) {

        clearTimeout(editor.silenceBlockTimer);

    }
    this.silenceBlockTimer = window.setTimeout(() => {
        editor.silenceBlock = false;
        editor.silenceBlockTimer = null;
    }, 40);

};

/**
 * Updates the text editor from the current code file in the
 * model. Might be prevented if an update event was already
 * percolating.
 */
DoubleEditor.prototype.updateTextFromModel = function () {

    if (this.silenceModel == 0) {

        let code = "a=1"//this.main.model.program();
        this.silenceText = true;
        this.setText(code);

    } else {

        this.silenceModel -= 1;

    }

};

/**
 * Updates the block editor from the current code file in the
 * model. Might be prevented if an update event was already
 * percolating. This can also report an error if one occurs.
 *
 * @returns {Boolean} Returns true upon success.
 */
DoubleEditor.prototype.updateBlocksFromModel = function () {

    if (this.silenceModel == 0) {

        let code = ""; // TODOTODOTODOTODOTODOTODOTODOTODOTODOTODOTODOTODO
        this.silenceBlock = true;
        this.setBlocks(code);
        this.resetBlockSilence();

    } else {

        this.silenceModel -= 1;

    }

};

/**
 * Helper function for retrieving the current Blockly workspace as
 * an XML DOM object.
 *
 * @returns {XMLDom} The blocks in the current workspace.
 */
DoubleEditor.prototype.getBlocksFromXml = function () {

    return Blockly.Xml.workspaceToDom(this.blockly);

};

/**
 * Helper function for setting the current Blockly workspace to
 * whatever XML DOM is given. This clears out any existing blocks.
 */
DoubleEditor.prototype.setBlocksFromXml = function (xml) {

    // this.blockly.clear();
    Blockly.Xml.domToWorkspaceDestructive(xml, this.blockly);
    // console.log(this.blockly.getAllBlocks());

};

/**
 * @property {Number} previousLine - Keeps track of the previously highlighted line.
 */
DoubleEditor.prototype.previousLine = null;
DoubleEditor.prototype._error = null;
DoubleEditor.prototype._active = null;

/**
 * Assuming that a line has been highlighted previously, this will set the
 * line to be highlighted again. Used when we need to restore a highlight.
 */
DoubleEditor.prototype.refreshHighlight = function () {

    if (this.previousLine !== null) {
        if (this.previousLine < this.codeMirror.lineCount()) {
            this.codeMirror.addLineClass(this.previousLine, 'text', 'editor-error-line');
        }
    }
    // TODO: Shouldn't this refresh the highlight in the block side too?

};

/**
 * Highlights a line of code in the CodeMirror instance. This applies the "active" style
 * which is meant to bring attention to a line, but not suggest it is wrong.
 *
 * @param {Number} line - The line of code to highlight. I think this is zero indexed?
 */
DoubleEditor.prototype.highlightLine = function (line) {

    if (this.previousLine !== null) {
        if (this.previousLine < this.codeMirror.lineCount()) {
            this.codeMirror.removeLineClass(this.previousLine, 'text', 'editor-active-line');
            this.codeMirror.removeLineClass(this.previousLine, 'text', 'editor-error-line');
        }
    }
    if (line < this.codeMirror.lineCount()) {
        this.codeMirror.addLineClass(line, 'text', 'editor-active-line');
    }
    this.previousLine = line;

};

/**
 * Highlights a line of code in the CodeMirror instance. This applies the "error" style
 * which is meant to suggest that a line is wrong.
 *
 * @param {Number} line - The line of code to highlight. I think this is zero indexed?
 */
DoubleEditor.prototype.highlightError = function (line) {

    if (this.previousLine !== null) {
        if (this.previousLine < this.codeMirror.lineCount()) {
            this.codeMirror.removeLineClass(this.previousLine, 'text', 'editor-active-line');
            this.codeMirror.removeLineClass(this.previousLine, 'text', 'editor-error-line');
        }
    }
    if (line < this.codeMirror.lineCount()) {
        this.codeMirror.addLineClass(line, 'text', 'editor-error-line');
    }
    this.refreshBlockHighlight(line);
    this.previousLine = line;

};

/**
 * Highlights a block in Blockly. Unfortunately, this is the same as selecting it.
 *
 * @param {Number} block - The ID of the block object to highlight.
 */
DoubleEditor.prototype.highlightBlock = function (block) {
    // this.blockly.highlightBlock(block);
};

/**
 * Used to restore a block's highlight when travelling from the code tab. This
 * uses a mapping between the blocks and text that is generated from the parser.
 * The parser has stored the relevant line numbers for each block in the XML of the
 * block. Very sophisticated, and sadly fairly fragile.
 * TODO: I believe there's some kind of off-by-one error here...
 *
 * @param {Number} line - The line of code to highlight. I think this is zero indexed?
 */
DoubleEditor.prototype.refreshBlockHighlight = function (line) {

    if (this.blocksFailed) {

        this.blocksFailed = false;
        return;

    }
    /*if (this.main.model.settings.editor() != "Blocks" &&
        this.main.model.settings.editor() != "Split") {

        return;

    }*/
    let all_blocks = this.blockly.getAllBlocks(),
        //console.log(all_blocks.map(function(e) { return e.lineNumber }));
        blockMap = {};
    all_blocks.forEach((elem) => {
        var lineNumber = parseInt(elem.lineNumber, 10);
        if (lineNumber in blockMap) {
            blockMap[lineNumber].push(elem);
        } else {
            blockMap[lineNumber] = [elem];
        }
    });
    if (1 + line in blockMap) {

        let hblocks = blockMap[1 + line],
            blockly = this.blockly;
        hblocks.forEach((elem) => {
            //elem.addSelect();
            blockly.highlightBlock(elem.id, true);
        });
        /* if (hblocks.length > 0) {
            this.blockly.highlightBlock(hblocks[0].id, true);
        }*/

    }

};


/**
 * Removes any highlight in the text code editor.
 *
 */
DoubleEditor.prototype.unhighlightLines = function () {

    if (this.previousLine !== null) {
        if (this.previousLine < this.codeMirror.lineCount()) {
            this.codeMirror.removeLineClass(this.previousLine, 'text', 'editor-active-line');
            this.codeMirror.removeLineClass(this.previousLine, 'text', 'editor-error-line');
        }
    }
    this.previousLine = null;

};

/**
 * Removes any highlight in the text code editor.
 *
 */
DoubleEditor.prototype.unhighlightAllLines = function () {
    var editor = this.codeMirror;
    var count = editor.lineCount(), i;
    for (i = 0; i < count; i++) {
        editor.removeLineClass(i, 'text', 'editor-error-line');
    }
};

/**
 * Updates the current file being edited in the editors.
 * This appears to be deprecated.
 *
 * @param {String} name - The name of the file being edited (e.g, "__main__", "starting_code")
 */
/*
DoubleEditor.prototype.changeProgram = function(name) {
    console.log("TEST")
    this.silentChange_ = true;
    if (name == 'give_feedback') {
        this.setMode('Text');
    }
    this.model.settings.filename = name;
    this.editor.setPython(this.model.programs[name]);
    this.toolbar.elements.programs.find("[data-name="+name+"]").click();
}*/

/**
 * Maps short category names in the toolbox to the full XML used to
 * represent that category as usual. This is kind of a clunky mechanism
 * for managing the different categories, and doesn't allow us to specify
 * individual blocks.
 */
DoubleEditor.CATEGORY_MAP = {
    "Variables": "<category name=\"Variables\" custom=\"VARIABLE\" colour=\"240\">" +
        "</category>",
    "Decisions": "<category name=\"Decisions\" colour=\"330\">" +
        "<block type=\"controls_if_better\"></block>" +
        "<block type=\"controls_if_better\"><mutation else=\"1\"></mutation></block>" +
        // '<block type="controls_if"></block>'+
        // '<block type="controls_if"><mutation else="1"></mutation></block>'+
        "<block type=\"logic_compare\"></block>" +
        "<block type=\"logic_operation\"></block>" +
        "<block type=\"logic_negate\"></block>" +
        "</category>",
    "Iteration": "<category name=\"Iteration\" colour=\"300\">" +
        "<block type=\"controls_forEach\"></block>" +
        "</category>",
    "Functions": "<category name=\"Functions\" custom=\"PROCEDURE\" colour=\"210\">" +
        "</category>",
    "Classes": "<category name=\"Classes\" colour=\"210\">" +
        "<block type=\"class_creation\"></block>" +
        "<block type=\"class_creation\">" +
        "<mutation value=\"k\"></mutation>" +
        "</block>" +
        "</category>",
    "Calculation": "<category name=\"Calculation\" colour=\"270\">" +
        // '<block type="raw_table"></block>'+
        "<block type=\"math_arithmetic\"></block>" +
        // '<block type="type_check"></block>'+
        // '<block type="raw_empty"></block>'+
        // '<block type="math_single"></block>'+
        // '<block type="math_number_property"></block>'+
        "<block type=\"math_round\"></block>" +
        // '<block type="text_join"></block>'+
        "</category>",
    "Python": "<category name=\"Python\" colour=\"180\">" +
        "<block type=\"raw_block\"></block>" +
        "<block type=\"raw_expression\"></block>" +
        // '<block type="function_call"></block>'+
        "</category>",
    "Output": "<category name=\"Output\" colour=\"160\">" +
        "<block type=\"text_print\"></block>" +
        // '<block type="text_print_multiple"></block>'+
        "<block type=\"plot_line\"></block>" +
        "<block type=\"plot_scatter\"></block>" +
        "<block type=\"plot_hist\"></block>" +
        "<block type=\"plot_show\"></block>" +
        "<block type=\"plot_title\"></block>" +
        "<block type=\"plot_xlabel\"></block>" +
        "<block type=\"plot_ylabel\"></block>" +
        "</category>",
    "Turtles": "<category name=\"Turtles\" colour=\"180\">" +
        "<block type=\"turtle_create\"></block>" +
        "<block type=\"turtle_forward\"></block>" +
        "<block type=\"turtle_backward\"></block>" +
        "<block type=\"turtle_left\"></block>" +
        "<block type=\"turtle_right\"></block>" +
        "<block type=\"turtle_color\"></block>" +
        "</category>",
    "Values": "<category name=\"Values\" colour=\"100\">" +
        "<block type=\"text\"></block>" +
        "<block type=\"math_number\"></block>" +
        "<block type=\"logic_boolean\"></block>" +
        "</category>",
    "Tuples": "<category name=\"Tuples\" colour=\"40\">" +
        "<block type=\"tuple_create\"></block>" +
        "</category>",
    "Lists": "<category name=\"Lists\" colour=\"30\">" +
        // '<block type="lists_create"></block>'+
        "<block type=\"lists_create_with\">" +
        "<value name=\"ADD0\">" +
        "<block type=\"math_number\"><field name=\"NUM\">0</field></block>" +
        "</value>" +
        "<value name=\"ADD1\">" +
        "<block type=\"math_number\"><field name=\"NUM\">0</field></block>" +
        "</value>" +
        "<value name=\"ADD2\">" +
        "<block type=\"math_number\"><field name=\"NUM\">0</field></block>" +
        "</value>" +
        "</block>" +
        "<block type=\"lists_create_with\"></block>" +
        "<block type=\"lists_create_empty\"></block>" +
        "<block type=\"lists_append\"></block>" +
        /* '<block type="lists_length"></block>'+*/
        /* '<block type="lists_index">'+
            '<value name="ITEM">'+
              '<shadow type="math_number">'+
                '<field name="NUM">0</field>'+
              '</shadow>'+
            '</value>'+
        '</block>'+*/
        "</category>",
    "Dictionaries": "<category name=\"Dictionaries\" colour=\"0\">" +
        "<block type=\"dicts_create_with\"></block>" +
        "<block type=\"dict_get_literal\"></block>" +
        // '<block type="dict_keys"></block>'+
        "</category>",
    /*
    'Data - Weather': '<category name="Data - Weather" colour="70">'+
                    '<block type="weather_temperature"></block>'+
                    '<block type="weather_report"></block>'+
                    '<block type="weather_forecasts"></block>'+
                    '<block type="weather_report_forecasts"></block>'+
                    '<block type="weather_all_forecasts"></block>'+
                    '<block type="weather_highs_lows"></block>'+
                '</category>',
    'Data - Stocks': '<category name="Data - Stock" colour="65">'+
                    '<block type="stocks_current"></block>'+
                    '<block type="stocks_past"></block>'+
                '</category>',
    'Data - Earthquakes': '<category name="Data - Earthquakes" colour="60">'+
                    '<block type="earthquake_get"></block>'+
                    '<block type="earthquake_both"></block>'+
                    '<block type="earthquake_all"></block>'+
                '</category>',
    'Data - Crime': '<category name="Data - Crime" colour="55">'+
                    '<block type="crime_state"></block>'+
                    '<block type="crime_year"></block>'+
                    '<block type="crime_all"></block>'+
                '</category>',
    'Data - Books': '<category name="Data - Books" colour="50">'+
                    '<block type="books_get"></block>'+
                '</category>',*/
    "Data - Parking": "<category name=\"Data - Parking\" colour=\"45\">" +
        "<block type=\"datetime_day\"></block>" +
        "<block type=\"datetime_time\"></block>" +
        "<block type=\"logic_compare\">" +
        "<field name=\"OP\">EQ</field>" +
        "<value name=\"A\">" +
        "<block type=\"datetime_time\">" +
        "<mutation isNow=\"1\"></mutation>" +
        "<field name=\"HOUR\">1</field>" +
        "<field name=\"MINUTE\">00</field>" +
        "<field name=\"MERIDIAN\">PM</field>" +
        "</block>" +
        "</value>" +
        "</block>" +
        "<block type=\"logic_compare\">" +
        "<field name=\"OP\">EQ</field>" +
        "<value name=\"A\">" +
        "<block type=\"datetime_day\">" +
        "<field name=\"DAY\">Monday</field>" +
        "</block>" +
        "</value>" +
        "</block>" +
        // '<block type="datetime_check_day"></block>'+
        // '<block type="datetime_check_time"></block>'+
        "</category>",
    "Separator": "<sep></sep>"
};

/**
 * Creates an updated representation of the Toolboxes XML as currently specified in the
 * model, using whatever modules have been added or removed. This method can either set it
 * or just retrieve it for future use.
 *
 * @param {Boolean} only_set - Whether to return the XML string or to actually set the XML. False means that it will not update the toolbox!
 * @returns {String?} Possibly returns the XML of the toolbox as a string.
 */
DoubleEditor.prototype.updateToolbox = function (only_set) {

    var xml = "<xml id=\"toolbox\" style=\"display: none\">",
        modules = ["Calculation", "Output", "Values"],
        started_misc = false,
        started_values = false,
        started_data = false;
    for (let i = 0, length = modules.length; i < length; i += 1) {

        let module = modules[i];
        if (!started_misc && ["Calculation", "Output", "Python"].indexOf(module) != -1) {

            started_misc = true;
            xml += DoubleEditor.CATEGORY_MAP.Separator;

        }
        if (!started_values && ["Values", "Lists", "Dictionaries"].indexOf(module) != -1) {

            started_values = true;
            xml += DoubleEditor.CATEGORY_MAP.Separator;

        }
        if (!started_data && module.slice(0, 6) == "Data -") {

            started_data = true;
            xml += DoubleEditor.CATEGORY_MAP.Separator;

        }
        if (typeof module === "string") {

            xml += DoubleEditor.CATEGORY_MAP[module];

        } else {

            var category = "<category name=\"" + module.name + "\" colour=\"" + module.color + "\">";
            for (let j = 0; category_length = module.blocks.length; j += 1) {

                let block = module.blocks[j];
                category += "<block type=\"" + block + "\"></block>";

            }
            category += "</category>";

        }
        // '<sep></sep>'+

    }
    xml += "</xml>";
    if (only_set) {

        this.blockly.updateToolbox(xml);
        this.blockly.resize();

    } else {

        return xml;

    }

};

DoubleEditor.prototype.DOCTYPE = "<?xml version=\"1.0\" standalone=\"no\"?>" + "<" + "!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\">";
DoubleEditor.prototype.cssData = null;
DoubleEditor.prototype.loadCss = function () {

    if (this.cssData == null) {

        let txt = ".blocklyDraggable {}\n";
        txt += Blockly.Css.CONTENT.join("\n");
        if (Blockly.FieldDate) {

            txt += Blockly.FieldDate.CSS.join("\n");

        }
        // Strip off any trailing slash (either Unix or Windows).
        this.cssData = txt.replace(/<<<PATH>>>/g, Blockly.Css.mediaPath_);

    }

};