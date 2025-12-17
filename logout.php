<!-- logout.php -->
 
 
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Logout</title>
		<link rel="icon" href="images/favicon.ico" type="image/x-icon">
	</head>
 
<?php
	session_start();
	$unser = isset($_SESSION['unser']) ? $_SESSION['unser'] : null;
	$usName = isset($_SESSION['uname']) ? $_SESSION['uname'] : null;
	$wl = isset($_SESSION['whiteLabels']) ? $_SESSION['whiteLabels'] : null;
	$rt = isset($_SESSION['rghts']) ? $_SESSION['rghts'] : null;
	//$rt = "1,2,3,5";

	if ($usName !== null && $wl !== null && $rt !== null){

	//echo " user:$usName  wl:$wl  rgt:$rt  " ;

	$rightsArray = explode(",",$rt);
	$whtLblArray = explode(",",$wl);
	$defaultWhiteLabel =  isset($_SESSION['defWhitelabel']) ? $_SESSION['defWhitelabel'] : 0;

		if(in_array(5,$rightsArray)) {
			
			session_destroy();
			$_SESSION['authenticated'] = false;
			header("Location: index.html");
			exit();
		}
		else{
			header("Location: dashboard.php");
			exit();
		}
	}
	else{
		$_SESSION['authenticated'] = false;
		header("Location: index.html");
		exit();
	}
?>