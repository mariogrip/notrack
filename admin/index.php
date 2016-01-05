<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="master.css" rel="stylesheet" type="text/css" />    
    <title>NoTrack Admin</title>
</head>

<body>
<div id="main">
<?php
include('topmenu.html');
echo "<h1>NoTrack Admin</h1>\n"; 

echo '<h3><a href="../admin/stats.php">Stats</a></h3>';
echo '<h3><a href="../admin/blocklist.php">Blocklist</a></h3>';
echo '<h3><a href="../admin/tldblocklist.php">TLD Blocklist</a></h3>';
echo '<h3>Domains in Tracker Blocklist: '.number_format(floatval(exec('wc -l /etc/notrack/tracker-quick.list | cut -d\  -f 1'))).'</h3>';
echo '<h3>Top Level Domains in Blocklist: '.number_format(floatval(exec('wc -l /etc/notrack/domain-quick.list | cut -d\  -f 1'))).'</h3>';
echo '<h3>DNS Queries made today: '.number_format(floatval(exec('cat /var/log/pihole.log | grep -F query[A] | wc -l'))).'</h3>';;

?>
</div>
</body>
</html>
