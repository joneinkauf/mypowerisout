<?php
require("dbinfo.php");

// Standard parsing function found online
function parseToXML($htmlStr) 
{ 
$xmlStr=str_replace('<','&lt;',$htmlStr); 
$xmlStr=str_replace('>','&gt;',$xmlStr); 
$xmlStr=str_replace('"','&quot;',$xmlStr); 
$xmlStr=str_replace("'",'&#39;',$xmlStr); 
$xmlStr=str_replace("&",'&amp;',$xmlStr); 
return $xmlStr; 
} 

// Opens a connection to a MySQL server
$connection=mysql_connect ($hostname,$username,$password);
if (!$connection) {
  die('Not connected : ' . mysql_error());
}

// Set the active MySQL database
$db_selected = mysql_select_db($database, $connection);
if (!$db_selected) {
  die ('Can\'t use db : ' . mysql_error());
}

// Select all the rows in the outage table.  Later add filter to select only outages within x miles of the user
$query = "SELECT * FROM outages";
$result = mysql_query($query);
if (!$result) {
  die('Invalid query: ' . mysql_error());
}

header("Content-type: text/xml");

// Start XML file, echo parent node
echo '<markers>';

// Iterate through the rows, printing XML nodes for each
while ($row = @mysql_fetch_assoc($result)){
  // ADD TO XML DOCUMENT NODE
  echo '<marker ';
  echo 'outageid="' . parseToXML($row['outageid']) . '" ';
  echo 'lat="' . parseToXML($row['lat']) . '" ';
  echo 'lng="' . $row['lng'] . '" ';
  echo 'status="' . $row['status'] . '" ';
  echo 'outage_dt="' . $row['outage_dt'] . '" ';
  echo '/>';
}

// End XML file
echo '</markers>';

?>