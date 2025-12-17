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


//echo $defaultWhiteLabel;

$operatingDir = "";



chdir(__DIR__);

// --- OS detection ---
$osName = PHP_OS_FAMILY;
if ($osName === "Windows") {
	$operatingDir = realpath(__DIR__); 
} else {
	$operatingDir = realpath(__DIR__);
}

function runPythonQuery($operatingDir, $defaultWhiteLabel, $qry) {
	$input = [
		"wlId" => (int)$defaultWhiteLabel,
		"qry"  => $qry
	];

	$json_input = json_encode($input);
	$pythonScript = "$operatingDir/pythonScripts/mysql_query2.py";
	$cmd = "python \"$pythonScript\"";

	$descriptorspec = [
		0 => ["pipe", "r"],  // stdin
		1 => ["pipe", "w"],  // stdout
		2 => ["pipe", "w"]   // stderr
	];

	$process = proc_open($cmd, $descriptorspec, $pipes);

	if (!is_resource($process)) {
		return ["error" => "Failed to start Python process"];
	}

	// Send JSON input
	fwrite($pipes[0], $json_input);
	fclose($pipes[0]);

	// Capture output and errors
	$output = stream_get_contents($pipes[1]);
	$error  = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	$return_value = proc_close($process);

	if ($error) {
		return ["error" => trim($error)];
	}

	$decoded = json_decode($output, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		return ["error" => "Invalid JSON returned: " . json_last_error_msg(), "raw" => $output];
	}

	return $decoded;
}

