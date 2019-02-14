class Runner {
    constructor(attrs, code, fail, success) {
        this.el = $('<iframe src="" sandbox></iframe>').appendTo('body');
        this.el.ready(() => {
            this.el.contents().find('body').append(`
            <script src="app/clientLib.js"></script>
            <script>
            window.addEventListener('message',function(e){
                clientLib.init(e.attrs);
                try {
                    eval(e.code);
                    e.sender.postMessage({status: "done"});
                } catch(err){
                    e.sender.postMessage({
                        status: 'err',
                        msg: err.name + ": "+ err.message
                    });
                }
            });

            </script>
            `);
            this.el[0].contentWindow.postMessage({ attrs, code, });
        });
        $(window).on('message', function (a) {
            if (a.data && a.data.status) {
                switch (a.data.status) {
                    case "loaded":
                        break;
                    case "err":
                        fail(a.data.msg);
                        break;
                    case "done":
                        success();
                        break;
                }
                this.kill();
            }
        })
    }
    kill() {
        this.el.remove();
    }
}