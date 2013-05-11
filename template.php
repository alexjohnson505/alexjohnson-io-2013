<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Take6Designs.com | Web Development</title>
<link rel="icon" type="image/x-icon" href="http://take6designs.com/favicon.ico" />
<link href="styles.css" rel="stylesheet" type="text/css" />
<link href="css/lightbox.css" rel="stylesheet" />
<link href='http://fonts.googleapis.com/css?family=Oxygen' rel='stylesheet' type='text/css'>
<link href='http://fonts.googleapis.com/css?family=Open+Sans:600,400' rel='stylesheet' type='text/css'>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
<script type="text/javascript" src="js/lightbox/lightbox.js"></script>
<script type="text/javascript" src="js/ddsmoothmenu.js">

</script>
<script type="text/javascript">
ddsmoothmenu.init({
	mainmenuid: "smoothmenu1",
	orientation: 'h',
	classname: 'ddsmoothmenu',
	contentsource: "markup" 
})
</script>
	<script type="text/javascript">
			
			$(document).ready(function() {
				$('.filter li a').click(function() {
					$('.filter li').removeClass('selected');
					$(this).parent('li').addClass('selected');					
					thisItem 	= $(this).attr('rel');
					if(thisItem != "all") {
						$('.item li[rel='+thisItem+']').stop()
																.animate({'width' : '290px', 
																			 'opacity' : 1, 
																			 'marginRight' : '.5em', 
																			 'marginLeft' : '.5em'
																			});								
						$('.item li[rel!='+thisItem+']').stop()
																.animate({'width' : 0, 
																			 'opacity' : 0,
																			 'marginRight' : 0, 
																			 'marginLeft' : 0
																			});
					} else {
						$('.item li').stop()
										.animate({'opacity' : 1, 
													 'width' : '290px', 
													 'marginRight' : '.5em', 
													 'marginLeft' : '.5em'
													});
					}
				})
				
				$('.item li img').animate({'opacity' : .8}).hover(function() {
					$(this).animate({'opacity' : 1});
				}, function() {
					$(this).animate({'opacity' : .8});
				});
				
			});
			
		</script>
<?php $css ?>

</head>
<body>
<?php include 'menu.php'; ?>
<div class="container">
  <div class="content"> <?php echo $maincontent; ?> </div>
  <!--close Content -->
</div>
<!--close container -->
<?php include 'footer.php'; ?>
</body>
</html>
