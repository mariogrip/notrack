<?php

$Version="0.4";
$Config = array();

if (!file_exists('/etc/notrack/notrack.conf')) die(http_response_code(404));
$Config = parse_ini_file('/etc/notrack/notrack.conf');

if (!array_key_exists('LatestVersion', $Config)) die(http_response_code(404));

print(json_encode(array('version' => $Version, 'latestVersion' => $Config[LatestVersion])));

?>
