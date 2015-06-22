<?php
exit;
$dir			= '/Library/LightTPD/Documents/img';
$conn			= pg_connect("dbname=database user=user password=pass");
$res      = pg_query($conn,'SET bytea_output = "escape";');
$files		= array_values(array_diff(scandir($dir), array('..', '.')));
for($i=0;$i<sizeof($files);$i++) {
	if(strpos($files[$i],'.jpg') !== FALSE) { 
		$filename = $files[$i];
		echo($filename.PHP_EOL);
		continue;
		$file     = file_get_contents($filename);
		$sql      = "SELECT image FROM picture WHERE filename = '$filename'";
		$res      = pg_query($conn, $sql);
		if(!$res) {
			// Something failed
		} elseif(pg_num_rows($res) != 1) {
			$dat    = pg_escape_bytea($conn, $file);
			$sql    = "INSERT INTO picture ( image , filename ) VALUES ( '{$dat}' , '$filename')";
			$ret    = pg_query($conn,$sql) or die(pg_last_error($conn));
		}
	}
}
pg_close($conn);



?>
