<title>Take6Designs.com | Web Development</title>
<?php
include('functions.php');
$items = array( 
				array("B2Q Associates.com", "webdev", "b2q_associates"),
				array("Texture Report.com", "webdev", "texturereport"),
				array("Texture Channel.com", "webdev", "texturechannel",),
				array("Texture Technologies.com", "webdev", "texturetechnologies"),
				array("Iterating/parameterizing", "programming", "fmap"),
				array("Photography: Macro", "photography", "macro"),				
				array("Quoth the Raver.com", "webdev", "quoththeraver"),
				array("Photography: Fire", "photography", "fire"),
				array("Tetris with Scheme", "programming", "tetris"),
				array("Brett Walfish.com", "webdev", "brettwalfish"),
				array("Salem State University", "webdev", "salemstateuniversity"),
				array("Photography: Scenery", "photography", "scenery"),
				array("Current Link Highlighting", "programming", "phpcurrent"),
				array("Video", "video", "inprogress"),
				array("Tutorials", "tuts", "inprogress"),
             ); 
 $page_name = "Projects and Experience";
$maincontent = print_header("EXPERIENCE", "Below are a few of our <br /> most", "recent projects") . '
    <br style="clear: left" />
	
<div id="portfolio">
	<ul class="filter">
		<li class="selected"><a href="#" rel="all">All</a></li>
		<li><a href="#" rel="webdev">Web Development</a></li>
		<li><a href="#" rel="photography">Photography</a></li>
		<li><a href="#" rel="programming">Programming</a></li>
		<li><a href="#" rel="video">Video</a></li>
		<li><a href="#" rel="tuts">Tutorials</a></li>
	</ul>
	
	<ul class="item">' . print_thumbs($items) . '</ul></div><div class="cleaner"></div>';
include_once('template.php');
?>