
onmessage = async function (e) {
    if (e.data.act == 'run') {
        (function () {
            function extFn(name) {
                function genUUID() {
                    let d = +new Date();
                    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                        let r = (d + Math.random() * 16) % 16 | 0;
                        d = Math.floor(d / 16);
                        return (c == 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                    });
                }
                return function (...args) {
                    let id = genUUID();
                    return new Promise((resolve) => {
                        addEventListener('message', function cb(e) {
                            if (e.data.id == id) {
                                removeEventListener('message', cb);
                                resolve(e.data.res);
                            }
                        });
                        postMessage({
                            act: 'fn',
                            name,
                            args,
                            id,
                        })
                    })
                }
            }
            importScripts('app/clientLib.js')
            clientLib.init(extFn('msg'), extFn('t'));
        })();
        try {
            eval(e.data.code);
            postMessage({ act: "done" });
        } catch (err) {
            postMessage({
                act: 'err',
                msg: `${err.name}: ${err.message || err.description} at line ${err.lineno || err.lineNumber || err.line}, col ${err.colno || err.columnNumber || err.column}`
            });
        }
    }
}