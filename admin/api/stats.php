<?php

$DomainList = array();
$DomainCount = array();
$SortedDomainList = array();
$TLDBlockList = array();
$CommonSites = array('cloudfront.net','googleusercontent.com','googlevideo.com','akamaiedge.com','stackexchange.com');
//CommonSites referres to websites that have a lot of subdomains which aren't necessarily relivent. In order to improve user experience we'll replace the subdomain of these sites with "*"


//HTTP GET Variables-------------------------------------------------
$SortCol = 0;
if (isset($_GET['sort'])) {
  switch ($_GET['sort']) {
    case 0: $SortCol = 0; break;                 //Requests
    case 1: $SortCol = 1; break;                 //Name
  }
}

//Direction: 0 = Ascending, 1 = Descending
$SortDir = 0;
if (isset($_GET['dir'])) {
  if ($_GET['dir'] == "1") {
    $SortDir = 1;
  }
}

$StartPoint = 1;				 //Start point
if (isset($_GET['start'])) {
  if (is_numeric($_GET['start'])) {
    if (($_GET['start'] >= 1) && ($_GET['count'] < PHP_INT_MAX - 2)) {
      $StartPoint = intval($_GET['start']);
    }
  }
}

$ItemsPerPage = 500;				 //Rows per page
if (isset($_GET['count'])) {
  if (is_numeric($_GET['count'])) {
    if (($_GET['count'] >= 2) && ($_GET['count'] < PHP_INT_MAX)) {
      $ItemsPerPage = intval($_GET['count']);
    }
  }
}

$View = 1;
if (isset($_GET['v'])) {
  switch ($_GET['v']) {
    case '1': $View = 1; break;                 //Show All
    case '2': $View = 2; break;                 //Allowed only
    case '3': $View = 3; break;                 //Blocked only
  }
}

$Earliest = 0;
$EarliestStr = "";
if (isset($_GET['earliest'])) {
  $EarliestStr = strtolower($_GET['earliest']);
  if ($EarliestStr != "today") {
    if (($Earliest = strtotime($EarliestStr)) === false) {
      $Earliest = 0;
      $EarliestStr = "today";
      echo "Invalid Time\n";
    }
  }
}


//ReturnURL - Gives a simplier formatted URL for displaying----------
function ReturnURL($Str) {
  //Conditions:
  //1: Drop www (its unnecessary and not all websites use it now)
  //2: Combine .co.xx, com.xx, .net.xx into one string. Otherwise .uk would be TLD and .co the website.
  //   .co.uk is the top level domain
  //3: Only return as far back as one subdomain. a.b.c.d.somesite.com is a bit excessive
  //   "d.somesite.com" will suffice
  //4: Try and combine well used sites $CommonSites which use a lot of different subdomains into "*"
  //   Its tempting to increase the list, however there is a processing limitation on a RaspberryPi
  global $CommonSites;
  $Split = explode('.', $Str);
  $c = count($Split) - 1;

  if ($Split[$c - 1] == 'co') {
    $Split[$c - 1] = 'co.' . $Split[$c];
    $c--;
  }
  elseif ($Split[$c - 1] == 'com') {
    $Split[$c - 1] = 'com.' . $Split[$c];
    $c--;
  }
  elseif ($Split[$c - 1] == 'net') {
    $Split[$c - 1] = 'net.' . $Split[$c];
    $c--;
  }

  if ($c == 0) return $Split[0];
  if ($c == 1) return $Split[0] . '.' . $Split[1];
  if ($c == 2) {
    if ($Split[0] == 'www') return $Split[1] . '.' . $Split[2];
    else {
      if (in_array($Split[$c - 1].'.'.$Split[$c], $CommonSites)) return '*.'.$Split[$c - 1].'.'.$Split[$c];
      else return $Split[0] . '.' . $Split[1] . '.' . $Split[2];
    }
  }
  if ($c >= 2) {
    if (in_array($Split[$c - 1].'.'.$Split[$c], $CommonSites)) return '*.'.$Split[$c - 1].'.'.$Split[$c];
    else return $Split[$c - 2] . '.' . $Split[$c - 1] . '.' . $Split[$c];
  }
  return 'Error in URL String';
}



