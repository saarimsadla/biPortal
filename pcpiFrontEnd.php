<?php
	session_start();
	if (!$_SESSION['authenticated']) {
		header("Location: index.html");
		exit();
	}
	 
	$defaultWhiteLabel =  isset($_SESSION['defWhitelabel']) ? $_SESSION['defWhitelabel'] : 0;
	$whiteLabel = $defaultWhiteLabel;
	$pgName = "pcpiFrontEnd.php";
	$wl = isset($_SESSION['whiteLabels']) ? $_SESSION['whiteLabels'] : null;
	$rt = isset($_SESSION['rghts']) ? $_SESSION['rghts'] : null;
	$usName = isset($_SESSION['uname']) ? $_SESSION['uname'] : null;
	//$rt = "1,3,5";

	//echo " user:$usName  wl:$wl  rgt:$rt  " ;

	$rightsArray = explode(",",$rt);
	$whtLblArray = explode(",",$wl);

	function getRunType($date) {
		// Convert the date string to a DateTime object
		$dateObj = DateTime::createFromFormat('Ymd', $date);
		
		// Get the day of the month
		$day = (int)$dateObj->format('d');
		
		// Get the total number of days in the month
		$daysInMonth = (int)$dateObj->format('t');
		
		// Determine if the date is closer to the start or end of the month checking first 3 dayas and last 10 days of month to mark as billing rest is AMI RUnned		
		if ($day <= 3 || $day > $daysInMonth - 10) {
			return "Billing Run";
		} 
		else {
			return "AMI Run";
		}
		

	}
	
chdir(__DIR__);
$operatingDir = "";

