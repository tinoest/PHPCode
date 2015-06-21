<?php 

	if(!array_key_exists('image', $_POST)) {

?>
<!DOCTYPE HTML PUBLIC 
               "-//W3C//DTD HTML 4.0 Transitional//EN"
               "http://www.w3.org/TR/html4/loose.dtd">
    <html>
    <head>
      <title>Upload an Image File</title>
    </head>
    <body bgcolor="white">
    <form method="post" action="databaseStore.php" enctype="multipart/form-data">
    <h1>Upload an Image File</h1> 
    <h3>Please fill in the details below to upload your file. 
    Fields shown in <font color="red">red</font> are mandatory.</h3>
    <table>
    <col span="1" align="right">

    <tr>
       <td><font color="green">Short description:</font></td>
       <td><input type="text" name="image" size=50></td>
    </tr>

    <tr>    
       <td><font color="red">File:</font></td>
       <td><input name="userfile" type="file"></td>
    </tr>

    <tr>
          <td><input type="submit" value="Submit"></td>
    </tr>
    </table>
    <input type="hidden" name="MAX_FILE_SIZE" value="30000">
    </form>
    <h3>Click <a href="index.php">here</a> to browse the images instead.</h3>
    </body>
    </html>

<?php 
//code for inserting into the database
	} 
	else {
		if(!empty($_POST['image'])) {
			$filename	= $_POST['image'];
		} 
		else {
			$filename = $_FILES['userfile']['name'];
		}
		$file			= file_get_contents($_FILES['userfile']['tmp_name']);
		$conn			= pg_connect("dbname=dbname user=user password=password");
		$res			= pg_query($conn,'SET bytea_output = "escape";');
		$sql			=	"SELECT image FROM picture WHERE filename = '$filename'";
		$res			= pg_query($conn, $sql);
		if(!$res) {
			// Something failed
		} elseif(pg_num_rows($res) == 1) { 
			// code for retreving from database
			$image		= pg_fetch_row($res,'image');
			header('Content-Type: image/jpeg');
			echo pg_unescape_bytea($image[0]);
			//echo $data;
		} else {
			$dat		= pg_escape_bytea($conn, $file); 
			$sql		= "INSERT INTO picture ( image , filename ) VALUES ( '{$dat}' , '$filename')";
			$ret		= pg_query($conn,$sql) or die(pg_last_error($conn));
		}
		pg_close($conn);
	}
?>