//Read Day All--------------------------------------------------------
function Read_Day_All($FileHandle) {
  global $DomainList;
  global $DomainCount;
  while (!feof($FileHandle)) {
    $Line = fgets($FileHandle);                  //Read Line of LogFile
    if (substr($Line, 4, 1) == ' ') {            //dnsmasq puts a double space for single digit dates
      $Seg = explode(' ', str_replace('  ', ' ', $Line));
    }
    else $Seg = explode(' ', $Line);             //Split Line into segments

    if (($Seg[4] == 'reply') && ($Seg[5] != $Dedup)) {
      $url = ReturnURL($Seg[5]);
      if (!($DomainCount[$url])){
        $DomainList[] = array('url' => $url, 'blocked' => false);
        $Dedup = $Seg[5];
        $DomainCount[$url] = 0;
      }
      $DomainCount[$url]++;
    }
    elseif (($Seg[4] == 'config') && ($Seg[5] != $Dedup)) {
      $url = ReturnURL($Seg[5]);
      if (!($DomainCount[$url])){
        $DomainList[] = array('url' => $url, 'blocked' => true);
        $Dedup = $Seg[5];
        $DomainCount[$url] = 0;
      }
      $DomainCount[$url]++;
    }
    elseif (($Seg[4] == '/etc/localhosts.list') && (substr($Seg[5], 0, 1) != '1')) {
      //!= "1" negates Reverse DNS calls. If RFC 1918 is obeyed 10.0.0.0, 172.31, 192.168 all start with "1"
      $DomainList[] = ReturnURL($Seg[5]) . '1';
      //$Dedup = $Seg[5];
    }
  }
  return null;
}
//Read Day Allowed---------------------------------------------------
function Read_Day_Allowed($FileHandle) {
  global $DomainList;
  while (!feof($FileHandle)) {
    $Line = fgets($FileHandle);                  //Read Line of LogFile
    if (substr($Line, 4, 1) == ' ') {            //dnsmasq puts a double space for single digit dates
      $Seg = explode(' ', str_replace('  ', ' ', $Line));
    }
    else $Seg = explode(' ', $Line);             //Split Line into segments

    if ($Seg[4] == 'reply' && $Seg[5] != $Dedup) {
      $DomainList[] = ReturnURL($Seg[5]) . '+';
      $Dedup = $Seg[5];
    }
  }
  return null;
}
//Read Day Allowed---------------------------------------------------
function Read_Day_Blocked($FileHandle) {
  global $DomainList;
  while (!feof($FileHandle)) {
    $Line = fgets($FileHandle);                  //Read Line of LogFile
    if (substr($Line, 4, 1) == ' ') {            //dnsmasq puts a double space for single digit dates
      $Seg = explode(' ', str_replace('  ', ' ', $Line));
    }
    else $Seg = explode(' ', $Line);             //Split Line into segments

    if ($Seg[4] == 'config' && $Seg[5] != $Dedup) {
      $DomainList[] = ReturnURL($Seg[5]) . '-';
      $Dedup = $Seg[5];
    }
  }
  return null;
}
//Read Time All--------------------------------------------------------
function Read_Time_All($FileHandle) {
  global $DomainList, $Earliest;
  while (!feof($FileHandle)) {
    $Line = fgets($FileHandle);                  //Read Line of LogFile
    if (substr($Line, 4, 1) == ' ') {            //dnsmasq puts a double space for single digit dates
      $Seg = explode(' ', str_replace('  ', ' ', $Line));
    }
    else $Seg = explode(' ', $Line);             //Split Line into segments

    if (strtotime($Seg[2]) >= $Earliest) {       //Check if time in log > Earliest required
      if (($Seg[4] == 'reply') && ($Seg[5] != $Dedup)) {
        $DomainList[] = ReturnURL($Seg[5]) . '+';
        $Dedup = $Seg[5];
      }
      elseif (($Seg[4] == 'config') && ($Seg[5] != $Dedup)) {
        $DomainList[] = ReturnURL($Seg[5]) . '-';
        $Dedup = $Seg[5];
      }
      elseif (($Seg[4] == '/etc/localhosts.list') && (substr($Seg[5], 0, 1) != '1')) {
        //!= "1" negates Reverse DNS calls. If RFC 1918 is obeyed 10.0.0.0, 172.31, 192.168 all start with "1"
        $DomainList[] = ReturnURL($Seg[5]) . '1';
      //$Dedup = $Seg[5];
      }
    }
  }
  return null;
}
//Read Day Allowed---------------------------------------------------
function Read_Time_Allowed($FileHandle) {
  global $DomainList, $Earliest;
  while (!feof($FileHandle)) {
    $Line = fgets($FileHandle);                  //Read Line of LogFile

    if (substr($Line, 4, 1) == ' ') {            //dnsmasq puts a double space for single digit dates
      $Seg = explode(' ', str_replace('  ', ' ', $Line));
    }
    else $Seg = explode(' ', $Line);             //Split Line into segments

    if (strtotime($Seg[2]) >= $Earliest) {       //Check if time in log > Earliest required
      if ($Seg[4] == 'reply' && $Seg[5] != $Dedup) {
        $DomainList[] = ReturnURL($Seg[5]) . '+';
        $Dedup = $Seg[5];
      }
    }
  }
  return null;
}
//Read Day Allowed---------------------------------------------------
function Read_Time_Blocked($FileHandle) {
  global $DomainList, $Earliest;
  while (!feof($FileHandle)) {
    $Line = fgets($FileHandle);                  //Read Line of LogFile
    if (substr($Line, 4, 1) == ' ') {            //dnsmasq puts a double space for single digit dates
      $Seg = explode(' ', str_replace('  ', ' ', $Line));
    }
    else $Seg = explode(' ', $Line);             //Split Line into segments

    if (strtotime($Seg[2]) >= $Earliest) {       //Check if time in log > Earliest required
      if ($Seg[4] == 'config' && $Seg[5] != $Dedup) {
        $DomainList[] = ReturnURL($Seg[5]) . '-';
        $Dedup = $Seg[5];
      }
    }
  }
  return null;
}
//Main---------------------------------------------------------------

