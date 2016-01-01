<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="master.css" rel="stylesheet" type="text/css" />    
    <title>NoTrack Stats</title>
</head>

<body>
<div id="main">
<?php
$CurTopMenu = 'stats';
include('topmenu.html');
echo "<h1>Domain Stats</h1>\n";
$DomainList = array();
$SortedDomainList = array();
$TLDBlockList = array();
$CommonSites = array("googleusercontent","akamaiedge");
//CommonSites referres to websites that have a lot of subdomains which aren't necessarily relivent. In order to improve user experience we'll replace the subdomain of these sites with "*"
//HTTP GET Variables-------------------------------------------------
$SortCol = 0;
if ($_GET['sort']) {
  switch ($_GET['sort']) {
    case 0: $SortCol = 0; break;                 //Requests
    case 1: $SortCol = 1; break;                 //Name
  }
}

//Direction: 0 = Ascending, 1 = Descending
$SortDir = 0;
if ($_GET['dir']) {
  if ($_GET['dir'] == 1) {
    $SortDir = 1;
  }  
}

$StartPoint = 1;				 //Start point
if ($_GET['start']) {
  if (is_numeric($_GET['start'])) {
    if ($_GET['start'] >= 1) {		         //Check Numeric value is above zero
      $StartPoint = $_GET['start'];
    }
  }
}
$ItemsPerPage = 200;				 //Rows per page
if ($_GET['count']) {
  if (is_numeric($_GET['count'])) {
    if ($_GET['count'] >= 2) {			 //Check Numeric value is above 2
      $ItemsPerPage = $_GET['count'];
    }
  }
}

//-------------------------------------------------------------------
function ReturnURL($Str) {
  global $CommonSites;
  $Split = explode(".", $Str);
  $c = count($Split) - 1;
  
  if ($Split[$c - 1] == "co") {
    $Split[$c - 1] = 'co.' . $Split[$c];    
    $c--;    
  }
  elseif ($Split[$c - 1] == "com") {
    $Split[$c - 1] = 'com.' . $Split[$c];
    $c--;
  }
  elseif ($Split[$c - 1] == "net") {
    $Split[$c - 1] = 'net.' . $Split[$c]; 
    $c--;    
  }
  
  if ($c == 0) return $Split[0];
  if ($c == 1) return $Split[0] . '.' . $Split[1];
  if ($c == 2) {
    if ($Split[0] == "www") return $Split[1] . '.' . $Split[2];
    else {
      if (in_array($Split[$c - 1], $CommonSites)) return '*.'.$Split[$c - 1].'.'.$Split[$c];
      else return $Split[0] . '.' . $Split[1] . '.' . $Split[2];
    }
  }
  if ($c >= 2) {
    if (in_array($Split[$c - 1], $CommonSites)) return '*.'.$Split[$c - 1].'.'.$Split[$c];
    else return $Split[$c - 2] . '.' . $Split[$c - 1] . '.' . $Split[$c];
  }
  return "Error in URL String";
}
//WriteLI Function for Pagination Boxes-------------------------------
function WriteLI($Character, $Start, $Active) {
  global $ItemsPerPage, $SortCol, $SortDir;
  if ($Active) {
    echo '<li class="active"><a href="?start='.$Start.'&amp;count='.$ItemsPerPage.'&amp;sort='.$SortCol.'&amp;dir='.$SortDir.'">';
  }
  else {
    echo '<li><a href="?start='.$Start.'&amp;count='.$ItemsPerPage.'&amp;sort='.$SortCol.'&amp;dir='.$SortDir.'">';
  }  
  echo "$Character $StartPoint</a></li>\n";  
  return null;
}

function WriteTH($Sort, $Dir, $Str) {
  global $ItemsPerPage, $StartPoint;
  echo '<th><a href="?start='.$StartPoint.'&amp;count='.$ItemsPerPage.'&amp;sort='.$Sort.'&amp;dir='.$Dir.'">'.$Str.'</a></th>';
  return null;
}

//Main---------------------------------------------------------------

//Open Log File------------------------------------------------------
$Dedup = "";
$FileHandle= fopen("/var/log/pihole.log", "r") or die("Error unable to open /var/log/pihole.log");
while (!feof($FileHandle)) {
  $Line = fgets($FileHandle);                    //Read Line of LogFile
  if (substr($Line, 4, 1) == " ") {              //dnsmasq puts a double space for single digit dates
    $Seg = explode(" ", str_replace("  ", " ", $Line));
  }
  else {  
    $Seg = explode(" ", $Line);                  //Split Line into segments
  }
  //0 - Month
  //1 - Day
  //2 - Time
  //3 - dnsmasq[pid]
  //4 - Function (query, forwarded, reply, cached, config)
  //5 - Website Requested
  //6 - "is"
  //7 - IP Returned
  if ($Seg[4] == "reply" && $Seg[5] != $Dedup) {
    $DomainList[] = ReturnURL($Seg[5]) . '+';
    $Dedup = $Seg[5];
  }
  elseif ($Seg[4] == "config" && $Seg[5] != $Dedup) {
    $DomainList[] = ReturnURL($Seg[5]) . '-';
    $Dedup = $Seg[5];
  }
  /*else {
  echo $Seg[4] . ' ' . $Seg[5];
  echo "<br/>\n";
  }*/
  
}
fclose($FileHandle);

//Read Malicious TLD List--------------------------------------------
$FileHandle = fopen("/etc/notrack/domain-quick.list", "r") or die("Error unable to open /etc/notrack/domain-quick.list");
while (!feof($FileHandle)) {
  $TLDBlockList[] = trim(fgets($FileHandle));
}
fclose($FileHandle);

