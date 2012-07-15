<?php  
# parameters
if (!isset($_REQUEST['siteid'])) {
  die("Need a siteid.");
}
$siteid=$_REQUEST['siteid'];
if (!isset($_REQUEST['modelid'])) {
  die("Need a modelid.");
}
$modelid=$_REQUEST['modelid'];
if (!isset($_REQUEST['modeltype'])) {
  die("Need a modeltype.");
}
$modeltype=$_REQUEST['modeltype'];
if (!isset($_REQUEST['hostname'])) {
  die("Need a hostname.");
}
$hostname=$_REQUEST['hostname'];
if (!isset($_REQUEST['start'])) {
	die("Need a start date.");
}
$startdate=$_REQUEST['start'];
if (!isset($_REQUEST['end'])) {
	die("Need a end date.");
}
$enddate=$_REQUEST['end'];
if (!isset($_REQUEST['pft'])) {
	die("Need a pft.");
}
$pft=$_REQUEST['pft'];

# specific for each model type
if ($modeltype == "ED") {
	if (!isset($_REQUEST['met'])) {
		die("Need a met.");
	}
	$met=$_REQUEST['met'];	
	if (!isset($_REQUEST['psscss'])) {
		die("Need a psscss.");
	}
	$psscss=$_REQUEST['psscss'];
} else if ($modeltype == "SIPNET") {
	
}

require("system.php");
require("dbinfo.php");
$connection=open_database();

// get site information
$query = "SELECT * FROM sites WHERE sites.id=$siteid";
$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}
$siteinfo = mysql_fetch_assoc($result);

// get model information
$query = "SELECT * FROM models WHERE models.id=$modelid";
$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}
$model = mysql_fetch_assoc($result);
$pieces = explode(':', $model["model_path"], 2);
$binary = $pieces[1];

// create the workflow execution
$params=mysql_real_escape_string(str_replace("\n", "", var_export($_REQUEST, true)));
if (mysql_query("INSERT INTO workflows (site_id, model_type, model_id, hostname, start_date, end_date, params, started_at, created_at) values ('${siteid}', '${modeltype}', '${modelid}', '${hostname}', '${startdate}', '${enddate}', '${params}', NOW(), NOW())") === FALSE) {
	die('Can\'t insert workflow : ' . mysql_error());
}
$workflowid=mysql_insert_id();

# folders
$folder = $output_folder . DIRECTORY_SEPARATOR . 'PEcAn_' . $workflowid;
if (mysql_query("UPDATE workflows SET folder='${folder}' WHERE id=${workflowid}") === FALSE) {
	die('Can\'t update workflow : ' . mysql_error());
}
if (!mkdir($folder . DIRECTORY_SEPARATOR . "out",  0777, true)) {
    die("Failed to create folders " . $folder . DIRECTORY_SEPARATOR . "out");
}
if (!mkdir($folder . DIRECTORY_SEPARATOR . "pecan",  0777, true)) {
    die("Failed to create folders " . $folder . DIRECTORY_SEPARATOR . "pecan");
}
if (!mkdir($folder . DIRECTORY_SEPARATOR . "pft",  0777, true)) {
    die("Failed to create folders " . $folder . DIRECTORY_SEPARATOR . "pft");
}
if (!mkdir($folder . DIRECTORY_SEPARATOR . "run",  0777, true)) {
    die("Failed to create folders " . $folder . DIRECTORY_SEPARATOR . "run");
}

# if on localhost replace with localhost
if ($hostname == gethostname()) {
	$hostname="localhost";
}

# create pecan.xml
$fh = fopen($folder . DIRECTORY_SEPARATOR . "pecan.xml", 'w');
fwrite($fh, "<?xml version=\"1.0\"?>" . PHP_EOL);
fwrite($fh, "<pecan>" . PHP_EOL);

fwrite($fh, "  <pecanDir>${pecan_home}</pecanDir>" . PHP_EOL);
fwrite($fh, "  <outdir>${folder}/pecan/</outdir>" . PHP_EOL);

$pft_id=1;
fwrite($fh, "  <pfts>" . PHP_EOL);
foreach($pft as $p) {
	if (!mkdir("${folder}/pft/${pft_id}",  0777, true)) {
		die("Failed to create folders $folder/pft");
	}
	fwrite($fh, "    <pft>" . PHP_EOL);
	fwrite($fh, "      <name>${p}</name> " . PHP_EOL);
	fwrite($fh, "      <outdir>${folder}/pft/${pft_id}/</outdir>" . PHP_EOL);
	fwrite($fh, "      <constants>" . PHP_EOL);
	fwrite($fh, "        <num>${pft_id}</num>" . PHP_EOL);
	fwrite($fh, "      </constants>" . PHP_EOL);
	fwrite($fh, "    </pft>" . PHP_EOL);
	$pft_id++;
}
fwrite($fh, "  </pfts>" . PHP_EOL);

