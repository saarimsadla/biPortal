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
$custidPRM 	= "";
$frcht	 	= "";
$repType	= "";
$runType	= "";
$prdType	= "";
$reportIds	= Null;
$usrType= '';

$fromReportIDs = [];
$sendOptions = [];
$chrtNames = [];
$reportType = [];
$runTypeA = [];
$productType = [];
$downloadAllArray[] = [];
$id_MainData[] = [];
$id_repId[] = [];
$tp5Rps = [];


//echo $defaultWhiteLabel;

// server request handle here
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	// Retrieve form data
/*	$frmRepId 	= $_POST['frmRepId'];
	$toRepId 	= $_POST['toRepId'];
	$sndT 		= $_POST['sndT'];
	$custidPRM 	= $_POST['custidPRM'];
	$frcht 		= $_POST['frcht'];
	$repType 	= $_POST['repType'];
	$runType 	= $_POST['runType'];
	$prdType 	= $_POST['prdType'];
*/

	// Set default values and sanitize POST inputs
	$frmRepId  = isset($_POST['frmRepId']) && is_numeric($_POST['frmRepId']) ? (int)$_POST['frmRepId'] : 0;
	$toRepId   = isset($_POST['toRepId']) && is_numeric($_POST['toRepId']) ? (int)$_POST['toRepId'] : null;

	$sndT      = isset($_POST['sndT']) ? trim($_POST['sndT']) : 'N'; // default 'N' if not set
	$custidPRM = isset($_POST['custidPRM']) && is_numeric($_POST['custidPRM']) ? (int)$_POST['custidPRM'] : null;

	$frcht     = isset($_POST['frcht']) && $_POST['frcht'] !== "0" ? trim($_POST['frcht']) : null;
	$repType   = isset($_POST['repType']) && $_POST['repType'] !== "0" ? trim($_POST['repType']) : null;
	$runType   = isset($_POST['runType']) && $_POST['runType'] !== "0" ? trim($_POST['runType']) : null;
	$prdType   = isset($_POST['prdType']) && $_POST['prdType'] !== "0" ? trim($_POST['prdType']) : null;
	
	//print_r(" -> Server detected: From report id=".$frmRepId." To report id=".$toRepId." Send Type=".$sndT." custid lists=".$custidPRM." chortname=".$frcht);
	// Redirect to the same page to clear POST and query string
	//header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
	//exit;

}
else{
	if (isset($_GET['fri']) && !empty($_GET['fri'])) {
		$_POST['frmRepId']	= $_GET['fri'];
		$frmRepId			= $_GET['fri'];
	}else{
		$_POST['frmRepId']	= "0";
	}
	if (isset($_GET['tri']) && !empty($_GET['tri'])) {
		$_POST['toRepId']	= $_GET['tri'];
		$toRepId			= $_GET['tri'];
	}else{
		$_POST['toRepId']	= "0";
	}
	if (isset($_GET['sndT']) && !empty($_GET['sndT'])) {
		$_POST['sndT'] = $_GET['sndT'];
		$sndT = $_GET['sndT'];
	} else {
		$_POST['sndT'] = "N";
		$sndT = "N";
	}
	if (isset($_GET['cip']) && !empty($_GET['cip'])) {
		$_POST['custidPRM'] = $_GET['cip'];
		$custidPRM			= $_GET['cip'];
	}else{
		$_POST['custidPRM'] = "Comma seperated custids add here";
	}
	if (isset($_GET['fcht']) && !empty($_GET['fcht'])) {
		$_POST['frcht'] = $_GET['fcht'];
		$frcht			= $_GET['fcht'];
	}else{
		$_POST['frcht'] = "0";
	}
	if (isset($_GET['rpttp']) && !empty($_GET['rpttp'])) {
		$_POST['repType'] = $_GET['rpttp'];
		$repType		  = $_GET['rpttp'];
	}else{
		$_POST['repType'] = "0";
	}
	if (isset($_GET['rntp']) && !empty($_GET['rntp'])) {
		$_POST['runType'] = $_GET['rntp'];
		$runType		  = $_GET['rntp'];
	}else{
		$_POST['runType'] = "0";
	}
	if (isset($_GET['prtp']) && !empty($_GET['prtp'])) {
		$_POST['prdType'] = $_GET['prtp'];
		$prdType		  = $_GET['prtp'];
	}else{
		$_POST['prdType'] = "0";
	}
}


