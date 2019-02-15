class Runner {
    constructor(code, fail, success) {
        this.el = $('<iframe src="" sandbox></iframe>').appendTo('body');
        this.el.ready(() => {
            this.el.contents().find('body').append(`
            <script>
            const worker = new Worker('worker.js');
            worker.onmessage = function(e){
                parent.postMessage(e)
            }
            window.addEventListener('message',function(e){
                if(e.data.act == 'kill') worker.terminate();
                else worker.postMessage(e.data);
            });
            </script>
            `);
        });
        this.win = this.el[0].contentWindow;
        this.win.postMessage({ act: 'run', code, });
        $(window).on('message', async function (a) {
            if (a.data && a.data.act) {
                switch (a.data.act) {
                    case "err":
                        fail(a.data.msg);
                        break;
                    case "done":
                        success();
                        break;
                    case "fn":
                        if (e.data.name == 'msg') {
                            this.win.postMessage({
                                act: 'fn',
                                id: e.data.id,
                                res: await context.ev3BrickServer.message.apply(this, e.data.args)
                            });
                        } else if (e.data.name == 't') {
                            this.win.postMessage({
                                act: 'fn',
                                id: e.data.id,
                                res: i18n.t.apply(this, e.data.args)
                            });
                        }

                        break;
                }
                this.kill();
            }
        })
    }
    kill() {
        this.win.postMessage({ act: 'kill' });
        this.el.remove();
    }
}