// --- OS detection ---
$osName = PHP_OS_FAMILY;
if ($osName === "Windows") {
	$operatingDir = realpath(__DIR__); 
} else {
	$operatingDir = realpath(__DIR__);
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Exclusions Report</title>
		<?php include './configs/headerScripts.php'; ?>
		<style>
			.tab-content {
				margin-top: 20px;
			}
			
		</style>
	</head>
	<body>
		<div class="container-fluid">
			<?php
				include './configs/navbarSetup.php'; 
				
				
				
				
				function getValDiff($data, $fieldReq, $hersRepId, $cohortId, $hersTypSorter) { //, $hersRDate
					$lastMonthRecord = null;
					$hersSorterT = null;
					
					//print_r("params: $feildReq, $hersRepId, $hersRDate, $cohortId ");
					//print_r($data);

					foreach ($data as $record) {
						if ($record['cohortId'] == $cohortId) {
							//$record['hersReportRuntime'] < $hersRDate && 
							if ($record['hersReportId'] < $hersRepId && $record['hersRunTypeSorter'] == $hersTypSorter) {
								if ($lastMonthRecord === null || ($record['hersReportRuntime'] > $lastMonthRecord['hersReportRuntime'] && $lastMonthRecord['hersReportId'] > $hersRepId  && $lastMonthRecord['hersRunTypeSorter'] == $hersTypSorter)) {
									$lastMonthRecord = $record;
									$hersSorterT = $record['hersRunTypeSorter'];
								//	print_r (" ");
								//	print_r(" last month data found");
								//	print_r (" ");
							//		print_r($lastMonthRecord);
									
								}
							}
						}
					}

					if ($lastMonthRecord !== null) {
						foreach ($data as $record) {// && $record['hersReportRuntime'] == $hersRDate
							if ($record['cohortId'] == $cohortId && $record['hersReportId'] == $hersRepId && $record['hersRunTypeSorter'] == $hersSorterT) {
								//print_r(" diff calc");
								return $record[$fieldReq] - $lastMonthRecord[$fieldReq];
							}
						}
					}

					return 0; // Return 0 if no matching record is found
				}



				
				$database_name = "hursPortal";
				$collection_name = "PCPI_MASTERDATA.";

				$initial_query = [
					"whiteLabelId" => (int)$defaultWhiteLabel,
					"cohortName" => [
						'$not' => [
							'$regex' => ".*\\(NO LONGER ACTIVE\\).*"
						]
					]
				];

				$initial_query_json = json_encode($initial_query, JSON_UNESCAPED_SLASHES);
				$ag_json = json_encode([]); // same as "[]"

				$cmd = sprintf(
					'python %s %s %s %s %s %s',
					escapeshellarg("./pythonScripts/mongodb_query.py"),
					escapeshellarg($database_name),
					escapeshellarg($collection_name),
					'"'.str_replace('"', '""', $initial_query_json).'"', // escape inner quotes,
					escapeshellarg("q"),
					'"'.$ag_json.'"'
				);

				$initial_output = shell_exec("$cmd 2>&1");
				
				// print_r($cmd);
				
				//print_r("     ss: ".$initial_output);
				$initial_data = json_decode($initial_output, true);
				
				//print_r($initial_data);
				
				if (empty($initial_data)) {
					//print_r( "JSON ERROR: " . json_last_error_msg());
					die(" Error: Unable to fetch initial data from MongoDB.");
				}
			 
				// Filter options
				$pcpiRunIdOptions = array_column($initial_data, 'hersReportStartMonth', 'hersReportStartMonth');
				arsort($pcpiRunIdOptions); // Sort in descending order
				
				$stateParamOptions = array_unique(array_map(function($item) {
					return trim(substr($item['cohortName'], 0, 2));
				}, $initial_data));
				$cohortIdOptions = array_column($initial_data, 'cohortName', 'cohortId');
				
				// Find the maximum value
				$maxValue = max($pcpiRunIdOptions);

				// Initialize the second maximum value
				$secondMaxValue = PHP_INT_MIN;

				// Loop through the array to find the second maximum value
				foreach ($pcpiRunIdOptions as $value) {
					if ($value != $maxValue && $value > $secondMaxValue) {
						$secondMaxValue = $value;
					}
				}

				//echo "Max Value: " . $maxValue . "\n";
				//echo "Second Max Value: " . $secondMaxValue . "\n";

			 
				// Form submission handling
				$query_json = "";
				if ($_SERVER["REQUEST_METHOD"] == "POST") {
					// Retrieve form data
					$pcpiRunId = $_POST['pcpiRunId'];
					$pcpiRunIdT = $_POST['pcpiRunIdT'];
					$stateParam = $_POST['stateParam'];
					$cohortId = $_POST['cohortId'];
			 
					// Adjust query based on form data
					$query_conditions = [];
					$query_conditions[] = ' "whiteLabelId": ' . $defaultWhiteLabel . ' ' ;
					
					if ($pcpiRunId > $pcpiRunIdT) {
						$pp = $pcpiRunId;
						$pcpiRunId = $pcpiRunIdT;
						$pcpiRunIdT = $pp;
					} else if ($pcpiRunIdT == 'All' && $pcpiRunId != 'All') {
						$pcpiRunIdT = $pcpiRunId;
					} else if ($pcpiRunIdT != 'All' && $pcpiRunId == 'All') {
						$pcpiRunId = $pcpiRunIdT;
					}
					
					if ($pcpiRunId !== 'All' && $pcpiRunIdT !== 'All') {
						$query_conditions[] = ' "hersReportStartMonth": {"$gte": ' . $pcpiRunId . ', "$lte": ' . $pcpiRunIdT . '} ';
					}
			 
					if ($stateParam !== 'All') {
						// Construct regex pattern to match the first two letters of cohortName
						$regex_pattern = '^' . $stateParam;
						$query_conditions[] = ' "cohortName": {"$regex": "' . $regex_pattern . '"} ';
					}
			 
					if ($cohortId !== 'All') {
						$query_conditions[] = ' "cohortId": ' . $cohortId . ' ';
					}
					
					if ($pcpiRunId == 'All' && $pcpiRunIdT == 'All' && $stateParam == 'All' && $cohortId == 'All') {
						$query_json = '{"whiteLabelId": '.$defaultWhiteLabel.',"cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}}}';
					}
					else{
						$query_json = '{' . implode(', ', $query_conditions) . ', "cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}}}';
					}
				} else {
					$_POST['pcpiRunIdT'] = $maxValue;
					$_POST['pcpiRunId'] = $secondMaxValue;
					$query_json = '{"whiteLabelId": '.$defaultWhiteLabel.' ,"hersReportStartMonth": {"$gte": ' . $secondMaxValue . ', "$lte": ' . $maxValue . '} , "cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}}}';
				}
			 
				//print(" ;data query: ");
				//print_r($query_json);
				//print(" ");
				// Fetch data from MongoDB based on query
				$initial_query_json = json_encode($query_json, JSON_UNESCAPED_SLASHES);
				$ag_json = json_encode([]); // same as "[]"

				$cmd = sprintf(
					'python %s %s %s %s %s %s',
					escapeshellarg("./pythonScripts/mongodb_query.py"),
					escapeshellarg($database_name),
					escapeshellarg($collection_name),
					$initial_query_json, // escape inner quotes,
					escapeshellarg("q"),
					'"'.$ag_json.'"'
				);

				$initial_output = shell_exec("$cmd 2>&1");
				
				//print_r($cmd);
				
				//print_r("     ss: ".$initial_output);
				$data1 = json_decode($initial_output, true);
				
				//print_r($initial_data);
				
				if (empty($data1)) {
					//print_r( "JSON ERROR: " . json_last_error_msg());
					die(" Error: Unable to fetch data from MongoDB.");
				}
				else{
					
					foreach ($data1 as &$record) {
						if (trim($defaultWhiteLabel) === "46"){
							$runType = getRunType($record['hersReportRuntime']);
						}
						else{
							$runType = "N/A";
						}
						$record['hersRunType'] = $runType;
						$record['hersRunTypeSorter'] = ($runType === "AMI Run") ? 1 : 2;
					}
					
					// sort by cohortId desc then hersRunTypeSorter Asc then hersReportId desc
					usort($data1, function ($a, $b) {
						// Cast hersReportStartMonth, cohortId, and hersRunTypeSorter to integers for comparison
						$reportStartMonthA = (int)$a['hersReportRuntime'];
						$reportStartMonthB = (int)$b['hersReportRuntime'];
						$cohortIdA = (int)$a['cohortId'];
						$cohortIdB = (int)$b['cohortId'];
						$runTypeSorterA = (int)$a['hersRunTypeSorter'];
						$runTypeSorterB = (int)$b['hersRunTypeSorter'];
						
						// Sort by cohortId in descending order
					    if ($cohortIdA !== $cohortIdB) {
							return $cohortIdB - $cohortIdA;
						}
						
						// Sort by hersRunTypeSorter in descending order
						if ($runTypeSorterA !== $runTypeSorterB) {
							return $runTypeSorterB - $runTypeSorterA;
						}
						
					    // Sort by hersReportStartMonth in descending order
						return $reportStartMonthB - $reportStartMonthA;
	
					});
					
					$dt = $data1;
					//print_r(getValDiff($dt, 'PC_Counts', 2141, 20250307, 1721, 1));
					
					// Loop through the data array and assign the runType
					foreach ($data1 as &$record) {
						//$runType = getRunType($record['hersReportRuntime']);
						//$record['hersRunType'] = $runType;
						//$record['hersRunTypeSorter'] = ($runType === "AMI Run") ? 1 : 2;
						// , $record['hersReportRuntime']
						$record['PC_Counts_diff'] = getValDiff($dt, 'PC_Counts', $record['hersReportId'], $record['cohortId'], $record['hersRunTypeSorter']);
						$record['DC_Counts_diff'] = getValDiff($dt, 'DC_Counts', $record['hersReportId'], $record['cohortId'], $record['hersRunTypeSorter']);
						$record['PI_Counts_diff'] = getValDiff($dt, 'PI_Counts', $record['hersReportId'], $record['cohortId'], $record['hersRunTypeSorter']);
					}

					// Print the updated data array
					//print_r($data1);
				}
				
				//'treatmentOptOuts' => 'Treatment Opted Out Cust'
			 
				$headings_mapping_dtl = [
					'clientName' => 'Client Name',
					'hersReportId' => 'Report ID',
					'hersReportStartMonth' => 'Report Start Month',
					'hersReportRuntime' => 'Report Runtime',
					'hersRunType' => 'Run Type',
					'cohortId' => 'Cohort ID',
					'cohortName' => 'Cohort Name',
					'bhrCounts' => 'BHR Counts',
					'archivedCounts' => 'Archived Counts',
					'treatmentGroupSize' => 'Treatment Group Size',
					'treatmentActiveCust' => 'Treatment Active Cust',
					'treatmentInActiveCust' => 'Treatment Inactive Cust',
					'treatmentOptOuts' => 'Treatment Opt Out Cust',
					'PC_Counts' => 'PC Counts',
					'PC_exclusionCounts' => 'PC Exclusion Counts',
					'DC_Counts' => 'DC Counts',
					'DC_exclusionCounts' => 'DC Exclusion Counts',
					'DC_ProcessedEmail' => 'DC Processed Email Counts',
					'DC_ProcessedPrints' => 'DC Processed Print Counts',
					'PI_Counts' => 'PI Counts',
					'PI_exclusionCounts' => 'PI Exclusions Counts',
					'emailSentCounts' => 'Email Sent Counts',
					'paperSentCounts' => 'Paper Sent Counts',
					'sfmcDeliveredCounts' => 'SFMC Recieved',
					'sfmcBouncedCounts' => 'SFMC Bounced',
					'sfmcSentCounts' => 'SFMC Delivered',
					'sfmcOpenCounts' => 'SFMC Opened',
					'sfmcClickCounts' => 'SFMC Click',
					'sfmcUnSubscribeCounts' => 'SFMC Un-Subscribe',
					'sfmcNoUpdateCounts' => 'SFMC No Update',
					'PC_manualExclusions' => 'PC Manual Exclusions',
					'PC_noBill' => 'PC No Bill',
					'PC_insufficientMonthsOfBillingData' => 'PC Insufficient Months of Billing Data',
					'PC_insufficientNearestNeighbours' => 'PC Insufficient Nearest Neighbours',
					'PC_zeroUsage' => 'PC Zero Usage',
					'PC_consecutiveZeroUsage' => 'PC Consecutive Zero Usage',
					'PC_negativeUsage' => 'PC Negative Usage',
					'whiteLabelId' => 'White Label ID',
					'opcoId' => 'Opco ID'
				];
				//'hersRunTypeSorter' => 'Report Sorter',
				$headings_mapping_smry = [
					'clientName' => 'Client Name',
					'hersReportId' => 'Report ID',
					'hersReportStartMonth' => 'Report Start Month',
					'hersReportRuntime' => 'Report Runtime',
					'hersRunType' => 'Run Type',
					'cohortId' => 'Cohort ID',
					'cohortName' => 'Cohort Name',
					'PC_Counts' => 'PC Counts',
					'PC_Counts_diff' => 'PC Counts Delta',
					'DC_Counts' => 'DC Counts',
					'DC_Counts_diff' => 'DC Counts Delta',
					'DC_ProcessedEmail' => 'DC Processed Email Counts',
					'DC_ProcessedPrints' => 'DC Processed Print Counts',
					'PI_Counts' => 'PI Counts',
					'PI_Counts_diff' => 'PI Counts Delta',
					'emailSentCounts' => 'Email Sent Counts',
					'sfmcSentCounts' => 'SFMC Delivered',
					'paperSentCounts' => 'Paper Sent Counts',
					'whiteLabelId' => 'White Label ID',
					'opcoId' => 'Opco ID',
					'sfmcDeliveredCounts' => 'DD'
				];
			 
				$numeric_fields = [
					'bhrCounts', 'archivedCounts', 'treatmentGroupSize', 'treatmentActiveCust',
					'treatmentInActiveCust', 'PC_Counts', 'PC_exclusionCounts', 'DC_Counts',
					'DC_exclusionCounts', 'PI_Counts', 'emailSentCounts',
					'paperSentCounts', 'PC_manualExclusions', 'PC_noBill',
					'PC_insufficientMonthsOfBillingData', 'PC_insufficientNearestNeighbours',
					'PC_zeroUsage', 'PC_consecutiveZeroUsage', 'PC_negativeUsage', 'DC_permanentExclusions',
					'DC_invalidEmail', 'DC_inactiveMeter', 'DC_insufficientTips', 'DC_efficiencyCap',
					'DC_monthEfficientNeighbourZero', 'DC_monthSimilarNeighbourZero', 'DC_ProcessedEmail', 'DC_ProcessedPrints',
					'sfmcDeliveredErrorCounts',
					'sfmcDeliveredCounts',
					'sfmcBouncedCounts',
					'sfmcSentCounts',
					'sfmcOpenCounts',
					'sfmcClickCounts',
					'sfmcUnSubscribeCounts',
					'sfmcNoUpdateCounts', 'PC_Counts_diff', 'DC_Counts_diff', 'PI_exclusionCounts','PI_Counts_diff'
				];
				
				/*
				usort($data, function ($a, $b) {
					// Cast PCPIRunId and hersReportStartMonth to integers for comparison
					$pcpiRunIdA = (int)$a['PCPIRunId'];
					$pcpiRunIdB = (int)$b['PCPIRunId'];
					$hersReportIdA = (int)$a['hersReportId'];
					$hersReportIdB = (int)$b['hersReportId'];

					// Extract parts of the string for additional sorting
					$partA0 = explode(' ', $a['cohortName'])[0];
					$par}tB0 = explode(' ', $b['cohortName'])[0];
					$partA2 = explode(' ', $a['cohortName'])[2];
					$partB2 = explode(' ', $b['cohortName'])[2];

					// Sort by PCPIRunId in descending order
					if ($pcpiRunIdA !== $pcpiRunIdB) {
						return $pcpiRunIdB - $pcpiRunIdA;
					}

					// If PCPIRunId values are equal, sort by hersReportStartMonth in descending order
					if ($hersReportIdA !== $hersReportIdB) {
						return $hersReportIdB - $hersReportIdA;
					}

					// If hersReportStartMonth values are equal, sort by the first part of the string
					if ($partA0 !== $partB0) {
						return strcmp($partA0, $partB0);
					}

					// If the first parts are equal, sort by the third part of the string
					return strcmp($partA2, $partB2);
				});
			*/
				
				
				//print_r(getValDiff($data1, 'PC_Counts', 2062, 20250102	, 1721));
				
				function createHtmlTable($data, $headings_mapping, $numeric_fields, $isSummary)
				{
					//if ($isSummary !== "Y"){
					//	reset($data);
					//	echo "is detail";
					//	print_r($data);
					//}
					// Initialize totals array
					try{
						$totals = array_fill_keys(array_keys($headings_mapping), 0);
					 
						// Calculate totals for numeric fields
						foreach ($data as $row) {
							/*if ($row['wlID'] !== $defaultWhiteLabel){
								remove row here
							}else{*/
								foreach ($headings_mapping as $heading => $display_name) {
									if (in_array($heading, $numeric_fields)) {
										$totals[$heading] += $row[$heading];
									}
								}
							//}
						}
						//print_r($totals);
						$fileNme = "";
						$tbleNme = "";
						$mdlId = "";
						if ($isSummary == "Y"){
							$fileNme = "exclusionsRepSmryData.csv";
							$tbleNme = "smryDataTable";
							$mdlId = "s";
						}
						else{
							$fileNme = "exclusionsRepDetailedData.csv";
							$tbleNme = "masterDataTable";
							$mdlId = "d";
						}
						$html = '<div class="table-responsive">';
							$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\''.$fileNme.'\', \''.$tbleNme.'\')">Download CSV</button>';
							$html .= '<table class="table table-bordered table-sticky-header" id="'.$tbleNme.'">';
								$html .= '<thead class="thead-light">';
								 
									// Headings row
									$html .= '<tr>';
									foreach ($headings_mapping as $heading => $display_name) {
										if ($display_name !== 'DD') {
											if ($heading === 'cohortName') {
												$html .= '<th style="width: 300px;">' . htmlspecialchars($display_name) . '</th>';
											} else {
												$html .= '<th>' . htmlspecialchars($display_name) . '</th>';
											}
										}
									}
									$html .= '</tr>';
							 
								$html .= '</thead>';
								$html .= '<tbody>';
									
									// Totals row
									$html .= '<tr>';
										$first_column = True;
										foreach ($headings_mapping as $heading => $display_name) {
											if ($display_name !== 'DD') {
												if ($first_column){
													$html .= '<td><b>Total:</b></td>';
													$first_column = false;
												}elseif (in_array($heading, $numeric_fields)) {
													if ($heading == 'sfmcBouncedCounts') {
														$html .= '<td><b>' . number_format($totals[$heading]) . '</b> ('.round((($totals[$heading]/$totals['sfmcDeliveredCounts'])*100),2).'%)</td>';
													}
													else if ($heading == 'sfmcSentCounts' || $heading == 'sfmcOpenCounts' || $heading == 'sfmcClickCounts' || $heading == 'sfmcUnSubscribeCounts' || $heading == 'sfmcNoUpdateCounts') {
														$html .= '<td><b>' . number_format($totals[$heading]).'</b> ('.round((($totals[$heading]/$totals['sfmcDeliveredCounts'])*100),2).'%)'. '</td>';	
													}
													else if ($heading ==  'DC_exclusionCounts') {
														$html .= '<td><b>' . number_format($totals[$heading]) .'</b> ('.round((($totals[$heading]/$totals['PC_Counts'])*100),2).'%)'. '</td>';	
													}
													else if ($heading ==  'PC_exclusionCounts') {
														$html .= '<td><b>' . number_format($totals[$heading]) .'</b> ('.round((($totals[$heading]/($totals['treatmentActiveCust'] - $totals['treatmentOptOuts']))*100),2).'%)'. '</td>';	
													}
													else if ($heading ==  'PI_exclusionCounts') {
														$html .= '<td><b>' . number_format($totals[$heading]) .'</b> ('.round((($totals[$heading]/$totals['DC_Counts'])*100),2).'%)'. '</td>';	
													}
													else if ($heading ==  'treatmentOptOuts') {
														$html .= '<td><b>' . number_format($totals[$heading]) .'</b> ('.round((($totals[$heading]/($totals['treatmentActiveCust']))*100),2).'%)'. '</td>';	
													}
													else if ($heading ==  'treatmentInActiveCust') {
														$html .= '<td><b>' . number_format($totals[$heading]) .'</b> ('.round((($totals[$heading]/($totals['treatmentGroupSize']))*100),2).'%)'. '</td>';	
													}
													else if (strpos($heading, '_diff') !== false) {	
														$clrt = null;
														if($totals[$heading] < 0){
															$clrt = '<td style=color:red >'. number_format($totals[$heading]) .'</td>';
														}
														else {
															$clrt = '<td style=color:darkblue ><b>'. number_format($totals[$heading]) .'</b></td>';
														}
														$html .= $clrt;	
													}
													else{
														$html .= '<td><b>' . number_format($totals[$heading]) . '</b></td>';
														
													}
												} else {
													$html .= '<td></td>';
												}
											}
										}
									$html .= '</tr>';
									
									// Data rows
									foreach ($data as $rowIndex => $row) {
										$html .= '<tr>';
											foreach ($headings_mapping as $heading => $display_name) {
												if ($display_name !== 'DD') {
													if (in_array($heading, $numeric_fields)) {
														if ($heading == 'sfmcBouncedCounts' || $heading == 'DC_exclusionCounts' || $heading == 'PC_exclusionCounts' || $heading == 'PI_exclusionCounts') { //DC_exclusionCounts,PC_exclusionCounts,PI_exclusionsCount,sfmcBouncedCounts //|| $heading == 'PC_exclusionCounts' || $heading == 'DC_exclusionCounts' 
															$modalId = $heading . $rowIndex . (int)$row['hersReportId']. $mdlId;
															if($heading == 'DC_exclusionCounts'){
																$hdd = $heading.'_hers';
																$valTShw = round((($row[$heading]/$row['PC_Counts'])*100),2);
															}
															elseif($heading == 'PC_exclusionCounts'){
																$hdd = $heading.'_hers';
																$valTShw = round((($row[$heading]/($row['treatmentActiveCust'] - $row['treatmentOptOuts']))*100),2);
															}
															elseif($heading == 'PI_exclusionCounts'){
																$hdd = $heading.'_hers';
																$valTShw = round((($row[$heading]/($row['DC_Counts']))*100),2);
															}
															else if($heading == 'sfmcBouncedCounts'){
																$hdd = $heading;
																$valTShw = round((($row[$heading]/$row['sfmcDeliveredCounts'])*100),2);
															}
															//print($modalId);
															$html .= '<td><a href="#" data-toggle="modal" data-target="#' . $modalId . '" onclick="loadBounceDetails(\'' . htmlspecialchars($row['whiteLabelId']) . '\', \'' . htmlspecialchars($row['hersReportId']) . '\', \'0\', \''. htmlspecialchars($row[$heading]) . '\', \'' . htmlspecialchars($modalId) . '\',\''.htmlspecialchars($hdd).'\')"><b>' . number_format($row[$heading]) .'</b> ('.$valTShw.'%)'.'</a></td>';
															$html .= '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">';
																$html .= '<div class="modal-dialog" role="document" style="max-width:75%; width: 75%">';
																	$html .= '<div class="modal-content">';
																		$html .= '<div class="modal-header">';
																			if ($heading == 'sfmcBouncedCounts'){
																				$html .= '<h5 class="modal-title" id="' . $modalId.'bncc'. 'Label">Bounce Rate Details</h5>';
																			}
																			if ($heading == 'PC_exclusionCounts'){
																				$html .= '<h5 class="modal-title" id="' . $modalId.'pcec' . 'Label">Post Comparables Exclusion Details</h5>';
																			}
																			if ($heading == 'DC_exclusionCounts'){
																				$html .= '<h5 class="modal-title" id="' . $modalId.'dcec' . 'Label">Post DCS Exclusion Details</h5>';
																			}
																			if ($heading == 'PI_exclusionCounts'){
																				$html .= '<h5 class="modal-title" id="' . $modalId .'imec' . 'Label">Post Imaging Exclusion Details</h5>';
																			}
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
														} else {
															if ($heading == 'sfmcSentCounts' || $heading == 'sfmcOpenCounts' || $heading == 'sfmcClickCounts' || $heading == 'sfmcUnSubscribeCounts' || $heading == 'sfmcNoUpdateCounts') {
																$html .= '<td><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/$row['sfmcDeliveredCounts'])*100),2).'%)'. '</td>';	
															}
															else if ($heading ==  'treatmentOptOuts') {
																$html .= '<td><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/($row['treatmentActiveCust']))*100),2).'%)'. '</td>';	
															}
															else if ($heading ==  'treatmentInActiveCust') {
																$html .= '<td><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/($row['treatmentGroupSize']))*100),2).'%)'. '</td>';	
															}
															else if (strpos($heading, '_diff') !== false) {	
																$clr = null;
																if($row[$heading] < 0){
																	$clr = '<td style=color:red >'. number_format($row[$heading]) .'</td>';
																}
																else {
																	$clr = '<td style=color:darkblue ><b>'. number_format($row[$heading]) .'</b></td>';
																}
																$html .= $clr;	
															}
															else{
																$html .= '<td>' . number_format($row[$heading]) . '</td>';
															}
														}
													} else {
														$html .= '<td>' . htmlspecialchars($row[$heading]) . '</td>';
													}
												}
											}
										$html .= '</tr>';
									}
								$html .= '</tbody>';
							$html .= '</table>';
						$html .= '</div>';
					}
					catch (Exception $e) {
						echo 'Error: ',  $e->getMessage(), "\n";
					}
					return $html;
				}
			?>
			<div class="container-fluid" style="padding-top:0.5%;padding-left:0.5%;">
				<div class="row">
					<div class="col-12 text-right mt-2">
						<a href="https://franklinenergy.sharepoint.com/:x:/r/sites/planet/_layouts/15/doc2.aspx?action=default&file=Post_Comparables_Post_Imaging_system_mapping_v3.xlsx&mobileredirect=true&sourcedoc=%7BEFDD84B0-D43D-4417-8707-81A369FCC491%7D" target="_blank" class="btn btn-success btn-sm">Report Mapping</a>
					</div>
				</div>
				<form method="post" id="secondForm">
					<div class="form-row">
						<div class="form-group col-md-3">
							<label for="pcpiRunId">From:</label>
							<select id="pcpiRunId" name="pcpiRunId" class="form-control select2" required>
								<option value="All">All</option>
								<?php foreach ($pcpiRunIdOptions as $value => $display): ?>
									<option value="<?= $value ?>" <?= isset($_POST['pcpiRunId']) && $_POST['pcpiRunId'] == $value ? 'selected' : '' ?>><?= $display ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group col-md-3">
							<label for="pcpiRunIdT">To:</label>
							<select id="pcpiRunIdT" name="pcpiRunIdT" class="form-control select2" required>
								<option value="All">All</option>
								<?php foreach ($pcpiRunIdOptions as $value => $display): ?>
									<option value="<?= $value ?>" <?= isset($_POST['pcpiRunIdT']) && $_POST['pcpiRunIdT'] == $value ? 'selected' : '' ?>><?= $display ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group col-md-3">
							<label for="stateParam">State:</label>
							<select id="stateParam" name="stateParam" class="form-control select2">
								<option value="All">All</option>
								<?php foreach ($stateParamOptions as $state): ?>
									<option value="<?= $state ?>" <?= isset($_POST['stateParam']) && $_POST['stateParam'] == $state ? 'selected' : '' ?>><?= $state ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group col-md-3">
							<label for="cohortId">Cohort:</label>
							<select id="cohortId" name="cohortId" class="form-control select2">
								<option value="All">All</option>
								<?php foreach ($cohortIdOptions as $value => $display): ?>
									<option value="<?= $value ?>" <?= isset($_POST['cohortId']) && $_POST['cohortId'] == $value ? 'selected' : '' ?>><?= $display ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<button type="submit" class="btn btn-primary">Search</button>
				</form>
				
				<br/>
				<ul class="nav nav-tabs">
					<?php if(in_array(2,$rightsArray)) {echo '<li class="nav-item">
						<a class="nav-link active" id="summaryData-tab" data-toggle="tab" href="#summaryData">Summary</a>
					</li>';}?>
					<?php if(in_array(3,$rightsArray)) {echo '<li class="nav-item">
						<a class="nav-link" id="masterData-tab" data-toggle="tab" href="#masterData">Details</a>
					</li>';}?>
				</ul>
				<?php 
					if(in_array(2,$rightsArray)) {
						echo '<div class="tab-content"> 
						<div class="tab-pane fade show active" id="summaryData">
							<!-- Smry Data rendering code here --> 
							';
								if(in_array(2,$rightsArray)) {
									//print_r($data1);
									echo createHtmlTable($data1, $headings_mapping_smry, $numeric_fields,"Y");  
								}
								else{
									echo "0 results";
								}
							
						echo ' </div> '; 
					}
					if(in_array(3,$rightsArray)) {
						echo '<div class="tab-pane fade" id="masterData">  
								  <!-- Master Data rendering code here -->  		  
								 ';
									if(in_array(3,$rightsArray)) {
										echo '<div style="clear: both;"></div>';
										//echo "Details to go here";
										echo createHtmlTable($data1, $headings_mapping_dtl, $numeric_fields,"N");  
									}
									else{
										echo "0 results";
									}
								echo' </div> 
							  </div> ';
					}
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
				let csv = [];
				let rows = document.querySelectorAll(`#${tableId} tr`);
				let headers = [];
				let headerRow = rows[0].querySelectorAll("th, td");

				// Loop through each column in the header row
				for (let i = 0; i < headerRow.length; i++) {
					let headerText = headerRow[i].innerText.trim();
					//console.log(headerText);
					headers.push(headerText); // Add the original header

					// Loop through each row in the column
					for (let j = 1; j < rows.length; j++) {
						let cell = rows[j].querySelectorAll("td")[i];
						//console.log(cell);
						if (!cell) {
							cell = document.createElement("td");
							rows[j].appendChild(cell);
						}
						let cellText = cell.innerText.trim();

						// Check if the cell contains a percentage value
						if (/\(.+%\)/.test(cellText)) {
							headers.push(`${headerText} %age`); // Add percentage column header
							break; // Only add the percentage header once
						}
					}
				}

				// Add headers to CSV
				csv.push(headers.join(","));

				// Loop through each row to add data to CSV
				for (let i = 1; i < rows.length; i++) {
					let row = [];
					let cols = rows[i].querySelectorAll("td");

					for (let j = 0; j < cols.length; j++) {
						let cellText = cols[j].innerText.trim();

						// Remove commas from the cell text
						cellText = cellText.replace(/,/g, "");

						

						// Check if the cell contains a percentage value and split it
						if (/\(.+%\)/.test(cellText)) {
							let [value, percentage] = cellText.split(" ");
							percentage = percentage.replace(/[()]/g, ""); // Remove parentheses
							row.push(value);
							row.push(percentage);
						}
						else{
							row.push(cellText);
						}
					}

					csv.push(row.join(","));
				}

				// Download CSV
				downloadCSV(csv.join("\n"), filename);
			}

			function downloadCSV(csv, filename) {
				let csvFile;
				let downloadLink;

				// CSV file
				csvFile = new Blob([csv], { type: "text/csv" });

				// Download link
				downloadLink = document.createElement("a");

				// File name
				downloadLink.download = filename;

				// Create a link to the file
				downloadLink.href = window.URL.createObjectURL(csvFile);

				// Hide download link
				downloadLink.style.display = "none";

				// Add the link to DOM
				document.body.appendChild(downloadLink);

				// Click download link
				downloadLink.click();
			}

			function loadBounceDetails(wlId, bdrEventId, transactionSr, actVal, modalId, flds) {
				var xhr = new XMLHttpRequest();
				var uri = 'fetch_bdr_modal_details.php?wlId=' + encodeURIComponent(wlId) + '&bdrEventId=' + encodeURIComponent(bdrEventId) + '&transactionSr=' + encodeURIComponent(transactionSr) + '&sfmcBouncedCounts='+ encodeURIComponent(actVal) + '&fldName='+encodeURIComponent(flds); 
				console.log(uri);
				xhr.open('GET',uri, true);
				xhr.onload = function() {
					if (xhr.status === 200) {
						document.getElementById(modalId + 'Content').innerHTML = xhr.responseText;
					} else {
						document.getElementById(modalId + 'Content').innerHTML = '<p>Error loading data. </p>'; //'+uri+'
					}
				};
				xhr.send();
			}
		</script>
		<?php include './configs/bottomScripts.php';  ?>
	</body>
</html>