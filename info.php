<?php
session_start();


// Redirect users with no location info
if(!isset($_POST['userid']) && !isset($_SESSION['userid']) && !isset($_COOKIE['userid'])){
	header('Location: index.php');	
}

// Connect to database
require_once("dbinfo.php");
$connection=mysql_connect ($hostname,$username,$password);
if (!$connection) {
	die('Not connected : ' . mysql_error());
}
$db_selected = mysql_select_db($database, $connection);
if (!$db_selected) {
	die ('Can\'t use db : ' . mysql_error());
}

// Users who came from address_form
if(isset($_POST['userid'])) {
	$path = "POST"; //debugging
	
	$userid = mysql_real_escape_string($_POST['userid']);
	$address = mysql_real_escape_string($_POST['address']);
	$city = mysql_real_escape_string($_POST['city']);
	$state = mysql_real_escape_string($_POST['state']);
	$lat = mysql_real_escape_string($_POST['lat']);
	$lng = mysql_real_escape_string($_POST['lng']);
	$zipcode = mysql_real_escape_string($_POST['zipcode']);
	$new_user = mysql_real_escape_string($_POST['new_user']);
	$select_action = mysql_real_escape_string($_POST['select_action']);
}

// Known users who skipped address_form
elseif(isset($_SESSION['userid'])) {
	$path = "SESSION"; //debugging
	
	$userid = mysql_real_escape_string($_SESSION['userid']);
	$address = mysql_real_escape_string($_SESSION['address']);
	$city = mysql_real_escape_string($_SESSION['city']);
	$state = mysql_real_escape_string($_SESSION['state']);
	$lat = mysql_real_escape_string($_SESSION['lat']);
	$lng = mysql_real_escape_string($_SESSION['lng']);
	$zipcode = mysql_real_escape_string($_SESSION['zipcode']);
	$new_user = "no";
	$select_action = "info";
}

elseif(isset($_COOKIE['userid'])) {
	$path = "COOKIE"; //debugging
	
	// get the userid
	$userid = mysql_real_escape_string($_COOKIE['userid']);

	// Pull data for given userid
	$query = "Select * from users where userid ='".$userid."'";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	$result_array = mysql_fetch_array($result);
	
	if($result_array['userid'] != null) {
		$address = $result_array['address'];
		$city = $result_array['city'];
		$state = $result_array['state'];
		$lat = $result_array['lat'];
		$lng = $result_array['lng'];
		$zipcode = $result_array['zipcode'];
	}
	
	$new_user = "no";
	$select_action = "info";
}



// Update outage table if user selected 'report an outage'
if ($select_action == "report") {
	$query = "INSERT into outages (address, city, state, lat, lng, zipcode, outage_dt, status, userid) VALUES ('$address', '$city', '$state', $lat, $lng, '$zipcode', now(), '','$userid')";	
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}	
}


// Update users table (new user)
if ($new_user == "yes") {
	$powerout_dt = ($select_action == "report") ? "now()" : "null";
	$query = "INSERT into users (userid, address, city, state, lat, lng, zipcode, powerout_dt, info_dt, email, notifications) VALUES ('$userid', '$address', '$city', '$state', $lat, $lng, '$zipcode', $powerout_dt, now(), '', '')";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}
}

// Update users table (returning user)
if ($new_user != "yes") {
	$powerout_dt = ($select_action == "report") ? "now()" : "powerout_dt";
	$query = "update users set address = '$address', city='$city' , state='$state', lat=$lat, lng=$lng, zipcode='$zipcode', powerout_dt = $powerout_dt, info_dt = now() where userid = '$userid'";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}
}

// Determine number of outages for user's STATE
$query = "select count(*) from outages where state='".$state."'";
$result = mysql_query($query);
if (!$result) {
	die('Invalid query: ' . mysql_error());
}
$state_result = mysql_result($result,0);

// Determine number of outages for user's CITY
$query = "select count(*) from outages where city='".$city."'";
$result = mysql_query($query);
if (!$result) {
	die('Invalid query: ' . mysql_error());
}
$city_result = mysql_result($result,0);

