<?php function isCurrent($url){ 
 	$current=basename($_SERVER['PHP_SELF']);
	if ($current==$url)
		{
			echo "current";
		}
		else 
		{
			echo "link";
		}
 }
?>

<div class="nav">
  <div id="menu" class="menu">
    <ul>
      <li><a href="">[ <?php echo $page_name ?> ]</li></a>
      <li><a href="index.php" class="<?php echo isCurrent("index.php") ?>">Home</a></li>
      <li><a href="contact.php" class="<?php echo isCurrent("contact.php") ?>">Contact</a></li>
      <li><a href="portfolio.php" class="<?php echo isCurrent("portfolio.php") ?>">Portfolio</a></li>
    </ul>
    <br style="clear: left" />
  </div>
</div>
