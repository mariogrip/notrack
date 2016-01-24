<?php

if (!file_exists('/etc/notrack/tracker-quick.list')) die("Error unable to open /etc/notrack/tracker-quick.list");

foreach (file('/etc/notrack/tracker-quick.list') as $key => $value) {
  $TrackerBlockList[]=str_replace("\n", "", $value);
}

print(json_encode($TrackerBlockList));

?>
