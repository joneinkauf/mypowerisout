<?php

// get info from form
$name = $_POST['name'];
$company_name = $_POST['company_name'];
$title = $_POST['title'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$subject = $_POST['subject'];

// send email
$to = "jon.einkauf@gmail.com";
$subject = "MPIO SIGN UP";
$body = $subject.", ".$name.", ".$company_name.", ".$title.", ".$email.", ".$phone;
mail($to, $subject, $body, "FROM: webmaster@mypowerisout.net");
 

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
  <title>Contact - Thanks</title>
  <meta name="description" content="" />
  <meta name="keywords" content="enter your keywords here" />
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
  <link rel="stylesheet" type="text/css" href="css/style.css" />
</head>

<body>
  <div id="container_header"><p>&nbsp;</p></div>
    <div id="main">
       <div id="header">
       <img src="../images/mpio_logo.png" /> 
       <div id="menubar">
          <ul id="nav">
            <li ><a href="dashboard.php">Dashboard</a></li>
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
		  <h1>Thank you</h1>
          <p>We will contact you shortly.</p>
          </div><!-- close div style --> 
          <br style="clear:both;" />
          
		</div><!-- close content_item -->
      </div><!-- close content -->
    </div><!-- site_content -->

      <div align="center"><font size="-2">website design by <a href="http://www.araynordesign.co.uk">ARaynorDesign</a></font></div>
    </div><!-- close main -->


<div id="container_footer">&nbsp;</div>

</body>
</html>