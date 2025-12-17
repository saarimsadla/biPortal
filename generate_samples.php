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

//defaulting to customer provide values
$custidParmVal = Null;

$whiteLabelMap = [
    46 => "8378988",
    47 => "45078523"
];

$custidParmVal = $whiteLabelMap[$defaultWhiteLabel] ?? Null;



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
					$pgName = "generate_samples.php";
					$varCntr = 0;
					//echo $pgName;
					//include './configs/navbarSetup.php';
					// Function to find clientAbr by wlId
					
					function getUniqueSampleRepIds(){
						global $defaultWhiteLabel;
						
						$database_name = "hursPortal";
						$collection_name = "QASamples.";
						// Add "ALL" option with value 0
						$transactionSr = 0;
						$initial_query_json = json_encode(['transactionSr' => $transactionSr]);
						$ag = '[
								{
									"$match": { 
										"qaRemoved" : "N","wlId": '.$defaultWhiteLabel.'
									}
								},
								{
									"$group": {
										"_id": "$reportId",
										"count": { "$sum": 1 }
									}
								},
								{
									"$match": {
										"count": 50
									}
								},
								{
									"$project": {
										"reportId": "$_id",
										"_id": 0
									}
								}
							]';
						//print_r($ag);
						$python_command = 'python ./pythonScripts/mongodb_query.py ' . escapeshellarg($database_name) . ' ' . escapeshellarg($collection_name) . ' ' . escapeshellarg($initial_query_json) . ' "ag" ' . escapeshellarg($ag);

						$io_repId = shell_exec($python_command);
						//print_r($python_command);
						$id_repId = json_decode($io_repId, true);
						$reportIds = Null;
						//rsort($id_repId);
						//print_r($id_repId);
						if (!$id_repId) {
							print_r($id_repId);
							print_r("Error: Unable to fetch sample data report id data from MongoDB.");
							return $reportIds;
						}
						else{
							$reportIds = array_map(function($item) {
								return $item['reportId'];
							}, $id_repId);
						
							//print_r($fromReportIDs);
							//print_r($sendOptions);
						}
						return $reportIds;
					}
					
					function getRepCounts($repId) {
						global $defaultWhiteLabel;

						$database_name = "hers";
						$collection_name = "imaging.";
						$transactionSr = 0;
						$initial_query_json = json_encode(['transactionSr' => $transactionSr]);

						$ag = '[
								{
									"$match": {
										"reportId": '.$repId.',
										"wlId": '.$defaultWhiteLabel.'
									}
								},
								{
									"$group": {
										"_id": "$reportType",
										"uniqueDocuments": { "$addToSet": "$_id" }
									}
								},
								{
									"$project": {
										"reportType": "$_id",
										"totalUniqueDocuments": { "$size": "$uniqueDocuments" }
									}
								}
							]';

						$python_command = 'python ./pythonScripts/mongodb_query.py ' . escapeshellarg($database_name) . ' ' . escapeshellarg($collection_name) . ' ' . escapeshellarg($initial_query_json) . ' "ag" ' . escapeshellarg($ag);
						
						//print_r($python_command);
						$io_repId = shell_exec($python_command);
						$id_repId = json_decode($io_repId, true);

						if ($id_repId === null) {
							die("Error: Unable to fetch data report COUNTS data from MongoDB.");
						} else {
							//print_r($id_repId);
							$result = [];

							foreach ($id_repId as $item) {
								if ($item['reportType'] === 'email' && $item['totalUniqueDocuments'] >= 100) {
									$result[] = 'E:100+';
								}
								elseif ($item['reportType'] === 'email' && $item['totalUniqueDocuments'] < 100) {
									$result[] = 'E:0';
								}
								
								elseif ($item['reportType'] === 'paper' && $item['totalUniqueDocuments'] >= 100) {
									$result[] = 'P:100+';
								}
								elseif ($item['reportType'] === 'paper' && $item['totalUniqueDocuments'] < 100) {
									$result[] = 'P:0';
								}
							}
							if (!empty($result)){
								return implode(' | ', $result);
							}
							else{
								return null;
							}

						}
					}
					
					function populateTheSelectionFields($dbNme,$colNme) { // this function populates all the search drop down feilds
						global $defaultWhiteLabel, $fromReportIDs, $sendOptions;
						//	print_r("deeeee: ".$defaultWhiteLabel);
						$database_name = $dbNme;
						$collection_name = $colNme;
						$smplReportIds = getUniqueSampleRepIds();
						//print_r($smplReportIds);
						// Add "ALL" option with value 0
						$transactionSr = 0;
						$initial_query_json = json_encode(['transactionSr' => $transactionSr]);
						$ri = "";
						
											

						if ($defaultWhiteLabel == 46 ){
							$ri = ' ,"reportId": { "$gte": 2254} '; //2254
						}
						elseif ($defaultWhiteLabel == 47 ){
							$ri = ' ,"reportId": { "$gte": 1125} ';
						}
						elseif ($defaultWhiteLabel == 38 ){
							$ri = ' ,"reportId": { "$gte": 1013} ';
						}
						elseif ($defaultWhiteLabel == 52 ){
							$ri = ' ,"reportId": { "$gte": 1117} ';
						}
						else{
							$ri = '';
						}
						
						$ag = '[
								{
									"$match": { 
										"$or": [	{ "abandoned": { "$exists": false } },{ "abandoned": false } ]
										,"wlId": '.$defaultWhiteLabel.$ri.'
									} 
								},
							  {
								"$lookup": {
								  "from": "cohorts",
								  "let": { "cohortId": "$cohortId", "wlId": "$wlId" },
								  "pipeline": [
									{
									  "$addFields": {
										"groupKey": {
										  "$concat": [
											{ "$toString": "$cohortId" },
											"_",
											{ "$toString": "$wlId" }
										  ]
										}
									  }
									},
									{
									  "$match": {
										"$expr": {
										  "$eq": [
											"$groupKey",
											{ "$concat": [{ "$toString": "$$cohortId" }, "_", { "$toString": "$$wlId" }] }
										  ]
										}
									  }
									}
								  ],
								  "as": "chorts"
								}
							  },
							  {
								"$addFields": {
								  "groupKey": {
									"$concat": [
									  { "$toString": "$cohortId" },
									  "_",
									  { "$toString": "$wlId" }
									]
								  }
								}
							  },
							  {
								"$group": {
								  "_id": {
									"reportId": "$reportId",
									"cohortNme": { "$arrayElemAt": ["$chorts.name", 0] },
									"groupKey": "$groupKey"
								  }
								}
							  },
							  {
								"$project": {
								  "_id": 0,
								  "reportId": "$_id.reportId",
								  "cohortNme": "$_id.cohortNme"
								}
							  }
							]';
						
						//print_r($ag);
						
						$python_command = 'python ./pythonScripts/mongodb_query.py ' . escapeshellarg($database_name) . ' ' . escapeshellarg($collection_name) . ' ' . escapeshellarg($initial_query_json) . ' "ag" ' . escapeshellarg($ag);

						$io_repId = shell_exec($python_command);
						//print_r($python_command);
						//print_r(getRepCounts(1998));
						$id_repId = json_decode($io_repId, true);
						rsort($id_repId);
						//print_r($id_repId);
						if (!$id_repId) {
							print_r($id_repId);
							die("Error: Unable to fetch data report id data from MongoDB.");
						}
						else{
							$fromReportIDs["0"] = "ALL";
							//print_r($id_repId);
							foreach ($id_repId as $row) {
								if(!empty($smplReportIds)){
									if (in_array($row['reportId'], $smplReportIds)) {
										//print_r("skip");
										continue;
									}
									//print_r($row['reportId'].",");
								}

								/*if ($result = getRepCounts($row['reportId'])) {
									$rId = (string) $row['reportId'] . " => " . $result;
								} else {
									continue;
								}*/
								
								$rId = (string) $row['reportId'];
								$dsply = (string) $row['reportId'] . ' | ' . $row['cohortNme'];
								if (!isset($fromReportIDs[$rId])) {
									$fromReportIDs[(string) $row['reportId']] = $dsply;
									//print_r("add");
								}
							}
						
							//print_r($fromReportIDs);
							//print_r($sendOptions);
						} 
												
						// Add "ALL" option with value 0
						$agt = '[
								{
									"$match": {"wlId": '.$defaultWhiteLabel.'  ,"cohortId": { "$exists": true }} 
								},
							{
								"$group": {
									"_id": { "reportType": "$reportType"} 
								}
							},
							{
								"$project": {
									"_id": 0,
									"reportType": "$_id.reportType"
								}
							}
						]';
						//print_r($ag);
						$python_command = 'python ./pythonScripts/mongodb_query.py ' . escapeshellarg($database_name) . ' ' . escapeshellarg($collection_name) . ' ' . escapeshellarg($initial_query_json) . ' "ag" ' . escapeshellarg($agt);

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
						}
						return Null;
					}
					
						//print_r("deeeee1: ".$defaultWhiteLabel);
					populateTheSelectionFields("hers", "report_runs.");// populate all drop down feilds with data
					
				?>
				<div class="container-fluid" style="padding-top:0.5%;padding-left:0.5%;">
					<form method="post" id="secondForm">
						<div class="form-row">
							<div class="form-group col-md-4">
								<label for="gsfrmRepId">From Report Id:</label>
								<select id="gsfrmRepId" name="gsfrmRepId" class="form-control select2" required>
									 <?php foreach ($fromReportIDs as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['gsfrmRepId']) && $_POST['gsfrmRepId'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									 <?php endforeach; ?>
								</select>

							</div>
							<div class="form-group col-md-4">
								<label for="gstoRepId">To Report Id:</label>
								<select id="gstoRepId" name="gstoRepId" class="form-control select2" required>
									<?php foreach ($fromReportIDs as $value => $display): ?>
										<option value="<?= $value ?>" <?= isset($_POST['gstoRepId']) && $_POST['gstoRepId'] == $value ? 'selected' : '' ?>><?= $display ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group col-md-4">
								<label for="gscustidPRM">Include Custid(s):</label>
								<textarea id="gscustidPRM" name="gscustidPRM" class="form-control" rows="2" cols="100" 
								onfocus="if (this.value === 'Add Comma seperated custids here') { this.value = ''; }" 
								onblur="if (this.value === '') { this.value = 'Add Comma seperated custids here'; }"><?php echo isset($custidParmVal) ? $custidParmVal : "Add Comma seperated custids here"; ?></textarea>
							</div>
							<!--<div class="form-group col-md-3">
								<label for="gssndT">Send Type:</label>
								<select id="gssndT" name="gssndT" class="form-control" required>
									<?php //foreach ($sendOptions as $value => $display): ?>
										<option value="<?//= $value ?>" <?//= isset($_POST['gssndT']) && $_POST['gssndT'] == $value ? 'selected' : '' ?>><?//= $display ?></option>
									<?php //endforeach; ?>
								</select>
							</div>-->
						</div>
						<!--generateSamples()-->
						<?php if(in_array(13,$rightsArray)) { echo '<button type="button" class="btn btn-primary" style="background-color: darkgreen;" onclick="generateSamples()"><i class="fa fa-plus-square" aria-hidden="true"></i></button>';} ?>
						<!--<button type="submit" class="btn btn-primary">Close</button>-->
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