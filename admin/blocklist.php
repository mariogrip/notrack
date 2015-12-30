 
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="master.css" rel="stylesheet" type="text/css" />    
    <title>NoTrack Tracker List</title>
</head>

<body>
<div id="main">
<?php
$TrackerBlockList = file('/etc/notrack/tracker-quick.list');

foreach ($TrackerBlockList as $Site) {
  echo "$Site <br />\n";
}
?> 
</div>
</body>
</html>
