<?php
$wlId = $_GET['wlId'];
$bdrEventId = $_GET['bdrEventId'];
$transactionSr = $_GET['transactionSr'];
$actVal = $_GET['sfmcBouncedCounts'];
$fld = $_GET['fldName'];

//print_r(" " .$wlId);
//print_r(" " .$bdrEventId);
//print_r(" " .$transactionSr);
//print_r(" " .$actVal);
//print_r(" " .$fld);

chdir(__DIR__);
$operatingDir = "";

// --- OS detection ---
$osName = PHP_OS_FAMILY;
if ($osName === "Windows") {
	$operatingDir = realpath(__DIR__); 
} else {
	$operatingDir = realpath(__DIR__);
}

$tr = "";
if ($transactionSr == 1){
	$tr = "Pre-Event";
}
if ($transactionSr == 2){
	$tr = "Post-Event";
}

if ($transactionSr == 0){
	$tr = $bdrEventId;
}
//DC_exclusionCounts,PC_exclusionCounts,PI_exclusionsCount,sfmcBouncedCounts
if ($fld == "sfmcBouncedCounts"){
	$html ="";
	$database_name = "salesForce";
	$collection_name = "journey_details.bounce_details";
		
	$initial_query = [
		'wlId' => (int)$wlId,
		'eventId' => (int)$bdrEventId,
		'transactionSr' => (int)$transactionSr
	];

	$initial_query_json = json_encode($initial_query, JSON_UNESCAPED_SLASHES);
	
		
	$ag_array = [
		[
			'$match' => [
				'JourneyActivityObjectID' => 'JourneyActivityObjectID'
			]
		],
		[
			'$group' => [
				'_id' => [
					'BounceCategory' => '$BounceCategory',
					'BounceSubcategory' => '$BounceSubcategory'
				],
				'uniqueCustIds' => [ '$addToSet' => '$custid' ]
			]
		],
		[
			'$project' => [
				'_id' => 0,
				'bounceCategory' => '$_id.BounceCategory',
				'bounceSubCategory' => '$_id.BounceSubcategory',
				'count' => [ '$size' => '$uniqueCustIds' ]
			]
		],
		[
			'$sort' => [
				'bounceCategory' => 1,
				'bounceSubCategory' => 1
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
		escapeshellarg("a"),
		'"'.str_replace('"', '""', $ag_json).'"'
	);

	$initial_output = shell_exec("$cmd 2>&1");
	
	//print_r($cmd);
	
	$bounceRates = json_decode($initial_output, true);
	 
	// Prepare HTML for table
	$html = '<p>** Please note, This is dummy data for viewing purposes</p>';
	$html .= '<div class="table-responsive">';
	$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'bounceDtls_'.$bdrEventId.'_'.$tr.'.csv\', \'bounceDetailsTable\')">Download CSV</button>';
	$html .= '<table class="table table-bordered" id="bounceDetailsTable">';
	$html .= '<thead class="thead-light">';
	$html .= '<tr>';
	$html .= '<th>Report Id</th>';
	$html .= '<th>Report Type</th>';
	$html .= '<th>Bounce Category</th>';
	$html .= '<th>Bounce Sub Category</th>';
	$html .= '<th>Count</th>';
	$html .= '<th>Total Bounce</th>';
	$html .= '<th>Percentage</th>';
	$html .= '</tr>';
	$html .= '</thead>';
	$html .= '<tbody>';

	$bncttl = 0;
	$prcTtl = 0;
	 
	if (!empty($bounceRates)) {
		foreach ($bounceRates as $bounceRate) {
			if (isset($bounceRate['error'])) {
				$html .= '<tr><td colspan="7">Error python script: ' . htmlspecialchars($bounceRate['error']) . '</td></tr>';
			} else {
				if ($bounceRate['bdrEventId'] == $bdrEventId && $bounceRate['wlId'] == $wlId && $bounceRate['transactionSr'] == $transactionSr) {
					$html .= '<tr>';
					$html .= '<td>' . htmlspecialchars($bounceRate['bdrEventId']) . '</td>';
					$html .= '<td>' . htmlspecialchars($bounceRate['eventType']) . '</td>';
					$html .= '<td>' . htmlspecialchars($bounceRate['bounceCategory']) . '</td>';
					$html .= '<td>' . htmlspecialchars($bounceRate['bounceSubCategory']) . '</td>';
					$html .= '<td>' . number_format($bounceRate['count']) . '</td>';
					$html .= '<td>' . number_format($actVal) . '</td>';
					$html .= '<td>' . number_format(($bounceRate['count'] / $actVal) * 100, 2) . '%</td>';
					$html .= '</tr>';
					$bncttl = $bncttl + $bounceRate['count'];
					$prcTtl = $prcTtl + (($bounceRate['count'] / $actVal) * 100);
				}
			}
		}
		$html .= '<tr>';
		$html .= '<td></td>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">Grand Total</td>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">'.number_format($bncttl).'</td>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">'.number_format($prcTtl).'%</td>';
		$html .= '</tr>';
		$bncttl = 0;
		$prcTtl = 0;	
		if (empty($html)) {
			$html .= '<tr><td colspan="7">No bounce rates available for this combination.</td></tr>';
		}
	} else {
		$html .= '<tr><td colspan="7">No bounce rate data available.</td></tr>';
	}
	 
	$html .= '</tbody>';
	$html .= '</table>';
	$html .= '</div>';
	 
	echo $html;
}

if ($fld == "PI_exclusionsCount"){
	$html ="";
	$database_name = "bdr";
	$collection_name = "imaging.";
	
	$initial_query = [
		"transactionSr" => (int)$transactionSr
	];

	$initial_query_json = json_encode($initial_query, JSON_UNESCAPED_SLASHES);
	//(int)$bdrEventId
	$ag_array = [
		[
			'$match' => [
				'wlId' => (int)$wlId,
				'eventId' => (int)$bdrEventId,
				'eventType' => $tr,
				'productType' => 'BDR',
				'exclusions' => [ '$exists' => true ]
			]
		],
		[
			'$group' => [
				'_id' => [
					'wlId' => '$wlId',
					'eventId' => '$eventId',
					'eventType' => '$eventType',
					'exclusionReason' => '$exclusions.message'
				],
				'uniqueCustIds' => [ '$addToSet' => '$custId' ]
			]
		],
		[
			'$project' => [
				'_id' => 0,
				'wlId' => '$_id.wlId',
				'eventId' => '$_id.eventId',
				'eventType' => '$_id.eventType',
				'exclusionReason' => '$_id.exclusionReason',
				'count' => [ '$size' => '$uniqueCustIds' ]
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
	
	$bounceRates = json_decode($initial_output, true);
	 
	//print("bounce: ");
	//print_r($bounceRates);
	 
	// Prepare HTML for table
	$html = '<p>** Please note, This is dummy data for viewing purposes</p>';
	$html .= '<div class="table-responsive">';
	$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'PIExclusionsDtls_'.$bdrEventId.'_'.$tr.'.csv\', \'PIExclusionsTable\')">Download CSV</button>';
	$html .= '<table class="table table-bordered" id="PIExclusionsTable">';
	$html .= '<thead class="thead-light">';
	$html .= '<tr>';
	$html .= '<th>Bdr Event</th>';
	$html .= '<th>Bdr Event Type</th>';
	$html .= '<th>Exclusion Reason</th>';
	$html .= '<th>Count</th>';
	$html .= '<th>Total Exclusions</th>';
	$html .= '<th>Percentage</th>';
	$html .= '</tr>';
	$html .= '</thead>';
	$html .= '<tbody>';
	 
	$bncttl = 0;
	$prcTtl = 0;
	 
	if (!empty($bounceRates)) {
		foreach ($bounceRates as $bounceRate) {
			if (isset($bounceRate['error'])) {
				$html .= '<tr><td colspan="7">Error python script: ' . htmlspecialchars($bounceRate['error']) . '</td></tr>';
			} else {
				if ($bounceRate['eventId'] == $bdrEventId && $bounceRate['wlId'] == (int)$wlId && $bounceRate['transactionSr'] == $transactionSr) {
					foreach ($bounceRate['exclusionReason'] as $reason) {
						$html .= '<tr>';
						$html .= '<td>' . htmlspecialchars($bounceRate['eventId']) . '</td>';
						$html .= '<td>' . htmlspecialchars($bounceRate['eventType']) . '</td>';
						$html .= '<td>' . htmlspecialchars($reason) . '</td>';
						$html .= '<td>' . number_format($bounceRate['count']) . '</td>';
						$html .= '<td>' . number_format($actVal) . '</td>';
						$html .= '<td>' . number_format(($bounceRate['count'] / $actVal) * 100, 2) . '%</td>';
						$html .= '</tr>';
	 
						$bncttl += $bounceRate['count'];
						$prcTtl += (($bounceRate['count'] / $actVal) * 100);
					}
				}
			}
		}
	 
		$html .= '<tr>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">Grand Total</td>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">' . number_format($bncttl) . '</td>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">' . number_format($prcTtl) . '%</td>';
		$html .= '</tr>';
		$bncttl = 0;
		$prcTtl = 0;
	 
		if (empty($html)) {
			$html .= '<tr><td colspan="7">No PI exclusions available for this combination.</td></tr>';
		}
	} else {
		$html .= '<tr><td colspan="7">No PI exclusions data available.</td></tr>';
	}
	 
	$html .= '</tbody>';
	$html .= '</table>';
	$html .= '</div>';
	 
	echo $html;
}

if ($fld == "DC_exclusionCounts_hers"){
	$html ="";
	$database_name = "hers";
	$collection_name = "data_service.";

	$initial_query = [
		"transactionSr" => (int)$transactionSr
	];

	$initial_query_json = json_encode($initial_query, JSON_UNESCAPED_SLASHES);
	
	// (int)$bdrEventId
	$ag_array = [
		  [
			'$match'=> [
			  'wlId'=> (int)$wlId,
			  'exclusions'=> [ '$exists'=> true ],
			  'reportId'=> 2708
			]
		  ],
		  [
			'$unwind'=> '$exclusions'
		  ],
		  [
			'$group'=> [
			  '_id'=> '$exclusions.reason',
			  'count'=> [ '$sum'=> 1 ]
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
	
	//print_r($cmd);
	
	//print_r ("");
	
	//print_r ("ooo");
	//print_r ($initial_output);
	
	$bounceRates = json_decode($initial_output, true);
	
	 
	//print("bounce: ");
	//print_r($bounceRates);
	 
	// Prepare HTML for table
	
	if (!empty($bounceRates)){
		$html = '<p>** Please note, customers can have more than one exclusion reason(s) as such overlapping is expected in counts below</p>';
		$html .= '<p>** Please note, This is dummy data for viewing purposes</p>';
		$html .= '<div class="table-responsive">';
		$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'DCExclusionsDtls_'.$bdrEventId.'_'.$tr.'.csv\', \'PIExclusionsTable\')">Download CSV</button>';
		$html .= '<table class="table table-bordered" id="PIExclusionsTable">';
		$html .= '<thead class="thead-light">';
		$html .= '<tr>';
		$html .= '<th>Report Id</th>';
		$html .= '<th>Exclusion Reason</th>';
		$html .= '<th>Count</th>';
		$html .= '<th>Total Exclusions</th>';
		$html .= '<th>Percentage</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		$bncttl = 0;
		$prcTtl = 0;

		foreach ($bounceRates as $item) {
			$html .= '<tr>';
			$html .= '<td>' . $bdrEventId . '</td>';
			$html .= '<td>' . htmlspecialchars($item['_id']) . '</td>';
			$html .= '<td>' . htmlspecialchars($item['count']) . '</td>';
			$html .= '<td>' . number_format($actVal) . '</td>';
			$html .= '<td>' . number_format(($item['count'] / $actVal) * 100, 2) . '%</td>';
			$html .= '</tr>';

			$bncttl += $item['count'];
			$prcTtl += (($item['count'] / $actVal) * 100);
		}

		$html .= '<tr>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">Grand Total</td>';
		$html .= '<td style="font-weight: bold;">' . number_format($bncttl) . '</td>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">' . number_format($prcTtl, 2) . '%</td>';
		$html .= '</tr>';

		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';

		if (empty($html)) {
			$html .= '<tr><td colspan="7">No DC exclusions available for this combination.</td></tr>';
		} 


		 
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';
		
		echo $html;
	}
	else {
		$html .= '<tr><td colspan="7">No DC exclusions data available.</td></tr>';
		echo $html;
	}
}

if ($fld == "PI_exclusionCounts_hers"){
	$html ="";
	$database_name = "hers";
	$collection_name = "imaging.";

	$initial_query = [
		"transactionSr" => (int)$transactionSr
	];

	$initial_query_json = json_encode($initial_query, JSON_UNESCAPED_SLASHES);
	//(int)$bdrEventId
	$ag_array = [
		  [
			'$match'=> [
			  'wlId'=> (int)$wlId,
			  'exclusions'=> [ '$exists'=> true ],
			  'reportId'=> 2669
			]
		  ],
		  [
			'$unwind'=> '$exclusions'
		  ],
		  [
			'$group'=> [
			  '_id'=> '$exclusions.reason',
			  'count'=> [ '$sum'=> 1 ]
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
	
	//print_r($cmd);
	
	$bounceRates = json_decode($initial_output, true);
	 
	//print("bounce: ");
	//print_r($bounceRates);
	 
	// Prepare HTML for table
	
	if ($bounceRates){
		$html = '<p>** Please note, customers can have more than one exclusion reason(s) as such overlapping is expected in counts below</p>';
		$html .= '<p>** Please note, this is dummy data for viewing purposes</p>';
		$html .= '<div class="table-responsive">';
		$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'DCExclusionsDtls_'.$bdrEventId.'_'.$tr.'.csv\', \'PIExclusionsTable\')">Download CSV</button>';
		$html .= '<table class="table table-bordered" id="PIExclusionsTable">';
		$html .= '<thead class="thead-light">';
		$html .= '<tr>';
		$html .= '<th>Report Id</th>';
		$html .= '<th>Exclusion Reason</th>';
		$html .= '<th>Count</th>';
		$html .= '<th>Total Exclusions</th>';
		$html .= '<th>Percentage</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		$bncttl = 0;
		$prcTtl = 0;

		foreach ($bounceRates as $item) {
			$html .= '<tr>';
			$html .= '<td>' . $bdrEventId . '</td>';
			$html .= '<td>' . htmlspecialchars($item['_id']) . '</td>';
			$html .= '<td>' . htmlspecialchars($item['count']) . '</td>';
			$html .= '<td>' . number_format($actVal) . '</td>';
			$html .= '<td>' . number_format(($item['count'] / $actVal) * 100, 2) . '%</td>';
			$html .= '</tr>';

			$bncttl += $item['count'];
			$prcTtl += (($item['count'] / $actVal) * 100);
		}

		$html .= '<tr>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">Grand Total</td>';
		$html .= '<td style="font-weight: bold;">' . number_format($bncttl) . '</td>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">' . number_format($prcTtl, 2) . '%</td>';
		$html .= '</tr>';

		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';

		if (empty($html)) {
			$html .= '<tr><td colspan="7">No DC exclusions available for this combination.</td></tr>';
		} 


		 
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';
		
		echo $html;
	}
	else {
		$html .= '<tr><td colspan="7">No PI exclusions data available.</td></tr>';
		echo $html;
	}
}

if ($fld == "PC_exclusionCounts_hers"){
	
	$html ="";
	$database_name = "hers";
	$collection_name = "data_service.";

	$initial_query = [
		"transactionSr" => (int)$transactionSr
	];

	$initial_query_json = json_encode($initial_query, JSON_UNESCAPED_SLASHES);
	
	// (int)$bdrEventId
	$ag_array = [
		  [
			'$match'=> [
			  'wlId'=> (int)$wlId,
			  'exclusions'=> [ '$exists'=> true ],
			  'reportId'=> 2708
			]
		  ],
		  [
			'$unwind'=> '$exclusions'
		  ],
		  [
			'$group'=> [
			  '_id'=> '$exclusions.reason',
			  'count'=> [ '$sum'=> 1 ]
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
	
	//print_r($cmd);
	
	//print_r ("");
	
	//print_r ("ooo");
	//print_r ($initial_output);
	
	$bounceRates = json_decode($initial_output, true);
	 
	//print("bounce: ");
	//print_r($bounceRates);
	 
	// Prepare HTML for table
	
	if ($bounceRates){
		$html = '<p>** Please note, customers can have more than one exclusion reason(s) as such doubling is expected in counts below</p>';
		$html .= '<p>** Please note, This is dummy data for viewing purposes</p>';
		$html .= '<div class="table-responsive">';
		$html .= '<button class="btn btn-primary mb-3" onclick="exportTableToCSV(\'DCExclusionsDtls_'.$bdrEventId.'_'.$tr.'.csv\', \'PIExclusionsTable\')">Download CSV</button>';
		$html .= '<table class="table table-bordered" id="PIExclusionsTable">';
		$html .= '<thead class="thead-light">';
		$html .= '<tr>';
		$html .= '<th>Report Id</th>';
		$html .= '<th>Exclusion Reason</th>';
		$html .= '<th>Count</th>';
		$html .= '<th>Total Exclusions</th>';
		$html .= '<th>Percentage</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		$bncttl = 0;
		$prcTtl = 0;

		foreach ($bounceRates as $item) {
			$html .= '<tr>';
			$html .= '<td>' . $bdrEventId . '</td>';
			$html .= '<td>' . htmlspecialchars($item['_id']) . '</td>';
			$html .= '<td>' . htmlspecialchars($item['count']) . '</td>';
			$html .= '<td>' . number_format($actVal) . '</td>';
			$html .= '<td>' . number_format(($item['count'] / $actVal) * 100, 2) . '%</td>';
			$html .= '</tr>';

			$bncttl += $item['count'];
			$prcTtl += (($item['count'] / $actVal) * 100);
		}

		$html .= '<tr>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">Grand Total</td>';
		$html .= '<td style="font-weight: bold;">' . number_format($bncttl) . '</td>';
		$html .= '<td></td>';
		$html .= '<td style="font-weight: bold;">' . number_format($prcTtl, 2) . '%</td>';
		$html .= '</tr>';

		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';

		if (empty($html)) {
			$html .= '<tr><td colspan="7">No DC exclusions available for this combination.</td></tr>';
		} 


		 
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';
		
		echo $html;
	}
	else {
		$html = '<tr><td colspan="7">No PC exclusions available for this combination.</td></tr>';
		echo $html;
	}
	
}

?>
 
<script>
function exportTableToCSV(filename, tableId) {
    var csv = [];
    var rows = document.querySelectorAll('#' + tableId + ' tr');
 
    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        var cols = row.querySelectorAll('td, th');
        var csvRow = [];
        for (var j = 0; j < cols.length; j++) {
            csvRow.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        csv.push(csvRow.join(','));
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