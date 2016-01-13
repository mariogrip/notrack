<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="./css/master.css" rel="stylesheet" type="text/css" />    
    <title>NoTrack Admin</title>
</head>

<body>
<div id="main">
<?php
include('topmenu.html');
echo "<h1>NoTrack Admin</h1>\n"; 
echo '<div class="row"><br /></div>'."\n";
echo '<div class="row">';
echo '<div class="home-nav-r"><h2>Tracker Blocklist</h2><div class="home-nav-left"><h3 class="home-nav">'.number_format(floatval(exec('wc -l /etc/notrack/tracker-quick.list | cut -d\  -f 1'))).'</h3><h4 class="home-nav">Domains</h4></div><div class="home-nav-right"><img class="full" src="./images/magnifying_glass.png" alt=""></div></div>'."\n";

echo '<div class="home-nav-b"><h2>TLD Blocklist</h2><div class="home-nav-left"><h3 class="home-nav">'.number_format(floatval(exec('wc -l /etc/notrack/domain-quick.list | cut -d\  -f 1'))).'</h3><h4 class="home-nav">Domains</h4></div><div class="home-nav-right"><img class="full" src="./images/globe.png" alt=""></div></div>'."\n";

echo '<div class="home-nav-g"><h2>DNS Queries</h2><div class="home-nav-left"><h3 class="home-nav">'.number_format(floatval(exec('cat /var/log/notrack.log | grep -F query[A] | wc -l'))).'</h3><h4 class="home-nav">Today</h4></div><div class="home-nav-right"><img class="full" src="./images/server.png" alt=""></div></div>'."\n";

if (file_exists('/var/lib/misc/dnsmasq.leases')) {
  echo '<div class="home-nav-y"><h2>DHCP</h2><div class="home-nav-left"><h3 class="home-nav">'.number_format(floatval(exec('wc /var/lib/misc/dnsmasq.leases | cut -d\  -f 3'))).'</h3><h4 class="home-nav">Systems</h4></div><div class="home-nav-right"><img class="full" src="./images/computer.png" alt=""></div></div>'."\n";
}
else {
  echo '<div class="home-nav-y"><h2>DHCP</h2><div class="home-nav-left"><h3 class="home-nav">N/A</h3></div><div class="home-nav-right"><img class="full" src="./images/computer.png" alt=""></div></div>'."\n";
}
echo '</div>';


?>
</div>
</body>
</html>
