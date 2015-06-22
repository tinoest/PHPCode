<?php 

	header("Location: http://tinoest.no-ip.org/");
	exit;
	//session_start();
	if(array_key_exists('userLogin',$_POST)){
		//$_SESSION['login_user'] = $_POST['userLogin'];
		//header("Location: http://tinoest.no-ip.org/");
	}

	if(!array_key_exists('image', $_POST)) {
?>
<html>
	<header>
		<link rel="stylesheet" type="text/css" href="//tinoest.no-ip.org/mvc/css/menubar.css">
		<link rel="stylesheet" type="text/css" href="//tinoest.no-ip.org/mvc/css/core.css">
		<link rel="stylesheet" type="text/css" href="//tinoest.no-ip.org/mvc/css/contact.css">
		<title>tinoest</title>
	</header>	
	<body>
		<div class="site-header">
			<div class="logo">
				<img src="//tinoest.no-ip.org/mvc/images/mini.jpg"</img>
				<div class="site-name">tinoest</div>
			</div>
		</div>
		<div class="nav">
		<div class="menu-container">
			<ul class="memu">
				<li class="memu-root"><a href="//tinoest.no-ip.org/home">Home</a>
				<li class="memu-root"><a href="//tinoest.no-ip.org/sql">Sql</a>
			<ul>
				<li class="has-children">
				<li><a href="//tinoest.no-ip.org/sql/sqlite">Sqlite</a></li>
				<li><a href="//tinoest.no-ip.org/sql/postgresql">Postgresql</a></li>
				<li><a href="//tinoest.no-ip.org/sql/mysql">Mysql</a></li>
				</li>
			</ul>
			<li class="memu-root"><a href="//tinoest.no-ip.org/misc">Misc</a>
			<ul>
				<li class="has-children">
					<li><a href="//tinoest.no-ip.org/misc/mail">Mail</a></li>
					<li><a href="//tinoest.no-ip.org/misc/system_status">System status</a></li>
				</li>
			</ul>
			<li class="memu-root"><a href="//tinoest.no-ip.org/forum/">Forum</a>
			<li class="memu-root"><a href="//tinoest.no-ip.org/skydive/">Skydive</a>
			</li>
			</ul>
		</div>
		</div>
		<div class="content">
<?php
	if(array_key_exists('login_user',$_SESSION)) {
?>
		<form method="post" action="databaseStore.php" enctype="multipart/form-data">
			<h1>Upload an Image File</h1> 
			<h3>Please fill in the details below to upload your file. Fields shown in <font color="#FF0000">red</font> are mandatory.</h3>
			<table>
				<tr>
					<td><font color="#00FF00">Short description:</font></td>
					<td><input type="text" name="image" size=25></td>
				</tr>
				<tr>    
					<td><font color="#FF0000">File:</font></td>
					<td><input name="userfile" type="file"></td>
				</tr>
				<tr>
					<td><input type="submit" value="Submit"></td>
				</tr>
			</table>
			<input type="hidden" name="MAX_FILE_SIZE" value="30000">
		</form>
		<h3>Click <a href="index.php">here</a> to browse the images instead.</h3>
<?php
	}
	else {
?>
	<form action="databaseStore.php" method="post">
		User: <input type="text" name="userLogin" /><br />
		Password: <input type="password" name="userPassword" /><br />
		<input type="submit" name="submit" value="Submit" />
	</form>
<?php
	}
?>
	</div>
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script type="text/javascript">
		google.load("jquery", "1.4.4");
		google.setOnLoadCallback(function() {
			$('.js-enabled').memu({ 
				icon: {
				inset: true,
				margin: {
					top: 4,
					right: 10
				}
				},
				width: 150,
				rootWidth: 75,
				height: 25
			});
		});
	</script>
	<script type="text/javascript" src="//tinoest.no-ip.org/mvc/js/jquery.memu-0.1.min.js"></script>
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
			$conn			= pg_connect("dbname=database user=user password=pass");
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
		//session_destroy();
?>
