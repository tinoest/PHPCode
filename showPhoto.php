<?php

set_time_limit (0);
if(array_key_exists('filename',$_GET) && !empty($_GET['filename'])) { 
	$filename = $_GET['filename'];
	$conn			= pg_connect("dbname=database user=user password=pass");
	$img			= '';
	$res      = pg_query($conn,'SET bytea_output = "escape";');
	$sql      = "SELECT image , filename FROM picture WHERE filename = '$filename'";
	$res      = pg_query($conn, $sql);
	if(!$res) {
		// Something failed
	} elseif(pg_num_rows($res) > 0) {
		while($image = pg_fetch_row($res)){
			$data			= pg_unescape_bytea($image[0]);
		}
		header('Content-Type: image/jpeg');
		resize_photo($data);
	}
	pg_close($conn);

}

function resize_photo($photo) {{{

	$width	= 480;
	$height = 640;

	// Get new dimensions
	list($width_orig, $height_orig) = getimagesizefromstring($photo);

	$ratio_orig = $width_orig/$height_orig;

	if ($width/$height > $ratio_orig) {
		$width = $height*$ratio_orig;
	} else {
		$height = $width/$ratio_orig;
	}

	// Resample
	$image_p	= imagecreatetruecolor($width, $height);
	$image		= imagecreatefromstring($photo);
	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

	// Output
	imagejpeg($image_p, NULL, 100); 

}}}


?>
