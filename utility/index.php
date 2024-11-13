<?php
session_start();

if(isset($_SESSION['utility']))
    unset($_SESSION['utility']);

// initialize password message
$password_msg = "";

// stop php if no form submission
if(isset($_POST['utility'])){
	
	// connect to the database
	require_once("../dbinfo.php");
	$connection=mysql_connect ($hostname,$username,$password);
	if (!$connection) {
		die('Not connected : ' . mysql_error());
	}
	$db_selected = mysql_select_db($database, $connection);
	if (!$db_selected) {
		die ('Can\'t use db : ' . mysql_error());
	}
	
	// Get data from form
	$utility = mysql_real_escape_string($_POST['utility']);
	$utility_password = mysql_real_escape_string($_POST['password']);
	
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
	
	
	// Pull data for given userid
	$query = "Select * from utilities where utility ='".$utility."' and password = '".$utility_password."'";
	$result = mysql_query($query);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}
	
	// Check username/password
	$result_array = mysql_fetch_array($result);
	
	if($result_array['utility'] != null) {
		$_SESSION['utility'] = $result_array['utility'];
		$_SESSION['center_lat'] = $result_array['center_lat'];
		$_SESSION['center_lng'] = $result_array['center_lng'];
		$_SESSION['permissions'] = $result_array['permissions'];
		header('Location: dashboard.php');	
	} else {
		$password_msg = "<div> <font color='#FF0000'>Invalid password.  Please try again.</font><p /></div> <!--error msg -->";	
	}
}

	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
  <title>Log In</title>
  <meta name="description" content="" />
  <meta name="keywords" content="enter your keywords here" />
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
  <link rel="stylesheet" type="text/css" href="css/style.css" />

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




<body>
  <div id="container_header"><p>&nbsp;</p></div>
    <div id="main">
       <div id="header">
       <img src="../images/mpio_logo.png" /> 
       <div id="menubar">
          <ul id="nav">
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
            <h1>Please log in</h1>
            <? echo $password_msg ?>
            <FORM action="index.php" method="post">
            <div style="width:160px; float:left; height:50px">Company Name</div>
            <div style="width:440px; float:right; height:50px;"><input class="contact" type="text" name="utility" value="" /></div>
            
            <div style="width:160px; float:left; height:50px;">Password</div>
            <div style="width:440px; float:right; height:50px;"><input class="contact" type="text" name="password" value="" /></div>
            
            <div style="width:440px; float:right; height:50px;"><input class="submit" type="submit" name="submit" value="Submit" /></div>
            <div style="width:440px; float:left; height:50px">Do you need to <a href="contact.html">sign up?</a></div>
            </FORM>
          </div>
        
          <br style="clear:both;" />
          
		</div><!-- close content_item -->
      </div><!-- content -->
    </div><!-- site_content -->

      <div align="center"><font size="-2">website design by <a href="http://www.araynordesign.co.uk">ARaynorDesign</a></font></div>
    </div><!-- close main -->


<div id="container_footer">&nbsp;</div>

</body>
</html>
