<?php
session_start();

// Initialize variables
$address = "na";
$city = "na";
$state = "na";
$recog = "no"; // User recognized?
$alert = "";// Alert to display at the top of the screen
$report_link = "<a href='#report_an_outage'>";
$init_function = "init_new()";

// Query database if cookie found
if(isset($_COOKIE['userid'])) {
	
	$recog = "yes";
	
	// get userid
	$userid = $_COOKIE['userid'];
	
	// get mysql cxn info
	require_once("dbinfo.php");
	
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

	// Pull data for given userid
	$query = "Select *, TIME_TO_SEC(TIMEDIFF(now(),info_dt))/60 as time_away, TIME_TO_SEC(TIMEDIFF(now(),powerout_dt))/60 as time_out from users where userid ='".$userid."'";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}

	// Create/update session variables
	$result_array = mysql_fetch_array($result);	
	$_SESSION['userid'] = $result_array['userid'];
	$_SESSION['address'] = $result_array['address'];
	$_SESSION['city'] = $result_array['city'];
	$_SESSION['state'] = $result_array['state'];
	$_SESSION['lat'] = $result_array['lat'];
	$_SESSION['lng'] = $result_array['lng'];
	$_SESSION['zipcode'] = $result_array['zipcode'];
	$_SESSION['powerout_dt'] = $result_array['powerout_dt'];
	$_SESSION['info_dt'] = $result_array['info_dt'];
	$_SESSION['email'] = $result_array['email'];
	$_SESSION['notifications'] = $result_array['notifications'];
	$_SESSION['time_away'] = $result_array['time_away'];
	$_SESSION['time_out'] = $result_array['time_out'];
	
	// Create local variables
	$address = $_SESSION['address'];
	$city = $_SESSION['city'];
	$state = $_SESSION['state'];
	$lat = $_SESSION['lat'];
	$lng = $_SESSION['lng'];
	$zipcode = $_SESSION['zipcode'];
	$time_away = $_SESSION['time_away'];
	$time_out = $_SESSION['time_out'];
	
	// Send user directly to info if power has been out for less than 24 hours
	if($time_out > 0 && $time_out < 1440 ) {
		$report_link = "<a href='info.php' rel='external'>";
	}
	
	// Check for active alerts
	$query = "SELECT * FROM alerts WHERE zipcode  ='".$zipcode."' and alert_exp_dt > now() order by alert_dt desc";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}
	$result_array = mysql_fetch_array($result);
	
	if($result_array['alert'] != null) {
		$msg = $result_array['alert'];
		$utility = $result_array['utility'];
		$alert_dt = $result_array['alert_dt'];
		$alert = "<div align='center'><font color='#FF0000'>Update from ".$utility." (".$alert_dt." GMT):"."<br />".$msg."</font></div><br />";
	} 
	
	// Switch init function so form is populated with user's stored info (stops geolocation)
	$init_function = "init_recog()";
	

}


?>

<!DOCTYPE html> 
<html> 

<head> 

	<link rel="shortcut icon" href="favicon.ico">
    <title>My Power Is Out!</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no"> 
	
    <!-- jquery libraries -->
    <link rel="stylesheet" href="jquery-mobile/jquery.mobile-1.0.min.css" />
    <script type="text/javascript" src="jquery-mobile/jquery-1.6.4.min.js"></script>
    <script type="text/javascript" src="jquery-mobile/jquery.mobile-1.0.min.js"></script>

    <!-- allows users to add app-like icon to to their home screen when they bookmark the site -->
    <link rel="apple-touch-icon" href="/images/icon_bulb.png" />
	<link rel="apple-touch-icon-precomposed" href="/images/icon_bulb.png" />
       
	<!-- javascript for geolocation, geocoding, address validation -->
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true"></script>


<script type="text/javascript">


// Generate random userid
function randomString() {
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var string_length = 8;
	var randomstring = '';
	for (var i=0; i<string_length; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		randomstring += chars.substring(rnum,rnum+1);
	}
	return randomstring;
}


