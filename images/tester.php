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
				$database_name = "hursPortal";
				$collection_name = "PCPI_MASTERDATA.";
			 
				// Fetch initial data to populate select options
				$initial_query_json = '{
					"cohortName": {
						"$not": {
							"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"
						}
					}
				}';
			 
				$query_json = '{}';
				//print("ini query: ");
				//print_r($initial_query_json);
				//print(" ");
				$initial_command = "python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query.py $database_name $collection_name '$initial_query_json' 'q' '[]'";
				$initial_output = shell_exec($initial_command);
				//print_r($initial_command);
				$initial_data = json_decode($initial_output, true);
				if (!$initial_data) {
					die("Error: Unable to fetch initial data from MongoDB.");
				}
			 
				// Filter options
				$pcpiRunIdOptions = array_column($initial_data, 'hersReportStartMonth', 'hersReportStartMonth');
				arsort($pcpiRunIdOptions); // Sort in descending order
				$stateParamOptions = array_unique(array_map(function($item) {
					return trim(substr($item['cohortName'], 0, 2));
				}, $initial_data));
				$cohortIdOptions = array_column($initial_data, 'cohortName', 'cohortId');
			 
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
						$query_json = '{"cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}}}';
					}
					else{
						$query_json = '{' . implode(', ', $query_conditions) . ', "cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}}}';
					}
				} else {
					$query_json = '{"cohortName": {"$not": {"$regex": ".*\\\\(NO LONGER ACTIVE\\\\).*"}}}';
				}
			 
				//print(" ;data query: ");
				//print_r($query_json);
				//print(" ");
				// Fetch data from MongoDB based on query
				$command = "python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query.py $database_name $collection_name '$query_json' 'q' '[]'";
				$output = shell_exec($command);
				$data = json_decode($output, true);
				if (!$data) {
					die("Error: Unable to fetch data from MongoDB.");
				}
			 
				$headings_mapping = [
					'whiteLabelId' => 'White Label ID',
					'clientName' => 'Client Name',
					'opcoId' => 'Opco ID',
					'hersReportId' => 'Report ID',
					'hersReportStartMonth' => 'Report Start Month',
					'cohortId' => 'Cohort ID',
					'cohortName' => 'Cohort Name',
					'bhrCounts' => 'BHR Counts',
					'archivedCounts' => 'Archived Counts',
					'treatmentGroupSize' => 'Treatment Group Size',
					'treatmentActiveCust' => 'Treatment Active Cust',
					'treatmentInActiveCust' => 'Treatment Inactive Cust',
					'PC_Counts' => 'PC Counts',
					'PC_exclusionCounts' => 'PC Exclusion Counts',
					'DC_Counts' => 'DC Counts',
					'DC_exclusionCounts' => 'DC Exclusion Counts',
					'DC_ProcessedEmail' => 'DC Processed Email Counts',
					'DC_ProcessedPrints' => 'DC Processed Print Counts',
					'PI_Counts' => 'PI Counts',
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
					'DC_permanentExclusions' => 'DC Permanent Exclusions',
					'DC_invalidEmail' => 'DC Invalid Email',
					'DC_inactiveMeter' => 'DC Inactive Meter',
					'DC_insufficientTips' => 'DC Insufficient Tips',
					'DC_efficiencyCap' => 'DC Efficiency Cap',
					'DC_monthEfficientNeighbourZero' => 'DC Month Efficient Neighbour Zero',
					'DC_monthSimilarNeighbourZero' => 'DC Month Similar Neighbour Zero'
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
					'sfmcNoUpdateCounts'
				];
			 
				usort($data, function ($a, $b) {
					// Cast PCPIRunId and hersReportStartMonth to integers for comparison
					$pcpiRunIdA = (int)$a['PCPIRunId'];
					$pcpiRunIdB = (int)$b['PCPIRunId'];
					$hersReportIdA = (int)$a['hersReportId'];
					$hersReportIdB = (int)$b['hersReportId'];
					// Sort by PCPIRunId in descending order
					if ($pcpiRunIdA !== $pcpiRunIdB) {
						return $pcpiRunIdB - $pcpiRunIdA;
					}
					// If PCPIRunId values are equal, sort by hersReportStartMonth in descending order
					return $hersReportIdB - $hersReportIdA;
				});
			 
				function createHtmlTable($data, $headings_mapping, $numeric_fields)
				{
					// Initialize totals array
					$totals = array_fill_keys(array_keys($headings_mapping), 0);
				 
					// Calculate totals for numeric fields
					foreach ($data as $row) {
						foreach ($headings_mapping as $heading => $display_name) {
							if (in_array($heading, $numeric_fields)) {
								$totals[$heading] += $row[$heading];
							}
						}
					}
				 
					$html = '<div class="table-responsive">';
					$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'exclusionsRepMasterData.csv\', \'masterDataTable\')">Download CSV</button>';
					$html .= '<table class="table table-bordered table-sticky-header" id="masterDataTable">';
					$html .= '<thead class="thead-light">';
				 
					// Headings row
					$html .= '<tr>';
					foreach ($headings_mapping as $heading => $display_name) {
						if ($heading === 'cohortName') {
							$html .= '<th style="width: 300px;">' . htmlspecialchars($display_name) . '</th>';
						} else {
							$html .= '<th>' . htmlspecialchars($display_name) . '</th>';
						}
					}
					$html .= '</tr>';
				 
					// Totals row
					$html .= '<tr>';
					$first_column = True;
					foreach ($headings_mapping as $heading => $display_name) {
						if ($first_column){
							$html .= '<th>Total:</th>';
							$first_column = false;
						}elseif (in_array($heading, $numeric_fields)) {
							$html .= '<th>' . number_format($totals[$heading]) . '</th>';
						} else {
							$html .= '<th></th>';
						}
					}
					$html .= '</tr>';
				 
					$html .= '</thead>';
					$html .= '<tbody>';
				 
					// Data rows
					foreach ($data as $rowIndex => $row) {
						$html .= '<tr>';
						foreach ($headings_mapping as $heading => $display_name) {
							if (in_array($heading, $numeric_fields)) {
								if ($heading == 'sfmcBouncedCounts') { //DC_exclusionCounts,PC_exclusionCounts,PI_exclusionsCount,sfmcBouncedCounts //|| $heading == 'PC_exclusionCounts' || $heading == 'DC_exclusionCounts' 
									$modalId = $heading . $rowIndex . (int)$row['hersReportId'];
									//print($modalId);
									$html .= '<td><a href="#" data-toggle="modal" data-target="#' . $modalId . '" onclick="loadBounceDetails(\'' . htmlspecialchars($row['whiteLabelId']) . '\', \'' . htmlspecialchars($row['hersReportId']) . '\', \'0\', \''. htmlspecialchars($row[$heading]) . '\', \'' . htmlspecialchars($modalId) . '\',\''.htmlspecialchars($heading).'\')"><b>' . number_format($row[$heading]) .'</b> ('.round((($row[$heading]/$row['sfmcDeliveredCounts'])*100),2).'%)'.'</a></td>';
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
								} else {
									$html .= '<td>' . number_format($row[$heading]) . '</td>';
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
						<a href="https://franklinenergy.sharepoint.com/:x:/r/sites/planet/_layouts/15/doc2.aspx?action=default&file=Post_Comparables_Post_Imaging_system_mapping_v3.xlsx&mobileredirect=true&sourcedoc=%7BEFDD84B0-D43D-4417-8707-81A369FCC491%7D" target="_blank" class="btn btn-success btn-sm">Report Mapping</a>
					</div>
				</div>
				<form method="post" id="secondForm">
					<div class="form-row">
						<div class="form-group col-md-3">
							<label for="pcpiRunId">From:</label>
							<select id="pcpiRunId" name="pcpiRunId" class="form-control" required>
								<option value="All">All</option>
								<?php foreach ($pcpiRunIdOptions as $value => $display): ?>
									<option value="<?= $value ?>" <?= isset($_POST['pcpiRunId']) && $_POST['pcpiRunId'] == $value ? 'selected' : '' ?>><?= $display ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group col-md-3">
							<label for="pcpiRunIdT">To:</label>
							<select id="pcpiRunIdT" name="pcpiRunIdT" class="form-control" required>
								<option value="All">All</option>
								<?php foreach ($pcpiRunIdOptions as $value => $display): ?>
									<option value="<?= $value ?>" <?= isset($_POST['pcpiRunIdT']) && $_POST['pcpiRunIdT'] == $value ? 'selected' : '' ?>><?= $display ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group col-md-3">
							<label for="stateParam">State:</label>
							<select id="stateParam" name="stateParam" class="form-control">
								<option value="All">All</option>
								<?php foreach ($stateParamOptions as $state): ?>
									<option value="<?= $state ?>" <?= isset($_POST['stateParam']) && $_POST['stateParam'] == $state ? 'selected' : '' ?>><?= $state ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group col-md-3">
							<label for="cohortId">Cohort:</label>
							<select id="cohortId" name="cohortId" class="form-control">
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
						<a class="nav-link active" id="masterData-tab" data-toggle="tab" href="#masterData">Master Data</a>
					</li>';}?>
					<!---<?php if(in_array(3,$rightsArray)) {echo '<li class="nav-item">
						<a class="nav-link" id="summaryData-tab" data-toggle="tab" href="#summaryData">Summary Data</a>
					</li>';}?>-->
				</ul>
				<?php 
					if(in_array(2,$rightsArray)) {
						echo '<div class="tab-pane fade show active" id="masterData">
							<!-- Master Data rendering code here -->
							<br/> 
							<div class="tab-content"> ';
								if(in_array(2,$rightsArray)) {
									echo createHtmlTable($data, $headings_mapping, $numeric_fields);  
								}
								else{
									echo "0 results";
								}
							echo ' </div> '; 
						echo ' </div> '; 
					}
					/*if(in_array(3,$rightsArray)) {
						echo '<div class="tab-pane fade" id="summaryData">  
								  <!-- Summary Data rendering code here -->  		  
								  <div class="tab-content"> ';
									if(in_array(3,$rightsArray)) {
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
			function exportTableToCSV(filename, tableId) {
				var csv = [];
				var rows = document.querySelectorAll(`#${tableId} tr`);
				var headers = [];
			 
				// Loop through the header row to build the headers
				var headerRow = rows[0].querySelectorAll("th");
				for (var i = 0; i < headerRow.length; i++) {
					console.log(headerRow[i].innerText);
					var headerText = headerRow[i].innerText.trim();
					headers.push(headerText);
					// Check if any row contains a percentage for this column
					for (var j = 1; j < rows.length; j++) {
						console.log(rows[j].querySelectorAll("td")[i].innerText);
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
						console.log(cols[j].innerText);
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