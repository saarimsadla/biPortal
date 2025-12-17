<!DOCTYPE html>
<html lang="en">
	 
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Reset Password</title>
		<link rel="icon" href="./images/favicon.ico" type="image/x-icon">
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

$usName = isset($_SESSION['unser']) ? $_SESSION['unser'] : null;
$oldPass = isset($_POST['oldpass']) ? $_POST['oldpass'] : null;
$newPass = isset($_POST['newPass']) ? $_POST['newPass'] : null;
$confrmnewPass = isset($_POST['confirmnewPass']) ? $_POST['confirmnewPass'] : null;

//echo " user:$usName  oldapass:$oldPass  new pass:$newPass  newpassConfrim:$confrmnewPass" ;

if ($usName !== null && $oldPass !== null && $newPass !== null && $confrmnewPass !== null){
	

	$result = shell_exec('../goScripts/changePass ' . escapeshellarg($usName) . ' ' . escapeshellarg($oldPass) . ' ' . escapeshellarg($newPass));

	
	if (strpos($result, 'No matching document found for username and last password') !== false || !isset($result)){
		echo "<script>alert('No matching record found for username and last password'); window.location.href = '../dashboard.php';</script>";
		//header("Location: dashboard.php");
		exit();
	}
	else{
		echo "<script>alert('Password changed successfully'); window.location.href = '../dashboard.php';</script>";
		//header("Location: dashboard.php");
		exit();
	}
}
else {
	if (strpos($oldPass,$newPass) == false) {
		echo "<script>alert('New password cant be same as old password'); window.location.href = '../dashboard.php';</script>";
		//header("Location: dashboard.php");
		exit();
	}
	echo "<script>alert('post variables inset please contact Hurs Dev Team'); window.location.href = '../dashboard.php';</script>";
	//header("Location: dashboard.php");
	exit();
}

?>

</html>