?>

	<!DOCTYPE html>
	<html lang="en">
		 
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Dashboard</title>
			<!--https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css-->
		  <!--<style>
			body { background-color: #f8f9fa; }
		  </style>-->

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
					$pgName = "dashboard.php";
					//echo $pgName;
					include './configs/navbarSetup.php'; 
					
					
					function getDashData($typ) {
						global $defaultWhiteLabel, $operatingDir;
						if ($typ === 'cohortStats'){
							$qry = 
							"select 
								(case when active = 1 then 'Active' when active = 0 then 'In-Active' else 'Un-Known' end) stats,
								count(distinct id) cts
							from hurs_cohort_details hcd
							where 
								hcd.id not in (1475,1476,1477)
								and hcd.white_label_id = $defaultWhiteLabel
							group by stats";

							$sndDtls = runPythonQuery($operatingDir, $defaultWhiteLabel, $qry);

							if (empty($sndDtls)) {
								echo "Python error: " . $result['error'] . "\n";
							} else {
								return $sndDtls;
							}

						}
						
						if ($typ === 'cohortStatsByFT'){
							$qry = "select 
									(
									case when upper(fuel_type) = 'E' Then
									'Electric Only'
									when Upper(fuel_type) = 'G' Then
									'Gas Only'
									when (upper(fuel_type) = 'E,G'  OR upper(fuel_type) = 'E,W') OR (upper(fuel_type) = 'E|G'  OR upper(fuel_type) = 'E|W') Then
									'Dual'
									ELSE
									'Unknown Fuel Type'
									End) stats,
									count(distinct id) cts
									from hurs_cohort_details
									where active = 1
									and white_label_id = $defaultWhiteLabel
									group by stats";
									
							$sndDtls = runPythonQuery($operatingDir, $defaultWhiteLabel, $qry);

							if (empty($sndDtls)) {
								echo "Python error: " . $result['error'] . "\n";
							} else {
								return $sndDtls;
							}
							
						}
						
						if ($typ === 'cohortStatsByRT'){
							$qry = "select 
									report_type stats,
									count(distinct id) cts
									from hurs_cohort_details
									where active = 1
									and white_label_id = $defaultWhiteLabel
									group by report_type";
									
							$sndDtls = runPythonQuery($operatingDir, $defaultWhiteLabel, $qry);

							if (empty($sndDtls)) {
								echo "Python error: " . $result['error'] . "\n";
							} else {
								return $sndDtls;
							}
						}
						
						if ($typ === 'cohortTreatmentCounts'){
							$qry = "select 
									hcd.cohort_name stats
									,count(distinct hrc.custid) cts
									from hurs_cohort_details hcd 
									inner join hurs_runned_cohorts hrc on hcd.id = hrc.hurs_runned_report_id   
									where hcd.id not in (1475,1476,1477)
									and hcd.active = 1
									and hcd.white_label_id = $defaultWhiteLabel
									group by hcd.cohort_name";
									
							$sndDtls = runPythonQuery($operatingDir, $defaultWhiteLabel, $qry);

							if (empty($sndDtls)) {
								echo "Python error: " . $result['error'] . "\n";
							} else {
								//print_r($sndDtls);
								return $sndDtls;
							}
						}
						
						
						if ($typ === 'cohortControlCounts'){						
							$qry = "select 
									hcd.cohort_name stats
									,count(distinct hrc.custid) cts
									from hurs_cohort_details hcd 
									inner join hurs_runned_controls hrc on hcd.id = hrc.hurs_runned_report_id 
									where hcd.id not in (1475,1476,1477)
									and hcd.active = 1
									and hcd.white_label_id = $defaultWhiteLabel
									group by hcd.cohort_name";
									
							$sndDtls = runPythonQuery($operatingDir, $defaultWhiteLabel, $qry);

							if (empty($sndDtls)) {
								echo "Python error: " . $result['error'] . "\n";
							} else {
								//print_r($sndDtls);
								return $sndDtls;
							}
						}
						
						return Null;
					}
					//print_r("Hi");
					$jsonCohortStats = Null;
					
					$jsonCohortTreatment = Null;
					
					$jsonCohortActvTreatment = Null;
					
					$jsonCohortControl = Null;
					
					$jsonCohortActvControl = Null;
					
					$jsonCohortStats = json_encode(getDashData('cohortStats'));
					
					$jsonCohortStatsByFT = json_encode(getDashData('cohortStatsByFT'));
					
					$jsonCohortStatsByRT = json_encode(getDashData('cohortStatsByRT'));
					
					$jsonCohortTreatment = json_encode(getDashData('cohortTreatmentCounts'));
					
					//$jsonCohortActvTreatment = json_encode(getDashData('cohortTreatmentActvCounts'));
					
					$jsonCohortControl = json_encode(getDashData('cohortControlCounts'));
					
					//$jsonCohortActvControl = json_encode(getDashData('cohortControlActvCounts'));

					//print_r($jsonCohortStats);
				?>
				  
				<div class="row">
					<div class="col-md-4 mb-4">
						<div id="cohortStatsChart"></div>
					</div>
					<div class="col-md-4 mb-4">
						<div id="cohortStatsByFTChart"></div>
					</div>
					<div class="col-md-4 mb-4">
						<div id="cohortStatsByRTChart"></div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-12 mb-12">
						<div id="cohortTreatmentChart"></div>
					</div>
					<!--<div class="col-md-6 mb-6">
						<div id="cohortTreatmentActvChart"></div>
					</div>-->
				</div>
				
				<div class="row pt-4">
					<div class="col-md-12 mb-12">
						<div id="cohortControlChart"></div>
					</div>
					<!--<div class="col-md-6 mb-6">
						<div id="cohortControlActvChart"></div>
					</div>-->
				</div>
				
				<script>
				const jsonCohortStats 			= <?php echo $jsonCohortStats; ?>;
				const jsonCohortStatsByFT 			= <?php echo $jsonCohortStatsByFT; ?>;
				const jsonCohortStatsByRT 			= <?php echo $jsonCohortStatsByRT; ?>;
								
				const jsonCohortTreatment 		= <?php echo $jsonCohortTreatment; ?>;
				//const jsonCohortActvTreatment 	= <?php echo $jsonCohortActvTreatment; ?>;
				const jsonCohortControl 		= <?php echo $jsonCohortControl; ?>;
				//const jsonCohortActvControl 	= <?php echo $jsonCohortActvControl; ?>;

				function showSimpleBarChart(dataArray, title) {
					if (!dataArray || dataArray.length === 0) {
					return '<div class="col-md-12 mb-12">' +
							  '<div class="border rounded shadow-sm p-3 bg-white text-center text-muted" style="height: 300px; display: flex; align-items: center; justify-content: center;">' +
								  '<h5 class="text-center mb-2">' + title + '</h5>' +
								  'No data available' +
							  '</div>' +
						   '</div>';
					}

					let maxVal = Math.max(...dataArray.map(item => item.cts));
					let bars = '';

					dataArray.forEach(function(item, index) {
					const heightPercent = (item.cts / maxVal) * 100;
					const barColor = index % 2 === 0 ? 'bg-success' : 'bg-primary';
					
					const number = item.cts;
					const formatted = number.toLocaleString(); // "85,282"


					bars += '<div class="text-center mx-2" style="width: 50px;">' +
					  '<div class="d-flex flex-column justify-content-end" style="height: 250px;">' + // fixed height container
						'<div class="' + barColor + ' d-flex align-items-center justify-content-center text-white" style="height: ' + heightPercent + '%; min-height: 20px;">' +
						  '<small><b>' + formatted + '</b></small>' +
						'</div>' +
					  '</div>' +
					  '<small class="d-block mt-2">' + item.stats + '</small>' +
					'</div>';

					});


					const chartWidth = dataArray.length > 25 ? dataArray.length * 60 : '100%';

					return '<div class="col-md-12 mb-12">' +
							 '<div class="border rounded shadow-sm p-3 bg-white">' +
							   '<h5 class="text-center mb-2">' + title + '</h5>' +
							   '<div class="overflow-auto">' + // scrollable container
								 '<div class="d-flex justify-content-start" style="width: ' + chartWidth + ';">' +
								   bars +
								 '</div>' +
							   '</div>' +
							 '</div>' +
						   '</div>';

				}

				document.getElementById("cohortStatsChart").innerHTML = showSimpleBarChart(jsonCohortStats, "Cohorts Status");
				
				document.getElementById("cohortStatsByFTChart").innerHTML = showSimpleBarChart(jsonCohortStatsByFT, "Active Cohorts By Fuel Type");
				
				document.getElementById("cohortStatsByRTChart").innerHTML = showSimpleBarChart(jsonCohortStatsByRT, "Active Cohorts By Report Type");
				
				document.getElementById("cohortTreatmentChart").innerHTML = showSimpleBarChart(jsonCohortTreatment, "Customers In Treatment");
				//document.getElementById("cohortTreatmentActvChart").innerHTML = showSimpleBarChart(jsonCohortActvTreatment, "Active Customers In Treatment");
				document.getElementById("cohortControlChart").innerHTML = showSimpleBarChart(jsonCohortControl, "Customers In Control");
				//document.getElementById("cohortControlActvChart").innerHTML = showSimpleBarChart(jsonCohortActvControl, "Active Customers In Control");
				</script>
				
			</div>
			<?php include './configs/bottomScripts.php';  ?>
		</body>
	 
	</html>
	
<?php
}
else {
	header("Location: logout.php");
	exit();
	
}

?>