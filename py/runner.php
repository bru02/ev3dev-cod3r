<?php
if(isset($_GET['run'])&&isset($_GET['f'])) {
	$f = htmlentities($_GET['run']));
	$p = htmlentities($_GET['f']));

	$old_path = getcwd();
	chdir("/home/robot/$p");
	$output = shell_exec("python3 $f");
	chdir($old_path);
	echo $output;
}
if(isset($_GET['stop'])&&isset($_GET['f'])) {
	$f = htmlentities($_GET['run']));
	$p = htmlentities($_GET['f']));

	$old_path = getcwd();
	chdir("/home/robot/$p");
	$output = shell_exec("^C");
	chdir($old_path);
	echo "t";
}
?>
<!DOCTYPE html>
<html>
<head>
<script src="../static/notcrap.js"></script>
<link rel="stylesheet" href="../static/style.css">
<title>Cod3r | Run script</title>
</head>
<body>
<div class="margin">
<h1>Run script</h1>
<p><button class="btn" id="start_btn">Start</button></p>
<p><button class="btn" id="stop_btn">Stop</button></p>
<pre id="console" hidden></pre>
</div>
<script>
get("#start_btn").addEvent("click",()=>{
	
});
get("#stop_btn").addEvent("click",()=>{
	
});
</script>
</body>
</html>
