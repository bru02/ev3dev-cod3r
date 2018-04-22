<?php
if(isset($_REQUEST['run'])) {
	$f = htmlentities($_GET['run']);
	$output = shell_exec("python3 $f");
	if(!$output) $output = "Script ran successfully!";
	echo $output;
	exit();
}
if(isset($_REQUEST['stop'])) {

	$output = shell_exec("^C");

	echo "t";
	exit();
}
 function redirect($URL) {
        // echo "<style>#loader{position:absolute;left:50%;top:50%;z-index:1;margin:-75px 0 0 -75px;border:16px solid #f3f3f3;border-radius:50%;border-top:16px solid #3498db;width:120px;height:120px;-webkit-animation:spin 2s linear infinite;animation:spin 2s linear infinite}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0)}100%{-webkit-transform:rotate(360deg)}}@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}</style><div id='loader'></div>\n";
        // echo "<script type='text/javascript'>document.location.href='{$URL}';</script>";
// echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $URL . '">';
        exit();
   }
if(!isset($_GET['f'])&&!isset($_REQUEST['run'])&&!isset($_REQUEST['stop'])) {
	redirect("manage.php");
} else {
	$f = htmlentities($_GET['f']);
}
?>
<!DOCTYPE html>
<html>
<head>
<script src="../static/notcrap.js"></script>
<link rel="stylesheet" href="../static/style.css">
<title>Cod3r | Run script</title>
<style>
#console {
	    background-color: #000000;
       color:#fff;
      font-family: consolas,"courier new",monospace;
     font-size: 15px;
     height: 100%;
     width: 100%;
     border: none;
     ine-height: normal;
}
</style>
</head>
<body>
<div class="margin">
<h1>Run script</h1>
<p>File: <?php echo array_pop(explode("/",$f));?></p>
<p><button class="btn green" id="start_btn">Start</button></p>
<p><button class="btn red" id="stop_btn">Stop</button></p>
<hr>
<h2>Console:</h2>
<p><button class="btn gray" onclick="get('#console').innerHTML=''">Clear</button></p>

<pre id="console"></pre>
</div>
<script>
function log(e) {
	get("#console").innerHTML+=e;
}
get("#start_btn").addEvent("click",()=>{
	log("Starting script...");
	ajax({url:"runner.php",data:{run:"<?php echo $f; ?>"},method:"post"},(e)=>{log(e)});
});
get("#stop_btn").addEvent("click",()=>{
	log("Stopping script...");
	ajax({url:"runner.php",data:{stop:"1"},method:"post"},(e)=>{});
});
</script>
</body>
</html>
