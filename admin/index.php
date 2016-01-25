<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="./css/master.css" rel="stylesheet" type="text/css" />
    <link rel="icon" type="image/png" href="./favicon.png" />
    <title>NoTrack Admin</title>
</head>

<body>
<div id="main">
<?php
include('topmenu.html');
echo "<h1>NoTrack Admin</h1>\n"; 

$Version="0.4";
$Config = array();

function Load_Config_File() {
  global $Config;
  if (!file_exists('/etc/notrack/notrack.conf')) {
    die("Error file /etc/notrack/notrack.conf doesn't exist");
  }
  $Config = parse_ini_file('/etc/notrack/notrack.conf');
  
  return null;
}

//Main---------------------------------------------------------------
echo '<div class="row"><br /></div>'."\n";
echo '<div class="row">';
echo '<a href="./blocklist.php"><div class="home-nav-r"><h2>Tracker Blocklist</h2><div class="home-nav-left"><h3 class="home-nav">'.number_format(floatval(exec('wc -l /etc/notrack/tracker-quick.list | cut -d\  -f 1'))).'</h3><h4 class="home-nav">Domains</h4></div><div class="home-nav-right"><img class="full" src="./images/magnifying_glass.png" alt=""></div></div></a>'."\n";

echo '<a href="./tldblocklist.php"><div class="home-nav-b"><h2>TLD Blocklist</h2><div class="home-nav-left"><h3 class="home-nav">'.number_format(floatval(exec('wc -l /etc/notrack/domain-quick.list | cut -d\  -f 1'))).'</h3><h4 class="home-nav">Domains</h4></div><div class="home-nav-right"><img class="full" src="./images/globe.png" alt=""></div></div></a>'."\n";

echo '<a href="./stats.php"><div class="home-nav-g"><h2>DNS Queries</h2><div class="home-nav-left"><h3 class="home-nav">'.number_format(floatval(exec('cat /var/log/notrack.log | grep -F query[A] | wc -l'))).'</h3><h4 class="home-nav">Today</h4></div><div class="home-nav-right"><img class="full" src="./images/server.png" alt=""></div></div></a>'."\n";

if (file_exists('/var/lib/misc/dnsmasq.leases')) {
  echo '<a href="./dhcpleases.php"><div class="home-nav-y"><h2>DHCP</h2><div class="home-nav-left"><h3 class="home-nav">'.number_format(floatval(exec('wc /var/lib/misc/dnsmasq.leases | cut -d\  -f 3'))).'</h3><h4 class="home-nav">Systems</h4></div><div class="home-nav-right"><img class="full" src="./images/computer.png" alt=""></div></div></a>'."\n";
}
else {
  echo '<a href="./dhcpleases.php"><div class="home-nav-y"><h2>DHCP</h2><div class="home-nav-left"><h3 class="home-nav">N/A</h3></div><div class="home-nav-right"><img class="full" src="./images/computer.png" alt=""></div></div><a href="./dhcpleases.php">'."\n";
}
echo '</div>';


Load_Config_File();  
  
if (array_key_exists('LatestVersion', $Config)) {
  if ($Version != $Config[LatestVersion]) {      //See if upgrade Needed
    echo '<div class="splitrow"><br /><br /></div>'."\n";
    echo '<div class="row"><br />'."\n";
    echo '<p>New version available: v'.$Config[LatestVersion].'</p><br /></div>'."\n";   
  }
}
else {                                           //Config line missing
  echo '<div class="splitrow"><br /><br /></div>'."\n";
  echo '<div class="row"><br />'."\n";
  echo '<p>Unable to identify latest version available. </p>';
  echo "<br /></div>\n";    
}

//Temp warning about Memcache
echo '<div class="row">'."\n";
echo '<h3>Please Note: NoTrack is now using memcache to improve page loading performance.</h4>';
echo '<p>Ubuntu / Debian users install memcached and php5-memcache:</p>';
echo '<pre>sudo apt-get install memcached php5-memcache<br />sudo service lighttpd restart</pre><br />';
echo '<p>Arch users install memcached and php-memcache:</p>';
echo '<pre>pacman -S memcached php-memcache</pre><br />';
echo '<p>Fedora users install memcached and php-pecl-memcached:</p>';
echo '<pre>dnf install memcached php-pecl-memcached<br />sudo service lighttpd restart</pre>';
echo '</div>';


?>
</div>
</body>
</html>
