<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="./css/master.css" rel="stylesheet" type="text/css" />    
    <title>NoTrack Upgrade</title>
</head>

<body>
<div id="main">
<?php
$CurTopMenu = 'upgrade';
include('topmenu.html');
echo "<h1>NoTrack Upgrade</h1>\n";
echo  "<br />\n";
$Version=0.2;

if (isset($_GET['u'])) {                        //Check if we are running upgrade or displaying status
  if ($_GET['u'] == '1') {                      //Doing the upgrade
    echo '<p>Upgrading NoTrack</p>';
    echo '<pre>';
    //passthru('/usr/local/sbin/notrack -b');
    echo "Upgrade not implemented in this version\n";
    echo "Execute with: notrack -u";
    echo "</pre>\n";
    echo "<br />\n";
    echo '<h3><a class="linkbutton" href="./">Back</a></h3>';
  }
}
else {                                           //Just displaying status
  $ch = curl_init();                             //Initiate curl
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
  curl_setopt($ch, CURLOPT_URL,'https://raw.githubusercontent.com/quidsup/notrack/master/conf/notrack.conf');
  $Data = curl_exec($ch);                        //Download version file
  curl_close($ch);                               //Close curl
  
  $V = floatval(trim(explode("=", $Data)[1]));   //Extract value
  
  if ($V > $Version) {
    echo '<p>Currently running version: v'.$Version.'</p>';
    echo '<p>Latest version available: v'.$V.'</p>';
    echo '<h3><a class="linkbutton" href="?u=1">Upgrade</a>&nbsp;<a class="linkbutton" href="./">Back</a></h3>';
  }
  elseif ($V == $Version) {
    echo '<p>You&#39;re running the latest version v'.$Version.'</p>';
    echo '<h3><a class="linkbutton" href="./">Back</a></h3>';
  }
  else {
    echo '<p>You&#39;re ahead of the latest release v'.$V.'</p>';
    echo '<h3><a class="linkbutton" href="./">Back</a></h3>';
  }
}
?> 
</div>
</body>
</html>