//Open Log File------------------------------------------------------
//Dnsmasq log line consists of:
//0 - Month
//1 - Day
//2 - Time
//3 - dnsmasq[pid]
//4 - Function (query, forwarded, reply, cached, config)
//5 - Website Requested
//6 - "is"
//7 - IP Returned
$Dedup = "";                                     //To prevent duplication
$FileHandle= fopen('/var/log/notrack.log', 'r') or die('Error unable to open /var/log/notrack.log');
//These while loops are replicated to reduce the number of if statements inside the loop, as this section is very CPU intensive and RPi struggles
if ($Earliest == 0) {
  if ($View == 1) Read_Day_All($FileHandle);     //Read both Allow & Block
  elseif ($View == 2) Read_Day_Allowed($FileHandle);  //Read Allowed only
  elseif ($View == 3) Read_Day_Blocked($FileHandle);  //Read Blocked only
}
else {
  if ($View == 1) Read_Time_All($FileHandle);    //Read both Allow & Block
  elseif ($View == 2) Read_Time_Allowed($FileHandle);  //Read Allowed only
  elseif ($View == 3) Read_Time_Blocked($FileHandle);  //Read Blocked only
}
fclose($FileHandle);

$ReturnDomainList = array();

foreach ($DomainList as $key => $value) {
  $value["num"] = $DomainCount[$value["url"]];
  $ReturnDomainList[] = $value;
}

print(json_encode($ReturnDomainList));

?>
