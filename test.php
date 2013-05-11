<?php



$items = array( 
				array("B2Q Associates.com", "webdev", "b2q_associates"),
				array("Texture Report.com", "webdev", "texturereport"),
				array("Texture Channel.com", "webdev", "texturechannel",),
				array("Texture Technologies.com", "webdev", "texturetechnologies"),
				array("Quoth the Raver.com", "webdev", "quoththeraver"),
				array("Brett Walfish.com", "webdev", "brettwalfish"),
				array("Salem State University", "webdev", "salemstateuniversity"),
             ); 
 
 
 
$last = count($items) - 1;

foreach ($items as $current)
{
	echo $current[0];

	echo $current[1];
	echo $current[2];

}


?>