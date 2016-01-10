<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="master.css" rel="stylesheet" type="text/css" />    
    <title>NoTrack Stats</title>
</head>

<body>
<div id="main">
<?php
$CurTopMenu = 'stats';
include('topmenu.html');
echo "<h1>Domain Stats</h1>\n";
echo "<br />\n";
echo "<h1>NoTrack Upgrade</h1>\n";
echo  "<br /><br />\n";
$Version=0.0;

if (isset($_GET['u'])) {                        //Check if we are running upgrade or displaying status
  if ($_GET['u'] == '1') {                      //Doing the upgrade
    echo '<p>Upgrading NoTrack</p>';
	  echo '<pre>';
	  passthru('~/NoTrack/upgrade.sh');
	  echo "</pre>\n";
	  echo "<br />\n";
	  echo '<h2><a class="linkbutton" href="./admin">Back</a></h2>';
  }
}
else {                                           //Just displaying status
  $ch = curl_init();                             //Initiate curl
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
  curl_setopt($ch, CURLOPT_URL,'https://api.github.com/repos/quidsup/notrack/releases/latest');
  $Data = json_decode(curl_exec($ch));           //Download and decode with json
  curl_close($ch);                               //Close curl
  
  $V = floatval(trim($Data->tag_name, "v"));     //Extract tag_name (latest version)
  
  if ($V > $Version) {
    echo 'You are ' . ceil(($V - $Version) * 10) . ' versions behind';
    echo '<h2><a class="linkbutton" href="?u=1">Upgrade</a>&nbsp;<a class="linkbutton" href="./admin">Back</a></h2>';
  }
  elseif ($V == $Version) {
	echo '<p>You&#39;re are running the latest version v'.$Version.'</p>';
	echo '<h2><a class="linkbutton" href="./admin">Back</a></h2>';
  }
  else {
	echo '<p>You&#39;re are ahead of the latest release v'.$V.'</p>';
	echo '<h2><a class="linkbutton" href="./admin">Back</a></h2>';
  }
?> 
</div>
</body>
</html>