//Sort Array of Domains from log file--------------------------------
$SortedDomainList = array_count_values($DomainList);//Take a count of number of hits
if ($SortCol == 1) {
  if ($SortDir == 0) ksort($SortedDomainList);
  else krsort($SortedDomainList);
}
else {
  if ($SortDir == 0) arsort($SortedDomainList);			 //Sort array by highest number of hits
  else asort($SortedDomainList);
}

//Pagination---------------------------------------------------------
$ListSize = count($SortedDomainList);
if ($ListSize > $ItemsPerPage) {                 //Is Pagination needed
  $ListSize = ceil($ListSize / $ItemsPerPage);   //Calculate List Size
  $CurPos = 0;
  while ($CurPos < $ListSize) {                  //Find Current Page
    $CurPos++;
    if ($StartPoint < $CurPos * $ItemsPerPage) {
      break;					 //Leave loop when found
    }
  }

  echo '<div class="pag-nav">';
  echo "<br /><ul>\n";
  
  if ($CurPos == 1) {                            //At the beginning display blank box
    echo '<li><span>&nbsp;&nbsp;</span></li>';
    echo "\n";
    WriteLI('1', 0, true);
  }    
  else {                                         // << Symbol & Print Box 1
    WriteLI('&#x00AB;', $ItemsPerPage * ($CurPos - 2), false);
    WriteLI('1', 0, false);
  }
	

  if ($ListSize <= 4) {                          //Small Lists don't need fancy effects
    for ($i = 2; $i <= $ListSize; $i++) {	 //List of Numbers
      if ($i == $CurPos) {
        WriteLI($i, $ItemsPerPage * ($i - 1), true);
      }
      else {
        WriteLI($i, $ItemsPerPage * ($i - 1), false);
      }
    }
  }
  elseif ($ListSize > 4 && $CurPos == 1) {       // < [1] 2 3 4 T >
    WriteLI('2', $ItemsPerPage, false);
    WriteLI('3', $ItemsPerPage * 2, false);
    WriteLI('4', $ItemsPerPage * 3, false);
    WriteLI($ListSize, ($ListSize - 1) * $ItemsPerPage, false);
  }
  elseif ($ListSize > 4 && $CurPos == 2) {       // < 1 [2] 3 4 T >
    WriteLI('2', $ItemsPerPage, true);
    WriteLI('3', $ItemsPerPage * 2, false);
    WriteLI('4', $ItemsPerPage * 3, false);
    WriteLI($ListSize, ($ListSize - 1) * $ItemsPerPage, false);
  }
  elseif ($ListSize > 4 && $CurPos > $ListSize - 2) {// < 1 T-3 T-2 T-1 T > 
    for ($i = $ListSize - 3; $i <= $ListSize; $i++) {//List of Numbers
      if ($i == $CurPos) {
        WriteLI($i, $ItemsPerPage * ($i - 1), true);
      }
      else {
        WriteLI($i, $ItemsPerPage * ($i - 1), false);
    	}
      }
    }
  else {                                         // < 1 c-1 [c] c+1 T >
    for ($i = $CurPos - 1; $i <= $CurPos + 1; $i++) {//List of Numbers
      if ($i == $CurPos) {
        WriteLI($i, $ItemsPerPage * ($i - 1), true);
      }
      else {
        WriteLI($i, $ItemsPerPage * ($i - 1), false);
      }
    }
    WriteLI($ListSize, ($ListSize - 1) * $ItemsPerPage, false);
  }
    
  if ($CurPos < $ListSize) {                     // >> Symbol for Next
    WriteLI('&#x00BB;', $ItemsPerPage * $CurPos, false);
  }	
  echo "</ul></div>\n";  
}

//Draw Table Headers-------------------------------------------------
echo '<div class="row"><br />';
echo '<table class="domain-table">';             //Table Start
echo "<tr>\n";
echo "<th>#</th>\n";
if ($SortCol == 1) {
  if ($SortDir == 0) WriteTH(1, 1, "Domain&#x25B4;");
  else WriteTH(1, 0, "Domain&#x25BE;");
}
else {
  WriteTH(1, $SortDir, "Domain");
}
if ($SortCol == 0) {
  if ($SortDir == 0) WriteTH(0, 1, "Requests&#x25BE;");
  else WriteTH(0, 0, "Requests&#x25B4;");      
}
else {
  WriteTH(0, $SortDir, "Requests");
}
echo "</tr>\n";

//Draw Table Cells---------------------------------------------------
$i = 1;

foreach ($SortedDomainList as $Site => $Value) {
  if ($i >= $StartPoint) {
    if ($i >= $StartPoint + $ItemsPerPage) break;
    $Action = substr($Site,-1,1);                  //Action last character
    if ($Action == '+') {				 //+ = Allowed
      if ($i & 1) echo '<tr class="odd">';
      else echo '<tr class="even">';
      echo '<td>' . $i . '</td><td>' . substr($Site, 0, -1) . '</td><td>' . $Value . '</td>';
    }
    elseif ($Action == '-') {
      echo '<tr class="blocked">';
      $SplitURL = explode(".", substr($Site, 0, -1));
      $CountSubDomains = count($SplitURL);  
      echo '<td>' . $i . '</td><td>' . substr($Site, 0, -1); 
      if ($CountSubDomains <= 1) {                 
        echo '<p class="small">Invalid domain</p>';     
      }
      elseif (in_array('.' . $SplitURL[$CountSubDomains -1] . ' ', $TLDBlockList)) {
        echo '<p class="small">.' . $SplitURL[$CountSubDomains -1] . ' Blocked by Top Level Domain List</p>';           
      }
      else echo '<p class="small">Blocked by Tracker List</p>';
      echo '</td><td>' . $Value . '</td>';
    }
    echo "</tr>\n";
  }
  
  $i++;
}

echo "</table>\n";
?>
</div></div>
</body>
</html>
