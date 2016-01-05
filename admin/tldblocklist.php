<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <link href="master.css" rel="stylesheet" type="text/css" />    
  <title>NoTrack TLD Blocklist</title>
</head>

<body>
<div id="main">
<?php
$CurTopMenu = 'tldblocklist';
include('topmenu.html');
echo "<h1>Top Level Domain Blocklist</h1>\n";

$TLDBlockList = array();

//Read Malicious TLD List--------------------------------------------
$FileHandle = fopen('/etc/notrack/domain-quick.list', 'r') or die('Error unable to open /etc/notrack/domain-quick.list');
while (!feof($FileHandle)) {
  $TLDBlockList[]= trim(fgets($FileHandle));  
}
fclose($FileHandle);
asort($TLDBlockList);

//Display List-------------------------------------------------------
echo '<div class="row-padded"><br />'."\n";
foreach($TLDBlockList as $Site) {
  echo $Site."<br />\n";
}
echo "</div>\n";
?> 
</div>
</body>
</html>