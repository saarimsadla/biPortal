<!DOCTYPE html>
<html lang="en">
	 
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Change Client</title>
		<link rel="icon" href="images/favicon.ico" type="image/x-icon">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
		<style>
			body {
				margin: 0;
				padding: 0;
				min-height: 100vh;
				background-image: url('images/background-image-dash.jpg');
				background-size: cover;
				background-position: center;
				display: flex;
				align-items: flex-start;
				justify-content: center;
			}

			.container {
				margin-top: auto; /* Push content to the top */
			}

			.card {
				margin-bottom: 20px;
			}
		</style>
	</head>

<?php
	session_start();
	if (!$_SESSION['authenticated']) {
		header("Location: index.html");
		exit();
	}

$whitlabel= isset($_POST['wl']) ? $_POST['wl'] : null;
$callPage = isset($_POST['callPage']) ? $_POST['callPage'] : null;


//echo "wl:$whitlabel      callpage:$callPage";

if ($whitlabel !== null && $callPage !== null ){
	
	//echo "   yes";

	$_SESSION['defWhitelabel'] = $whitlabel;
	echo "<script>alert('Client Changed'); window.location.href = '../".$callPage."';</script>";

}
?>