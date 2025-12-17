<?php
// Get the raw POST data
$postData = file_get_contents('php://input');
$request = json_decode($postData, true);

$dataArr = isset($request['dataArr']) ? $request['dataArr'] : null;
$modalId = isset($request['modalId']) ? $request['modalId'] : null;
$clnt = isset($request['clnt']) ? $request['clnt'] : null;
$hersMnth = isset($request['herMonth']) ? $request['herMonth'] : null;

//$dataArr = json_decode($datArr, true);

//print_r($dataArr);

if (json_last_error() !== JSON_ERROR_NONE) {
	echo "Error decoding JSON: " . json_last_error_msg();
	exit;
}

if (strpos($modalId, 'status') !== false) {
	$html = '<div class="table-responsive">';
	$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'' . htmlspecialchars($modalId) . '.csv\', \'modalTable\')">Download CSV</button>';
	$html .= '<table class="table table-bordered" id="modalTable">';
	$html .= '<thead class="thead-light">';
	$html .= '<tr>';
	$html .= '<th>Client</th>';
	$html .= '<th>Hers Month</th>';
	$html .= '<th>Report Id</th>';
	$html .= '<th>Hers Date</th>';
		$html .= '<th>Hers Runtime</th>';
		$html .= '<th>Hers Run Type</th>';
	$html .= '<th>Report Type</th>';
	$html .= '<th>Fuel Type</th>';
	$html .= '<th>Cohort Id</th>';
	$html .= '<th>Cohort Name</th>';
	$html .= '<th>Status</th>';
	$html .= '<th>Email Exclusions Count</th>';
	$html .= '<th>Email Count</th>';
	$html .= '<th>Paper Exclusions Count</th>';
	$html .= '<th>Paper Count</th>';
	$html .= '<th>Exclusions Total</th>';
	$html .= '<th>Total</th>';
	$html .= '</tr>';
	$html .= '</thead>';
	$html .= '<tbody>';

	if (!empty($dataArr)) {
		
		usort($dataArr, function ($a, $b) {
			// Cast hersReportId, cohortId, and hersRunTypeSorter to integers for comparison
			$reportIdA = (int)$a['id'];
			$reportIdB = (int)$b['id'];
			$cohortIdA = (int)$a['orig_runned_id'];
			$cohortIdB = (int)$b['orig_runned_id'];
			$runTypeSorterA = (int)$a['hersRunTypeSorter'];
			$runTypeSorterB = (int)$b['hersRunTypeSorter'];

			// Sort by cohortId in descending order
			if ($cohortIdA !== $cohortIdB) {
				return $cohortIdB - $cohortIdA;
			}

			// Sort by hersRunTypeSorter in ascending order
			if ($runTypeSorterA !== $runTypeSorterB) {
				return $runTypeSorterA - $runTypeSorterB;
			}

			// Sort by hersReportId in descending order
			return $reportIdB - $reportIdA;
		});
		
		$emlExcCts = 0;
		$emlCts = 0;
		$pprExcCts = 0;
		$pprCts = 0;
		$ttlExcCts = 0;
		$ttlCts = 0;
		foreach ($dataArr as $dta) {
			$emlExcCts = $emlExcCts + $dta['emailExclusionsCounts'];
			$emlCts = $emlCts + $dta['emailCounts'];
			$pprExcCts = $pprExcCts + $dta['paperExclusionsCounts'];
			$pprCts = $pprCts + $dta['paperCounts'];
			$ttlExcCts = $ttlExcCts + ($dta['emailExclusionsCounts'] + $dta['paperExclusionsCounts']);
			$ttlCts = $ttlCts + ($dta['paperCounts'] + $dta['emailCounts']);
			$stat = ($dta['sent'] == 1) ? 'Sent' : 'In Progress';
			$html .= '<tr>';
			$html .= '<td>' . htmlspecialchars($clnt) . '</td>';
			$html .= '<td>' . htmlspecialchars($hersMnth) . '</td>';
			$html .= '<td>' . $dta['id'] . '</td>';
			$html .= '<td>' . date('Y-m-d H:i:s', htmlspecialchars($dta['report_start_Date'])) . '</td>';
			$html .= '<td>' . date('Y-m-d H:i:s', htmlspecialchars($dta['rtme'])) . '</td>';
			$html .= '<td>' . htmlspecialchars($dta['hersRunType']) . '</td>';
			$html .= '<td>' . htmlspecialchars(ucwords($dta['report_type'])) . '</td>';
			$html .= '<td>' . htmlspecialchars($dta['fuel_type']) . '</td>';
			$html .= '<td>' . htmlspecialchars($dta['orig_runned_id']) . '</td>';
			$html .= '<td>' . htmlspecialchars($dta['cohort_name']) . '</td>';
			$html .= '<td>' . (($stat =='Sent') ? htmlspecialchars($stat): '<b>' . htmlspecialchars($stat)  . '</b>') .'</td>';
			$html .= '<td>' . number_format($dta['emailExclusionsCounts']) . '</td>';
			$html .= '<td>' . number_format($dta['emailCounts']) . '</td>';
			$html .= '<td>' . number_format($dta['paperExclusionsCounts']) . '</td>';
			$html .= '<td>' . number_format($dta['paperCounts']) . '</td>';
			$html .= '<td>' . number_format(($dta['paperExclusionsCounts'] + $dta['emailExclusionsCounts'])) . '</td>';
			$html .= '<td>' . number_format(($dta['paperCounts'] + $dta['emailCounts'])) . '</td>';
			
			$html .= '</tr>';
		}
		$html .= '<tr>';
		$html .= '<td><b>Grand Total</b></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td><b>' . number_format($emlExcCts) . '</b></td>';
		$html .= '<td><b>' . number_format($emlCts) . '</b></td>';
		$html .= '<td><b>' . number_format($pprExcCts) . '</b></td>';
		$html .= '<td><b>' . number_format($pprCts) . '</b></td>';
		$html .= '<td><b>' . number_format($ttlExcCts) . '</b></td>';
		$html .= '<td><b>' . number_format($ttlCts) . '</b></td>';
		
		$html .= '</tr>';
	} else {
		$html .= '<tr><td colspan="7">No data available.</td></tr>';
	}


	$html .= '</tbody>';
	$html .= '</table>';
	$html .= '</div>';

	echo $html;
}
else{
	echo 'No Modal id found';
}
?>
<script>
function exportTableToCSV(filename, tableId) {
    var csv = [];
    var rows = document.querySelectorAll('#' + tableId + ' tr');

    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        if (row) {
            var cols = row.querySelectorAll('td, th');
            var csvRow = [];
            for (var j = 0; j < cols.length; j++) {
                var col = cols[j];
                if (col && col.innerText !== undefined) {
                    csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
                } else {
                    csvRow.push('""'); // Add empty quotes if col or innerText is undefined
                }
            }
            csv.push(csvRow.join(','));
        }
    }

    var csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    var downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>
