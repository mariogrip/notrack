<?php


if (!file('/etc/notrack/domain-quick.list')) die('Error unable to open /etc/notrack/domain-quick.list');

foreach (file('/etc/notrack/domain-quick.list') as $key => $value) {
  $TrackerBlockList[]=str_replace("\n", "", $value);
}

print(json_encode($TrackerBlockList));


?>
