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
		echo $data;
	}
	pg_close($conn);

}

?>
