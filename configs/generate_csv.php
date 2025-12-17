<?php
/*$osName = PHP_OS;

echo "os is :$osName";

$operatingDir = "";

if (strtoupper(substr($osName,0,3)) === "WIN"){
	shell_exec('cd ..');
	$operatingDir = '\configs\ ';
}elseif (strtoupper($osName) === "LINUX"){
	$operatingDir = '../goScripts/authConfirm ';
}
elseif (strtoupper($osName) === "DARWIN"){
	$operatingDir = '../goScripts/authConfirm ';
}
*/
// $result = shell_exec('dir');
 
// echo $result;
// Use shell_exec to call the Go program with arguments
$result = shell_exec('./dom_participation_extract.sh');

?>