function change_city () {
document.getElementById("city").value = "test";	
}

// Called if user IS recognized.  Updates address form
function init_recog () {
	document.getElementById("address").value = "<? echo $address ?>";
	document.getElementById("city").value = "<? echo $city ?>";
	document.getElementById("state").options[0].text = "<? echo $state ?>";
	document.getElementById("state").options[0].value = "<? echo $state ?>";
}

// Called if user is NOT recognized.  Calls Geolocation, reverse geocodes, updates address form
function init_new() {
	
	// Use W3C geolocation standard
	if (navigator.geolocation) {
		  navigator.geolocation.getCurrentPosition(success, error);
	} else {
	}  //geolocation not supported
	
	// Success: geolocation supported
	function success(position) {
		var lat = parseFloat(position.coords.latitude);
		var lng = parseFloat(position.coords.longitude);
		var latlng = new google.maps.LatLng(lat, lng);
		var geocoder = new google.maps.Geocoder()
		geocoder.geocode({'latLng': latlng}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				if (results[0]) {
					var geoResult = results[0];
					
					// pull data from geocode results
					var result_length = geoResult.address_components.length;
					for (var i = 0; i< result_length; i++) {
						// Street_number
						if (geoResult.address_components[i].types[0] == "street_number") {
							var street_number = geoResult.address_components[i].long_name;							
						}
						// Street
						if (geoResult.address_components[i].types[0] == "route") {
							var street = geoResult.address_components[i].long_name;
						}
						// City
						if (geoResult.address_components[i].types[0] == "locality") {
							var city = geoResult.address_components[i].long_name;
						}
						// State_long
						if (geoResult.address_components[i].types[0] == "administrative_area_level_1") {
							var state = geoResult.address_components[i].long_name;
						}
					}
					document.getElementById("address").value = street_number+" "+street;
					document.getElementById("city").value = city;
					document.getElementById("state").options[0].text = state;
					document.getElementById("state").options[0].value = state;
			  	} else {
				alert("No results found");
			  	}
			} else {
			  alert("Geocoder failed due to: " + status);
			}
		});
	}

	// Error: geolocation failed
	function error() {
	}	//alert("sorry, geolocation failed");
	
}

// Called when user clicks submit on address form
function submit_address() {
	
	// get data from form
	var address = document.getElementById("address").value;
	var city = document.getElementById("city").value;
	var state = document.getElementById("state").value;
	var user_input = address + ", " + city + ", " + state;
	
	// verify user has entered a valid city and state
	if(city == "") {
		alert("Please enter your city.");
		return false;
	}
	
	if(state == "") {
		alert("Please enter your state.");
		return false;
	}	
	
	// geocode user input; update form elements
	var geocoder = new google.maps.Geocoder()
	geocoder.geocode({'address': user_input}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			if (results[0]) {
				var geoResult = results[0];
				
				// get latitude
				var lat = geoResult.geometry.location.lat();  //Pa
				document.getElementById("lat").value = lat;
				
				// get longitude
				var lng = geoResult.geometry.location.lng();	//Qa
				document.getElementById("lng").value = lng;
				
				// get zip code
				var result_length = geoResult.address_components.length;
				for (var i = 0; i< result_length; i++) {
					if (geoResult.address_components[i].types[0] == "postal_code") {
						var zipcode = geoResult.address_components[i].long_name;
						document.getElementById("zipcode").value = zipcode;
					}
				}
				
				// check/set cookie
				checkCookie();
								
				// submit the form
				document.forms["address_form"].submit();
			
			} else {
				alert("Sorry, that address is not recognized.");
				return false;
			}		
		
		} else {
			alert("Sorry, that address is not recognized.");
			return false;
		}	
	});
}

//Cookie Functions
function checkCookie() {
	var userid=getCookie("userid");
	if (userid == null || userid == "") {
		var userid = randomString();
		document.getElementById("new_user").value = "yes";				
		setCookie("userid",userid,730);
	} 
	document.getElementById("userid").value = userid;
}