?>

	<!DOCTYPE html>
	<html lang="en">
		 
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Sampling System</title>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
 
			<?php include './configs/headerScripts.php';  ?>
			
		</head>
		 
		<body> 
		
			<!-- Bootstrap Navbar -->
			<div class="container-fluid">
				<!--navbar here -->
				<?php
					$pgName = "sampling_system.php";
					$varCntr = 0;
					//echo $pgName;
					include './configs/navbarSetup.php';
					// Function to find clientAbr by wlId
					
					function getClientByWlId($wlId) {
						if (isset($_SESSION['whiteLabelArr'])) {
							foreach ($_SESSION['whiteLabelArr'] as $item) {
								if ($item['wlId'] == $wlId) {
									return $item['clientAbr'];
								}
							}
						}
						return null; // Return null if wlId not found
					}
					
					function getRuntimeVals($repId, $wlId){
						$qry = 'select DATE_FORMAT(FROM_UNIXTIME(runtime), \'%Y%m%d\') runtime from hurs_runned_reports where id = '.$repId.' limit 1;';
									
						$python_command = 'python3.9 /var/www/html/hursPortal/pythonScripts/mysql_query.py '.$wlId.' '.escapeshellarg($qry);
						//print($python_command);
						$ini_out = shell_exec($python_command);
						$sndDtls = json_decode($ini_out, true);
						
						$res = 0;
						//print_r($sndDtls);
						foreach ($sndDtls as &$r) {
							$res = $r['runtime'];
						}
						return $res;
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
					
					function populateTheSelectionFields($dbNme,$colNme) { // this function populates all the search drop down feilds
						global $defaultWhiteLabel, $fromReportIDs, $sendOptions, $sndT, $rightsArray, $chrtNames, $reportType, $runTypeA, $productType, $id_repId, $reportIds;
						$database_name = $dbNme;
						$collection_name = $colNme;
						// Add "ALL" option with value 0
						$transactionSr = 0;
						$initial_query = [
							"transactionSr" => (int)$transactionSr
						];
						$initial_query_json = json_encode($initial_query, JSON_UNESCAPED_SLASHES);
						$showPmsFltr = "";
						$chrtFiltr = "";
						$repTypeFltr = "";
						$runTypeFltr = "";
						$prdTypeFltr = "";
						
						$ri = []; // initialize as empty array

						switch ($defaultWhiteLabel) {
							case 46:
								$ri['$gte'] = 2254;
								break;
							case 47:
								$ri['$gte'] = 1125;
								break;
							case 38:
								$ri['$gte'] = 1013;
								break;
							case 52:
								$ri['$gte'] = 1117;
								break;
							default:
								// leave $ri empty
								break;
						}
						
						if(in_array(15,$rightsArray)) {
							$showPmsFltr = '';
						}
						else{
							$showPmsFltr = ' , "showToPms" : "Y"';
						}
						
						if(isset($_POST['frcht']) && $_POST['frcht'] !== "0"){
							$chrtFiltr = ' , "cohort_name" : "'.$_POST['frcht'].'"';
						}
						if(isset($_POST['repType']) && $_POST['repType'] !== "0"){
							$repTypeFltr = ' , "reportType" : "'.$_POST['repType'].'"';
						}
						if(isset($_POST['runType']) && $_POST['runType'] !== "0"){
							$runTypeFltr = ' , "runtype" : "'.$_POST['runType'].'"';
						}
						if(isset($_POST['prdType']) && $_POST['prdType'] !== "0"){
							$prdTypeFltr = ' , "productType" : "'.$_POST['prdType'].'"';
						}
						
						$match = [
							'qaRemoved' => 'N',
							'passedQA'  => $sndT,
							'wlId'      => (int)$defaultWhiteLabel
						];

						// Add dynamic filters only if they are set
						if (!empty($frcht)) {
							$match['cohort_name'] = $frcht;
						}
						if (!empty($repType)) {
							$match['reportType'] = $repType;
						}
						if (!empty($runType)) {
							$match['runtype'] = $runType;
						}
						if (!empty($prdType)) {
							$match['productType'] = $prdType;
						}
						if (!empty($ri)) { // for reportId conditions
							$match['reportId'] = $ri;
						}

						// Build the aggregation array
						
						//print_r($match);
						$ag_array = [
							[
								'$match' => $match
							],
							[
								'$group' => [
									'_id' => [
										'reportId'    => '$reportId',
										'cohort_name' => '$cohort_name',
										'reportType'  => '$reportType',
										'runtype'     => '$runtype',
										'productType' => '$productType'
									]
								]
							],
							[
								'$project' => [
									'_id'         => 0,
									'reportId'    => '$_id.reportId',
									'cohortName'  => '$_id.cohort_name',
									'reportType'  => '$_id.reportType',
									'runtype'     => '$_id.runtype',
									'productType' => '$_id.productType'
								]
							]
						];
						
						$ag_json = json_encode($ag_array, JSON_UNESCAPED_SLASHES);


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
						
						$io_repId = $initial_output;
						
						//print_r($cmd);
						//print_r($io_repId);
						
						$id_repId = json_decode($io_repId, true);
						
						
						
						//print_r($id_repId);
						if (!$id_repId) {
							print_r($id_repId);
							//die("Error: Unable to fetch data report id data from MongoDB.");
							print_r("Error: Unable to fetch data report id data from MongoDB.");
						}
						else{
							rsort($id_repId);
							$fromReportIDs["0"] = "ALL";
							$chrtNames["0"] 	= "ALL";
							$reportType["0"] 	= "ALL";
							$runTypeA["0"] 		= "ALL";
							$productType["0"] 	= "ALL";
							foreach ($id_repId as $row) {
								$rId = (string) $row['reportId'];
								$dsply = (string) $row['reportId'] . ' | ' .$row['cohortName'];
								if (!isset($fromReportIDs[$rId])) {
									$fromReportIDs[$rId] = $dsply;
									$chrtNames[$row['cohortName']] = $row['cohortName'];
									$reportType[$row['reportType']] = $row['reportType'];
									$runTypeA[$row['runtype']] = $row['runtype'];
									$productType[$row['productType']] = $row['productType'];
								}
							}
							
							$reportIds = array_unique(array_column($id_repId, 'reportId'));
						
							//print_r($fromReportIDs);
							//print_r($sendOptions);
						}
						
						
						$sendOptions["F"] = "Failed";
						$sendOptions["Y"] = "Passed";
						$sendOptions["N"] = "In QA Queue";
						//print_r($ag);
						/*$python_command = 'python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query.py ' . escapeshellarg($database_name) . ' ' . escapeshellarg($collection_name) . ' ' . escapeshellarg($initial_query_json) . ' "ag" ' . escapeshellarg($agt);

						$io_repT = shell_exec($python_command);
						//print_r($python_command);
						$id_repT = json_decode($io_repT, true);
						//print_r($initial_data);
						if (!$id_repT) {
							print_r($id_repT);
							die("Error: Unable to fetch data report type data from MongoDB.");
						}
						else{
							$sendOptions["0"] = "ALL";
							foreach ($id_repT as $row) {
								$snT = (string) $row['reportType'];
								if (!isset($sendOptions[$snT])) {
									$sendOptions[$snT] = $snT;
								}
							}
						
							//print_r($fromReportIDs);
							//print_r($sendOptions);
						}*/
						return Null;
					}

					function getData($dbNme, $colNme) {
						global $defaultWhiteLabel, $id_repId, $id_MainData, $frmRepId, $toRepId ,$sndT ,$custidPRM, $downloadAllArray, $rightsArray, $sndT, $frcht, $usrType, $tp5Rps;
						
						$database_name = $dbNme;
						$collection_name = $colNme;
						
							$zipNMe = "";
							$zpId = 0;
							$dArray = [];

						
						// Fetch initial data to populate select options
						/*$initial_query_json = //'{ "reportId": 2062, "wlId": 46 }';
						'{
							"qry": { "reportId": 2062, "wlId": '.$defaultWhiteLabel.' },
							"fld": { 
								"reportId": 1, 
								"wlId": 1, 
								"reportType": 1, 
								"productType": 1,
								"cohortId":1,
								"custId": 1, 
								"printReport": 1,
								"_id": 0
							}
						}';
					 
						//print("ini query: ");
						//print_r($initial_query_json);
						//print(" ");
						$initial_command = "python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query.py $database_name $collection_name '$initial_query_json' 'q' '[]'";*/
						$sndFltr = '';
						$repIdFltr = '';
						$custidFltr = '';
						$showPmsFltr = '';
						$chrtFiltr = '';
						$repTypeFltr = '';
						$runTypeFltr = '';
						$prdTypeFltr = '';
						$torep = NULL;
						$ri = "";
						
						$top5ReportIds[] = [];
						// Step 1: Check if array is not empty
						if (!empty($id_repId)) {
							// Step 2: Extract unique reportIds
							$reportIds = array_column($id_repId, 'reportId');
							$uniqueReportIds = array_unique($reportIds);

							// Sort in descending order
							rsort($uniqueReportIds);

							// Get top 5
							$top5ReportIds = array_slice($uniqueReportIds, 0, 6);

							// Output result
							//print_r($top5ReportIds);
							//print_r($top5ReportIds[0]);
							//print_r($top5ReportIds[5]);
						} else {
							echo "Array is empty.";
						}
						
					
						
						// send type filter
						if (isset($_POST['sndT'])) {
							$sndFltr = ' ,"passedQA":"' . $_POST['sndT'] . '"';
						} else {
							$sndFltr = '';
						}

						// report ids filter
						if (isset($_POST['frmRepId']) && isset($_POST['toRepId'])) {
							if ($_POST['frmRepId'] == "0" && $_POST['toRepId'] == "0") {
								$repIdFltr = "";
							} else {
								if ($_POST['frmRepId'] > $_POST['toRepId']){
									$torep = $_POST['toRepId'];
									$_POST['toRepId'] = $_POST['frmRepId'];
									$_POST['frmRepId'] = $torep;
								}
								if (($_POST['frmRepId'] !== "0" && $_POST['toRepId'] == "0") || ($_POST['frmRepId'] == "0" && $_POST['toRepId'] !== "0") || ($_POST['frmRepId'] !== "0" && $_POST['toRepId'] !== "0")) {
									$repIdFltr = ' , "reportId": {"$gte": ' . $_POST['frmRepId'] . ', "$lte": ' . $_POST['toRepId'] . '} ';
								}
							}
						} else {
							$repIdFltr = "";
						}
						
						$cntr = 0;
						if(!empty($top5ReportIds)){
							foreach ($top5ReportIds as $r) {
								if ($cntr == 0) {
									$maxRepId = $r; // First value is max
								}
								$minRepId = $r; // Will end up being the last value
								$cntr++;
							}
						}

						
						if ($repIdFltr !== ""){
							$ri = "";
						}
						else{
							$ri = ' ,"reportId": { "$gte": '.$minRepId.', "$lte": '.$maxRepId.'} '; //2254
						}
						
						if(isset($_POST['custidPRM']) and $_POST['custidPRM'] !== Null and $_POST['custidPRM'] !== "Comma seperated custids add here"){
							$custidFltr = ' , "custId" : {"$in":['.$_POST['custidPRM'].']}';
						}
						else{
							$custidFltr = '';
						}
						
						
						if(isset($_POST['frcht']) && $_POST['frcht'] !== "0"){
							$chrtFiltr = ' , "cohort_name" : "'.$_POST['frcht'].'"';
						}
						
						if(isset($_POST['repType']) && $_POST['repType'] !== "0"){
							$repTypeFltr = ' , "reportType" : "'.$_POST['repType'].'"';
						}
						
						if(isset($_POST['runType']) && $_POST['runType'] !== "0"){
							$runTypeFltr = ' , "runtype" : "'.$_POST['runType'].'"';
						}
						if(isset($_POST['prdType']) && $_POST['prdType'] !== "0"){
							$prdTypeFltr = ' , "productType" : "'.$_POST['prdType'].'"';
						}
						
					//********* add for resume report hiding feature	
					//	if(in_array(15,$rightsArray)) {
							$showPmsFltr = '';
					//	}
					//	else{
					//		$showPmsFltr = ' , "showToPms" : "Y"';
					//	}
//					print_r($rightsArray);
					if (in_array(12, $rightsArray) && !in_array(15, $rightsArray)) {
						$usrType = 'PM';
					} elseif (in_array(15, $rightsArray)) {
						$usrType = 'QATM';
					} else {
						$usrType = 'Unknown';
					}

						
						//print_r("repFltr: " . $repIdFltr);
						//print_r(" sendFltr: " . $sndFltr);
					//	print_r(" custidFltr: " . $custidFltr);
					//	print_r(" spf: " . $showPmsFltr);
						
						// Base query
						$qry = [
							'qaRemoved' => 'N',
							'passedQA'  => $sndT,
							'wlId'      => (int)$defaultWhiteLabel
						];

						// Add dynamic filters only if they are set
						if (!empty($frcht)) {
							$qry['cohort_name'] = $frcht;
						}
						if (!empty($repType)) {
							$qry['reportType'] = $repType;
						}
						if (!empty($runType)) {
							$qry['runtype'] = $runType;
						}
						if (!empty($prdType)) {
							$qry['productType'] = $prdType;
						}
						if (!empty($custidPRM)) {
							$custIds = array_map('intval', explode(',', $custidPRM)); // convert comma-separated string to int array
							$qry['custId'] = ['$in' => $custIds];
						}

						// Proper reportId filter
						$reportIdFilter = []; // init

						if (!empty($repIdFltr)) {
							// Convert the string filter to a PHP array
							// Example: ' , "reportId": {"$gte": 2254, "$lte": 2270} ' 
							$reportIdFilter = [
								'$gte' => (int)$_POST['frmRepId'],
								'$lte' => (int)$_POST['toRepId']
							];
						} elseif (!empty($ri)) {
							$reportIdFilter = [
								'$gte' => (int)$minRepId,
								'$lte' => (int)$maxRepId
							];
						}

						// Add reportId filter only if it exists
						if (!empty($reportIdFilter)) {
							$qry['reportId'] = $reportIdFilter;
						}

						// showToPms filter
						if (!empty($showPmsFltr)) {
							$qry['showToPms'] = $showPmsFltr;
						}

						// Fields to return
						$fld = [
							'reportId'            => 1,
							'wlId'                => 1,
							'reportType'          => 1,
							'productType'         => 1,
							'cohortId'            => 1,
							'custId'              => 1,
							'cohort_name'         => 1,
							'printReport'         => 1,
							'sr'                  => 1,
							'client'              => 1,
							'downFileNme'         => 1,
							'runtime'             => 1,
							'runtype'             => 1,
							'showToPms'           => 1,
							'passedQA'            => 1,
							'qaReason'            => 1,
							'wooFields.updatedBy' => 1,
							'wooFields.updatedOn' => 1,
							'repVsPortal'         => 1,
							'_id'                 => 0
						];

						// Final PHP array ready for json_encode
						$initial_query_array = [
							'qry' => $qry,
							'fld' => $fld
						];

						// Convert to JSON for MongoDB
						$initial_query_json = json_encode($initial_query_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

						// Aggregation empty array
						$ag_json = json_encode([]);

						// Build shell command
						$cmd = sprintf(
							'python %s %s %s %s %s %s',
							escapeshellarg("./pythonScripts/mongodb_query.py"),
							escapeshellarg($database_name),
							escapeshellarg($collection_name),
							'"'.str_replace('"', '""', $initial_query_json).'"', // escape inner quotes
							escapeshellarg("q"),
							'"'.$ag_json.'"'
						);

						//print_r($cmd);
						$initial_output = shell_exec("$cmd 2>&1");

						
						$io_MainDat = $initial_output;
						$io_MainDat = preg_replace('/[[:^print:]]/', '', $io_MainDat);
						$io_MainDat = trim($io_MainDat);
						//print("data ");
						//print_r($io_MainDat);
						//print(" ");
						$id_MainData = json_decode($io_MainDat, true);
					//	var_dump(json_last_error_msg());
						
						//print_r($initial_data);
						if (!$id_MainData) {
							//print_r($id_MainData);
							print_r("Error: Unable to fetch data from MongoDB.");
						}
						else{
							/*usort($id_MainData, function($a, $b) {
								return $b['reportId'] <=> $a['reportId'];
							});*/
							//print_r($id_MainData);
							
							$rprtIds = array_column($id_MainData, 'reportId');
							$unqRpts = array_unique($rprtIds);

							// Sort in descending order
							rsort($unqRpts);

							// Get top 5
							$tp5Rps = array_slice($unqRpts, 0, 6);
							
							//print_r($tp5Rps);
							
							usort($id_MainData, function($a, $b) {
								// First, compare by reportId in descending order
								$reportIdComparison = $b['reportId'] <=> $a['reportId'];
								if ($reportIdComparison !== 0) {
									return $reportIdComparison;
								}
								// If reportId is the same, compare by sr in ascending order
								return $a['sr'] <=> $b['sr'];
							});
							// Create array for download purposes
							foreach ($id_MainData as &$record) {
								$uxt = time();
								$ridC = $record['reportId'];
								if ($ridC !== $zpId) {
									// Store the current array before switching to a new one
									if (!empty($dArray)) {
										$downloadAllArray[$zipNMe] = $dArray;
									}
							 
									// Generate new zip file name
									$zipNMe = $record['client'] . '_' . str_replace(' ', '_', $record['cohort_name']) . '_' . $record['reportType'] . '_' . $record['wlId'] . '_' . $record['reportId'] . '_' . $uxt . '.zip';
									// Reset dArray for the new reportId
									$zpId = $record['reportId'];
									$dArray = [];  // Proper reset
								}
							 
								// Add the current PDF record to the array
								$dArray[] = [
									'pdfNme' => $record['downFileNme'],
									'linkUrl' => $record['printReport'],
									'reportId' => $record['reportId'],
									'custid' => $record['custId'],
									'sr' => $record['sr'],
									'wlId' => $record['wlId'],
									'repVsPortal' => isset($record['repVsPortal']) ? $record['repVsPortal'] : null
								];
							}
							 
							// Ensure the last set is added
							if (!empty($dArray)) {
								$downloadAllArray[$zipNMe] = $dArray;
							}
							
							$downloadAllArray = array_filter($downloadAllArray, function ($item) {
								return !empty($item); // Removes any empty sub-arrays
							});

							// Print the updated data array
							//print_r($_SESSION['whiteLabelArr']);
							//print_r($initial_data);
							
							//print_r($downloadAllArray);
						}
					
						//print_r($zipFileNme);
						
						// Start the HTML output
						//$html = '<p>*Please click on Blue exclusions counts to get details.</p>';
						//print_r($downloadAllArray);
					}
					
					function getCommenterTypeAtMaxKey(array $qaReason) {
						if (empty($qaReason)) {
							return null;
						}

						$maxKey = max(array_map('intval', array_keys($qaReason)));
						return $qaReason[(string)$maxKey]['commenterType'] ?? null;
					}


					
					function createHtmlTable($data, $headings_mapping) {
						global $defaultWhiteLabel, $rightsArray, $initial_data, $frmRepId, $toRepId, $reportIds ,$sndT ,$custidPRM, $downloadAllArray, $usrType, $tp5Rps;
						//print_r("rr:".$rightsArray);
						
						$reportsPerPage = 5;
						$totalReportPages = ceil(count($reportIds) / $reportsPerPage);
						$currentReportPage = isset($_GET['reportPage']) ? (int)$_GET['reportPage'] : 1;
						$currentReportPage = max(1, min($totalReportPages, $currentReportPage));

						$startIndex = ($currentReportPage - 1) * $reportsPerPage;
						$visibleReportIds = array_slice($reportIds, $startIndex, $reportsPerPage);
						
						$pageReport = !empty($tp5Rps) ? $tp5Rps[0] : 0;

						
						//print_r($pageReport);

						$html = '<div class="table-responsive">';
						$html .= '<button class="btn btn-primary btn-sm mb-1" onclick="exportTableToCSV(\'hersQASS_'.$frmRepId . '_' . $toRepId . '_' . $sndT.'.csv\', \'masterDataTable\')"><i class="fas fa-download"></i> CSV</button>';
						if(in_array(14,$rightsArray)) {
							$html .= '&nbsp;&nbsp;<button class="btn btn-primary btn-sm mb-1" style="background-color: darkblue;" onclick="getAllpdfsAsZip()"><i class="fas fa-download"></i> All PDFs</button>';
						}
						$html .= '<table class="table table-bordered table-sticky-header" id="masterDataTable">';
						$html .= '<thead class="thead-light">';
						$html .= '<tr>';
						
						// Add table headers
						foreach ($headings_mapping as $key => $value) {
							$html .= '<th>'. $value . '</th>';
						}
						/*$html .= '<th>Client</th>';
						$html .= '<th>Custid</th>';
						$html .= '<th>Product</th>';
						$html .= '<th>Type</th>';
						$html .= '<th>Report Id</th>';
						$html .= '<th>Access Link</th>';*/
						$html .= '</tr>';
						$html .= '</thead>';
						$html .= '<tbody>';
							$rowIndex = 0;
							foreach ($data as $row) {
								$rowIndex = $rowIndex +1;
								if ($row['reportId'] !== $pageReport){
									break;
								}
								$html .= '<tr>';
								foreach ($headings_mapping as $key => $value) {
																		
									if ($key === "printReport"){
										$html .= '<td style="padding: 0.5%; text-align: center;">
										<div class="d-flex justify-content">
										  <button class="btn btn-primary btn-sm mb-1" style="background-color: darkgreen;" onclick="openPopup(\'' . $row[$key] . '\',\'' . $row['downFileNme'] . '\'); return false;"><i class="fas fa-eye"></i></button>
										  &nbsp;
										  <button class="btn btn-primary btn-sm mb-1" onclick="downloadPdfData(\'' . $row[$key] . '\',\''.$row['downFileNme'].'\'); return false;"><i class="fas fa-download"></i></button>
										  </div>
									  </td>';

									}
									elseif($key === "showToPms"){
										$shown = "";
										$bgc = "";
										if($row[$key] ==="N"){
											$shown = "Make Visible";
											$bgc = "#5F8575";
										}
										else{
											$shown = "Hide";
											$bgc = "#AA4A44";
										}
										$html .= '<td style="padding: 0.5%; text-align: center;">
													  <button id="smplVsble" class="btn btn-primary btn-sm mb-3" style="background-color: '.$bgc.';" onclick="makeSampleVisible('.$row['reportId'].', '.$row['custId'].', '.$row['sr'].'); return false;">'.$shown .'</button>
												  </td>';
									}
									elseif($key === "passedQA"){
										$valTShw = '';
										$cmtrT = Null;
										if ($row[$key]=== "N"){
											$cmtrT  = getCommenterTypeAtMaxKey($row['qaReason']);
											if ($cmtrT !== "SYS"){
												$valTShw = $cmtrT.': '.'In QA Queue';
											}
											else{
												$valTShw = 'In QA Queue';
											}
											
										}
										elseif ($row[$key]=== "Y"){
											$cmtrT  = getCommenterTypeAtMaxKey($row['qaReason']);
											if ($cmtrT !== "SYS"){
												$valTShw = $cmtrT.': '.'Passed';
											}
											else{
												$valTShw = 'Passed';
											}
											
										}
										elseif ($row[$key]=== "F"){
											$cmtrT  = getCommenterTypeAtMaxKey($row['qaReason']);
											if ($cmtrT !== "SYS"){
												$valTShw = $cmtrT.': '.'Failed';
											}
											else{
												$valTShw = 'Failed';
											}
										}
										
										if($row[$key]=== "Y" || $row[$key]=== "F"){
											//echo "Condition met";
											$rpeHtml = '<div class="table-responsive">';

											//$rpeHtml .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'hersQAS_S_' . $row['reportId'] . '_' . $row['wlId'] . '_' . $row['custId'] . '.csv\', \'masterDataTable_' . $row['reportId'] . '_' . $row['wlId'] . '_' . $row['custId'] . '\')">Download CSV</button>';

											$rpeHtml .= '<table class="table table-bordered table-sticky-header" id="masterDataTable_' . $row['reportId'] . '_' . $row['wlId'] . '_' . $row['custId'] . '">';
											$rpeHtml .= '<thead class="thead-light">';
											$rpeHtml .= '<tr>';

											$rpeHtml .= '<th>Sr#</th>';
											$rpeHtml .= '<th>Comment</th>';
											$rpeHtml .= '<th>Origin</th>';
											$rpeHtml .= '<th>Made By</th>';
											$rpeHtml .= '<th>Made On (UTC)</th>';
											$rpeHtml .= '</tr>';

											$rpeHtml .= '</thead>';
											$rpeHtml .= '<tbody>';

											if(!empty($row['qaReason']) && is_array($row['qaReason'])) {
												
												uksort($row['qaReason'], function($a, $b) {
													return (int)$b <=> (int)$a;
												});

												foreach ($row['qaReason'] as $key => $reason) {
													$rsns = "";
													if($key == "0" && $reason['comment'] == ""){
														$rsns = "QA Sample Created";
													}
													else{
														$rsns = $reason['comment'];
													}
													$rpeHtml .= '<tr>';
													$rpeHtml .= '<td>' . htmlspecialchars((string)((int)$key + 1)) . '</td>';
													$rpeHtml .= '<td>' . htmlspecialchars($rsns ?? '') . '</td>';
													$rpeHtml .= '<td>' . htmlspecialchars($reason['commenterType'] ?? '') . '</td>';
													$rpeHtml .= '<td>' . htmlspecialchars($reason['madeBy'] ?? '') . '</td>';
													$rpeHtml .= '<td>' . htmlspecialchars($reason['madeOn'] ?? '') . '</td>';
													$rpeHtml .= '</tr>';
												}
											}
											$rpeHtml .= '</tbody>';
											$rpeHtml .= '</table>';
											$rpeHtml .= '</div>';

											// Encode the HTML table string
											
											$encodedRpeHtml = htmlspecialchars(addslashes($rpeHtml), ENT_QUOTES, 'UTF-8');
											
											if ($cmtrT === "QATM"){
												$html .= '<td style="background-color: #d4edda;"><div class="d-flex justify-content">';
											}
											elseif ($cmtrT === "PM"){
												$html .= '<td style="background-color: #ffe7cc;"><div class="d-flex justify-content">';
											}
											else{
												$html .= '<td><div class="d-flex justify-content">';
											}
													$html .= '<a href="#remarks'.$row['reportId'].$row['sr'].'" onclick="openPassFailDetails(\'Remarks\', \'' . $encodedRpeHtml . '\')">'.$valTShw.'</a>';
													$html .= '&nbsp;&nbsp;/&nbsp;&nbsp;';
													$html .= '<a href="#remarks'.$row['reportId'].$row['sr'].'" onclick="callAddComment('.$row['reportId'].', '.$row['custId'].', '.$row['sr'].', \''.$usrType.'\')">Add</i></a>';
												$html .= '</div></td>';



											//$html .= '<td><a href="#" onclick="openPassFailDetails(\'' . $valTShw . '\',\''.$rpeHtml.'\')">' . $valTShw . '</a></td>';
										}
										else
										{
											//echo "Condition not met";
											$html .= '<td>'.$valTShw.'</td>';
										}
									}
									else{
										$html .= '<td>'. $row[$key] . '</td>';
									}
								}
								$html .= '</tr>';
							}
							
						$html .= '</tbody>';
						$html .= '</table>';
						
						// Pagination controls
						$html .= '<div class="d-flex justify-content-center mt-3">';
						$html .= '<nav><ul class="pagination">';
						if ($currentReportPage > 1) {
							$html .= '<li class="page-item"><a class="page-link" href="?reportPage='.($currentReportPage - 1).'&sndT='.$_POST['sndT'].'">«</a></li>';
						}
						$cntr = $startIndex + 1;
						foreach ($tp5Rps as $reportId) {
							$active = (isset($_GET['fri']) && $reportId == $_GET['fri']) ? 'active' : '';
							$html .= '<li class="page-item '.$active.'">
								<a class="page-link" href="?fri='.$reportId.'&tri='.$reportId.'&reportPage='.$currentReportPage.'&sndT='.$_POST['sndT'].'">'.$cntr.'</a>
							</li>';
							$cntr++;
						}
						if ($currentReportPage < $totalReportPages) {
							$html .= '<li class="page-item"><a class="page-link" href="?reportPage='.($currentReportPage + 1).'&sndT='.$_POST['sndT'].'">»</a></li>';
						}
						$html .= '</ul></nav></div>';
						
						$html .= '</div>';// table div closed
						
						return $html;
					}
					
					populateTheSelectionFields("hursPortal", "QASamples.");// populate all drop down feilds with data
					
				?>
				<div class="container-fluid" style="padding-top:0.5%;padding-left:0.5%;">
					<form method="post" id="secondForm" action="">
						<div class="form-row">
							<div class="form-group col-md-3">
								<label for="frmRepId">From Report Id:</label>
								<select id="frmRepId" name="frmRepId" class="form-control select2">
									<?php foreach ($fromReportIDs as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['frmRepId']) && $_POST['frmRepId'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="toRepId">To Report Id:</label>
								<select id="toRepId" name="toRepId" class="form-control select2">
									<?php foreach ($fromReportIDs as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['toRepId']) && $_POST['toRepId'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="sndT">QA Status:</label>
								<select id="sndT" name="sndT" class="form-control select2" onchange="this.form.submit()" required>
									<?php foreach ($sendOptions as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['sndT']) && $_POST['sndT'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="frcht">Cohort:</label>
								<select id="frcht" name="frcht" class="form-control select2">
									<?php foreach ($chrtNames as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['frcht']) && $_POST['frcht'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="form-row">
							<div class="form-group col-md-3">
								<label for="repType">Report Type:</label>
								<select id="repType" name="repType" class="form-control select2">
									<?php foreach ($reportType as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['repType']) && $_POST['repType'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="runType">Run Type:</label>
								<select id="runType" name="runType" class="form-control select2">
									<?php foreach ($runTypeA as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['runType']) && $_POST['runType'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="prdType">Product Type:</label>
								<select id="prdType" name="prdType" class="form-control select2">
									<?php foreach ($productType as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['prdType']) && $_POST['prdType'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-3">
								<label for="custidPRM">By CustId:</label>
								<textarea id="custidPRM" name="custidPRM" class="form-control" rows="2" cols="100" 
								onfocus="if (this.value === 'Comma seperated custids add here') { this.value = ''; }" 
								onblur="if (this.value === '') { this.value = 'Comma seperated custids add here'; }"><?php echo isset($_POST['custidPRM']) ? $_POST['custidPRM'] : "Comma seperated custids add here"; ?></textarea>
							</div>
						</div>
						<!--generateSamples()-->
						<div class="d-flex justify-content-end">
							<button type="submit" value="submit" class="btn btn-primary btn-sm mx-1"><i class="fa fa-search" aria-hidden="true"></i></button>
							<?php if(in_array(13,$rightsArray)) { echo ' <button type="button" class="btn btn-primary btn-sm mx-1" style="background-color: indigo;" onclick="opGenSamples()"><i class="fa-solid fa-plus"></i> Samples</button>'; } ?>
							<button type="button" class="btn btn-primary btn-sm mx-1" style="background-color: #923807 ;" onclick="revertSamplesToQAQue()"><i class="fas fa-undo"></i> To QA Queue</button>
							<?php if(in_array(13,$rightsArray)) { echo ' <button type="button" class="btn btn-primary btn-sm mx-1" style="background-color: #8B0000;" onclick="opDelSamples()"><i class="fa-regular fa-trash-can"></i> Samples</button>'; } ?>
							<?php if(in_array(13,$rightsArray)) { echo ' <button type="button" class="btn btn-primary btn-sm mx-1" style="background-color: #A99914;" onclick="opPFReason()"><i class="fa-solid fa-check"></i> / <i class="fa-solid fa-xmark"></i> Batch</button>'; } ?>
							<?php //if(in_array(13,$rightsArray)) { echo '<button type="button" class="btn btn-primary btn-sm mx-1" style="background-color: darkgreen;" onclick="markQaPass(\'Y\')">Pass</button>';} ?>
							<?php //if(in_array(13,$rightsArray)) { echo '<button type="button" class="btn btn-primary btn-sm mx-1" style="background-color: #AA4A44;" onclick="markQaPass(\'F\')">Fail</button>';} ?>
						</div>
					</form>
					<!--//markQaPass(\'Y\') markQaPass(\'F\')-->
					<div class="container-fluid" style="padding-top:0.5%;padding-left:0.5%;">
						<!-- Reusable Modal for Reason Input -->
						<div id="reasonModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:#fff; padding:20px; border:1px solid #ccc; z-index:2000; width:400px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
							<h3 id="modalTitle">Enter Reason</h3>
							<textarea id="reasonInput" rows="5" style="width:100%;"></textarea>
							<br><br>
							<button id="submitReasonBtn">Submit</button>
							<button onclick="closeReasonModal()">Cancel</button>
						</div>

						<?php 
							if(in_array(15,$rightsArray)) {
								$headings_mapping = [
									'sr' => 'Sr#',
									'productType' => 'Product',
									'reportId' => 'Report Id',
									'runtime' => 'Hers Runtime',
									'runtype' => 'Run Type',
									'client' => 'Client',
									'custId' => 'Custid',
									'cohortId' => 'Cohort Id',
									'cohort_name' => 'Cohort',
									'reportType' => 'Send Type',								
									'printReport' => 'Report',
									'passedQA' => 'Remarks'
								];
								/*//add for resume report hiding feature
								,
									'showToPms' => 'Visibility'*/
							}
							else{
								$headings_mapping = [
									'sr' => 'Sr#',
									'productType' => 'Product',
									'reportId' => 'Report Id',
									'runtime' => 'Hers Runtime',
									'runtype' => 'Run Type',
									'client' => 'Client',
									'custId' => 'Custid',
									'cohortId' => 'Cohort Id',
									'cohort_name' => 'Cohort',
									'reportType' => 'Send Type',			
									'printReport' => 'Report',
									'passedQA' => 'Remarks'
								];
							}
							if(in_array(12,$rightsArray)) {
								getData("hursPortal", "QASamples_QF.");// get relevant data
								//echo createHtmlTable($id_MainData, $headings_mapping);
								// Sample data (replace this with your actual $data array)
								$data = $id_MainData; // Your large dataset here
								// Pagination setup	
								
								$fri = isset($_GET['fri']) ? $_GET['fri'] : null;
								$tri = isset($_GET['tri']) ? $_GET['tri'] : null;
							
								if ($fri && $tri) {
									$filteredData = array_filter($data, function($row) use ($fri, $tri) {
									return $row['reportId'] >= $fri && $row['reportId'] <= $tri;
									});
								} else {
									$filteredData = $data;
								}
								// Render the table
								echo createHtmlTable($filteredData, $headings_mapping);

								

							}
							else{
								echo "No data Available";
							}
							
						?>
					</div>
					
				</div>
					<!-- Bootstrap Modal Template -->
				<div class="modal fade" id="remarksModal" tabindex="-1" role="dialog" aria-labelledby="remarksModalLabel" aria-hidden="true">
				  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
					<div class="modal-content">
					  <div class="modal-header">
						<h5 class="modal-title" id="remarksModalLabel">Modal Title</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						  <span aria-hidden="true">&times;</span>
						</button>
					  </div>
					  <div class="modal-body" id="remarksModalBody">
						Modal message goes here.
					  </div>
					  <div class="modal-footer">
						<button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
					  </div>
					</div>
				  </div>
				</div>
			
				<style>
				
					.pagination {
						flex-wrap: wrap;
					}

				</style>
				<script>
					$(document).ready(function() {
						 $('.select2').select2({
							 placeholder: "Select a report ID",
							 allowClear: true
						 });
					});
					
					document.getElementById('secondForm').addEventListener('submit', function(e) {
						e.preventDefault(); // Stop default form submission

						const form = e.target;

						// Clear query parameters from the URL
						const cleanUrl = window.location.origin + window.location.pathname;
						window.history.replaceState({}, '', cleanUrl);

						// Submit the form after clearing the URL
						setTimeout(() => {
							form.submit(); // This triggers a normal POST
						}, 0);
					});


					function reloadPage(){
						location.reload();
					}
					
					function escapeHtml(unsafe) {
						return unsafe
							.replace(/&/g, "&amp;")
							.replace(/</g, "&lt;")
							.replace(/>/g, "&gt;")
							.replace(/"/g, "&quot;")
							.replace(/'/g, "&#039;");
					}

					async function openPassFailDetails(value, dta) {
						try {
							const details = `${value} Details`;
							const report = escapeHtml(dta);
							showModal(details, dta,'QA-RM');
						} catch (error) {
							console.error("Error generating pass/fail details:", error);
						}
					}


				
					async function makeSampleVisible(reportID, custIdVal, srNum){
						var msv_button = document.getElementById('smplVsble');
						let spinner = document.createElement("div");
						spinner.innerHTML = "⏳ Marking ...";
						spinner.style.position = "fixed";
						spinner.style.top = "50%";
						spinner.style.left = "50%";
						spinner.style.transform = "translate(-50%, -50%)";
						spinner.style.background = "#fff";
						spinner.style.padding = "10px 20px";
						spinner.style.border = "1px solid #ccc";
						spinner.style.borderRadius = "5px";
						spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
						spinner.style.zIndex = "10000"; // Set a high z-index
						document.body.appendChild(spinner);

						// Get form values
						var scrptNme = "QAmarkVisibleInvisible";
						var frRpId = reportID;
						var toRpId = custIdVal;
						var sndTyp = srNum;
						var wld = <?php echo json_encode($defaultWhiteLabel); ?>; // Properly escape PHP variable
						var usr = <?php echo json_encode($unser); ?>; // Properly escape PHP variable

						try {
							let requestData = JSON.stringify({
								frmRepId: frRpId,
								toRepId: toRpId,
								sndT: sndTyp,
								rsn :"",
								wtLbl: wld,
								scrptNm: scrptNme,
								usrN:usr
							});

							let response = await fetch("phpRunners/run_PY.php", {
								method: "POST",
								headers: {
									"Content-Type": "application/json",
								},
								body: requestData,
							});

							if (!response.ok) {
								throw new Error(`HTTP error! Status: ${response.status}`);
							}

							let resultText = await response.text();

							// ✅ Remove spinner IMMEDIATELY after response is received
							if (spinner && spinner.parentNode) {
								document.body.removeChild(spinner);
								spinner.style.zIndex = "0"; // Set a high z-index
							}

							let resultJson;
							try {
								resultJson = JSON.parse(resultText);
								if (resultJson.error) {
									showModal("Error", resultJson.error);
								} else {
									showModal("Success:QA Pass Status", genQaPassRep(resultJson.output),'QA-MV');
									let output = JSON.parse(resultJson.output);

									if (output.Status === "Sample Now Hidden") {
										msv_button.innerHTML = "Make Visible";
										msv_button.style.backgroundColor = "#5F8575";
									} else {
										msv_button.innerHTML = "Hide";
										msv_button.style.backgroundColor ="#AA4A44";
									}
								}
							} catch (e) {
								showModal("Error", "Invalid JSON response: " + resultText);
							}
						} catch (error) {
							showModal("Error running PHP script:", error);
							// Remove spinner in case of error
							if (spinner && spinner.parentNode) {
								document.body.removeChild(spinner);
							}
							showModal("Error", error.message);
						}
					}
					
					function showModal(title, message, type = 'default') {
					  const modalTitle = document.getElementById('remarksModalLabel');
					  const modalBody = document.getElementById('remarksModalBody');

					  if (modalTitle && modalBody) {
						modalTitle.textContent = title;
						modalBody.innerHTML = message;
					  }

					  // Show the Bootstrap modal
					  $('#remarksModal').modal('show');

					  // After modal is shown, check if it contains any .select2 elements
					  $('#remarksModal').off('shown.bs.modal').on('shown.bs.modal', function () {
						if ($('#remarksModal .select2').length > 0) {
						  $('#remarksModal .select2').select2({
							dropdownParent: $('#remarksModal'),
							placeholder: "Select a report ID",
							allowClear: true
						  });
						}
					  });

					  // Handle modal close logic
					  $('#remarksModal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
						if (['generate-samples', 'QA-Pass', 'QA-MV'].includes(type)) {
						  if (type === 'QA-Pass') {
							if (!window.location.href.includes('sndT=N')) {
							  window.location.href = "http://sandbox8.planetecosystems.com/hursPortal/sampling_system.php?sndT=N";
							}
						  } else {
							location.reload();
						  }
						}
					  });
					}

					
					/*function showModal(title, message, type = 'default') {
						// Set modal title and message
						const modalTitle = document.getElementById('remarksModalLabel');
						const modalBody = document.getElementById('remarksModalBody');

						if (modalTitle && modalBody) {
							modalTitle.textContent = title;
							modalBody.innerHTML = message;
						}

						// Show the Bootstrap modal
						$('#remarksModal').modal('show');

						// Handle modal close logic
						$('#remarksModal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
							if (['generate-samples', 'QA-Pass', 'QA-MV'].includes(type)) {
								if (type === 'QA-Pass') {
									if (!window.location.href.includes('sndT=N')) {
										window.location.href = "http://sandbox8.planetecosystems.com/hursPortal/sampling_system.php?sndT=N";
									}
								} else {
									location.reload();
								}
							}
						});
					}*/


					
					
					//Function to show modal
					/*function showModal(title, message, type = 'default') {
						let modal = document.getElementById('modal');
						if (!modal) {
							modal = document.createElement("div");
							modal.id = "modal";
							modal.className = "modal";
							modal.style.display = "none";
							modal.innerHTML = `
								<div class="modal-content">
									<span class="modal-close">&times;</span>
									<h2 id="modal-title">Message goes here</h2> <!-- Title added here -->
									<div id="modal-message" class="modal-body">Message goes here</div> <!-- Message goes here -->
									<button id="modal-ok">OK</button>
								</div>
							`;
							document.body.appendChild(modal);

							// Modal CSS
							const modalCSS = `
								.modal {
									position: fixed;
									top: 0;
									left: 0;
									width: 100%;
									height: 100%;
									background-color: rgba(0, 0, 0, 0.5);
									display: flex;
									justify-content: center;
									align-items: center;
									z-index: 9999;
								}
								.modal-content {
									background: #fff;
									padding: 20px;
									border-radius: 8px;
									center;
									width: 40% !important;  // Set the modal width to 40% of the page 
									70% !important;  // Set the modal height to 70% of the page 
									min-width: 300px !important;  // Set a minimum width to prevent it from being too small 
									box-sizing: border-box; // To include padding in the width calculation 
									overflow-y: auto; // Add vertical scrollbar 
									z-index: 10001; // Set a higher z-index for modal content 
								}
								.modal-close {
									position: absolute;
									top: 10px;
									right: 10px;
									font-size: 24px;
									cursor: pointer;
								}
								#modal-ok {
									padding: 10px 20px;
									background-color: #007BFF;
									color: #fff;
									border: none;
									border-radius: 5px;
									cursor: pointer;
								}
								#modal-ok:hover {
									background-color: #0056b3;
								}
								.modal-body {
									max-height: calc(100% - 60px); // Adjust for padding and button 
									overflow-y: auto; // Add vertical scrollbar for the content 
								}
								.modal-content.generate-samples {
									z-index: 10002; // Higher z-index for generate samples modal 
								}
							`;
							let style = document.createElement("style");
							style.innerHTML = modalCSS;
							document.head.appendChild(style);
						}

						// Set modal title and message
						document.getElementById('modal-title').textContent = title;  // Set the title here
						document.getElementById('modal-message').innerHTML = message;  // Set the message here as HTML

						// Apply different styles based on the modal type
						if (type === 'generate-samples') {
							document.querySelector('.modal-content').classList.add('generate-samples');
						} else {
							document.querySelector('.modal-content').classList.remove('generate-samples');
						}

						modal.style.display = "flex";
						modal.removeAttribute('inert'); // Remove inert attribute
						modal.setAttribute('aria-hidden', 'false'); // Optionally keep aria-hidden for compatibility

						// Close modal on click of 'X' or 'OK'
						document.querySelector('.modal-close').onclick = closeModal;
						document.getElementById('modal-ok').onclick = closeModal;
						
						function closeModal() {
							let modal = document.getElementById('modal');
							modal.style.display = "none";
							modal.setAttribute('inert', ''); // Add inert attribute
							modal.removeAttribute('aria-hidden'); // Remove aria-hidden attribute
							if (type === 'generate-samples' || type === 'QA-Pass' || type === 'QA-MV') {
								if (type === 'QA-Pass'){
									// Reload the page with the specified parameter
									window.location.href = "http://sandbox8.planetecosystems.com/hursPortal/sampling_system.php?sndT=N";
								}
								else{
									location.reload();  // Refresh the page if needed
								}
							}
						}
					}*/
					
					function showReasonModal(title, callback) {
						document.getElementById('modalTitle').textContent = title;
						document.getElementById('reasonInput').value = "";
						document.getElementById('reasonModal').style.display = 'block';

						const submitBtn = document.getElementById('submitReasonBtn');
						const newBtn = submitBtn.cloneNode(true);
						submitBtn.parentNode.replaceChild(newBtn, submitBtn);

						newBtn.onclick = function () {
							const reason = document.getElementById('reasonInput').value.trim();
							if (reason) {
								document.getElementById('reasonModal').style.display = 'none';
								setTimeout(() => callback(reason), 100);
							} else {
								alert("Please enter a reason.");
							}
						};
					}
					
					function closeReasonModal() {
						document.getElementById('reasonModal').style.display = 'none';
					}
					
					async function opDelSamples() {
						const confirmed = confirm("Are you sure you want to delete the samples? This action is irreversible.");

						if (confirmed) {
							// Proceed with deletion logic
							var wld = <?php echo $defaultWhiteLabel;?>;
							var usr = <?php echo json_encode($unser); ?>; // Properly escape PHP variable
							showReasonModal("Enter reason for Sample Deletion", async function(reason) {
								var scrptNme = "markDeleted";
								var sndTyp = "Y";
								var frRpId = document.getElementById('frmRepId').value;
								var toRpId = document.getElementById('toRepId').value;
								
								let spinner = document.createElement("div");
								spinner.innerHTML = "⏳ Deleting listed samples...";
								spinner.style.position = "fixed";
								spinner.style.top = "50%";
								spinner.style.left = "50%";
								spinner.style.transform = "translate(-50%, -50%)";
								spinner.style.background = "#fff";
								spinner.style.padding = "10px 20px";
								spinner.style.border = "1px solid #ccc";
								spinner.style.borderRadius = "5px";
								spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
								spinner.style.zIndex = "10000"; // Set a high z-index
								document.body.appendChild(spinner);
								
								try {
									let requestData = JSON.stringify({
										frmRepId: frRpId,
										toRepId: toRpId,
										sndT: sndTyp,
										rsn :reason,
										wtLbl: wld,
										scrptNm: scrptNme,
										usrN: usr
									});
									
									console.log(requestData);

									let response = await fetch("phpRunners/run_PY.php", {
										method: "POST",
										headers: {
											"Content-Type": "application/json",
										},
										body: requestData,
									});

									if (!response.ok) {
										showModal("HTTP error! Status", response.status);
									}

									let resultText = await response.text();

									// ✅ Remove spinner IMMEDIATELY after response is received
									if (spinner && spinner.parentNode) {
										document.body.removeChild(spinner);
										spinner.style.zIndex = "0"; // Set a high z-index
									}

									let resultJson;
									try {
										resultJson = JSON.parse(resultText);
										if (resultJson.error) {
											showModal("Error", resultJson.error);
										} else {
											showModal("Success:Deletion Status", genQaPassRep(resultJson.output),'QA-Pass');
										}
									} catch (e) {
										showModal("Error", "Invalid JSON response: " + resultText);
									}
								} catch (error) {
									showModal("Error running PHP script:", error);
									// Remove spinner in case of error
									if (spinner && spinner.parentNode) {
										document.body.removeChild(spinner);
									}
									showModal("Error", error.message);
								}
								
							});
						} else {
							// User canceled
							console.log("Deletion canceled.");
							return;
						}
					}
					
					function revertSamplesToQAQue(){
						var Cmtaddr = <?php echo json_encode($usrType); ?>;
						var wlId = <?php echo json_encode($defaultWhiteLabel); ?>;
						var usrs = <?php echo json_encode($unser); ?>;
						showReasonModal("Please Enter Reason for revert", async function(reason) {
							var scrptNme = "qaRevertSamples";
							var frRpId = document.getElementById('frmRepId').value;
							var toRpId = document.getElementById('toRepId').value;
							if (frRpId == "ALL" || frRpId == ""  || frRpId == null || toRpId == "ALL" || toRpId == ""  || toRpId == null){
								showModal("Error", "From and To report need to be selected for Sample Revert");
								return;
							}
							
							let spinner = document.createElement("div");
							spinner.innerHTML = "⏳ Reverting Sample Reports...";
							spinner.style.position = "fixed";
							spinner.style.top = "50%";
							spinner.style.left = "50%";
							spinner.style.transform = "translate(-50%, -50%)";
							spinner.style.background = "#fff";
							spinner.style.padding = "10px 20px";
							spinner.style.border = "1px solid #ccc";
							spinner.style.borderRadius = "5px";
							spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
							spinner.style.zIndex = "10000"; // Set a high z-index
							document.body.appendChild(spinner);
							
							try {
								let requestData = JSON.stringify({
									frmRepId: frRpId,
									toRepId: toRpId,
									sndT: Cmtaddr,
									rsn: reason,
									wtLbl: wlId,
									scrptNm: scrptNme,
									usrN: usrs
								});
								
								//alert(requestData);

								let response = await fetch("phpRunners/run_PY.php", {
									method: "POST",
									headers: {
										"Content-Type": "application/json",
									},
									body: requestData,
								});

								if (!response.ok) {
									alert("HTTP error! Status: "+response.status);
								}

								let resultText = await response.text();
								//alert(resultText);

								// ✅ Remove spinner IMMEDIATELY after response is received
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
									spinner.style.zIndex = "0"; // Set a high z-index
								}

								let resultJson;
								try {
									resultJson = JSON.parse(resultText);
									if (resultJson.error) {
										alert("Error: "+ resultJson.error);
									} else {
										alert("Success:QA Passed123 Status: "+ resultJson.output);
										//alert("✅ Comment Added: Report ID " + reportId + ", CustID: " + custid + ", reason: " + reason);
									}
								} catch (e) {
									alert("Invalid JSON response: " + resultText);
								}
							} catch (error) {
								alert("Error running PHP script:"+ error);
								// Remove spinner in case of error
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
								}
								alert("Error:"+ error.message);
							}
							
						},"Revert QA Samples To QA Queue");
						
					}
					
					function opGenSamples() {
						let spinner = document.createElement("div");
						spinner.innerHTML = "⏳ Opening Sample Generation Page Please Wait...";
						spinner.style.position = "fixed";
						spinner.style.top = "50%";
						spinner.style.left = "50%";
						spinner.style.transform = "translate(-50%, -50%)";
						spinner.style.background = "#fff";
						spinner.style.padding = "10px 20px";
						spinner.style.border = "1px solid #ccc";
						spinner.style.borderRadius = "5px";
						spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
						document.body.appendChild(spinner);
						fetch('generate_samples.php')
							.then(response => response.text())
							.then(data => {
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
								}
								showModal("Generate Samples", data);
								
								$('.select2').select2({
								placeholder: "Select a report ID",
								allowClear: true
								});

							})
							.catch(error => {
								console.error('Error loading the PHP page:', error);
							});
					}
					
					function opPFReason() {
						var frRpId = document.getElementById('frmRepId').value;
						var toRpId = document.getElementById('toRepId').value;
						if (frRpId == "ALL" || frRpId == ""  || frRpId == null || toRpId == "ALL" || toRpId == ""  || toRpId == null){
							showModal("Error", "From and To report need to be selected for bulk Pass or fail");
							return;
						}
						let spinner = document.createElement("div");
						spinner.innerHTML = "⏳ Opening Pass/Fail Batch Page Please Wait...";
						spinner.style.position = "fixed";
						spinner.style.top = "50%";
						spinner.style.left = "50%";
						spinner.style.transform = "translate(-50%, -50%)";
						spinner.style.background = "#fff";
						spinner.style.padding = "10px 20px";
						spinner.style.border = "1px solid #ccc";
						spinner.style.borderRadius = "5px";
						spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
						document.body.appendChild(spinner);
						fetch('pas_fail_load.php')
							.then(response => response.text())
							.then(data => {
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
								}
								showModal("Pass Or Fail Whole Batch", data);
								
								$('.select2').select2({
								placeholder: "Select a report ID",
								allowClear: true
								});

							})
							.catch(error => {
								console.error('Error loading the PHP page:', error);
							});
					}
					
					async function generateSamples() {
						// Create a spinner
						let spinner = document.createElement("div");
						spinner.innerHTML = "⏳ Sample Generation In Progress...";
						spinner.style.position = "fixed";
						spinner.style.top = "50%";
						spinner.style.left = "50%";
						spinner.style.transform = "translate(-50%, -50%)";
						spinner.style.background = "#fff";
						spinner.style.padding = "10px 20px";
						spinner.style.border = "1px solid #ccc";
						spinner.style.borderRadius = "5px";
						spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
						spinner.style.zIndex = "10000"; // Set a high z-index
						document.body.appendChild(spinner);

						// Get form values
						var scrptNme = "generateSamples2";
						var frRpId = document.getElementById('gsfrmRepId').value;
						var toRpId = document.getElementById('gstoRepId').value;
						var fixedCustids = document.getElementById('gscustidPRM').value;
						
						if (fixedCustids === "Add Comma seperated custids here"){
							fixedCustids = "";
						}
						//var sndTyp = document.getElementById('gssndT').value;
						var sndTyp = "";
						var wld = <?php echo $defaultWhiteLabel;?>;
						var usr = <?php echo json_encode($unser); ?>; // Properly escape PHP variable

						try {
							let requestData = JSON.stringify({
								frmRepId: frRpId,
								toRepId: toRpId,
								sndT: sndTyp,
								rsn : fixedCustids,
								wtLbl: wld,
								scrptNm: scrptNme,
								usrN: usr
							});
							//console.log(requestData);
							let response = await fetch("phpRunners/run_PY.php", {
								method: "POST",
								headers: {
									"Content-Type": "application/json",
								},
								body: requestData,
							});

							if (!response.ok) {
								throw new Error(`HTTP error! Status: ${response.status}`);
							}

							let resultText = await response.text();

							// ✅ Remove spinner IMMEDIATELY after response is received
							if (spinner && spinner.parentNode) {
								document.body.removeChild(spinner);
								spinner.style.zIndex = "0"; // Set a high z-index
							}

							let resultJson;
							try {
								resultJson = JSON.parse(resultText);
								if (resultJson.error) {
									showModal("Error", resultJson.error, 'generate-samples');
								} else {
									showModal("Success:Generation Report", genSGRep(resultJson.output), 'generate-samples');
								}
							} catch (e) {
								showModal("Error", "Invalid JSON response: " + resultText, 'generate-samples');
							}
						} catch (error) {
							showModal("Error running PHP script:", error, 'generate-samples');
							// Remove spinner in case of error
							if (spinner && spinner.parentNode) {
								document.body.removeChild(spinner);
							}
							showModal("Error", error.message, 'generate-samples');
						}
					}
					
					function genSGRep(message){
						const data = JSON.parse(message);
						let table = '<div class="table-responsive">';
						table += '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'sample_gen_report.csv\', \'sampleGenRepTable\')">Download CSV</button>'
						table += '<table class="table table-bordered" id="sampleGenRepTable">';
						table += '<thead class="thead-light">';
						table += '<tr>';
						table += '<th>Report Id</th><th>Status</th><th>Email Samples Size</th><th>Paper Samples Size</th><th>Saved Status</th>';
						table += '</tr>';
						table += '</thead>';
						table += '<tbody>';

						for (const reportId in data) {
							if (data.hasOwnProperty(reportId)) {
								const report = data[reportId];
								table += `<tr>
											<td>${reportId}</td>
											<td>${report.Status}</td>
											<td>${report.Email_samples_size}</td>
											<td>${report.Paper_samples_size}</td>
											<td>${report.GenStats}</td>
										  </tr>`;
							}
						}

						table += '</tbody>';
						table += '</table>';
						table += '</div>';
						return table;
					}
					
					function genQaPassRep(message) {
						const data = JSON.parse(message);
						let table = '<div class="table-responsive">';
						table += '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'sample_gen_report.csv\', \'sampleGenRepTable\')">Download CSV</button>';
						table += '<table class="table table-bordered" id="sampleGenRepTable">';
						table += '<thead class="thead-light">';
						table += '<tr>';
						table += '<th>Status</th>';
						table += '</tr>';
						table += '</thead>';
						table += '<tbody>';

						table += `<tr>
									<td>${data.Status}</td>
								  </tr>`;

						table += '</tbody>';
						table += '</table>';
						table += '</div>';
						return table;
					}
				
					async function markQaPass(stt, typ) {
						// Get form values
						var scrptNme = "markQaPass";
						var frRpId = document.getElementById('frmRepId').value;
						var toRpId = document.getElementById('toRepId').value;
						var reason = "";
						if (typ === 1) {
							reason = document.getElementById('reasonPrm').value;
						}
						else {
							if (stt === "Y"){
								reason = "Batch Bulk Passed";
							}
							else if (stt === "F"){
								reason = "Batch Bulk Failed with no reason";
							}
						}
						if (reason === "Reason Text Here" || reason === "Please enter reason here" || reason === "") {
							showModal("Error", "Reason Required For Batch Fail Or Pass",'QA-Pass');
							
						}
						else{

							let spinner = document.createElement("div");
							spinner.innerHTML = "⏳ Marking QA Status...";
							spinner.style.position = "fixed";
							spinner.style.top = "50%";
							spinner.style.left = "50%";
							spinner.style.transform = "translate(-50%, -50%)";
							spinner.style.background = "#fff";
							spinner.style.padding = "10px 20px";
							spinner.style.border = "1px solid #ccc";
							spinner.style.borderRadius = "5px";
							spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
							spinner.style.zIndex = "10000"; // Set a high z-index
							document.body.appendChild(spinner);

							var sndTyp = stt;
							var wld = <?php echo json_encode($defaultWhiteLabel); ?>; // Properly escape PHP variable
							var usr = <?php echo json_encode($unser); ?>; // Properly escape PHP variable

							try {
								let requestData = JSON.stringify({
									frmRepId: frRpId,
									toRepId: toRpId,
									sndT: sndTyp,
									rsn :reason,
									wtLbl: wld,
									scrptNm: scrptNme,
									usrN: usr
								});
								
								console.log(requestData);

								let response = await fetch("phpRunners/run_PY.php", {
									method: "POST",
									headers: {
										"Content-Type": "application/json",
									},
									body: requestData,
								});

								if (!response.ok) {
									showModal("HTTP error! Status", response.status);
								}

								let resultText = await response.text();

								// ✅ Remove spinner IMMEDIATELY after response is received
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
									spinner.style.zIndex = "0"; // Set a high z-index
								}

								let resultJson;
								try {
									resultJson = JSON.parse(resultText);
									if (resultJson.error) {
										showModal("Error", resultJson.error);
									} else {
										showModal("Success:QA Pass Status", genQaPassRep(resultJson.output),'QA-Pass');
									}
								} catch (e) {
									showModal("Error", "Invalid JSON response: " + resultText);
								}
							} catch (error) {
								showModal("Error running PHP script:", error);
								// Remove spinner in case of error
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
								}
								showModal("Error", error.message);
							}
						}
					}
					
					async function callAddComment(reportId, custid, sr, Cmtaddr) {
						var wlId = <?php echo json_encode($defaultWhiteLabel); ?>;
						var usrs = <?php echo json_encode($unser); ?>;
						showReasonModal("Please Enter Your Comment", async function(reason) {
							var scrptNme = "qaSysAddRemarks";
							var frRpId = reportId;
							var toRpId = sr;
							
							let spinner = document.createElement("div");
							spinner.innerHTML = "⏳ Adding Comment...";
							spinner.style.position = "fixed";
							spinner.style.top = "50%";
							spinner.style.left = "50%";
							spinner.style.transform = "translate(-50%, -50%)";
							spinner.style.background = "#fff";
							spinner.style.padding = "10px 20px";
							spinner.style.border = "1px solid #ccc";
							spinner.style.borderRadius = "5px";
							spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
							spinner.style.zIndex = "10000"; // Set a high z-index
							document.body.appendChild(spinner);
							
							try {
								let requestData = JSON.stringify({
									frmRepId: frRpId,
									toRepId: toRpId,
									sndT: Cmtaddr,
									rsn: reason,
									wtLbl: wlId,
									scrptNm: scrptNme,
									usrN: usrs
								});
								

								let response = await fetch("phpRunners/run_PY.php", {
									method: "POST",
									headers: {
										"Content-Type": "application/json",
									},
									body: requestData,
								});

								if (!response.ok) {
									alert("HTTP error! Status: "+response.status);
								}

								let resultText = await response.text();
								//alert(resultText);

								// ✅ Remove spinner IMMEDIATELY after response is received
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
									spinner.style.zIndex = "0"; // Set a high z-index
								}

								let resultJson;
								try {
									resultJson = JSON.parse(resultText);
									if (resultJson.error) {
										alert("Error: "+ resultJson.error);
									} else {
										//alert("Success:QA Pass Status: "+ resultJson.output);
										alert("✅ Comment Added: Report ID " + reportId + ", CustID: " + custid + ", reason: " + reason);
										location.reload();
									}
								} catch (e) {
									alert("Invalid JSON response: " + resultText);
								}
							} catch (error) {
								alert("Error running PHP script:"+ error);
								// Remove spinner in case of error
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
								}
								alert("Error:"+ error.message);
							}
							
						},"Empty Comment Added");
						
					}

					async function getAllpdfsAsZip() {
						// Progress Bar UI Setup
						const progressContainer = document.createElement("div");
						progressContainer.style = "position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 10px 20px; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2); width: 300px; text-align: center;";
						const progressText = document.createElement("p");
						progressText.innerHTML = "📋 Reading download table...";
						const progressOuter = document.createElement("div");
						progressOuter.style = "width: 100%; height: 20px; background: #ddd; border-radius: 5px;";
						const progressBar = document.createElement("div");
						progressBar.style = "height: 20px; width: 0%; background: #4caf50; border-radius: 5px; transition: width 0.3s;";
						progressOuter.appendChild(progressBar);
						progressContainer.appendChild(progressText);
						progressContainer.appendChild(progressOuter);
						document.body.appendChild(progressContainer);

						// Get PDF Data
						const downloadAllArray = <?php echo json_encode($downloadAllArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
						if (!downloadAllArray || Object.keys(downloadAllArray).length === 0) {
							console.error("No PDFs to download.");
							document.body.removeChild(progressContainer);
							return;
						}

						const zipNames = Object.keys(downloadAllArray);
						const totalZips = zipNames.length;
						let completedZips = 0;
						let zipQueue = [...zipNames];
						let activeZips = 0;
						const maxConcurrentZips = 6;
						let zipFiles = {};
						let totalPDFs = 0;
						let fetchedPDFs = 0;

						// Calculate total PDFs
						for (const zipName of zipNames) {
							totalPDFs += downloadAllArray[zipName].length;
						}

						function updateProgressUI() {
							const fetchProgress = fetchedPDFs / totalPDFs;
							const zipProgress = completedZips / totalZips;
							const combinedProgress = Math.round((fetchProgress * 0.7 + zipProgress * 0.3) * 100);

							if (combinedProgress < 10) {
								progressText.innerHTML = "📋 Reading download table...";
							} else if (combinedProgress < 70) {
								progressText.innerHTML = `📥 Fetching PDFs (${combinedProgress}%)`;
							} else if (combinedProgress < 100) {
								progressText.innerHTML = `📦 Creating Zip File (${combinedProgress}%)`;
							} else {
								progressText.innerHTML = "✅ Download Complete!";
								setTimeout(() => document.body.removeChild(progressContainer), 2000);
							}

							progressBar.style.width = combinedProgress + "%";
						}

						async function fetchPDF({ pdfNme, linkUrl }) {
							try {
								const fetchUrl = `phpRunners/fetch_pdf.php?url=${encodeURIComponent(linkUrl)}`;
								const response = await fetch(fetchUrl);
								if (!response.ok) throw new Error(`Failed to fetch: ${pdfNme}`);
								const blob = await response.blob();
								return { pdfNme, blob };
							} catch (error) {
								console.error("Error downloading PDF:", pdfNme, error);
								return null;
							}
						}

						async function fetchPDFsForZip(zipNme, pdfEntries) {
							zipFiles[zipNme] = [];
							const batchSize = 5;

							for (let i = 0; i < pdfEntries.length; i += batchSize) {
								const batch = pdfEntries.slice(i, i + batchSize);
								const batchResults = await Promise.all(batch.map(fetchPDF));
								const successful = batchResults.filter(r => r !== null);
								zipFiles[zipNme].push(...successful);
								fetchedPDFs += successful.length;
								updateProgressUI();
							}

							createAndDownloadZip(zipNme);
						}

						async function createAndDownloadZip(zipNme) {
							const zip = new JSZip();
							(zipFiles[zipNme] || []).forEach(({ pdfNme, blob }) => {
								if (pdfNme && blob) zip.file(pdfNme, blob);
							});
							const zipBlob = await zip.generateAsync({ type: "blob", compression: "DEFLATE" });
							const link = document.createElement("a");
							link.href = URL.createObjectURL(zipBlob);
							link.download = zipNme || "Reports.zip";
							document.body.appendChild(link);
							link.click();
							document.body.removeChild(link);
							URL.revokeObjectURL(link.href);

							completedZips++;
							updateProgressUI();
							activeZips--;
							processNextZip();
						}

						function processNextZip() {
							if (zipQueue.length > 0 && activeZips < maxConcurrentZips) {
								activeZips++;
								const nextZip = zipQueue.shift();
								fetchPDFsForZip(nextZip, downloadAllArray[nextZip]);
							}
						}

						// Start processing zips
						for (let i = 0; i < maxConcurrentZips; i++) {
							processNextZip();
						}
					}

					
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
					
					function openPopup(pdfUrl, fileNme) {
						const rawDownloadAllArray = <?php echo json_encode($downloadAllArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
						const downloadAllArray = Object.values(rawDownloadAllArray).flat();

						const currentIndex = downloadAllArray.findIndex(item => item.linkUrl === pdfUrl);
						const popup = window.open("", fileNme, "width=1000,height=800");

						const pfShowable = "<?php echo in_array(13, $rightsArray) ? 'Y' : 'N'; ?>";
						const usr = <?php echo json_encode($unser); ?>;
						//repVsPortal

						const passFailButtons = pfShowable === "Y" ? `
							<div>
								<button class="ai-btn" id="aiCheck"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
								<button class="pass-btn" id="passBtn"><i class="fa-solid fa-check"></i></button>
								<button class="fail-btn" id="failBtn"><i class="fa-solid fa-xmark"></i></button>
							</div>
						` : "";

						if (!popup) {
							alert("Popup blocked! Please allow popups for this site.");
							return;
						}

						const encodedArray = JSON.stringify(downloadAllArray);
						const encodedInitialUrl = encodeURIComponent(pdfUrl);

						popup.document.open();
						popup.document.write(`
					<!DOCTYPE html>
					<html lang="en">
					<head>
					<meta charset="UTF-8">
					<meta name="viewport" content="width=device-width, initial-scale=1.0">
					<title>${fileNme}</title>
					<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
					<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
					<style>
					html, body {
						margin: 0;
						padding: 0;
						height: 100%;
						overflow: hidden;
						font-family: sans-serif;
					}
					#controls {
						display: flex;
						flex-wrap: wrap;
						align-items: center;
						justify-content: space-between;
						padding: 10px 20px;
						background: #f0f0f0;
						border-bottom: 1px solid #ccc;
						position: fixed;
						top: 0;
						left: 0;
						width: 100%;
						z-index: 1000;
						box-sizing: border-box;
						gap: 10px;
					}
					#custidDisplay {
						font-weight: bold;
						user-select: text;
					}
					#progressCounter {
						position: absolute;
						left: 50%;
						transform: translateX(-50%);
						font-weight: bold;
						font-size: 14px;
					}
					button {
						padding: 6px 12px;
						font-size: 14px;
						cursor: pointer;
						white-space: nowrap;
					}
					button:disabled {
						opacity: 0.5;
						cursor: not-allowed;
					}
					.ai-btn {
						background-color: #434544;
						color: white;
						border: none;
					}
					.pass-btn {
						background-color: #28a745;
						color: white;
						border: none;
					}
					.fail-btn {
						background-color: #dc3545;
						color: white;
						border: none;
					}
					iframe {
						width: 100%;
						height: calc(100% - 60px);
						border: none;
						margin-top: 60px;
					}
					</style>
					</head>
					<body oncontextmenu="return false">
					<div id="controls">
						<div>
							<button id="prevBtn">⬅ Previous</button>
							<span id="custidDisplay">Current: Custid: </span>
							<button id="nextBtn">Next ➡</button>
						</div>
						<div id="progressCounter"></div>
						${passFailButtons}
					</div>

					<iframe id="pdfFrame" src="phpRunners/fetch_pdf.php?url=${encodedInitialUrl}#toolbar=0&navpanes=0&scrollbar=1"></iframe>

					<!-- Reusable Modal for Reason Input -->
					<div id="reasonModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:#fff; padding:20px; border:1px solid #ccc; z-index:2000; width:400px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
					  <h3 id="modalTitle">Enter Reason</h3>

					  <!-- This section will be hidden when showing the table -->
					  <div id="reasonInputSection">
						<textarea id="reasonInput" rows="5" style="width:100%;"></textarea>
						<br><br>
					  </div>

					  <!-- This will be used to show your table or message -->
					  <div id="modalContentArea"></div>

					  <button id="submitReasonBtn">Submit</button>
					  <button id ="closeModal" onclick="closeReasonModal()">Cancel</button>
					</div>

					<script>
					const pdfList = ${encodedArray};
					let currentIndex = ${currentIndex};

					const iframe = document.getElementById("pdfFrame");
					const prevBtn = document.getElementById("prevBtn");
					const nextBtn = document.getElementById("nextBtn");
					const custidDisplay = document.getElementById("custidDisplay");
					const progressCounter = document.getElementById("progressCounter");
					const aiCheck = document.getElementById("aiCheck");
					const passBtn = document.getElementById("passBtn");
					const failBtn = document.getElementById("failBtn");
					

					function updateIframe() {
						const current = pdfList[currentIndex];
						
						const flNMe = current["pdfNme"];

						// ✅ update the window title
						window.document.title = flNMe;

						const url = "phpRunners/fetch_pdf.php?url=" + encodeURIComponent(current.linkUrl) + "#toolbar=0&navpanes=0&scrollbar=1";
						iframe.src = url;
						custidDisplay.textContent = "Report Id: " + (current.reportId || "N/A") + ", Custid: " + (current.custid || "N/A");

						prevBtn.disabled = currentIndex === 0;
						nextBtn.disabled = currentIndex === pdfList.length - 1;
						progressCounter.textContent = \`\${currentIndex + 1} / \${pdfList.length}\`;
					}

					prevBtn.addEventListener("click", () => {
						if (currentIndex > 0) {
							currentIndex--;
							updateIframe();
						}
					});

					nextBtn.addEventListener("click", () => {
						if (currentIndex < pdfList.length - 1) {
							currentIndex++;
							updateIframe();
						}
					});

					// Initial load
					updateIframe();

					// Attach button handlers once
					if (aiCheck) {
						aiCheck.addEventListener("click", () => {
							const current = pdfList[currentIndex];
							CallaiCheck(current.repVsPortal,current.reportId, current.custid);
						});
					}
					
					if (passBtn) {
						passBtn.addEventListener("click", () => {
							const current = pdfList[currentIndex];
							callPdfPass(current.reportId, current.custid, current.sr, current.wlId);
						});
					}

					if (failBtn) {
						failBtn.addEventListener("click", () => {
							const current = pdfList[currentIndex];
							callPdfFail(current.reportId, current.custid, current.sr, current.wlId);
						});
					}
					
					function formatMonthYear(monthStr) {
						const [year, month] = monthStr.split('-');
						//const monthNames = [
						//	"January", "February", "March", "April", "May", "June",
						//	"July", "August", "September", "October", "November", "December"
						//];
						const monthNames = [
							"Jan", "Feb", "Mar", "Apr", "May", "Jun",
							"July", "Aug", "Sep", "Oct", "Nov", "Dec"
						];
						const monthIndex = parseInt(month, 10) - 1;
						return monthNames[monthIndex] + '-' + year;
					}

					// Modal logic
					function showReasonModal(title, callback, rsIp = "") {
						document.getElementById('modalTitle').textContent = title;
						document.getElementById('reasonInput').value = rsIp;
						document.getElementById('reasonModal').style.display = 'block';

						const submitBtn = document.getElementById('submitReasonBtn');
						const newBtn = submitBtn.cloneNode(true);
						submitBtn.parentNode.replaceChild(newBtn, submitBtn);

						newBtn.onclick = function () {
							const reason = document.getElementById('reasonInput').value.trim();
							if (reason) {
								document.getElementById('reasonModal').style.display = 'none';
								setTimeout(() => callback(reason), 100);
							} else {
								alert("Please enter a reason.");
							}
						};
					}

					function closeReasonModal() {
						document.getElementById('reasonModal').style.display = 'none';
						if(document.getElementById("closeModal").innerText = "Ok"){
							document.getElementById("closeModal").innerText = "Cancel";
							document.getElementById("reasonModal").style.width = "400px";
							document.getElementById("reasonInputSection").style.display = "block";
							document.getElementById("submitReasonBtn").style.display = "block";
							document.getElementById("modalContentArea").style.display = "none";
						}
					}
					
					function CallaiCheck(repVsPortal, reportId, custid) {
						//alert("AI checker reached");

						try {
							if (typeof repVsPortal === "string") {
								repVsPortal = JSON.parse(repVsPortal);
							}
						} catch (e) {
							//alert("JSON parse failed: " + e.message);
							repVsPortal = null;
						}

						//alert("Type of repVsPortal: " + (repVsPortal === null ? "null" : typeof repVsPortal));
						//alert("Value of repVsPortal: " + JSON.stringify(repVsPortal));
						
						function escapeHtml(text) {
							if (text == null) return "";
							return text.toString()
								.replace(/&/g, "&amp;")
								.replace(/</g, "&lt;")
								.replace(/>/g, "&gt;")
								.replace(/"/g, "&quot;")
								.replace(/'/g, "&#039;");
						}
						
						
						let tble = "otheer";

						if (
							repVsPortal &&
							typeof repVsPortal === "object" &&
							Object.keys(repVsPortal).length > 0
						) {
							window.repVsPortal = repVsPortal;
							tble = '<div class="table-responsive">';
								tble += '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\\'SmartScanReport_\\'+reportId\\'_\\'+custid+\\'.csv\\', \\'tableSC\\')">Download CSV</button>'; //adding this line causes issue function does now work
								tble += '<table class="table table-bordered table-sticky-header" id="tableSC">';
									tble += '<thead class="thead-light">';
										tble += "<tr>";
											tble += "<th>Field</th>";
											tble += "<th>Report Value</th>";
											tble += "<th>Fetched Portal Value</th>";
											tble += "<th>Status</th>";
										tble += "</tr>";
									tble += "</thead>";
									tble += "<tbody>";
										tble += "<tr>";
											
											if (escapeHtml(repVsPortal.account_check) === "NF") {
												tble += "<th style='color:red;'><b>Account</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.account) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.account_p) + "</b></th>";

												if (
													(escapeHtml(repVsPortal.account) === "" || escapeHtml(repVsPortal.account) === null) &&
													(escapeHtml(repVsPortal.account_p) === "" || escapeHtml(repVsPortal.account_p) === null)
												) {
													tble += "<th style='color:red;'><b>Report and Portal Data Not Found</b></th>";
												} else if (escapeHtml(repVsPortal.account) === "" || escapeHtml(repVsPortal.account) === null) {
													tble += "<th style='color:red;'><b>Report Data Not Found</b></th>";
												} else {
													tble += "<th style='color:red;'><b>Portal Data Not Found</b></th>";
												}
											} else if (escapeHtml(repVsPortal.account_check) !== "Y") {
												tble += "<th style='color:red;'><b>Account</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.account) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.account_p) + "</b></th>";
												tble += "<th style='color:red;'><b>Failed</b></th>";
											}  else {
												tble += "<th>Account</th>";
												tble += "<th>" + escapeHtml(repVsPortal.account) + "</th>";
												tble += "<th>" + escapeHtml(repVsPortal.account_p) + "</th>";
												tble += "<th>Passed</th>";
											}

										tble += "</tr>";
										tble += "<tr>";
											if (escapeHtml(repVsPortal.address_check) === "NF") {
												tble += "<th style='color:red;'><b>Address</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.address) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.address_p) + "</b></th>";

												if (
													(escapeHtml(repVsPortal.address) === "" || escapeHtml(repVsPortal.address) === null) &&
													(escapeHtml(repVsPortal.address_p) === "" || escapeHtml(repVsPortal.address_p) === null)
												) {
													tble += "<th style='color:red;'><b>Report and Portal Data Not Found</b></th>";
												} else if (escapeHtml(repVsPortal.address) === "" || escapeHtml(repVsPortal.address) === null) {
													tble += "<th style='color:red;'><b>Report Data Not Found</b></th>";
												} else {
													tble += "<th style='color:red;'><b>Portal Data Not Found</b></th>";
												}
											}else if(escapeHtml(repVsPortal.address_check) !== "Y"){
												tble += "<th style='color:red;'><b>Address</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.address) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.address_p) + "</b></th>";
												tble += "<th style='color:red;'><b>Failed</b></th>";
											} 
											else{
												tble += "<th>Address</th>";
												tble += "<th>" + escapeHtml(repVsPortal.address) + "</th>";
												tble += "<th>" + escapeHtml(repVsPortal.address_p) + "</th>";
												tble += "<th>Passed</th>";
											}
										tble += "</tr>";
										tble += "<tr>";
											if (escapeHtml(repVsPortal.name_check) === "NF") {
												tble += "<th style='color:red;'><b>Name</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.name) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.name_p) + "</b></th>";

												if (
													(escapeHtml(repVsPortal.name) === "" || escapeHtml(repVsPortal.name) === null) &&
													(escapeHtml(repVsPortal.name_p) === "" || escapeHtml(repVsPortal.name_p) === null)
												) {
													tble += "<th style='color:red;'><b>Report and Portal Data Not Found</b></th>";
												} else if (escapeHtml(repVsPortal.name) === "" || escapeHtml(repVsPortal.name) === null) {
													tble += "<th style='color:red;'><b>Report Data Not Found</b></th>";
												} else {
													tble += "<th style='color:red;'><b>Portal Data Not Found</b></th>";
												}
											}else if(escapeHtml(repVsPortal.name_check) !== "Y"){
												tble += "<th style='color:red;'><b>Name</th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.name) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.name_p) + "</b></th>";
												tble += "<th style='color:red;'><b>Failed</b></th>";
											}
											else{
												tble += "<th>Name</th>";
												tble += "<th>" + escapeHtml(repVsPortal.name) + "</th>";
												tble += "<th>" + escapeHtml(repVsPortal.name_p) + "</th>";

												tble += "<th>Passed</th>";
											}
										tble += "</tr>";
										if (escapeHtml(repVsPortal.fuelType0) !== ""){
											tble += "<tr>";
											if (escapeHtml(repVsPortal.user0_check) === "NF") {
												tble += "<th style='color:red;'><b>Kw/H</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.user0) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml((parseFloat(repVsPortal.user0_P).toFixed(1))) + "</b></th>";

												if (
													(escapeHtml(repVsPortal.user0) === "" || escapeHtml(repVsPortal.user0) === null || escapeHtml(repVsPortal.user0) === "0") &&
													(escapeHtml(repVsPortal.user0_P) === "" || escapeHtml(repVsPortal.user0_P) === null || escapeHtml(repVsPortal.user0_P) === "0")
												) {
													tble += "<th style='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(1)'><b><u>Report and Portal Data Not Found</u></b></th>";
												} else if (escapeHtml(repVsPortal.user0) === "" || escapeHtml(repVsPortal.user0) === null || escapeHtml(repVsPortal.user0_P) === "0") {
													tble += "<th style='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(1)'><b><u>Report Data Not Found</u></b></th>";
												} else {
													tble += "<th style='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(1)'><b><u>Portal Data Not Found</u></b></th>";
												}
											}else if(escapeHtml(repVsPortal.user0_check) !== "Y"){
													tble += "<th style='color:red;'><b>Kw/H</b></th>";
													tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.user0) + "</b></th>";
													tble += "<th style='color:red;'><b>" + escapeHtml((parseFloat(repVsPortal.user0_P).toFixed(1))) + "</b></th>";
													tble += "<th style='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(1)'><b><u>Failed</u></b></th>";
												}
												else{
													tble += "<th>Kw/H</th>";
													tble += "<th>" + escapeHtml(repVsPortal.user0) + "</th>";
													tble += "<th>" + escapeHtml((parseFloat(repVsPortal.user0_P).toFixed(1))) + "</th>";
													tble += "<th style='cursor:pointer;color:blue;' onclick='showBarChartFromRepVsPortal(1)'><u>Passed</u></th>";
												}
											tble += "</tr>";
										}
										if (escapeHtml(repVsPortal.fuelType1) !== ""){
											tble += "<tr>";
											if (escapeHtml(repVsPortal.user1_check) === "NF") {
												tble += "<th style='color:red;'><b>Therm</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.user1) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.user1_P) + "</b></th>";

												if (
													(escapeHtml(repVsPortal.user1) === "" || escapeHtml(repVsPortal.user1) === null || escapeHtml(repVsPortal.user1) === "0") &&
													(escapeHtml(repVsPortal.user1_P) === "" || escapeHtml(repVsPortal.user1_P) === null || escapeHtml(repVsPortal.user1_P) === "0")
												) {
													tble += "<th style='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(2)'><b><u>Report and Portal Data Not Found</u></b></th>";
												} else if (escapeHtml(repVsPortal.user1) === "" || escapeHtml(repVsPortal.user1) === null || escapeHtml(repVsPortal.user1) === "0") {
													tble += "<th style='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(2)'><b><u>Report Data Not Found</u></b></th>";
												} else {
													tble += "<th style='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(2)'><b><u>Portal Data Not Found</u></b></th>";
												}
											}else if(escapeHtml(repVsPortal.user1_check) !== "Y"){
													tble += "<th style='color:red;'><b>Therm</b></th>";
													tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.user1) + "</b></th>";
													tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.user1_p) + "</b></th>";
													tble += "<th style='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(2)'><b><u>Failed</u></b></th>";
												}
												else{
													tble += "<th>Therm</th>";
													tble += "<th>" + escapeHtml(repVsPortal.user1) + "</th>";
													tble += "<th>" + escapeHtml(repVsPortal.user1_p) + "</th>";
													tble += "<thstyle='cursor:pointer;color:red;' onclick='showBarChartFromRepVsPortal(2)'><u>Passed</u></th>";
												}
											tble += "</tr>";
										}
										tble += "<tr>";
											if (escapeHtml(repVsPortal.rewardsPoints_check) === "NF") {
												tble += "<th style='color:red;'><b>Rewards</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.rewardsPoints) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.rewardsPointsAvilable) + "</b></th>";

												if (
													((escapeHtml(repVsPortal.rewardsPoints) === "" || escapeHtml(repVsPortal.rewardsPoints) === null) || escapeHtml(repVsPortal.rewardsPoints) === "0") &&
													((escapeHtml(repVsPortal.rewardsPointsAvilable) === "" || escapeHtml(repVsPortal.rewardsPointsAvilable === null) || escapeHtml(repVsPortal.rewardsPointsAvilable) === "0"))
												) {
													tble += "<th style='color:red;'><b>Report and Portal Data Not Found</b></th>";
												} else if ((escapeHtml(repVsPortal.rewardsPoints) === "" || escapeHtml(repVsPortal.rewardsPoints) === null) && escapeHtml(repVsPortal.rewardsPoints) === "0") {
													tble += "<th style='color:red;'><b>Report Data Not Found</b></th>";
												} else {
													tble += "<th style='color:red;'><b>Portal Data Not Found</b></th>";
												}
											}else if(escapeHtml(repVsPortal.rewardsPoints_check) !== "Y"){
												tble += "<th style='color:red;'><b>Rewards</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.rewardsPoints) + "</b></th>";
												tble += "<th style='color:red;'><b>" + escapeHtml(repVsPortal.rewardsPointsAvilable) + "</b></th>";
												tble += "<th style='color:red;'><b>Failed</b></th>";
											}
											else{
												tble += "<th>Rewards</th>";
												tble += "<th>" + escapeHtml(repVsPortal.rewardsPoints) + "</th>";
												tble += "<th>" + escapeHtml(repVsPortal.rewardsPointsAvilable) + "</th>";
												tble += "<th>Passed</th>";
											}
										tble += "</tr>";
										tble += "<tr>";
											tble += "<th><b>Overall Result</b></th>";
											tble += "<th></th>";
											tble += "<th></th>";
											if(escapeHtml(repVsPortal.overallRes) === "Y"){
												tble += "<th>Passed</th>";
											}
											else{
												tble += "<th style='color:red;'><b>Failed</b></th>";
											}
										tble += "</tr>";
									tble += "</tbody>";
								tble += "</table>";
							tble += "</div>";
						} else {
							tble = "No Data Found";
						}

						//alert(tble);
						// Set modal title
						document.getElementById("modalTitle").innerText = "Smart Scan Results";
						document.getElementById("closeModal").innerText = "Ok";
						document.getElementById("reasonModal").style.width = "70%";
						document.getElementById("modalContentArea").style.display = "block";
						
						if (tble !== "No Data Found") {
						  // Hide textarea and submit button
						  document.getElementById("reasonInputSection").style.display = "none";
						  document.getElementById("submitReasonBtn").style.display = "none";

						  // Show the table inside the content area
						  document.getElementById("modalContentArea").innerHTML = tble;
						} else {
						  // Show "No Data Found" message inside the content area
						  document.getElementById("reasonInputSection").style.display = "none";
						  document.getElementById("submitReasonBtn").style.display = "none";

						  document.getElementById("modalContentArea").innerHTML = "<p>No Data Found</p>";
						}

						 // Show the modal
						document.getElementById("reasonModal").style.display = "block";

						
					}
					
					function showBarChartFromRepVsPortal(barVal) {
						if (
							!repVsPortal ||
							((!repVsPortal.ub_history || Object.keys(repVsPortal.ub_history).length === 0) &&
							(!repVsPortal.rb_history || Object.keys(repVsPortal.rb_history).length === 0))
						) {
							alert("No data available");
						} else {
							showBarChartFromTwoJSONs(
								{ ub_history: repVsPortal.ub_history },
								{ rb_history: repVsPortal.rb_history },
								barVal
							);
						}
					}


					function showBarChartFromTwoJSONs(jsonData1, jsonData2, barVal) {
						const buildChart = function(historyData, title, barVal) {
							if (!historyData || Object.keys(historyData).length === 0) {
								return '<div class="col-md-6 mb-4">' +
											'<h5 class="text-center mb-2">' + title + '</h5>' +
											'<div class="border rounded shadow-sm p-3 bg-white text-center text-muted" style="height: 300px; display: flex; align-items: center; justify-content: center;">' +
												'No data available' +
											'</div>' +
									   '</div>';
							}

							const months = Object.keys(historyData).sort();
							let maxVal = 0;

							if (barVal === 1) {
								maxVal = Math.max(...months.map(m => parseFloat(historyData[m].kwVal)));
							} else if (barVal === 2) {
								maxVal = Math.max(...months.map(m => parseFloat(historyData[m].thVal)));
							}

							let bars = '';
							months.forEach(function(month, index) {
								let val = 0;
								if (barVal === 1) {
									val = parseFloat(historyData[month].kwVal);
								} else if (barVal === 2) {
									val = parseFloat(historyData[month].thVal);
								}

								const heightPercent = (val / maxVal) * 100;
								const barColor = index % 2 === 0 ? 'bg-success' : 'bg-primary';

								bars += '<div class="text-center mx-2" style="width: 50px;">' +
											'<div class="' + barColor + ' d-flex align-items-end justify-content-center text-white" style="height: ' + heightPercent + '%; min-height: 20px;">' +
												'<small>' + val.toFixed(0) + '</small>' +
											'</div>' +
											'<small class="d-block mt-2">' + formatMonthYear(month) + '</small>' +
										'</div>';
							});

							return '<div class="col-md-6 mb-4">' +
										'<h5 class="text-center mb-2">' + title + '</h5>' +
										'<div class="border rounded shadow-sm p-3 bg-white">' +
											'<div class="d-flex align-items-end justify-content-center overflow-hidden" style="height: 300px;">' +
												bars +
											'</div>' +
										'</div>' +
								   '</div>';
						};

						let headin = "";
						if (barVal === 1) {
							headin = "Kw/h History";
						} else if (barVal === 2) {
							headin = "Therm History";
						}

						const chartHTML = '<html>' +
												'<head>' +
													'<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">' +
													'<style>body { background-color: #f8f9fa; }</style>' +
												'</head>' +
												'<body class="p-4 bg-light">' +
													'<h4 class="mb-4 text-center">' + headin + '</h4>' +
													'<div class="row">' +
														buildChart(jsonData1.ub_history, "Usage & Cost", barVal) +
														buildChart(jsonData2.rb_history, "Bill History", barVal) +
													'</div>' +
												'</body>' +
												'</html>';

						const chartWindow = window.open("", "ChartWindow", "width=1200,height=600");
						chartWindow.document.write(chartHTML);
						chartWindow.document.close();
					}





					async function callPdfPass(reportId, custid, sr, wlId) {
						var usrs = "${usr}";
						showReasonModal("Enter reason for Sample Passed", async function(reason) {
							var scrptNme = "markIndQaPass";
							var frRpId = reportId;
							var toRpId = sr;
							
							if (reason === "Individual Sample passed" || reason === "Reason Text Here" || reason === "Please enter reason here" || reason === "") {
								alert("Please enter a valid reason");								
								return;
							}
							
							let spinner = document.createElement("div");
							spinner.innerHTML = "⏳ Marking QA Status...";
							spinner.style.position = "fixed";
							spinner.style.top = "50%";
							spinner.style.left = "50%";
							spinner.style.transform = "translate(-50%, -50%)";
							spinner.style.background = "#fff";
							spinner.style.padding = "10px 20px";
							spinner.style.border = "1px solid #ccc";
							spinner.style.borderRadius = "5px";
							spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
							spinner.style.zIndex = "10000"; // Set a high z-index
							document.body.appendChild(spinner);
							
							
							try {
								let requestData = JSON.stringify({
									frmRepId: frRpId,
									toRepId: toRpId,
									sndT: "Y",
									rsn: reason,
									wtLbl: wlId,
									scrptNm: scrptNme,
									usrN: usrs
								});
								

								let response = await fetch("phpRunners/run_PY.php", {
									method: "POST",
									headers: {
										"Content-Type": "application/json",
									},
									body: requestData,
								});

								if (!response.ok) {
									alert("HTTP error! Status: "+response.status);
								}

								let resultText = await response.text();

								// ✅ Remove spinner IMMEDIATELY after response is received
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
									spinner.style.zIndex = "0"; // Set a high z-index
								}

								let resultJson;
								try {
									resultJson = JSON.parse(resultText);
									if (resultJson.error) {
										alert("Error: "+ resultJson.error);
									} else {
										//alert("Success:QA Pass Status: "+ resultJson.output);
										alert("✅ Passed: Report ID " + reportId + ", CustID: " + custid + ", reason: " + reason);
									}
								} catch (e) {
									alert("Invalid JSON response: " + resultText);
								}
							} catch (error) {
								alert("Error running PHP script:"+ error);
								// Remove spinner in case of error
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
								}
								alert("Error:"+ error.message);
							}
							
						},"Individual Sample passed");
						
					}

					async function callPdfFail(reportId, custid, sr, wlId) {
						var usrs = "${usr}";
						showReasonModal("Enter reason for Sample Failed", async function(reason) {
							var scrptNme = "markIndQaPass";
							var frRpId = reportId;
							var toRpId = sr;
							
							if (reason === "Individual Sample passed" || reason === "Reason Text Here" || reason === "Please enter reason here" || reason === "") {
								alert("Please enter a valid reason");								
								return;
							}
							
							let spinner = document.createElement("div");
							spinner.innerHTML = "⏳ Marking QA Status...";
							spinner.style.position = "fixed";
							spinner.style.top = "50%";
							spinner.style.left = "50%";
							spinner.style.transform = "translate(-50%, -50%)";
							spinner.style.background = "#fff";
							spinner.style.padding = "10px 20px";
							spinner.style.border = "1px solid #ccc";
							spinner.style.borderRadius = "5px";
							spinner.style.boxShadow = "0px 0px 10px rgba(0, 0, 0, 0.2)";
							spinner.style.zIndex = "10000"; // Set a high z-index
							document.body.appendChild(spinner);
							
							
							try {
								let requestData = JSON.stringify({
									frmRepId: frRpId,
									toRepId: toRpId,
									sndT: "F",
									rsn: reason,
									wtLbl: wlId,
									scrptNm: scrptNme,
									usrN: usrs
								});
								

								let response = await fetch("phpRunners/run_PY.php", {
									method: "POST",
									headers: {
										"Content-Type": "application/json",
									},
									body: requestData,
								});

								if (!response.ok) {
									alert("HTTP error! Status: "+response.status);
								}

								let resultText = await response.text();

								// ✅ Remove spinner IMMEDIATELY after response is received
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
									spinner.style.zIndex = "0"; // Set a high z-index
								}

								let resultJson;
								try {
									resultJson = JSON.parse(resultText);
									if (resultJson.error) {
										alert("Error: "+ resultJson.error);
									} else {
										alert("❌ Failed: Report ID " + reportId + ", CustID: " + custid + ", reason: " + reason);
										//alert("Success:QA Pass Status: "+ resultJson.output);
									}
								} catch (e) {
									alert("Invalid JSON response: " + resultText);
								}
							} catch (error) {
								alert("Error running PHP script:"+ error);
								// Remove spinner in case of error
								if (spinner && spinner.parentNode) {
									document.body.removeChild(spinner);
								}
								alert("Error:"+ error.message);
							}
							
						});
					}

					// Disable right-click
					document.addEventListener("contextmenu", function(e) {
						e.preventDefault();
					});

					// Disable Save As, Print, DevTools shortcuts
					document.addEventListener("keydown", function(e) {
						if (
							(e.ctrlKey || e.metaKey) && (e.key === "s" || e.key === "p") ||
							e.key === "F12" ||
							(e.ctrlKey && e.shiftKey && ["I", "J", "C"].includes(e.key))
						) {
							e.preventDefault();
							alert("This action is disabled.");
						}
					});
					<\/script>
					</body>
					</html>
					`);
						popup.document.close();

					}
				
					
					function downloadPdfData(pdfUrl, fileNme) {
						let pdfFileUrl = "phpRunners/fetch_pdf.php?url=" + encodeURIComponent(pdfUrl);
					 
						fetch(pdfFileUrl)
							.then(response => response.blob()) // Convert response to Blob
							.then(blob => {
								let link = document.createElement("a");
								link.href = URL.createObjectURL(blob);
								link.download = fileNme; // Set the filename
								document.body.appendChild(link);
								link.click();
								document.body.removeChild(link);
								URL.revokeObjectURL(link.href); // Clean up URL object
							})
							.catch(error => console.error("Error downloading PDF:", error));
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