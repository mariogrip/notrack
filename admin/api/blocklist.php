<?php
$start = 0;
$count = -1;

if (!file_exists('/etc/notrack/tracker-quick.list')) die(http_response_code(404));

if (isset($_GET['total'])){
  die(print(count(file('/etc/notrack/tracker-quick.list'))));
}

if (isset($_GET['start'])){
  $start = $_GET['start'];
}

if (isset($_GET['count'])){
  $count = $_GET['count'];
}

foreach (file('/etc/notrack/tracker-quick.list') as $key => $value) {
  if ($start > 0){
    $start--;
    continue;
  }
  if ($count == 0) break;
  if ($count != -1) $count--;
  $TrackerBlockList[]=str_replace("\n", "", $value);
}

print(json_encode($TrackerBlockList));

?>