function getCookie(c_name) {
	var i,x,y,ARRcookies=document.cookie.split(";");
	for (i=0;i<ARRcookies.length;i++) {
		x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
	  	y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
	  	x=x.replace(/^\s+|\s+$/g,"");
	  	if (x==c_name) {
			return unescape(y);
		}
	 }
}
			 
function setCookie(c_name,value,exdays) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
	document.cookie=c_name + "=" + c_value;
	//alert("cookie set");
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

<body onload="<? echo $init_function ?>">
<!-- Main landing page.  -->
<div data-role="page" id="home" data-theme="c">
	<div data-role="header" data-theme="b">
		<h1>My Power is Out!</h1>
    </div>
	<div data-role="content">	
		
        <? echo $alert ?>
        <ul data-role="listview" data-inset="true">
			<li>
            	<? echo $report_link ?>
                <img src="images/icon_powerout.png" class="ui-li-icon" />
                <h3 >&nbsp;&nbsp;&nbsp;&nbsp Power Outage</h3>
                </a>          
            </li>
            <li>
            	<a href="#power_outage_tips" onClick="randdomString()">
                <img src="images/icon_flashlight.png" class="ui-li-icon"/>
                <h3>&nbsp;&nbsp;&nbsp;&nbsp Power Outage Tips</h3></a>
           	</li>
            <!--
            <li>
            	<a href="#" onClick = "change_city()">
                <img src="images/icon_danger.png" class="ui-li-icon"/>
                <h3>&nbsp;&nbsp;&nbsp;&nbsp Report Safety Issue</h3></a>
           	</li>
            -->
            <li>
            	<a href="#about_this_app">
                <img src="images/icon_bulb.png" class="ui-li-icon"/>
                <h3>&nbsp;&nbsp;&nbsp;&nbsp About This App</h3></a>
           </li>
          
		</ul>	
	</div>
    
    <script type="text/javascript" >
	
	</script>
    
</div>

