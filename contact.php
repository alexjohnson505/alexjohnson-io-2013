<title>Take6Designs.com | Web Development</title>

<?php
include('functions.php');
$page_name = "Contact Us";
$maincontent = 
print_header("Contact Us", "What can we do <br /> for ", "you") . 
'<img src="images/sayhello.png" width="278" height="170" alt="Use the form below to contact us" style="float:right;"/>
<div class="options">
      <ul>
        <li><a href="#">alexjohnson505@gmail.com</a> </li>
        <li><p style="font-size:22px; display: inline; padding: 0 30px;">or</p><a href="#" id="next_step">Drop us a message:</a></li>
      </ul>
    </div>
    <!--close Options -->
    <br style="clear: left" />

<div id="stylized" class="myform">
  <form id="form1" id="form1" action="mail.php" method="POST">
  <label>Name <span class="small">Add your name</span> </label>
  <input type="text" name="name">
  <label>Email <span class="small">Enter a Valid Email</span> </label>
  <input type="text" name="email">
  <label>Message <span class="small">How may we help?</span> </label>
  <textarea name="message" rows="6" cols="25" id="messageentry"></textarea>

  <button type="submit" value="Send" style="margin-top:15px;">Submit</button>
  <div class="spacer"></div>
  </form>
</div>
<!-- end of form class -->';

include_once('template.php');
?>
