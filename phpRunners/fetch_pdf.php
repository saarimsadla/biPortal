<?php
session_start();
if (!$_SESSION['authenticated']) {
	header("Location: index.html");
	exit();
}


if (!isset($_GET['url'])) {
    die('Error: URL parameter is missing.');
}

$url = $_GET['url'];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$pdfData = curl_exec($ch);
curl_close($ch);

if ($pdfData === false) {
    die('Error fetching the PDF file.');
}

header('Content-Type: application/pdf');
echo $pdfData;
?>