fwrite($fh, "  <database>" . PHP_EOL);
fwrite($fh, "    <userid>${db_username}</userid>" . PHP_EOL);
fwrite($fh, "    <passwd>${db_password}</passwd>" . PHP_EOL);
fwrite($fh, "    <location>${db_hostname}</location>" . PHP_EOL);
fwrite($fh, "    <name>${db_database}</name>" . PHP_EOL);
fwrite($fh, "  </database>" . PHP_EOL);

fwrite($fh, "  <meta.analysis>" . PHP_EOL);
fwrite($fh, "    <iter>1000</iter>" . PHP_EOL);
fwrite($fh, "    <random.effects>TRUE</random.effects>" . PHP_EOL);
fwrite($fh, "  </meta.analysis>" . PHP_EOL);

fwrite($fh, "  <ensemble>" . PHP_EOL);
fwrite($fh, "    <size>1</size>" . PHP_EOL);
fwrite($fh, "  </ensemble>" . PHP_EOL);

fwrite($fh, "  <model>" . PHP_EOL);
if ($modeltype == "ED") {
	fwrite($fh, "    <config.header>" . PHP_EOL);
	fwrite($fh, "      <radiation>" . PHP_EOL);
	fwrite($fh, "        <lai_min>0.01</lai_min>" . PHP_EOL);
	fwrite($fh, "      </radiation>" . PHP_EOL);
	fwrite($fh, "      <ed_misc>" . PHP_EOL);
	fwrite($fh, "        <output_month>12</output_month>      " . PHP_EOL);
	fwrite($fh, "      </ed_misc> " . PHP_EOL);
	fwrite($fh, "    </config.header>" . PHP_EOL);
	fwrite($fh, "    <edin>${folder}/ED2IN.template</edin>" . PHP_EOL);
	fwrite($fh, "    <binary>${binary}</binary>" . PHP_EOL);
	fwrite($fh, "    <veg>${ed_veg}</veg>" . PHP_EOL);
	fwrite($fh, "    <soil>${ed_soil}</soil>" . PHP_EOL);
	fwrite($fh, "    <psscss>$psscss</psscss>" . PHP_EOL);
	fwrite($fh, "    <inputs>${ed_inputs}</inputs>" . PHP_EOL);
	fwrite($fh, "    <phenol.scheme>0</phenol.scheme>" . PHP_EOL);
}
fwrite($fh, "  </model>" . PHP_EOL);
fwrite($fh, "  <run>" . PHP_EOL);
fwrite($fh, "    <folder>${folder}</folder>" . PHP_EOL);
fwrite($fh, "    <site>" . PHP_EOL);
fwrite($fh, "      <name>{$siteinfo['sitename']}</name>" . PHP_EOL);
fwrite($fh, "      <lat>{$siteinfo['lat']}</lat>" . PHP_EOL);
fwrite($fh, "      <lon>{$siteinfo['lon']}</lon>" . PHP_EOL);
if ($modeltype == "ED") {
	fwrite($fh, "      <met>$met</met>" . PHP_EOL);
	fwrite($fh, "      <met.start>${startdate}</met.start>" . PHP_EOL);
	fwrite($fh, "      <met.end>${enddate}</met.end>" . PHP_EOL);
}
fwrite($fh, "    </site>" . PHP_EOL);
fwrite($fh, "    <start.date>${startdate}</start.date>" . PHP_EOL);
fwrite($fh, "    <end.date>${enddate}</end.date>" . PHP_EOL);
fwrite($fh, "    <host>" . PHP_EOL);
fwrite($fh, "      <name>${hostname}</name>" . PHP_EOL);
fwrite($fh, "      <rundir>${folder}/run/</rundir>" . PHP_EOL);
fwrite($fh, "      <outdir>${folder}/out/</outdir>" . PHP_EOL);
fwrite($fh, "      <ed>" . PHP_EOL);
fwrite($fh, "      </ed>" . PHP_EOL);
fwrite($fh, "    </host>" . PHP_EOL);
fwrite($fh, "  </run>" . PHP_EOL);
fwrite($fh, "</pecan>" . PHP_EOL);
fclose($fh); 

# copy ED2IN.template
copy("template/{$model['model_name']}_r{$model['revision']}", "${folder}/ED2IN.template");
copy("workflow.R", "${folder}/workflow.R");

# start the actual workflow
chdir($folder);
pclose(popen('R_LIBS_USER="/home/kooper/R/x86_64-pc-linux-gnu-library/2.15" R CMD BATCH workflow.R &', 'r'));

#done
header("Location: running.php?workflowid=$workflowid");
close_database($connection);
?>
