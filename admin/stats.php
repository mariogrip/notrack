<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="./css/master.css" rel="stylesheet" type="text/css" />
    <link rel="icon" type="image/png" href="./favicon.png" />
    <title>NoTrack Stats</title>
</head>

<body>
<div id="main">
<?php
$CurTopMenu = 'stats';
include('topmenu.html');
echo "<h1>Domain Stats</h1>\n";
echo "<br />\n";
$DomainList = array();
$SortedDomainList = array();
$TLDBlockList = array();
$CommonSites = array('cloudfront.net','googleusercontent.com','googlevideo.com','akamaiedge.com','stackexchange.com');
//CommonSites referres to websites that have a lot of subdomains which aren't necessarily relivent. In order to improve user experience we'll replace the subdomain of these sites with "*"

$Mem = new Memcache;                             //Initiate Memcache
$Mem->connect('localhost');

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

//WriteLI Function for Pagination Boxes-------------------------------
function WriteLI($Character, $Start, $Active) {
  global $ItemsPerPage, $SortCol, $SortDir, $View;
  if ($Active) {
    echo '<li class="active"><a href="?start='.$Start.'&amp;count='.$ItemsPerPage.'&amp;sort='.$SortCol.'&amp;dir='.$SortDir.'&amp;v='.$View.'">';
  }
  else {
    echo '<li><a href="?start='.$Start.'&amp;count='.$ItemsPerPage.'&amp;sort='.$SortCol.'&amp;dir='.$SortDir.'&amp;v='.$View.'">';
  }  
  echo "$Character</a></li>\n";  
  return null;
}

//WriteTH Function for Table Header----------------------------------- 
function WriteTH($Sort, $Dir, $Str) {
  global $ItemsPerPage, $StartPoint, $View;
  echo '<th><a href="?start='.$StartPoint.'&amp;count='.$ItemsPerPage.'&amp;sort='.$Sort.'&amp;dir='.$Dir.'&amp;v='.$View.'">'.$Str.'</a></th>';
  return null;
}

//Load TLD Block List------------------------------------------------
function Load_TLDBlockList() {
//Blocklist is held in Memcache for 10 minutes
  global $TLDBlockList, $Mem;
  $TLDBlockList=$Mem->get('TLDBlockList');
  if (! $TLDBlockList) {    
    $FileHandle = fopen('/etc/notrack/domain-quick.list', 'r') or die('Error unable to open /etc/notrack/domain-quick.list');
    while (!feof($FileHandle)) {
      $TLDBlockList[] = trim(fgets($FileHandle));
    }
    fclose($FileHandle);
    $Mem->set('TLDBlockList', $TLDBlockList, 0, 600);
  }
  return null;
}