<!-- Address Page -->  
<div data-role="page" id="report_an_outage" data-theme="c">
	<div data-role="header" data-theme="b">
        <a href="#home" data-icon="home">Home</a>
        <h1>Confirm address</h1>
	</div>    
    
    <div data-role="content">	
		<form name="address_form" action="info.php" method="post" data-ajax="false">

            <!-- Action -->
           	<div data-role="fieldcontain">
			    <fieldset data-role="controlgroup">
			    	<legend>I would like to:</legend>
			         	<input type="radio" name="select_action" id="radio-choice-1" value="report" checked="checked" />
			         	<label for="radio-choice-1">Report an outage</label>

			         	<input type="radio" name="select_action" id="radio-choice-2" value="info"  />
			         	<label for="radio-choice-2">Get information</label>
			    </fieldset>
			</div>
            
            <!-- Address -->
            <div data-role="fieldcontain">
            	<label for="address">Address:</label>
            	<input type="text" name="address" id="address" value=""  />
			</div>
            
            <!-- City --> 
            <div data-role="fieldcontain">
		    	<label for="city">City:</label>
		        <input type="text" name="city" id="city" value=""  />
			</div>
            
            <!-- State -->
            <div data-role="fieldcontain" >
				<label for="state" class="select">State:</label>
				<select name="state" id="state" data-theme="c" data-overlay-theme="d" data-native-menu="false">
					<option selected="selected" value="">Select State...</option>
                      <option value="Alabama">Alabama</option>
                      <option value="Alaska">Alaska</option>
                      <option value="Arizona">Arizona</option>
                      <option value="Arkansas">Arkansas</option>
                      <option value="California">California</option>
                      <option value="Colorado">Colorado</option>
                      <option value="Connecticut">Connecticut</option>
                      <option value="Delaware">Delaware</option>
                      <option value="Florida">Florida</option>
                      <option value="Georgia">Georgia</option>
                      <option value="Hawaii">Hawaii</option>
                      <option value="Idaho">Idaho</option>
                      <option value="Illinois">Illinois</option>
                      <option value="Indiana">Indiana</option>
                      <option value="Iowa">Iowa</option>
                      <option value="Kansas">Kansas</option>
                      <option value="Kentucky">Kentucky</option>
                      <option value="Louisiana">Louisiana</option>
                      <option value="Maine">Maine</option>
                      <option value="Maryland">Maryland</option>
                      <option value="Massachusetts">Massachusetts</option>
                      <option value="Michigan">Michigan</option>
                      <option value="Minnesota">Minnesota</option>
                      <option value="Mississippi">Mississippi</option>
                      <option value="Missouri">Missouri</option>
                      <option value="Montana">Montana</option>
                      <option value="Nebraska">Nebraska</option>
                      <option value="Nevada">Nevada</option>
                      <option value="New Hampshire">New Hampshire</option>
                      <option value="New Jersey">New Jersey</option>
                      <option value="New Mexico">New Mexico</option>
                      <option value="New York">New York</option>
                      <option value="North Carolina">North Carolina</option>
                      <option value="North Dakota">North Dakota</option>
                      <option value="Ohio">Ohio</option>
                      <option value="Oklahoma">Oklahoma</option>
                      <option value="Oregon">Oregon</option>
                      <option value="Pennsylvania">Pennsylvania</option>
                      <option value="Rhode Island">Rhode Island</option>
                      <option value="South Carolina">South Carolina</option>
                      <option value="South Dakota">South Dakota</option>
                      <option value="Tennessee">Tennessee</option>
                      <option value="Texas">Texas</option>
                      <option value="Utah">Utah</option>
                      <option value="Vermont">Vermont</option>
                      <option value="Virginia">Virginia</option>
                      <option value="Washington">Washington</option>
                      <option value="West Virginia">West Virginia</option>
                      <option value="Wisconsin">Wisconsin</option>
                      <option value="Wyoming">Wyoming</option>
					</select>
				</div>     
                
                <!-- Hidden fields: lat, lng, zipcode -->              	
                <input  type="hidden" id ="lat" name="lat" value=""/>
    			<input  type="hidden" id ="lng" name="lng" value=""/>
                <input  type="hidden" id ="zipcode" name= "zipcode" value=""/>
                <input  type="hidden" id ="userid" name= "userid" value=""/>
                <input  type="hidden" id ="new_user" name= "new_user" value=""/>
        </form>        
                <!-- Submit button -->
                <center>
                <button data-theme="e" data-inline="true" onclick="submit_address()">Submit</button> 
                </center>			
      

	</div>
</div>

<!-- Outage tips -->
<div data-role="page" id="power_outage_tips" data-theme="b">
    <div data-role="header" data-theme="b">
        <a href="#home" data-icon="home">Home</a>
        <h1>Outage Tips</h1>
	</div>
	<div data-role="content">	
		<h3> Power Outage Safety Tips:</h3>
        
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

<!-- 'About' information -->
<div data-role="page" id="about_this_app" data-theme="b">
    <div data-role="header" data-theme="b">
        <a href="#home" data-icon="home">Home</a>
        <h1>About this app</h1>
	</div>
	<div data-role="content">	
		
       <h3><img src="images/icon_bulb.png" hspace="4" align="absmiddle" />About this app</h3>
       <p>The goal of this app is to faciliate communication between electric utilities and their customers during power outages.  </p>
       <p>To report an issue or provide feedback please click <a href="mailto:webmaster@mypowerisout.net">here</a>.  </p>
        
       <h3><img src="images/icon_powerco.png" hspace="10" align="absmiddle" />Utility login</h3>
       Utility companies can login <a href="utility/dashboard.php" rel="external">here</a>.
        
        	
    </div>

</div>

<!-- Set cookie so user can be identified when they visit again -->
       



</body>
</html>