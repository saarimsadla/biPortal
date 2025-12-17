<?php
	session_start();
	if (!$_SESSION['authenticated']) {
		header("Location: index.html");
		exit();
	}
//$_SESSION['uname']	= $userFound;
//$_SESSION['whiteLabels']	= $finalWhiteLabels;
//$_SESSION['rghts']	= $finalRights;

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
	
	if(in_array(4,$rightsArray)) {
	

	?>

		<!DOCTYPE html>
		<html lang="en">
			 
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<title>Dashboard</title>
				<?php include './configs/headerScripts.php';  ?>
				
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
			 
			<body> 
			
				<!-- Bootstrap Navbar -->
				<div class="container-fluid">
					<!--navbar here -->
					
					<?php
						$pgName = "rstPass.php";
						//echo $pgName;
						include './configs/navbarSetup.php'; 
					?>

					
					<?php if(in_array(4,$rightsArray)) {echo '<div class="container" style="width: 25%; padding-top:10%">
						<div class="card">
							<div class="card-header">
								<form action="./configs/resetPass.php" method="post">
									<div class="form-group mb-2">
										<label for="oldpass" class="mb-0">Old Password:</label>
										<input type="password" class="form-control form-control-sm" id="oldpass" name="oldpass" required>
									</div>
									<div class="form-group mb-2">
										<label for="newPass" class="mb-0">New Password:</label>
										<input type="password" class="form-control form-control-sm" id="newPass" name="newPass" required>
									</div>
									<div class="form-group mb-2">
										<label for="confirmnewPass" class="mb-0">Confirm Password:</label>
										<input type="password" class="form-control form-control-sm" id="confirmnewPass" name="confirmnewPass" required>
									</div>
									<button type="submit" class="btn btn-primary btn-sm btn-block">Change Password</button>
								</form>
							</div>
						</div>
					</div>'; } ?>
					 
					
					<?php include './configs/bottomScripts.php';  ?>
					
				</div>
			</body>
		 
		</html>
		
	<?php
	}
	else {
		header("Location: dashboard.php");
		exit();
		
	}
}
else{
	header("Location: logout.php");
	exit();
}

?>