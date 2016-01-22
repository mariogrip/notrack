 
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="./css/master.css" rel="stylesheet" type="text/css" />
    <link rel="icon" type="image/png" href="./favicon.png" />
    <title>NoTrack Tracker List</title>
</head>

<body>
<div id="main">
<?php
$CurTopMenu = 'blocklist';
include('topmenu.html');
echo "<h1>Tracker Blocklist</h1>\n";

$Show='all';
$SingleLetter=false;
$SingleNumber=false;
$Letters = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
$Numbers = array('0','1','2','3','4','5','6','7','8','9');
if (isset($_GET['show'])) {
  if ($_GET['show'] == 'num') {
    $SingleNumber = true;
  }
  if (in_array($_GET['show'], $Letters)) { 
    $Show = $_GET['show'];
    $SingleLetter = true;
  } 
}

//WriteLI Function for Pagination Boxes-------------------------------
function WriteLI($Character, $Active) {
  if ($Active) {
    echo '<li class="active"><a href="?show=all">';
  }
  else {
    echo '<li><a href="?show='.strtolower($Character).'">';
  }  
  echo "$Character</a></li>\n";  
  return null;
}

//Check Quick List exists--------------------------------------------
if (!file_exists('/etc/notrack/tracker-quick.list')) die("Error unable to open /etc/notrack/tracker-quick.list");

//Character Select----------------------------------------------------
echo '<div class="pag-nav">';
echo "<br /><ul>\n";
if ($Show=='all') WriteLI('All', true);
else WriteLI('All', false);
WriteLI('Num', $SingleNumber);
foreach($Letters as $Val) {
  if ($Val == $Show) WriteLI(strtoupper($Val), true);
  else WriteLI(strtoupper($Val), false);
}
echo "</ul></div>\n";
echo '<div class="row"><br /></div>';

//Load Blocklist------------------------------------------------------
$TrackerBlockList = file('/etc/notrack/tracker-quick.list');

echo '<div class="row-padded">'."\n";
foreach ($TrackerBlockList as $Site) {
  if (($SingleLetter) || ($SingleNumber)) {
    $Char1 = substr($Site,0,1);
    if (SingleLetter) {
      if ($Char1 == $Show) {
        echo $Site.'<br />'."\n";
      }
    }
    if ($SingleNumber) {
      if (in_array($Char1, $Numbers)) {
        echo $Site.'<br />'."\n";
      }
    }
  }
  else {
    echo $Site.'<br />'."\n";
  }
}
echo "</div>\n";
?> 
</div>
</body>
</html>
