<?php

if (file_exists('/var/lib/misc/dnsmasq.leases')) {
  $FileHandle= file('/var/lib/misc/dnsmasq.leases');

  foreach ($FileHandle as $key => $value) {
    $Seg = explode(' ', str_replace("\n", "", $value));
    $arr = array('TimeStamp' => $Seg[0], 'Mac' => $Seg[1], 'IP' => $Seg[2], 'DeviceName' => $Seg[3], 'MacS' => $Seg[4],);
    $dhcpLeases[] = $arr;
  }

  print(json_encode($dhcpLeases));
}
else {
  echo "null";
}

?>
