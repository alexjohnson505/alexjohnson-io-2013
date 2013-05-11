<title>Take6Designs.com | Web Development</title>
<?php
include('functions.php');
$page_name = "Welcome";
$maincontent = 

print_header("Web Development", "Because we love <br /> the  ", " internet.") . 

'<a href="portfolio.php"><img src="images/checkoutourwork.jpg" width="404" height="170" alt="Check Out Our Previous Projects Below" style="float:right;"/></a>
<div class="options">
      <ul>
        <li><a href="#whoweare">learn more about us</a></li>
        <li><a href="portfolio.php">see our work</a></li>
        <li><a href="contact.php" id="next_step">take the next step  >></a></li>
      </ul>
    </div>
    <!--close Options -->
    

<br style="clear: left" />

<div class="gallery">
	<a href="portfolio.php"><img src="images/thumbnails/b2q_associates.png" alt="Portfolio Item" width="280" height="250" /></a>
	<a href="portfolio.php"><img src="images/thumbnails/texturereport.png" alt="Portfolio Item" width="280" height="250" /></a>
	<a href="portfolio.php"><img src="images/thumbnails/texturechannel.png" alt="Portfolio Item" width="280" height="250" /></a>
</div>

<br style="clear: left;" />


<div class="category_title">
	<h1>What We Do</h1>
</div>

<div class="textbox">
	<div class="col60" style="float:left;"><p>We use the right tools to get the job done. Whether that is PHP and MySQL for a video training website, Wordpress for a corporate blog, a DSLR for photography, or Adobe Creative suite for video and graphics, we take pride in our creations. I personally enjoy using my experience to bring an idea to life, bridging the gap between design and technical implementation.</p></div>
	<div class="col40" style="float:right;">
				<ul>
					<li>web development</li>
					<li>graphic design</li>
					<li>photography + video</li>
					<li>web hosting</li>
					<li>programming</li>
				</ul>
	</div>
</div>

	<div class="cleaner"></div>
	<a name="whoweare"></a>
	<div class="category_title"><h1>Who We Are</h1></div>
		<div class="textbox">
			<div class="col40" style="float:left;">
					<img src="images/group.png" width="264" height="163" alt="Who We Are" />
			</div>
			<div class="col60" style="float:right;"><p>My name is Alex. I am a Computer Science and Interactive Media major at Northeastern University. I am a freelance web developer, and computer enthusiast.  I love web development because it is the meeting point of tech and design. Programming, hosting, networking, and graphic design all come together to a medium of communication with unlimited possibilities. </p></div>
		</div>
	</div>
	</div>
	
	'
	;

include_once('template.php');
?>
