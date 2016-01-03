 
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link href="master.css" rel="stylesheet" type="text/css" />    
    <title>NoTrack Tracker List</title>
</head>

<body>
<div id="main">
<?php
$Show="all";
$SingleLetter=false;
$SingleNumber=false;
$Letters = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
$Numbers = array("0","1","2","3","4","5","6","7","8","9");
if (isset($_GET['show'])) {
  if ($_GET['show'] == "num") {
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
    echo '<li class="active"><a href="?show='.strtolower($Character).'">';
  }
  else {
    echo '<li><a href="?show='.strtolower($Character).'">';
  }  
  echo "$Character</a></li>\n";  
  return null;
}

//Character Select----------------------------------------------------
echo '<div class="pag-nav">';
echo "<br /><ul>\n";
if ($Show=="all") WriteLI("All", true);
else WriteLI("All", false);
WriteLI("Num", $SingleNumber);
foreach($Letters as $Val) {
  if ($Val == $Show) WriteLI(strtoupper($Val), true);
  else WriteLI(strtoupper($Val), false);
}
echo "</ul></div>\n";

//Load Blocklist------------------------------------------------------
echo '<div class="row"><br />';

$TrackerBlockList = file('/etc/notrack/tracker-quick.list');

foreach ($TrackerBlockList as $Site) {
  if (($SingleLetter) || ($SingleNumber)) {
    $Char1 = substr($Site,0,1);
	
	if (SingleLetter) {
	  if ($Char1 == $Show) {
		 echo "$Site <br />\n";
	  }
	}
	if ($SingleNumber) {
	  if (in_array($Char1, $Numbers)) {
		 echo "$Site <br />\n";
	  }
	}
  }
  else {
    echo "$Site <br />\n";
  }
}?> 
</div>
</body>
</html>
