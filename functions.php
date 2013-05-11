<?php 

//name
//category
//tag (tag.png, category.php#tag)


 function print_thumbs($items){
//	foreach ($items as $current){
	$size = count($items);
	$acc = "";
	for($i = 0; $i < $size; $i++){
		$acc = $acc . '<li rel="' . $items[$i][1] . '"><div>' . '<a href="' . $items[$i][1] . '.php#' .  $items[$i][2] . '">' . '<img src="images/thumbnails/' . $items[$i][2] . '.png" alt="Portfolio Item" width="280" height="250" />' . '<div class="thumbnail-title">' . '<h2>' . $items[$i][0] . '</h2></div></a></div></li>';
	}
	return $acc;
	};	
	
	//	foreach ($items as $current){
//		return $current[1];
//	}

 function print_header($title, $sub_title1, $sub_title2){
	 return 	 '<div class="header">' . 
	'<h1>' . $title . '</h1>' . 
	'<div class="subtitle"><h2><span style="color:#1a91aa;">' . $sub_title1 . '</span><span style="color:#ba372f;">' . $sub_title2 . 
	'</span></h2></div></div> <!-- end header -->';
 }
 
	//$items[$i][0] . $items[$i][1] . $items[$i][2];
//		return '<li rel="' . $current[1] . '"><div>' . '<a href="' . $current[1] . '.php#' .  $current[2] . '">' . '<img src="images/thumbnails/' . $current[2] . '.png" alt="Portfolio Item" width="280" height="250" />' . '<div class="thumbnail-title">' . '<h2>' . $current[0] . '</h2></div></a></div></li>';


?>