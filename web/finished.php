<?php

// runid
if (!isset($_REQUEST['workflowid'])) {
  die("Need a workflowid.");
}
$workflowid=$_REQUEST['workflowid'];

// database parameters
require("dbinfo.php");
$connection=open_database();

// get run information
$query = "SELECT * FROM workflows WHERE workflows.id=$workflowid";
$result = mysql_query($query);
if (!$result) {
	die('Invalid query: ' . mysql_error());
}
$workflow = mysql_fetch_assoc($result);
$start = substr($workflow['start_date'], 0, 4);
$end = substr($workflow['end_date'], 0, 4);
$folder = $workflow['folder'];

# check to make sure all is ok
$error=false;
$status=file($folder . DIRECTORY_SEPARATOR . "STATUS");
if ($status === FALSE) {
	$status = array();
	$error = true;
}
foreach ($status as $line) {
	$data = explode("\t", $line);
	if ((count($data) >= 4) && ($data[3] == 'ERROR')) {
		$error = true;
	}
}

$years="";
for($year=$start; $year<=$end; $year++) {
	$years .= "<option>$year</option>";
}

$vars  = "";
if ($error === false) {
	$vars .= "<option>Reco</option>\n";
	$vars .= "<option>NPP</option>\n";
	$vars .= "<option>NEE</option>\n";
}

$logs="";
$logs .= createOption("workflow.Rout");

$outputs  = "";
$outputs .= "<option>pecan.xml</option>";
$outputs .= "<option>workflow.R</option>";

# check the run output folder
foreach(scandir("$folder/run") as $file) {
	if (substr($file, 0, 5) === "c.ENS") {
		$outputs .= createOption("run/${file}");
		$outputs .= createOption("run/ED2IN${file}");
		$logs .= createOption("run/ED2IN${file}.log");
	}
}

# check the out folder
foreach(scandir("$folder/out") as $file) {
	if ($file[0] == ".") {
		continue;
	}
	$outputs .= createOption("out/$file");
	if (preg_match("/.*-T-${year}-00-00-000000-g01.h5/", $file)) {
		$vars .= shell_exec("h5ls $folder/out/$file | awk '{print \"<option>\" $1 \"</option>\" }'");
	}
}

# check the pft folder
foreach(scandir("$folder/pft") as $pft) {
	if ($file[0] == ".") {
		continue;
	}
	foreach(scandir("$folder/pft/${pft}") as $file) {
		if (preg_match("/^ma.summaryplots./", $file)) {
			$logs .= "<option>pft/${pft}/${file}</option>\n";
		}
		if ($file == "meta-analysis.log") {
			$logs .= "<option>pft/${pft}/${file}</option>\n";
		}
	}
}

function createOption($file) {
	$name = basename($file);
	return "<option value=\"$file\">$name</option>\n";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>EBI Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="sites.css" />
<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
	google.load("jquery", "1.3.2");

	function resize() {
    	$("#stylized").height($(window).height() - 5);
    	$("#output").height($(window).height() - 1);
    	$("#output").width($(window).width() - $('#stylized').width() - 5);
	} 

	function prevStep() {
		$("#formprev").submit();
	}

	function nextStep() {
		$("#formnext").submit();
	}
	
    function showPlot() {
		var url="dataset.php?workflowid=<?=$workflowid?>&type=plot&year=" + $('#year')[0].value + "&var=" + $('#var')[0].value + "&width=" + ($("#output").width()-10) + "&height=" + ($("#output").height() - 10);
		$("#output").html("<img src=\"" + url + "\">");
	}
	
	function showLog() {
		var url="dataset.php?workflowid=<?=$workflowid?>&type=file&name=" + $('#log')[0].value;
		jQuery.get(url, {}, setOuput);
	}

	function show(name) {
		var url="dataset.php?workflowid=<?=$workflowid?>&type=file&name=" + name;
		if (endsWith(url, ".xml")) {
			jQuery.get(url, {}, function(data) {
				setOuput((new XMLSerializer()).serializeToString(data));
			});
		} else if (endsWith(url, ".R") || endsWith(url, ".pavi") || endsWith(url, ".log")) {
			jQuery.get(url, {}, setOuput);
		} else if (url.indexOf("c.ENS") != -1) {
			jQuery.get(url, {}, setOuput);
		} else if (url.indexOf("ED2IN.template") != -1) {
			jQuery.get(url, {}, setOuput);
		} else {
			window.location = url;
		}
	}

	function setOuput(data) {
		data = data.replace(/&/g, "&amp;").replace(/</g,"&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;");
		$("#output").html("<pre>" + data + "</pre>");
	}

	function endsWith(haystack, needle) {
		return (haystack.substr(haystack.length - needle.length) === needle);
	}

	function startsWith(haystack, needle) {
		return (haystack.substr(0, needle.length) === needle);
	}
	
    window.onresize = resize;
    window.onload = resize;
</script>
</head>
<body>
<div id="wrap">
	<div id="stylized">
		<form action="#" id="form">
			<h1>Plots</h1>
			<p>Results from PEcAn.</p>
			
			<h2>Plots</h2>
			<label>Selected Year</label>
			<select id="year">
				<?=$years?>
			</select>
			<div class="spacer"></div>
			
			<label>Selected Variable</label>
			<select id="var">
				<?=$vars?>
			</select>
			<div class="spacer"></div>
			
			<input id="home" type="button" value="Show Plot" onclick="showPlot();" />
			<div class="spacer"></div>

			<h2>Output Files</h2>
			<select id="outputs">
				<?=$outputs?>
			</select>
			<div class="spacer"></div>
			
			<input id="home" type="button" value="Show File" onclick="show($('#outputs')[0].value);" />
			<div class="spacer"></div>

			<h2>Log Files</h2>
			<select id="log">
				<?=$logs?>
			</select>
			<div class="spacer"></div>
			<input id="home" type="button" value="Show File" onclick="show($('#log')[0].value);" />
			
			<div class="spacer"></div>
		</form>
		
		<form id="formprev" method="POST" action="history.php">
		</form>
		
		<form id="formnext" method="POST" action="selectsite.php">
		<p></p>
		<span id="error" class="small">&nbsp;</span>
		<input id="prev" type="button" value="History" onclick="prevStep();" />
		<input id="next" type="button" value="Start Over" onclick="nextStep();"/>		
		<div class="spacer"></div>
		</form>
	</div>
	<div id="output">Please select an option on the left.</div>
</div>
</body>
</html>

<?php 
close_database($connection);
?>