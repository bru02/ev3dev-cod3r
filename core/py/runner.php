<!doctype html>
<title>phptty</title>
<script src="term.js"></script>
<style>
  html {
    background: #555;
  }

  h1 {
    margin-bottom: 20px;
    font: 20px/1.5 sans-serif;
  }


  .terminal {
    float: left;
    border: #000 solid 5px;
    font-family: Consolas;
    font-size: 14px;
    color: #f0f0f0;
    background: #000;
  }

  .terminal-cursor {
    color: #000;
    background: #f0f0f0;
  }

</style>
<script>
    if(window.WebSocket){
        window.addEventListener('load', function() {
            var socket = new WebSocket("ws://"+document.domain+":7778");
            socket.onopen = function() {
                var term = new Terminal({
                    cols: 130,
                    rows: 50,
                    cursorBlink: true
                });
				term.on('title', function(title) {
					document.title = title;
				});

				term.open(document.body);

				term.write('\x1b[31mWelcome to term.js!\x1b[m\r\n');
                term.open(document.body);
                term.on('data', function(data) {
                    socket.send(data);
                });
                socket.onmessage = function(data) {
                    term.write(data.data);
                };
                socket.onclose = function() {
                    term.write("Connection closed.");
					term.destroy();
                };
            };
        }, false);
    }
    else {
        alert("Browser do not support WebSocket.");
    }
</script>
