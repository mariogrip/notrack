 
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
$TLDBlockList = array();
$TrackerBlockList = array();

//Read Malicious TLD List------------------------
$FileHandle= fopen("/etc/dnsmasq.d/malicious-domains.list", "r") or die("Error unable to open /etc/dnsmasq.d/malicious-domains.list");
while (!feof($FileHandle)) {
  $Line = fgets($FileHandle);                    //Read Line of TLD List
  if (substr($Line, 0, 1) != "#") {		 //Disregard comment line
    
    $TLDBlockList[] = trim(explode("/", $Line)[1]);
    echo trim(explode("/", $Line)[1]);
    echo "<br />\n";
  }
}
fclose($FileHandle);

?> 
</div>
</body>
</html>