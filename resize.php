<?php

if(isset($_GET['i'])) { 
	$filename = $_GET['i'];
} else {
	echo "No image specified";
	exit;
}


if(isset($_GET['w'])) { 
	$width = $_GET['w'];
} else {
	$width = 600;
}

if(isset($_GET['h'])) { 
	$height = $_GET['h'];
} else {
	$height = 800;
}
// Content type
//header('Content-Type: image/jpeg');

// Get new dimensions
list($width_orig, $height_orig) = getimagesize($filename);

$ratio_orig = $width_orig/$height_orig;

if ($width/$height > $ratio_orig) {
   $width = $height*$ratio_orig;
} else {
   $height = $width/$ratio_orig;
}

// Resample
$image_p	= imagecreatetruecolor($width, $height);
$image		= imagecreatefromjpeg($filename);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

// Output
if( imagejpeg($image_p, $filename, 100) === TRUE) {
	echo "Resize success";
} else {
	echo "Resize failure";
}

?>
