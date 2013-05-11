<title>Take6Designs.com | Web Development</title>

<?php
include('functions.php');

 
$page_name = "Programming";
$maincontent = print_header("PROGRAMMING", "Below are a few select <br /> ", "coding projects") . '
<div id="webportfolio">

<div class="portfolio_item">
<a href="images/fmapcode.png" rel="lightbox[portfolio]" name="fmap"><img src="images/thumbnails/fmap.png" alt="Iterating over an  FMap" width="280" height="250" /></a> 
<h3>Iterating over an FMap<K, V></h3>
<p>A programming challenge: Design the data class and Iterator that handles iterating over a parameterized FMap.     FMap<K,V> is an immutable abstract data type with parameterized values that represent     keys of type K to values of type V.  In addition, design FMapIterator which implements Iterator<K> and iterates over the keys of a given FMap. To download the source file (.java) right mouse click, and save the following link: <a href="files/FMap.java" style="text-decoration:underline;">Download Fmap<K,V> and Iterator source file</a></p>
<br />
</div>

<div class="portfolio_item">
<a href="images/phpcurrentcode.png" rel="lightbox[portfolio]" class="right" name="phpcurrent"><img src="images/thumbnails/phpcurrent.png" alt="TextureReport" width="280" height="250" /></a>
<h3>Using PHP and CSS to highlight current page</h3>
<p>Here is an interesting little solution I came up with. When looking at a website navigation menu, what is the best way to change the CSS so that the current page is labeled? There is a million solutions. However, I am using this little PHP snippet to check the current pages file name, against the link name. In the isCurrent function, the method takes in a string(name of the link), compares it to the current page via the basename($_SERVER[PHP_SELF]) function. If true, the function returns "current" as the class name, or "link" for the non-current class name. Result: the CSS class names reflect the current page. Just add CSS styles for .current</p>
<br />
</div>

<div class="portfolio_item">
<a href="images/tetris_in_drracket_scheme.jpg" rel="lightbox[portfolio]" name="tetris"><img src="images/thumbnails/tetris.png" alt="Tetris in Scheme" width="280" height="250" /></a> 
<h3>Tetris in Scheme</h3>
<p>Here is a funny little assignment from freshman year. Programming Tetris in Racket using DrRacket (dialect of scheme). A partner and I wrote this code as one of the final projects in an Introduction to Programming course. It is basic tetris . It has the blocks, row removal, rotation, templates, and way too many check-expects (requirements of the assignment). 

Right & Left to move the block, A & S to rotate.

Sorry about the indentation below. To download the source file (.rkt) right mouse click, and save the following link: <a href="files/tetris_in_drracket_scheme.rkt" style="text-decoration:underline;">Download Tetris in DrRacket</a></p>
<br />
</div>
</div>








';

include_once('template.php');
?>
