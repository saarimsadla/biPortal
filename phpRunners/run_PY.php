<?php
session_start();
header('Content-Type: application/json'); // Ensure JSON response
//echo "Executing User: " . shell_exec('whoami');
 
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}
 
// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
 
// If not a POST request, return an error
echo json_encode(["error" => "Invalid request method"]);
exit();
?>