//Read Day All--------------------------------------------------------
function Read_Day_All($FileHandle) {
  global $DomainList;
  while (!feof($FileHandle)) {
    $Line = fgets($FileHandle);                  //Read Line of LogFile
    if (substr($Line, 4, 1) == ' ') {            //dnsmasq puts a double space for single digit dates
      $Seg = explode(' ', str_replace('  ', ' ', $Line));
    }
    else $Seg = explode(' ', $Line);             //Split Line into segments
    
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
Load_TLDBlockList();                             //Load TLD Blocklist from memory or file

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



//Sort Array of Domains from log file--------------------------------
$SortedDomainList = array_count_values($DomainList);//Take a count of number of hits
if ($SortCol == 1) {
  if ($SortDir == 0) ksort($SortedDomainList);
  else krsort($SortedDomainList);
}
else {
  if ($SortDir == 0) arsort($SortedDomainList);  //Sort array by highest number of hits
  else asort($SortedDomainList);
}

$ListSize = count($SortedDomainList);
if ($StartPoint >= $ListSize) $StartPoint = 1;   //Start point can't be greater than the list size

//Draw Filter Dropdown list------------------------------------------
echo '<form action="?" method="get">';
echo '<input type="hidden" name="sort" value="'.$SortCol.'" />'; //Parse other GET variables as hidden form values
echo '<input type="hidden" name="dir" value="'.$SortDir.'" />';  
echo '<input type="hidden" name="start" value="'.$StartPoint.'" />';
echo '<input type="hidden" name="count" value="'.$ItemsPerPage.'" />';
echo '<input type="hidden" name="earliest" value="'.$EarliestStr.'" />';
echo '<Label><b>Filter:</b> View  <select name="v" onchange="submit()">';
switch ($View) {                                 //First item is unselectable, therefore we need to
  case 1:                                        //give a different selection for each value of $View
    echo '<option value="1">All Requests</option>';
    echo '<option value="2">Only requests that were allowed</option>';
    echo '<option value="3">Only requests that were blocked</option>';
  break;
  case 2:
    echo '<option value="2">Only requests that were allowed</option>';
    echo '<option value="1">All Requests</option>';
    echo '<option value="3">Only requests that were blocked</option>';
  break;
  case 3:
    echo '<option value="3">Only requests that were blocked</option>';
    echo '<option value="1">All Requests</option>';
    echo '<option value="2">Only requests that were allowed</option>';
  break;
}
echo '</select></label></form>'."\n";

//Draw Time Dropdown list------------------------------------------
echo '<form action="?" method="get">';
echo '<input type="hidden" name="sort" value="'.$SortCol.'" />'; //Parse other GET variables as hidden form values
echo '<input type="hidden" name="dir" value="'.$SortDir.'" />';  
echo '<input type="hidden" name="start" value="'.$StartPoint.'" />';
echo '<input type="hidden" name="count" value="'.$ItemsPerPage.'" />';
echo '<input type="hidden" name="v" value="'.$View.'" />';
echo '<Label><b>Time:</b> Show  <select name="earliest" onchange="submit()">';
switch ($EarliestStr) {                          //First item is unselectable
  case "today": case "":
    echo '<option value="today">Today</option>';
    echo '<option value="-5minutes">5 Minutes</option>';
    echo '<option value="-1hours">1 Hour</option>';
    echo '<option value="-8hours">8 Hours</option>';
  break;
  case "-5minutes":
    echo '<option value="-5minutes">5 Minutes</option>';
    echo '<option value="today">Today</option>';
    echo '<option value="-1hours">1 Hour</option>';
    echo '<option value="-8hours">8 Hours</option>';
  break;
  case "-1hours":
    echo '<option value="-1hours">1 Hour</option>';
    echo '<option value="today">Today</option>';
    echo '<option value="-5minutes">5 Minutes</option>';
    echo '<option value="-8hours">8 Hours</option>';
  break;
  case "-8hours":
    echo '<option value="-8hours">8 Hours</option>';
    echo '<option value="today">Today</option>';
    echo '<option value="-5minutes">5 Minutes</option>';
    echo '<option value="-1hours">1 Hour</option>';
  break;
  default:
    echo '<option value="'.$EarliestStr.'">Other</option>';
    echo '<option value="today">Today</option>';
    echo '<option value="-5minutes">5 Minutes</option>';
    echo '<option value="-1hours">1 Hour</option>';
    echo '<option value="-8hours">8 Hours</option>';
  break;
}
echo '</select></label></form>'."\n";

//Draw Table Headers-------------------------------------------------
echo '<div class="row"><br />'."\n";
echo '<table class="domain-table">';             //Table Start
echo "<tr>\n";
echo "<th>#</th>\n";
if ($SortCol == 1) {
  if ($SortDir == 0) WriteTH(1, 1, 'Domain&#x25B4;');
  else WriteTH(1, 0, 'Domain&#x25BE;');
}
else {
  WriteTH(1, $SortDir, 'Domain');
}
echo "<th>G</th>\n";
echo "<th>W</th>\n";
if ($SortCol == 0) {
  if ($SortDir == 0) WriteTH(0, 1, 'Requests&#x25BE;');
  else WriteTH(0, 0, 'Requests&#x25B4;');
}
else {
  WriteTH(0, $SortDir, 'Requests');
}
echo "</tr>\n";

//Draw Table Cells---------------------------------------------------
$i = 1;
foreach ($SortedDomainList as $Str => $Value) {
  if ($i >= $StartPoint) {                       //Start drawing the table when we reach the StartPoint of Pagination
    if ($i >= $StartPoint + $ItemsPerPage) break;//Exit the loop at end of Pagination + Number of Items per page
    $Action = substr($Str,-1,1);                 //Last character tells us whether URL was blocked or not
    $Site = substr($Str, 0, -1);
    if ($Action == '+') {                        //+ = Allowed
      if ($i & 1) echo '<tr class="odd">';       //Light grey row on odd numbers
      else echo '<tr class="even">';             //White row on even numbers
      echo '<td>'. $i.'</td><td>'.$Site.'</td>';
    }
    elseif ($Action == '-') {                    //- = Blocked
      $SplitURL = explode('.', $Site);           //Find out wheter site was blocked by TLD or Tracker list
      $CountSubDomains = count($SplitURL);
      echo '<tr class="blocked">';               //Red row for blocked
      if ($CountSubDomains <= 1) {               //No TLD Given, this could be a search via address bar  
        echo '<td>'.$i.'</td><td>'.$Site.'<p class="small">Invalid domain</p></td>';
      }
      elseif (in_array('.'.$SplitURL[$CountSubDomains-1], $TLDBlockList)) {
        echo '<td>'.$i.'</td><td>'.$Site.'<p class="small">.'.$SplitURL[$CountSubDomains -1].' Blocked by Top Level Domain List</p></td>';
      }
      else {
        echo '<td>'.$i.'</td><td>'.$Site.'<p class="small">Blocked by Tracker List</p></td>';
      }
    }
    elseif ($Action == '1') {                    //1 = Local lookup
      echo '<tr class="local">';
      echo '<td>'.$i.'</td><td>'.$Site.'</td>';
    }
    echo '<td><a target="_blank" href="https://www.google.com/search?q='.$Site.'"><img class="icon" src="./images/search_icon.png" alt=""</a></td><td><a target="_blank" href="https://who.is/whois/'.$Site.'"><img class="icon" src="./images/whois_icon.png" alt=""></a></td><td>'.$Value.'</td></tr>'."\n";    
  }  
  $i++;
}

echo "</table></div>\n";
echo '<div class="row"><br /></div>';


//Pagination---------------------------------------------------------
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

?>
</div>
</body>
</html>
