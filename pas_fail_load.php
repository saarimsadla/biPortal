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

$frmRepId 	= "";
$toRepId 	= "";
$sndT 		= "";

$fromReportIDs = [];
$sendOptions = [];
$downloadAllArray[] = [];
$id_MainData[] = [];


//echo $defaultWhiteLabel;

// server request handle here
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	// Retrieve form data
	$frmRepId 	= $_POST['gsfrmRepId'];
	$toRepId 	= $_POST['gstoRepId'];
	$sndT 		= $_POST['gssndT'];
	//print_r("Server detected: From report id=".$frmRepId." To report id=".$toRepId." Send Type=".$sndT." custid lists=".$custidPRM);
}
else{
	$_POST['gsfrmRepId']	= "0";
	$_POST['gstoRepId']	= "0";
	$_POST['gssndT'] 		= "0";
}


?>

	<!DOCTYPE html>
	<html lang="en">
		 
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Generate Samples</title>
 
			<?php include './configs/headerScripts.php';  ?>
			
			


			
		</head>
		 
	<body>
    <!-- Bootstrap Navbar -->
    <div class="container-fluid">
        <!--navbar here -->
        <?php
            $pgName = "pas_fail_load.php";
        ?>
        <div style="padding-top:0.5%;padding-left:0.5%;">
            <form method="post" id="secondForm" onsubmit="return validatePassFailForm()">
				<div class="form-row">
					<div class="form-group col-md-12">
						<label for="reasonPrm">Please Enter Reason here</label>
						<textarea id="reasonPrm" name="reasonPrm" class="form-control" rows="2" 
						onfocus="if (this.value === 'Please enter reason here') { this.value = ''; }" 
						onblur="if (this.value === '') { this.value = 'Please enter reason here'; }" required>Please enter reason here</textarea>
					</div>
				</div>
				<!--pass or fail()-->
				<?php if(in_array(13,$rightsArray)) { echo '<button type="button" class="btn btn-primary" style="background-color: darkgreen;" onclick="markQaPass(\'Y\',1)"><i class="fa-solid fa-check"></i></button>';} ?>
				<?php if(in_array(13,$rightsArray)) { echo '<button type="button" class="btn btn-primary" style="background-color: #AA4A44;" onclick="markQaPass(\'F\',1)"><i class="fa-solid fa-xmark"></i></button>';} ?>
			</form>

            
            <br/>
        </div>

        <?php include './configs/bottomScripts.php';  ?>
        
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    placeholder: "Select a report ID",
                    allowClear: true
                });
            });
        </script>
    </div>
</body>

	 
	</html>
	
<?php
}
else {
	header("Location: logout.php");
	exit();
	
}

?>