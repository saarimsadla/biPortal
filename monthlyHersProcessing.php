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
$wlAr = isset($_SESSION['whiteLabelArr']) ? $_SESSION['whiteLabelArr'] : null;
$defaultWhiteLabel =  isset($_SESSION['defWhitelabel']) ? $_SESSION['defWhitelabel'] : 0;

chdir(__DIR__);
$operatingDir = "";

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


if ($usName !== null && $wl !== null && $rt !== null){

//echo " user:$usName  wl:$wl  rgt:$rt  " ;

$rightsArray = explode(",",$rt);
$whtLblArray = explode(",",$wl);
$defaultWhiteLabel =  isset($_SESSION['defWhitelabel']) ? $_SESSION['defWhitelabel'] : 0;

$mnthValSel = "";
$yearValSel = "";
$wlFxParam = "";


//echo $defaultWhiteLabel;

?>

	<!DOCTYPE html>
	<html lang="en">
		 
		<head>
			<title>DCS Processing Summary</title>
			<?php include './configs/headerScripts.php'; ?>
			<style>
				.tab-content {
					margin-top: 20px;
				}
				body {
					margin: 0;
					padding: 0;
					min-height: 100vh;
					background-image: url('images/loadingData.jpg');
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
					$pgName = "monthlyHerProcessing.php";
					//echo $pgName;
					include './configs/navbarSetup.php';
					
					// server request handle here
					if ($_SERVER["REQUEST_METHOD"] == "POST") {
						// Retrieve form data
						$mnthValSel = $_POST['mnthValSel'];
						$yearValSel = $_POST['yearValSel'];
						$wlFxParam = $_POST['wlFxParam'];
					}
					else{
						// Retrieve form data
						$_POST['mnthValSel'] = date("F", strtotime("first day of last month"));
						$_POST['yearValSel'] = date("Y");
						$_POST['wlFxParam'] = "0";
						
						$mnthValSel =  date("F", strtotime("first day of last month"));
						$yearValSel = date("Y");
						$wlFxParam = "0";
					}
					
					// LOV setup here
					$mnthOptions = [
						"January" => "January",
						"February" => "February",
						"March" => "March",
						"April" => "April",
						"May" => "May",
						"June" => "June",
						"July" => "July",
						"August" => "August",
						"September" => "September",
						"October" => "October",
						"November" => "November",
						"December" => "December"
					];

					//arsort($pcpiRunIdOptions); // Sort in descending order
					$yearOptions = [];
					$currentYear = date("Y");

					for ($i = 0; $i < 15; $i++) {
						$year = $currentYear - $i;
						$yearOptions[$year] = $year;
					}
					
					$wlFxOptions = [	"1" => "Yes",
										"0" => "No"
					];
					
					// get data logic from here required variable values are in $mnthValSel ,$yearValSel , $wlFxParam 
					$database_name = "hers";
					$collection_name = "data_service.";
					$transactionSr = 0;
					$initial_query_json = json_encode(['transactionSr' => $transactionSr]);
					$wlIdParamSet = "";
					
					if ($wlFxParam == 1){
						$wlIdParamSet = '"wlId":'.$defaultWhiteLabel.',';
					}
					else {
						$wlIdParamSet='';
					}

					$ag = [
						[
							'$match' => array_merge(
								json_decode('{' . $wlIdParamSet . '}', true), // converts dynamic params to array
								[
									'reportMonth' => $mnthValSel,
									'reportYear' => (int)$yearValSel,
									'exclusions' => ['$exists' => false]
								]
							)
						],
						[
							'$group' => [
								'_id' => [
									'wlValue' => [
										'$cond' => [
											[ '$ifNull' => ['$wlId', false] ],
											'$wlId',
											'$whitelabel'
										]
									],
									'reportType' => '$reportType',
									'reportId' => '$reportId'
								],
								'uniqueCustIds' => [ '$addToSet' => '$custId' ]
							]
						],
						[
							'$group' => [
								'_id' => '$_id.wlValue',
								'reportTypes' => [
									'$push' => [
										'reportType' => '$_id.reportType',
										'count' => [ '$size' => '$uniqueCustIds' ]
									]
								]
							]
						],
						[
							'$unwind' => '$reportTypes'
						],
						[
							'$group' => [
								'_id' => [
									'wlValue' => '$_id',
									'reportType' => '$reportTypes.reportType'
								],
								'totalReportCount' => [ '$sum' => '$reportTypes.count' ]
							]
						],
						[
							'$group' => [
								'_id' => '$_id.wlValue',
								'reportTypes' => [
									'$push' => [
										'reportType' => '$_id.reportType',
										'count' => '$totalReportCount'
									]
								]
							]
						],
						[
							'$project' => [
								'_id' => 0,
								'wlValue' => '$_id',
								'reportTypes' => 1
							]
						]
					];

					$ag_json = json_encode($ag, JSON_UNESCAPED_SLASHES);


					$cmd = sprintf(
						'python %s %s %s %s %s %s',
						escapeshellarg("./pythonScripts/mongodb_query.py"),
						escapeshellarg($database_name),
						escapeshellarg($collection_name),
						'"'.str_replace('"', '""', $initial_query_json).'"', // escape inner quotes,
						escapeshellarg("ag"),
						'"'.str_replace('"', '""', $ag_json).'"'
					);

					$initial_output = shell_exec("$cmd 2>&1");
					
					 
					$data = json_decode($initial_output, true);
					
						
					function getReportCounts($hersId) {
						global $wlFxParam,$mnthValSel,$yearValSel;
						
						//print_r("hurs id: ".$hersId);
					//	print_r("wl fx: ".$wlFxParam);
					//	print_r("mnt val: ".$mnthValSel);
					//	print_r("year val: ".$yearValSel);
						
						if ($wlFxParam == 1){
							$wlIdParamSet = '"wlId":'.$defaultWhiteLabel.',';
						}
						else {
							$wlIdParamSet='';
						}

						$ag = [
								[
									'$match' => array_merge(
										json_decode('{' . $wlIdParamSet . '}', true), // expands dynamic "wlId" filter
										[
											'reportMonth' => $mnthValSel,
											'reportYear' => (int)$yearValSel,
											'reportId' => (int)$hersId,
											'exclusions' => ['$exists' => false]
										]
									)
								],
								[
									'$group' => [
										'_id' => [
											'wlValue' => [
												'$cond' => [
													[ '$ifNull' => ['$wlId', false] ],
													'$wlId',
													'$whitelabel'
												]
											],
											'reportType' => '$reportType',
											'reportId' => '$reportId'
										],
										'uniqueCustIds' => [ '$addToSet' => '$custId' ]
									]
								],
								[
									'$group' => [
										'_id' => '$_id.wlValue',
										'reportTypes' => [
											'$push' => [
												'reportType' => '$_id.reportType',
												'count' => [ '$size' => '$uniqueCustIds' ]
											]
										]
									]
								],
								[
									'$unwind' => '$reportTypes'
								],
								[
									'$group' => [
										'_id' => [
											'wlValue' => '$_id',
											'reportType' => '$reportTypes.reportType'
										],
										'totalReportCount' => [ '$sum' => '$reportTypes.count' ]
									]
								],
								[
									'$group' => [
										'_id' => '$_id.wlValue',
										'reportTypes' => [
											'$push' => [
												'reportType' => '$_id.reportType',
												'count' => '$totalReportCount'
											]
										]
									]
								],
								[
									'$project' => [
										'_id' => 0,
										'wlValue' => '$_id',
										'reportTypes' => 1
									]
								]
							];

						
						//print_r($ag);
						
						$database_name = "hers";
						$collection_name = "data_service.";
						$transactionSr = 0;
						$initial_query_json = json_encode(['transactionSr' => $transactionSr]);						
						$ag_json = json_encode($ag, JSON_UNESCAPED_SLASHES);


						$cmd = sprintf(
							'python %s %s %s %s %s %s',
							escapeshellarg("./pythonScripts/mongodb_query.py"),
							escapeshellarg($database_name),
							escapeshellarg($collection_name),
							'"'.str_replace('"', '""', $initial_query_json).'"', // escape inner quotes,
							escapeshellarg("ag"),
							'"'.str_replace('"', '""', $ag_json).'"'
						);

						$initial_output = shell_exec("$cmd 2>&1");
						
						//print_r ($initial_output);
						$dt = json_decode($initial_output, true);
						//print_r($dt);
						
						
						$counts = ["email" => 0, "paper" => 0, "emailExclusions" => 0, "paperExclusions" => 0];
						foreach ($dt as $item) {
							if (isset($item['reportTypes'])) {
								foreach ($item['reportTypes'] as $report) {
									if (isset($report['reportType']) && isset($report['count'])) {
										if ($report['reportType'] == "email") {
											$counts["email"] += $report['count'];
										} elseif ($report['reportType'] == "paper") {
											$counts["paper"] += $report['count'];
										}
									}
								}
							}
						}
						
						$ag = [
								[
									'$match' => array_merge(
										json_decode('{' . $wlIdParamSet . '}', true), // dynamic $wlId field(s)
										[
											'reportMonth' => $mnthValSel,
											'reportYear' => (int) $yearValSel,
											'reportId' => (int) $hersId,
											'exclusions' => ['$exists' => true],
										]
									),
								],
								[
									'$group' => [
										'_id' => [
											'wlValue' => [
												'$cond' => [
													['$ifNull' => ['$wlId', false]],
													'$wlId',
													'$whitelabel',
												],
											],
											'reportType' => '$reportType',
											'reportId' => '$reportId',
										],
										'uniqueCustIds' => ['$addToSet' => '$custId'],
									],
								],
								[
									'$group' => [
										'_id' => '$_id.wlValue',
										'reportTypes' => [
											'$push' => [
												'reportType' => '$_id.reportType',
												'count' => ['$size' => '$uniqueCustIds'],
											],
										],
									],
								],
								[
									'$unwind' => '$reportTypes',
								],
								[
									'$group' => [
										'_id' => [
											'wlValue' => '$_id',
											'reportType' => '$reportTypes.reportType',
										],
										'totalReportCount' => ['$sum' => '$reportTypes.count'],
									],
								],
								[
									'$group' => [
										'_id' => '$_id.wlValue',
										'reportTypes' => [
											'$push' => [
												'reportType' => '$_id.reportType',
												'count' => '$totalReportCount',
											],
										],
									],
								],
								[
									'$project' => [
										'_id' => 0,
										'wlValue' => '$_id',
										'reportTypes' => 1,
									],
								],
							];

						
						//print_r($ag);
						
						$database_name = "hers";
						$collection_name = "data_service.";
						$transactionSr = 0;
						$initial_query_json = json_encode(['transactionSr' => $transactionSr]);						
						$ag_json = json_encode($ag, JSON_UNESCAPED_SLASHES);


						$cmd = sprintf(
							'python %s %s %s %s %s %s',
							escapeshellarg("./pythonScripts/mongodb_query.py"),
							escapeshellarg($database_name),
							escapeshellarg($collection_name),
							'"'.str_replace('"', '""', $initial_query_json).'"', // escape inner quotes,
							escapeshellarg("ag"),
							'"'.str_replace('"', '""', $ag_json).'"'
						);

						$initial_output = shell_exec("$cmd 2>&1");
						
						//print_r ($initial_output);
						$dts = json_decode($initial_output, true);
						//print_r($dt);
						
						
						foreach ($dts as $item) {
							if (isset($item['reportTypes'])) {
								foreach ($item['reportTypes'] as $report) {
									if (isset($report['reportType']) && isset($report['count'])) {
										if ($report['reportType'] == "email") {
											$counts["emailExclusions"] += $report['count'];
										} elseif ($report['reportType'] == "paper") {
											$counts["paperExclusions"] += $report['count'];
										}
									}
								}
							}
						}
						
						
						
						return $counts;
					}
					
					function getRunType($date) {
						// Convert the date string to a DateTime object
						//print_r($date);
						$dateObj = DateTime::createFromFormat('Ymd', $date);
						
						//print_r($dateObj);
						
						// Get the day of the month
						$day = (int)$dateObj->format('d');
						
						// Get the total number of days in the month
						$daysInMonth = (int)$dateObj->format('t');
						
						// Determine if the date is closer to the start or end of the month
						if ($day <= 10 || $day >= $daysInMonth - 9) {
							return "Billing Run";
						} else {
							return "AMI Run";
						}
					}
					

					
					function createHtmlTable($data, $headings_mapping, $numeric_fields, $wlIdent) {
						$emailTtl = 0;
						$paperTtl = 0;
						$totalTtl = 0;
						global $yearValSel, $mnthValSel, $wlFxParam, $operatingDir, $defaultWhiteLabel;

						// Start the HTML output
						//$html = '<p>*Please click on Blue exclusions counts to get details.</p>';
						$html = '<div class="table-responsive">';
						$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'hersProcessing_'.$mnthValSel.'_'.$yearValSel.'.csv\', \'masterDataTable\')">Download CSV</button>';
						$html .= '<table class="table table-bordered table-sticky-header" id="masterDataTable">';
						$html .= '<thead class="thead-light">';
						$html .= '<tr>';
					 
						// Add table headers
						$html .= '<th>Client</th>';
						$html .= '<th>Scope</th>';
						$html .= '<th>PM</th>';
						$html .= '<th>Status</th>';
						$html .= '<th>' . htmlspecialchars($headings_mapping['email']) . '</th>';
						$html .= '<th>' . htmlspecialchars($headings_mapping['paper']) . '</th>';
						$html .= '<th>Total</th>';
						$html .= '</tr>';
						$html .= '</thead>';
						$html .= '<tbody style="background-color: #ffffff;">';
					 
						// Add table rows
						foreach ($data as $row) {
							$wlValue = $row['wlValue'];
							//$client = isset($wlIdent[$wlValue]) ? $wlIdent[$wlValue] : $wlValue;
							$client = getWlData($wlIdent, $wlValue, "clientName");
							$emailCount = 0;
							$paperCount = 0;
							
							
							$sg = [
									[
										'$match' => [
											'reportMonth' => $mnthValSel,
											'reportYear' => (int) $yearValSel,
											'wlId' => (int) $wlValue,
										],
									],
									[
										'$group' => [
											'_id' => null,
											'uniqueReportIds' => [
												'$addToSet' => '$reportId',
											],
										],
									],
									[
										'$project' => [
											'_id' => 0,
											'uniqueReportIds' => 1,
										],
									],
								];

							
							$database_name = "hers";
							$collection_name = "data_service.";
							$transactionSr = 0;
							$initial_query_json = json_encode(['transactionSr' => $transactionSr]);
							
							$ag_json = json_encode($sg, JSON_UNESCAPED_SLASHES);


							$cmd = sprintf(
								'python %s %s %s %s %s %s',
								escapeshellarg("./pythonScripts/mongodb_query.py"),
								escapeshellarg($database_name),
								escapeshellarg($collection_name),
								'"'.str_replace('"', '""', $initial_query_json).'"', // escape inner quotes,
								escapeshellarg("ag"),
								'"'.str_replace('"', '""', $ag_json).'"'
							);

							$initial_out = shell_exec("$cmd 2>&1");
							//print_r ($initial_output);
							$repIds = json_decode($initial_out, true);
							$rpIds = "";
							foreach ($repIds[0]['uniqueReportIds'] as $rw) {
								$rpIds .= "," . $rw;
							}

							// Remove the leading comma
							$rpIds = ltrim($rpIds, ',');
							//print_r ($rpIds);
							
							$qry = "select hrr.id, hrr.orig_runned_id, hcd.cohort_name, hcd.cohort_frequency, hrr.fuel_type, hrr.report_type, hrr.report_start_Date,hrr.whitelabel_id, hrr.sent
									, DATE_FORMAT(FROM_UNIXTIME(hrr.runtime), '%Y%m%d') runtime, hrr.runtime rtme
									from hurs_runned_reports hrr 
									inner join hurs_cohort_details hcd
									on hcd.id = hrr.orig_runned_Id
									where hrr.id in ($rpIds);";
									

							$sndDtls = runPythonQuery($operatingDir, $defaultWhiteLabel, $qry);
							
							if (empty($sndDtls)) {
								die("Python mysql error: " . $result['error'] . "\n");
							}
							
							$sdCount = 0;
							$sendStatus = "Sent";

							foreach ($sndDtls as &$r) {
								if ($r['sent'] == 0) {
									$sdCount += 1;
									$sendStatus = "In Progress";
								}
								
								// Assuming getReportCounts function takes an array with report details
								$reportCounts = getReportCounts($r['id']);
								$r['emailCounts'] = $reportCounts['email'];
								$r['emailExclusionsCounts'] = $reportCounts['emailExclusions'];
								$r['paperCounts'] = $reportCounts['paper'];
								$r['paperExclusionsCounts'] = $reportCounts['paperExclusions'];
								
								$runType = getRunType($r['runtime']);
								$r['hersRunType'] = $runType;
								$r['hersRunTypeSorter'] = ($runType === "AMI Run") ? 1 : 2;
								
								// Print counts for debugging
								//echo "Email counts: " . $r['emailCounts'] . "\n";
								//echo "Paper counts: " . $r['paperCounts'] . "\n";
							}

							//print_r($sndDtls);
						
							// Process report types
							foreach ($row['reportTypes'] as $report) {
								if ($report['reportType'] === 'email') {
									$emailCount = $report['count'];
								}
								if ($report['reportType'] === 'paper') {
									$paperCount = $report['count'];
								}
							}
							$totalCount = $emailCount + $paperCount;
							$emailTtl = $emailTtl + $emailCount;
							$paperTtl = $paperTtl + $paperCount;
							$totalTtl = $totalTtl + $totalCount;
							// Add table row
							$html .= '<tr>';
							$html .= '<td><b>' . htmlspecialchars($client) . '</b></td>';
							
							$html .= '<td>'.str_replace(";", " ", getWlData($wlIdent, $wlValue, "scope")).'</td>'; // Assuming static values, change as needed
							
							$html .= '<td>'.str_replace(";", " ", getWlData($wlIdent, $wlValue, "pmName")).'</td>';
							
							/*
								sent verification code
							*/
							$SS= "";
							if ($sdCount>0){
								$SS = '<b>'.$sdCount.' Cohorts '.$sendStatus.'</b>'; //here need to check report id for sent flag in mysql
							}
							else{
								$SS = $sendStatus;
							}
							
							
							if($SS){
								$full_month = $mnthValSel.'-'.$yearValSel;
								$modalId = 'status_' . $wlValue.'_'.$full_month;
//								print($modalId);
								$html .= '<td><a href="#" data-toggle="modal" data-target="#' . $modalId . '" onclick="loadBounceDetails(' . htmlspecialchars(json_encode($sndDtls), ENT_QUOTES, 'UTF-8') . ', \'' . $modalId . '\',\''.htmlspecialchars($client).'\',\''.htmlspecialchars($full_month).'\')">' . $SS . '</a></td>';

								$html .= '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">';
								$html .= '<div class="modal-dialog" role="document" style="max-width:85%; width: 85%">';
								$html .= '<div class="modal-content">';
								$html .= '<div class="modal-header">';
								$html .= '<h5 class="modal-title" id="' . $modalId . 'Label">'.htmlspecialchars($client).' Send Details '.htmlspecialchars($full_month).'</h5>';
								$html .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
								$html .= '<span aria-hidden="true">&times;</span>';
								$html .= '</button>';
								$html .= '</div>';
								$html .= '<div class="modal-body" id="' . $modalId . 'Content">';
								$html .= '<p>Loading data...</p>';
								$html .= '</div>';
								$html .= '<div class="modal-footer">';
								$html .= '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>';
								$html .= '</div>';
								$html .= '</div>';
								$html .= '</div>';
								$html .= '</div>';
							}
							
							//$html .= '<td>'.$SS.'</td>'; //here need to check report id for sent flag in mysql
							
							
							/*
								sent verification code completed
							*/
							// Email column with comma formatting if it's numeric
							if (in_array('count', $numeric_fields)) {
								$html .= '<td>' . number_format($emailCount) . '</td>';
							} else {
								$html .= '<td>' . htmlspecialchars($emailCount) . '</td>';
							}
							
					 
							// Paper column with comma formatting
							if (in_array('count', $numeric_fields)) {
								$html .= '<td>' . number_format($paperCount) . '</td>';
							} else {
								$html .= '<td>' . htmlspecialchars($paperCount) . '</td>';
							}
							
					 
							// Total column with comma formatting
							if (in_array('count', $numeric_fields)) {
								$html .= '<td>' . number_format($totalCount) . '</td>';
							} else {
								$html .= '<td>' . htmlspecialchars($totalCount) . '</td>';
							}
					 
							$html .= '</tr>';
						}
						// staticly setting FBC until able to fetch from table
						$html .= '<tr>';
							$html .= '<td><b>Fortis BC</b></td>';
							$html .= '<td>'.str_replace(";", " ", getWlData($wlIdent, 44, "scope")).'</td>';
							$html .= '<td>'.str_replace(";", " ", getWlData($wlIdent, 44, "pmName")).'</td>';
							$html .= '<td></td>';
							$html .= '<td>0</td>';
							$html .= '<td>0</td>';
							$html .= '<td>0</td>';
						$html .= '</tr>';
						$html .= '<tr>';
							$html .= '<td><b>Total</b></td>';
							$html .= '<td></td>';
							$html .= '<td></td>';
							$html .= '<td></td>';
							$html .= '<td><b>' . number_format($emailTtl) . '</b></td>';
							$html .= '<td><b>' . number_format($paperTtl) . '</b></td>';
							$html .= '<td><b>' . number_format($totalTtl) . '</b></td>';
						$html .= '</tr>';
					 
						$html .= '</tbody>';
						$html .= '</table>';
						$html .= '</div>';
					 
						return $html;
					}

				?>
				

				<div class="container-fluid" style="padding-top:0.5%;padding-left:0.5%;">
					<form method="post" id="secondForm">
						<div class="form-row">
							<div class="form-group col-md-3">
								<label for="mnthValSel">Hers Month:</label>
								<select id="mnthValSel" name="mnthValSel" class="form-control select2" required>
									<?php foreach ($mnthOptions as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['mnthValSel']) && $_POST['mnthValSel'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="yearValSel">Hers Year:</label>
								<select id="yearValSel" name="yearValSel" class="form-control select2" required>
									<?php foreach ($yearOptions as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['yearValSel']) && $_POST['yearValSel'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="wlFxParam">Selected White label:</label>
								<select id="wlFxParam" name="wlFxParam" class="form-control select2">
									<?php foreach ($wlFxOptions as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['wlFxParam']) && $_POST['wlFxParam'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<button type="submit" class="btn btn-primary">Search</button>
					</form>
					
					<br/>
					<div class="container-fluid" style="padding-top:0.5%;padding-left:0.5%;">
						<?php 
							$headings_mapping = [
								'email' => 'Email Counts',
								'paper' => 'Paper Counts'
							];
							
							$numeric_fields = [
							'count', 
							'wlValue'
							];
							
							
							
							if(in_array(10,$rightsArray)) {
								echo createHtmlTable($data, $headings_mapping, $numeric_fields, $wlAr);
							}
							else{
								echo "0 results";
							}
							//print("Data: ");
							//print_r($data);
						?>
					</div>
					
				</div>
			
				<script>
				
					$(document).ready(function() {
						 $('.select2').select2({
							 placeholder: "Select a value",
							 allowClear: true
						 });
					});
					function exportTableToCSV(filename, tableId) {
						var csv = [];
						var rows = document.querySelectorAll(`#${tableId} tr`);
						var headers = [];
					 
						// Loop through the header row to build the headers
						var headerRow = rows[0].querySelectorAll("th");
						for (var i = 0; i < headerRow.length; i++) {
							var headerText = headerRow[i].innerText.trim();
							headers.push(headerText);
							// Check if any row contains a percentage for this column
							for (var j = 1; j < rows.length; j++) {
								var cellText = rows[j].querySelectorAll("td")[i].innerText.trim();
								if (/\(.+%\)/.test(cellText)) {
									headers.push(`${headerText} %age`); // Add percentage column header
									break; // Only add the percentage header once
								}
							}
						}
						csv.push(headers.join(","));
						// Process each row
						for (var i = 1; i < rows.length; i++) { // Start from 1 to skip headers
							var row = [];
							var cols = rows[i].querySelectorAll("td");
							for (var j = 0; j < cols.length; j++) {
								var cellText = cols[j].innerText.trim();
								// Check if the cell contains a value with a percentage
								var match = cellText.match(/^([0-9,.]+)\s\((.+)%\)$/);
								if (match) {
									row.push(removeCommas(match[1])); // Add the main number without commas
									row.push(match[2]); // Add the percentage as a separate column
								} else {
									// Remove commas from numbers if found and push the cleaned value
									row.push(removeCommas(cellText));
								}
							}
							csv.push(row.join(",")); // Join and push the row to CSV array
						}
						// Download CSV file
						downloadCSV(csv.join("\n"), filename);
					}
					 
					// Function to remove commas from numbers
					function removeCommas(value) {
						// If the value is a number (contains digits and commas), remove commas
						return value.replace(/(\d),(\d)/g, "$1$2");
					}
					 
					function downloadCSV(csv, filename) {
						var csvFile = new Blob([csv], { type: "text/csv" });
						var downloadLink = document.createElement("a");
						downloadLink.download = filename;
						downloadLink.href = window.URL.createObjectURL(csvFile);
						downloadLink.style.display = "none";
						document.body.appendChild(downloadLink);
						downloadLink.click();
					}

					function loadBounceDetails(dataArray, modalId, clnt, herMonth) {
						var xhr = new XMLHttpRequest();
						var uri = 'fetch_modal_details.php';
						xhr.open('POST', uri, true);
						xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
						xhr.onload = function() {
							if (xhr.status === 200) {
								document.getElementById(modalId + 'Content').innerHTML = xhr.responseText;
							} else {
								document.getElementById(modalId + 'Content').innerHTML = '<p>Error loading data.</p>';
							}
						};
						xhr.send(JSON.stringify({ dataArr: dataArray, modalId: modalId, clnt: clnt, herMonth: herMonth }));
					}
				</script>
				<?php include './configs/bottomScripts.php';  ?>
				
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
