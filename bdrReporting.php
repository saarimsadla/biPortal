<?php
	session_start();
	if (!$_SESSION['authenticated']) {
		header("Location: index.html");
		exit();
	}
	 
	$defaultWhiteLabel =  isset($_SESSION['defWhitelabel']) ? $_SESSION['defWhitelabel'] : 0;
	$whiteLabel = $defaultWhiteLabel;
	$pgName = "bdrReporting.php";
	$wl = isset($_SESSION['whiteLabels']) ? $_SESSION['whiteLabels'] : null;
	$rt = isset($_SESSION['rghts']) ? $_SESSION['rghts'] : null;
	$usName = isset($_SESSION['uname']) ? $_SESSION['uname'] : null;
	//$rt = "1,3,5";

	//echo " user:$usName  wl:$wl  rgt:$rt  " ;

	$rightsArray = explode(",",$rt);
	$whtLblArray = explode(",",$wl);

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
		<title>BDR Process Overview Report</title>
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
				$database_name = "bdr";
				$collection_name = "bdr_proc_Ovrvw_Rep.";
			 
				// Fetch initial data to populate select options
								
				$initial_query = [
		
					"cohortName" => [
						'$not' => [
							'$regex' => ".*\\(NO LONGER ACTIVE\\).*"
						]
					],
					"isValidFlag" => "Y"
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
			 
				//print_r($cmd);
				
//				print_r($initial_output);
				
				$initial_data = json_decode($initial_output, true);
				
				//print_r($initial_data);
				
				if (empty($initial_data)) {
					//print_r( "JSON ERROR: " . json_last_error_msg());
					die("Error: Unable to fetch initial data from MongoDB.");
				}
			 
				// Filter options
				//$pcpiRunIdOptions = array_column($initial_data, 'bdrEventId', 'bdrEventId');
				//arsort($pcpiRunIdOptions); // Sort in descending order
				$pcpiRunIdOptions = [];

				foreach ($initial_data as $item) {
					$id = $item['bdrEventId'];
					$name = $item['cohortName'];
					$pcpiRunIdOptions[$id] = "$id | $name";
				}

				arsort($pcpiRunIdOptions); // Sort by key in descending order

				$stateParamOptions = array_column($initial_data, 'transactionType', 'transactionSr');
				$cohortIdOptions = array_column($initial_data, 'cohortName', 'cohortId');
				
			//	print("state options: ");
			//	print_r($stateParamOptions);
				
			//	print("cohort options: ");
			//	print_r($cohortIdOptions);
			 
				// Form submission handling
				if ($_SERVER["REQUEST_METHOD"] == "POST") {
					// Retrieve form data
					$pcpiRunId = $_POST['pcpiRunId'];
					$pcpiRunIdT = $_POST['pcpiRunIdT'];
					$stateParam = $_POST['stateParam'];
					$cohortId = $_POST['cohortId'];
			 
					// Adjust query based on form data
					$query_conditions = [];
			 
					if ($pcpiRunId !== 'All' && $pcpiRunIdT !== 'All') {
						$query_conditions[] = '"bdrEventId": {"$gte": ' . $pcpiRunId . ', "$lte": ' . $pcpiRunIdT . '} , "isValidFlag" : "Y" ';
					}
			 
					if ($stateParam !== 'All') {
						// Construct regex pattern to match the first two letters of cohortName
						$query_conditions[] = '"transactionSr": '.(int)$stateParam.' , "isValidFlag" : "Y" ';
					}
			 
					if ($cohortId !== 'All') {
						$query_conditions[] = '"cohortId": ' . $cohortId . ' , "isValidFlag" : "Y" ';
					}
					
					if ($pcpiRunId == 'All' && $pcpiRunIdT == 'All' && $stateParam == 'All' && $cohortId == 'All') {
						$query_json = '{"cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}} , "isValidFlag" : "Y"}';
					}
					else{
						$query_json = '{' . implode(', ', $query_conditions) . ', "cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}}}';
					}
				} else {
					$query_json = '{"cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}} , "isValidFlag" : "Y"}';
				}
			 
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
				$data = json_decode($initial_output, true);
				
				//print_r($initial_data);
				
				if (empty($data)) {
					die("Error: Unable to fetch data from MongoDB.");
				}
			//	print_r($data);
				$headings_mapping = [
					'bdrEventId' => 'BDR Event Id',
					'bdrEventMonth' => 'Event Month',
					'cohortName' => 'Cohort Name',
					'transactionType' => 'Type',
					'bdrCustomersWhoSavedCount' => 'Participants Saving',
					'bdrCustomersWhoDidntSaveCount' => 'Participants Not Saving',
					'treatmentGroupSize' => 'Treatment Group Size',
					'treatmentInActiveCust' => 'In-Active',
					'treatmentActiveCust' => 'Active',
					'treatmentOptOuts' => 'Opt-Outs',
					'PC_exclusionCounts' => 'X of Y Exclusion',
					'PC_Counts' => 'Post Comp / DCS Start',
					'DC_exclusionCounts' => 'DCS Exclusion',
					'DC_Counts' => 'Post DCS / Imaging Start',
					'PI_exclusionsCount' => 'PI Exclusion',
					'PI_Counts' => 'PI',
					'emailSentCounts' => 'Sent to Email Srv',
					'paperSentCounts' => 'Sent to Paper Srv',
					'sfmcDeliveredCounts' => 'SFMC Recieved',
					'sfmcBouncedCounts' => 'SFMC Bounced',
					'sfmcSentCounts' => 'SFMC Delivered',
					'sfmcOpenCounts' => 'SFMC Opened',
					'sfmcClickCounts' => 'SFMC Click',
					'sfmcUnSubscribeCounts' => 'SFMC Un-Subscribe',
					'sfmcNoUpdateCounts' => 'SFMC No Update'
					//,'comments' => 'Remarks'
				];
				//					'sfmcDeliveredErrorCounts' => 'Delta Sent Vs Recieved Email Srv', // add after paper sent counts
			 
				$numeric_fields = [
					'treatmentGroupSize',
					'treatmentActiveCust',
					'treatmentInActiveCust',
					'treatmentOptOuts',
					'PC_Counts',
					'PC_exclusionCounts',
					'bdrCustomersWhoDidntSaveCount',
					'bdrCustomersWhoSavedCount',
					'DC_Counts',
					'DC_exclusionCounts',
					'PI_Counts',
					'PI_exclusionsCount',
					'emailSentCounts',
					'paperSentCounts',
					'sfmcDeliveredErrorCounts',
					'sfmcDeliveredCounts',
					'sfmcBouncedCounts',
					'sfmcSentCounts',
					'sfmcOpenCounts',
					'sfmcClickCounts',
					'sfmcUnSubscribeCounts',
					'sfmcNoUpdateCounts'
				];
			 
				usort($data, function ($a, $b) {
					// Cast bdrEventId to integers for comparison
					$bdrEventIdA = (int)$a['bdrEventId'];
					$bdrEventIdB = (int)$bdrEventIdB = (int)$b['bdrEventId'];
				 
					// First, sort by bdrEventId in descending order
					if ($bdrEventIdA !== $bdrEventIdB) {
						return $bdrEventIdB - $bdrEventIdA;
					}
				 
					// If bdrEventId values are equal, sort by transactionSr in descending order
					$transactionSrA = (int)$a['transactionSr'];
					$transactionSrB = (int)$b['transactionSr'];
				 
					return $transactionSrB - $transactionSrA;
				});
				
				//print_r($data);
				
				$bounceRates = [];
				function createHtmlTable($data, $headings_mapping, $numeric_fields)
				{
					// Start the HTML output
					$html = '<p>*Please click on Blue exclusions counts to get details.</p>';
					$html .= '<div class="table-responsive">';
					$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'BDRProcessOverView.csv\', \'masterDataTable\')">Download CSV</button>';
					$html .= '<table class="table table-bordered table-sticky-header" id="masterDataTable">';
					$html .= '<thead class="thead-light">';
					$html .= '<tr>';
					// Add table headers
					foreach ($headings_mapping as $heading => $display_name) {
						if ($heading === 'cohortName') {
							$html .= '<th style="width: 300px;">' . htmlspecialchars($display_name) . '</th>';
						} else {
							$html .= '<th>' . htmlspecialchars($display_name) . '</th>';
						}
					}
					$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';
					// Add table rows
					foreach ($data as $rowIndex => $row) {
						$html .= '<tr>';
						foreach ($headings_mapping as $heading => $display_name) {
							if (in_array($heading, $numeric_fields)) {
								if ($heading == 'sfmcBouncedCounts' || $heading == 'PI_exclusionsCount') { //DC_exclusionCounts,PC_exclusionCounts,PI_exclusionsCount,sfmcBouncedCounts //|| $heading == 'PC_exclusionCounts' || $heading == 'DC_exclusionCounts' 
									$modalId = $heading . $rowIndex . (int)$row['transactionSr'];
									//print($modalId);
									if ($heading == 'sfmcBouncedCounts') {
										$html .= '<td><a href="#" data-toggle="modal" data-target="#' . $modalId . '" onclick="loadBounceDetails(\'' . htmlspecialchars($row['wlId']) . '\', \'' . htmlspecialchars($row['bdrEventId']) . '\', \'' . htmlspecialchars($row['transactionSr']) . '\', \''. htmlspecialchars($row[$heading]) . '\', \'' . htmlspecialchars($modalId) . '\',\''.htmlspecialchars($heading).'\')"><b>' . number_format($row[$heading]).'</b> ('.round((($row[$heading]/$row['sfmcDeliveredCounts'])*100),2).'%)'. '</a></td>';
									}
									
									if ($heading == 'PI_exclusionsCount') {
										$html .= '<td><a href="#" data-toggle="modal" data-target="#' . $modalId . '" onclick="loadBounceDetails(\'' . htmlspecialchars($row['wlId']) . '\', \'' . htmlspecialchars($row['bdrEventId']) . '\', \'' . htmlspecialchars($row['transactionSr']) . '\', \''. htmlspecialchars($row[$heading]) . '\', \'' . htmlspecialchars($modalId) . '\',\''.htmlspecialchars($heading).'\')"><b>' . number_format($row[$heading]).'</b> ('.round((($row[$heading]/$row['DC_Counts'])*100),2).'%)'. '</a></td>';
									}
									
									$html .= '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog" aria-labelledby="' . $modalId . 'Label" aria-hidden="true">';
									$html .= '<div class="modal-dialog" role="document" style="max-width:75%; width: 75%">';
									$html .= '<div class="modal-content">';
									$html .= '<div class="modal-header">';
									if ($heading == 'sfmcBouncedCounts'){
										$html .= '<h5 class="modal-title" id="' . $modalId . 'Label">Bounce Rate Details</h5>';
									}
									if ($heading == 'PI_exclusionsCount'){
										$html .= '<h5 class="modal-title" id="' . $modalId . 'Label">Post Imaging Exclusion Details</h5>';
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
								} else {/*
									'sfmcDeliveredCounts' => 'SFMC Recieved',

									'sfmcBouncedCounts' => 'SFMC Bounced',

									'sfmcSentCounts' => 'SFMC Delivered',
									'sfmcOpenCounts' => 'SFMC Opened',
									'sfmcClickCounts' => 'SFMC Click',
									'sfmcUnSubscribeCounts' => 'SFMC Un-Subscribe',
									'sfmcNoUpdateCounts' => 'SFMC No Update'
								*/
									if ($heading == 'sfmcSentCounts' || $heading == 'sfmcOpenCounts' || $heading == 'sfmcClickCounts' || $heading == 'sfmcUnSubscribeCounts' || $heading == 'sfmcNoUpdateCounts') {
										$html .= '<td><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/$row['sfmcDeliveredCounts'])*100),2).'%)'. '</td>';	
									}
									else if ($heading ==  'DC_exclusionCounts') {
										$html .= '<td><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/$row['PC_Counts'])*100),2).'%)'. '</td>';	
									}
									else if ($heading ==  'PC_exclusionCounts') {
										$html .= '<td><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/($row['treatmentActiveCust'] - $row['treatmentOptOuts']))*100),2).'%)'. '</td>';	
									}
									else if ($heading ==  'treatmentOptOuts') {
										$html .= '<td><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/($row['treatmentActiveCust']))*100),2).'%)'. '</td>';	
									}
									else if ($heading ==  'treatmentInActiveCust') {
										$html .= '<td><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/($row['treatmentGroupSize']))*100),2).'%)'. '</td>';	
									}
									else{
										$html .= '<td>' . number_format($row[$heading]) . '</td>';
									}
								}
							} else {
								$html .= '<td>' . htmlspecialchars($row[$heading]) . '</td>';
							}
						}
						$html .= '</tr>';
					}
					$html .= '</tbody>';
					$html .= '</table>';
					$html .= '</div>';
					return $html;
				}
			?>
			<div class="container-fluid" style="padding-top:0.5%;padding-left:0.5%;">
				<div class="row">
					<div class="col-12 text-right mt-2">
						<!--<a href="https://franklinenergy.sharepoint.com/:x:/r/sites/planet/_layouts/15/doc2.aspx?action=default&file=Post_Comparables_Post_Imaging_system_mapping_v3.xlsx&mobileredirect=true&sourcedoc=%7BEFDD84B0-D43D-4417-8707-81A369FCC491%7D" target="_blank" class="btn btn-success btn-sm">Report Mapping</a>-->
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
							<label for="stateParam">Event Type:</label>
							<select id="stateParam" name="stateParam" class="form-control select2">
								<option value="All">All</option>
								<?php foreach ($stateParamOptions as $value => $display): ?>
									<option value="<?= $value ?>" <?= isset($_POST['stateParam']) && $_POST['stateParam'] == $value ? 'selected' : '' ?>><?= $display ?></option>
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
					<?php if(in_array(8,$rightsArray)) {echo '<li class="nav-item">
						<a class="nav-link active" id="masterData-tab" data-toggle="tab" href="#masterData">Master Data</a>
					</li>';}?>
					<?php /*if(in_array(9,$rightsArray)) {echo '<li class="nav-item">
						<a class="nav-link" id="summaryData-tab" data-toggle="tab" href="#summaryData">Summary Data</a>
					</li>';}*/?>
				</ul>
				<?php 
					if(in_array(2,$rightsArray)) {
						echo '<div class="tab-pane fade show active" id="masterData">
							<!-- Master Data rendering code here -->
							<br/> 
							<div class="tab-content"> ';
								if(in_array(8,$rightsArray)) {
									echo createHtmlTable($data, $headings_mapping, $numeric_fields,$bounceRates);  
								}
								else{
									echo "0 results";
								}
							echo ' </div> '; 
						echo ' </div> '; 
					}
					/*if(in_array(9,$rightsArray)) {
						echo '<div class="tab-pane fade" id="summaryData">  
								  <!-- Summary Data rendering code here -->  		  
								  <div class="tab-content"> ';
									if(in_array(9,$rightsArray)) {
										echo '<p> summary will go here</p> ';
										echo '<h1> summary will go here</h1> ';
									}
									else{
										echo "0 results";
									}
								echo' </div> 
							  </div> ';
					}*/
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

			function loadBounceDetails(wlId, bdrEventId, transactionSr, actVal, modalId, flds) {
				var xhr = new XMLHttpRequest();
				var uri = 'fetch_bdr_modal_details.php?wlId=' + encodeURIComponent(wlId) + '&bdrEventId=' + encodeURIComponent(bdrEventId) + '&transactionSr=' + encodeURIComponent(transactionSr) + '&sfmcBouncedCounts='+ encodeURIComponent(actVal) + '&fldName='+encodeURIComponent(flds); 
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