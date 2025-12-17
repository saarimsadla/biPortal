<?php

/*session_start();
header('Content-Type: application/json'); // Ensure JSON response
//echo "Executing User: " . shell_exec('whoami');
 
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}
*/

//echo("hello world". "<br>");


// Check if parameters are set before accessing them
$envName = isset($_GET['envName']) ? $_GET['envName'] : null;
$database_name = isset($_GET['dbName']) ? $_GET['dbName'] : null;
$collection_name = isset($_GET['colName']) ? $_GET['colName'] : null;
$cmdToRun = isset($_GET['fnc']) ? $_GET['fnc'] : null;
$qry = isset($_GET['qry']) ? $_GET['qry'] : null;

// Echo them if they are set
/*
if ($envName !== null) {
    echo "Environment Name: " . htmlspecialchars($envName) . "<br>";
}
if ($database_name !== null) {
    echo "Database Name: " . htmlspecialchars($database_name) . "<br>";
}
if ($collection_name !== null) {
    echo "Collection Name: " . htmlspecialchars($collection_name) . "<br>";
}
if ($cmdToRun !== null) {
    echo "Command Name: " . htmlspecialchars($cmdToRun) . "<br>";
}
if ($qry !== null) {
    echo "Query: " . htmlspecialchars($qry) . "<br><br><br>";
}
*/
// Fetch initial data to populate select options

$initial_query_json = json_encode($qry);


$initial_command = "python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query_V2.py '$envName' '$database_name' '$collection_name' '$cmdToRun' '$qry'";
$initial_output = shell_exec($initial_command);
//print_r($initial_command);
$initial_data = json_decode($initial_output, true);
if (!$initial_data) {
	die('{"Error": "Customers data not found."}');
}
$data=json_encode($initial_data);

header('Content-Type: application/json');
print_r($data);

// Ensure this is a POST request
/*if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read raw JSON input
	set_time_limit(600); // 600 seconds = 10 minutes

    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);
 
    // Debugging: Log raw data to see if it's received correctly
    //file_put_contents("debug.log", "Raw POST data: " . $rawData . "\n", FILE_APPEND);
 
    // Validate parameters
    if (!isset($data['frmRepId'], $data['toRepId'], $data['sndT'])) {
        echo json_encode(["error" => "Missing required parameters"]);
        exit();
    }
 
    // Extract and sanitize values
    $frRpId = escapeshellarg($data['frmRepId']);
    $toRpId = escapeshellarg($data['toRepId']);
    $sndTyp = escapeshellarg($data['sndT']);
	$wlId = escapeshellarg($data['wtLbl']);
	$us = escapeshellarg($data['usrN']);
	$rs = escapeshellarg($data['rsn']);
	$scrptNmePy = $data['scrptNm'];
 
    // Debugging: Log extracted values
   // file_put_contents("debug.log", "Received: frmRepId=$frRpId, toRepId=$toRpId, sndT=$sndTyp, scriptNME=$scrptNmePy\n", FILE_APPEND);
 
    // Set the working directory and Python script path
	$scrptis = "/../pythonScripts/" . $scrptNmePy . ".py";
    $pythonScript = __DIR__ . $scrptis;
	//file_put_contents("debug.log", , FILE_APPEND);
 
    // Construct and execute the Python command
    $command = "python3.9 $pythonScript $frRpId $toRpId $sndTyp $wlId $us $rs 2>&1";
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
 
    // Debugging: Log Python output
   // file_put_contents("debug.log", "Python output: " . implode("\n", $output) . "\n", FILE_APPEND);
 
    if ($return_var !== 0) {
        echo json_encode(["error" => "Python script execution failed", "details" => implode("\n", $output)]);
        exit();
    }
 
    // Return JSON response
    echo json_encode(["success" => true, "output" => implode("\n", $output)]);//"Received: frmRepId=$frRpId, toRepId=$toRpId, sndT=$sndTyp, scriptNME=$scrptNmePy, pythonscript: $pythonScript\n";//
    exit();
}*/
 
// If not a POST request, return an error
//echo json_encode(["error" => "Invalid request method"]);
//exit();
?>