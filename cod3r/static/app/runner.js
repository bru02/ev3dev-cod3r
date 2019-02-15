class Runner {
    constructor(code, fail, success) {
        this.el = $('<iframe src="frame.html" sandbox="allow-scripts"></iframe>').appendTo('body');
        this.el.ready(() => {
            this.win = this.el[0].contentWindow;
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
                                }, '*');
                            } else if (e.data.name == 't') {
                                this.win.postMessage({
                                    act: 'fn',
                                    id: e.data.id,
                                    res: i18n.t.apply(this, e.data.args)
                                }, '*');
                            }

                            break;
                    }
                    this.kill();
                }
            });
            this.win.postMessage({ act: 'run', code, }, '*');
        });
    }
    kill() {
        this.win.postMessage({ act: 'kill' }, '*');
        this.el.remove();
    }
}