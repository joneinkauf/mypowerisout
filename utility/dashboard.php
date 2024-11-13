<?php
session_start();


// bounce to index if user is not signed in
if(!isset($_SESSION['utility'])) {
	header('Location: index.php');
}


// get session variables
$utility = $_SESSION['utility'];
$center_lat = $_SESSION['center_lat'];
$center_lng = $_SESSION['center_lng'];

// get mysql cxn info
require_once("../dbinfo.php");

// Opens a connection to MySQL server
$connection=mysql_connect ($hostname,$username,$password);
if (!$connection) {
die('Not connected : ' . mysql_error());
}

// Set the active MySQL database
$db_selected = mysql_select_db($database, $connection);
if (!$db_selected) {
die ('Can\'t use db : ' . mysql_error());
}

// set alerts
if(isset($_POST['alert'])){
	$zipcode_array = $_POST['zipcode_array'];
	$alert = $_POST['alert'];
	foreach ($zipcode_array as $zipcode) {
		$query = 
		"Insert into alerts (zipcode, alert, alert_dt, alert_exp_dt, utility) 
		values ('".$zipcode."', '".$alert."', now(), date_add(now(), interval 6 hour), '".$utility."')";
		$result = mysql_query($query);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
	}	
}

// Pull cities for list box
$query = "Select city, zipcode from places where utility = '".$utility."' order by city, zipcode";
$result = mysql_query($query);
if (!$result) {
die('Invalid query: ' . mysql_error());
}

$city_listbox = $result;

// Total outages
$query = "Select count(*) as total_outages from outages a inner join places b on a.zipcode = b.zipcode where utility = '".$utility."'";
$result = mysql_query($query);
if (!$result) {
die('Invalid query: ' . mysql_error());
}

$total_outages = mysql_result($result,0);


// Outages by city
$query = "Select a.city, count(*) as outages from outages a inner join places b on a.zipcode = b.zipcode where b.utility = '".$utility."' group by a.city order by count(*) desc limit 0,10";
$result = mysql_query($query);
if (!$result) {
die('Invalid query: ' . mysql_error());
}
$city_outages = $result;






?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>


<!--


-->


  <title>Utility Dashboard</title>
  <meta name="description" content="" />
  <meta name="keywords" content="enter your keywords here" />
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
  <link rel="stylesheet" type="text/css" href="css/style.css" />
  
      <!-- google maps api -->   
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>
    
    <!-- Query database to create list of markers, create+populate map.  Code based on Google's API documentation -->
	<script type="text/javascript">    
	
		//<![CDATA[
		
		function load() {
			var map = new google.maps.Map(document.getElementById("map_canvas"), {
				center: new google.maps.LatLng(<? echo $center_lat.",".$center_lng ?>),
				zoom: 8,
				mapTypeId: 'roadmap'
			});
			var infoWindow = new google.maps.InfoWindow;
			var image = '../powerouticon.png';
		
			// Change this depending on the name of your PHP file
			downloadUrl("../sql_marker_xml.php", function(data) {
				var xml = data.responseXML;
				var markers = xml.documentElement.getElementsByTagName("marker");
				for (var i = 0; i < markers.length; i++) {
					var name = markers[i].getAttribute("outage_dt");
					var point = new google.maps.LatLng(
						parseFloat(markers[i].getAttribute("lat")),
						parseFloat(markers[i].getAttribute("lng")));
					var html = "Reported on:<br>" + name + " GMT<br/>";
					var marker = new google.maps.Marker({
						map: map,
						position: point,
						icon: image,
					});
					bindInfoWindow(marker, map, infoWindow, html);
				}
			});
			
		}
				
		function bindInfoWindow(marker, map, infoWindow, html) {
			google.maps.event.addListener(marker, 'click', function() {
				infoWindow.setContent(html);
				infoWindow.open(map, marker);
			});
		}
		
		function downloadUrl(url, callback) {
			var request = window.ActiveXObject ?
			new ActiveXObject('Microsoft.XMLHTTP') :
			new XMLHttpRequest;
		
			request.onreadystatechange = function() {
			if (request.readyState == 4) {
				request.onreadystatechange = doNothing;
				callback(request, request.status);
			}
			};
		
			request.open('GET', url, true);
			request.send(null);
		}
		
		function doNothing() {}
		
		//]]>
		
		</script>
  
  
  
  <script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-28336634-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
  
  
</head>

<body onLoad="load()">
  <div id="container_header"><p>&nbsp;</p></div>
    <div id="main">
       <div id="header">

        <table>
        <td><img src="../images/mpio_logo.png" /> </td>
        <td style="vertical-align:center">| <? echo $utility ?></td>
        </table>

       <div id="menubar">
          <ul id="nav">
            <li><a href="logout.php">Log Out</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="about.html">About</a></li>
            <li class="last"><a href="contact.html">Contact</a></li>
          </ul>
        </div> <!--close menubar -->
        <div id="banner"></div>
   	  </div><!-- close header -->
    <div id="site_content">
      <div id="content">
        <div class="clear"></div>
        <div class="content_item">
          <div style="width:600px; float:left;">
              <div>
                  <h1>Map of local outages</h1>
                  <div id="map_canvas" style="height:300px"><!-- map loads here... --></div>  
              </div>    
              <div>
                  <h1>Manage alerts</h1>
                  <FORM action="dashboard.php" method="post">
                	<div style="width:160px; float:left;"><p>City (Zip)</p></div>
                	<div style="width:440px; float:left;"><p>

					<?
                    echo '<select name="zipcode_array[]" multiple="multiple" size="8" >';
                    while ($row=mysql_fetch_assoc($city_listbox)) {
                      $city = ucfirst($row['city']);				  
                      $zip = $row['zipcode'];
                      echo "<option value='$zip'>$city ($zip)</option>";
                    }
                    echo '</select>';
                    ?>

                	</p></div>
                    <div style="width:160px; float:left;"><p>Alert</p></div>
                	<div style="width:440px; float:right;"><p><textarea class="contact textarea" rows="4" cols="50" name="alert"></textarea></p></div>
		  			<div style="width:440px; float:right;"><p style="padding-top: 15px"><input class="submit" type="submit" name="submit" value="Send Alert" /></p></div>
          		</FORM>
           
          
          <br style="clear:both;" />
          
		</div>
      </div>
    <div style="width:250px; float:left; margin-left:20px;">
            <div style="width:220px; float:left;"><h1>Outage Summary</h1></div>
              <br style="clear:both;" />			
              <p>Total Outages: <? echo $total_outages ?></p>
              <p>Outages by City: </p>
              <ul>
                  <?php while ($row=mysql_fetch_assoc($city_outages)) {
                      $city = ucfirst($row['city']);
                      $outages = $row['outages'];
                      echo "<li>".$city.": ".$outages."</li>";
                    }
					?>
              </ul>
            
	      </div><br style="clear:both;" />
          
		</div>
      </div>
    </div>

      <font size="-2"><center>website design by <a href="http://www.araynordesign.co.uk">ARaynorDesign</a></center></font></div>
   

  <div id="container_footer">&nbsp;</div>

</body>
</html>

                  
                  
                  
                  
                  
                 