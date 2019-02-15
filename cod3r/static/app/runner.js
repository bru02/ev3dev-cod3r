class Runner {
    constructor(code, fail, success) {
        this.el = $('<iframe src="frame.html" sandbox="allow-scripts allow-same-origin"></iframe>').hide().appendTo('body');
        let self = this;
        this.cb = async (e) => {
            if (e.data && e.data.act) {
                switch (e.data.act) {
                    case "err":
                        self.kill();
                        fail(e.data.msg);
                        break;
                    case "done":
                        self.kill();
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
            }
        };
        this.el[0].onload = () => {
            this.win = this.el[0].contentWindow;
            addEventListener('message', this.cb);
            this.win.postMessage({ act: 'run', code, }, '*');
        };
    }
    kill() {
        this.win.postMessage({ act: 'kill' }, '*');
        removeEventListener('message', this.cb);
        this.el.remove();
    }
}