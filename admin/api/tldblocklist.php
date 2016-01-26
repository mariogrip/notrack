<?php

if (!file('/etc/notrack/domain-quick.list')) die(http_response_code(404));

foreach (file('/etc/notrack/domain-quick.list') as $key => $value) {
  $TrackerBlockList[]=str_replace("\n", "", $value);
}

print(json_encode($TrackerBlockList));

?>
