<!DOCTYPE html>
<html>
<head>
<title>EBI Sites</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="sites.css" />
<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
	google.load("jquery", "1.3.2");

	window.onresize = resize;
	window.onload = resize;
	
	function resize() {
    	$("#stylized").height($(window).height() - 5);
    	$("#output").height($(window).height() - 1);
    	$("#output").width($(window).width() - $('#stylized').width() - 5);
		}

    function validate() {
        $("#error").html("");
    }
        
	function prevStep() {
		$("#formprev").submit();
		}

	function nextStep() {
		console.log($("#formnext"));
		$("#formnext").submit();
	}
</script>
</head>
<body>
<div id="wrap">
	<div id="stylized">
		<form id="formprev" method="POST" action="history.php">
		</form>
		<form id="formnext" method="POST" action="selectsite.php">
			<h1>Introduction</h1>
			<p>Below you will find the buttons to step through the
			workflow creation process.</p>

			<label>Workflow</label>
			<span id="error" class="small">&nbsp;</span>
			<input id="prev" type="button" value="History" onclick="prevStep();" />
			<input id="next" type="button" value="Next" onclick="nextStep();" />
			
			<div class="spacer"></div>
			</form>
	</div>
	<div id="output">
		<h1>Introduction</h1>
		<p>The following pages will guide you through setting up a
		PEcAn worlflow. You will be able to always go back to a
		previous step to change inputs. However once the model is
		runnign it will continue to run until it finishes. You will
		be able to use the history button to jump to existing 
		executions of PEcAn.</p>
		<p>The following webpages will help to setup the PEcAn
		workflow. You will be asked the following questions:</p>
		<ol>
		<li><b>Host and Model</b> You will first select the host to
		run the workflow on as well as the model to be exectuted.</li>
		<li><b>Site</b> The next step is to select the site where
		the model should be run for.</li>
		<li><b>Model Parameters</b> Based on the site some final
		parameters for the model will need to be selected.</li>
		<li><b>Model Execution</b> Once all variables are selected
		PEcAn will execute the workflow.</li>
		<li><b>Results</b> After execution of the PEcAn workflow you
		will be presented with a page showing the results of the
		PEcAn workflow.</li> 
		</ol>
	</div>
</div>
</body>
</html>