// Check for active alerts
$query = "SELECT utility, alert, TIME_TO_SEC(TIMEDIFF(now(),alert_dt))/60 as time_since_alert FROM alerts WHERE zipcode  ='".$zipcode."' and alert_exp_dt > now() order by alert_dt desc limit 0,5";
$result = mysql_query($query);
if (!$result) {
	die('Invalid query: ' . mysql_error());
}
$alert_result = $result;

/*
 
CODE FOR DEBUGGING:

echo "POST:"; print_r($_POST); 
echo "SESSION:"; print_r($_SESSION);
echo "COOKIE:"; print_r($_COOKIE);
echo $path;  

*/

?>

<!DOCTYPE html >

<head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Current Updates</title>

    <!-- jquery libraries -->
    <link rel="stylesheet" href="jquery-mobile/jquery.mobile-1.0.min.css" />
    <script type="text/javascript" src="jquery-mobile/jquery-1.6.4.min.js"></script>
    <script type="text/javascript" src="jquery-mobile/jquery.mobile-1.0.min.js"></script>
   
    <!-- google maps api -->   
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>
    
    <!-- Query database to create list of markers, create+populate map.  Code based on Google's API documentation -->
	<script type="text/javascript">    
	
		//<![CDATA[
		
		function load() {
			var map = new google.maps.Map(document.getElementById("map_canvas"), {
				center: new google.maps.LatLng(<? echo $lat ?>,<? echo $lng ?>),
				zoom: 13,
				mapTypeId: 'roadmap'
			});
			var infoWindow = new google.maps.InfoWindow;
			var image = 'powerouticon.png';
		
			// Change this depending on the name of your PHP file
			downloadUrl("sql_marker_xml.php", function(data) {
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
	function hide_map() {
		if(document.getElementById("map_canvas").style.height != "0px") {
			document.getElementById("map_canvas").style.height = "0px";
			document.getElementById("map_button").innerHTML="Show Map";
		}
		else {
			document.getElementById("map_canvas").style.height = .4*screen.availHeight+"px";
			document.getElementById("map_button").innerHTML="Hide Map";
		}
	}
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

<body onload="load()">

<!-- Main page -->
<div data-role="page" id="main" data-theme="c">
	
    <div data-role="header" data-theme="b">
		<a href="index.php" rel="external" data-icon="home">Home</a>
        <h1>Current Updates</h1>
    </div>
    
	<div data-role="content">	       
        
        <h4>Outage summary</h4>
        <ul>
        	<li>There are <em><? echo $city_result ?></em> known outages in <em><? echo strtoupper($city) ?></em> </li>
        	<li>There are <em><? echo $state_result ?></em> known outages in <em><? echo strtoupper($state) ?></em> </li>
        </ul>

		<h4>Status update </h4>
        <? 
		$alert_count = 0;
		echo "<ul>";
		while ($row = mysql_fetch_assoc($alert_result)) {
			$msg = $row['alert'];
			$utility = $row['utility'];
			$time = $row['time_since_alert'];
			if ($time < 60) {
				$time_elapsed = round($time)." minutes ago";	
			}
			elseif ($time < 120) {
				$time_elapsed = "1 hour and ".round($time-60)." minutes ago";	
			} else {
				$time_elapsed = floor($time/60)." hours and ".round($time%60)." minutes ago";
			}
			
			$alert = "<li>Update from ".$utility." (".$time_elapsed."): ".$msg."</li>";
			echo $alert;
			$alert_count += 1;
		}
		if($alert_count == 0) {
			$alert = "<li>There are currently no updates for your area</li>"; 
			echo $alert;		
		}
		echo "</ul>";
		?>

		<h4>Map of local outages 
        <small><a id="map_button" href="#" onclick="hide_map()"> (Hide Map)</a></small></h4>

        <div id="map_canvas" style="height:200px"><!-- map loads here... --></div>

		<script type="text/javascript"> document.getElementById("map_canvas").style.height = .4*screen.availHeight+"px";</script>
        
        <h4>Weather update </h4>
        Get the latest <a href="http://www.weather.com/weather/today/<? echo $zipcode ?>" target="_blank"> weather forecast</a>
             
        <h4>Update Twitter/Facebook </h4>
        Coming soon...
                
        <h4>Power Outage Tips</h4>
        <ul>
        
<li>Take Control in Restoring Power to Your Home: Electrical fires sometimes occur when there is a power surge upon restoration of electrical service to the home. Turn off all electrical appliances and devices that were on before the power went off, including television sets, washers, dryers, space heaters, and lighting. Leave one lamp on so you know when the power is restored. </li> <br/>
 
<li>Do Not Use Candles or Camping Lanterns: Flashlights are the safest form of alternate lighting to use. Candles are frequently forgotten, and when they burn down or if they are placed too close to combustibles, they can cause a fire. Also, candles invite child fire play. When you're not looking, a child may play with a candle and cause a fire or get burned. Camping lanterns are designed for use in very well ventilated areas only. They produce large amounts of Carbon Monoxide (CO), which is an odorless, tasteless gas that kills quickly and silently. If you are unsure whether a gas-fueled water heater or furnace is working, use a flashlight to look for the pilot light. Some people have been injured or killed while using a candle to check a gas appliance.</li><br/>
 
<li>Be Cautious With Portable and Space Heaters: Place heaters at least three feet away from anything combustible, including wallpaper, bedding, clothing, pets, and people. Never leave portable or other space heaters operating when you are not in the room or when you go to bed. Don't leave children or pets unattended with space heaters and be sure everyone knows that drying wet mittens or other clothing over space heaters is a fire danger and should not be done.</li><br/>
 
<li>Be Very Cautious When Using Alternate Heating Devices: Be sure a wood or coal stove or liquid fuel heater bears the label of a recognized testing laboratory and meets local fire codes. Follow manufacturers' recommendations for proper use and maintenance. Follow the same safety rules for wood stoves as you would for space heaters. Burn only wood, and be sure the wood stove is placed on an approved stove board to protect the floor from heat and hot coals.</li><br/>
 
<li>Refuel Portable Liquid Fuel Heaters Carefully: Let the heater completely cool off before refueling. Refuel it outdoors, following manufacturer's recommendations. Do not refuel a portable heater while it is operating or if it is hot!</li><br/>
 
<li>Never Using Cooking Equipment For Heat: Stoves and ovens are designed for cooking, not heating a home. Fires and deaths have occurred in winter months from people using cooking equipment to heat a home. This is a dangerous fire hazard, and should not be done.</li><br/>
 
<li>Do Not Open the Refrigerator or Freezer: Perishable foods should not be held above 40 degrees for more than 2 hours. Tell your little ones not to open the door. An unopened refrigerator will keep foods cold enough for a couple of hours at least. A freezer that is half full will hold up for 24 hours and a full freezer for 48 hours.</li><br/>
 
<li>Pack a Cooler: If it looks like the power outage will be for more than 2 to 4 hours, pack refrigerated milk, dairy products, meats, fish, poultry, eggs, gravy, stuffing and leftovers into a cooler surrounded by ice. If it looks like the power outage will be prolonged, prepare a cooler with ice for your freezer items.</li><br/>
 
<li>Eat Shelf-stable Foods: Shelf-stable foods such as canned goods and powdered or boxed milk should be safe to eat. These can be eaten cold or heated on the grill.</li><br/>
 
<li>Use a Food Thermometer: Check the internal temperature of the food in your refrigerator with a quick response thermometer. A liquid such as milk or juice is easy to check. Spot check other items like steaks or leftovers also. If the internal temperature is about 40 degrees, it is best to throw it out. If the food in the freezer is not above 40 degrees and there are still ice crystals, you can refreeze.	</li><br/>

</ul>

These tips were prepared by the <a href="http://www.seattleredcross.org/article.aspx?a=4574">Seattle Red Cross.</a>
        	
	</div>
</div>		
</body>
</html>