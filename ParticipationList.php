<!-- ParticipationList.php -->
<?php
session_start();
if (!$_SESSION['authenticated']) {
	header("Location: index.html");
	exit();
}
$showDiv = false;
$defaultWhiteLabel =  isset($_SESSION['defWhitelabel']) ? $_SESSION['defWhitelabel'] : 0;
$whiteLabel = $defaultWhiteLabel;
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<title>Participation List</title>
<?php include './configs/headerScripts.php';  ?>
<style>
    .loader {
        border: 10px solid #f3f3f3;
        border-radius: 75%;
        border-top: 10px solid #3498db;
        width: 50px;
        height: 50px;
        -webkit-animation: spin 2s linear infinite;
        animation: spin 2s linear infinite;
    }
 
    @-webkit-keyframes spin {
        0% {
            -webkit-transform: rotate(0deg);
        }
        100% {
            -webkit-transform: rotate(360deg);
        }
    }
 
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
 
    /* Add styles for success message */
    #success-message {
        color: green;
        font-weight: bold;
        margin-top: 10px;
    }
</style>
</head>
<body>
<div class="container-fluid">
<!--navbar here -->
<?php
    $pgName = "pcpiFrontEnd.php";
    $wl = isset($_SESSION['whiteLabels']) ? $_SESSION['whiteLabels'] : null;
    $rt = isset($_SESSION['rghts']) ? $_SESSION['rghts'] : null;
    $usName = isset($_SESSION['uname']) ? $_SESSION['uname'] : null;
    $rightsArray = explode(",", $rt);
    $whtLblArray = explode(",", $wl);
 
    include './configs/navbarSetup.php';
 
    If ($defaultWhiteLabel == 54) {
        if (in_array(6, $rightsArray)) {
            echo '<div class="d-flex justify-content-center align-items-center flex-grow-1" style="padding-top: 0.5%; padding-left: 0.5%;">
<!-- spin loader -->
<div id="loader-container"></div>
<!-- success message -->
<div id="success-message"></div>
</div>
<div class="d-flex justify-content-center align-items-center flex-grow-1" style="padding-top: 0.5%; padding-left: 0.5%;">
<!-- file Generate text -->
<div class="text-right mt-2">
<button type="submit" class="btn btn-primary" onclick="generateReqFile()">Generate Report</button>
<button type="submit" class="btn btn-primary" onclick="downloadFile(\'/hursPortal/configs/dom_participation_d.txt\', \'dom_participation_d.txt\');">Download Report</button>
</div>
</div>';
        }
    }
    include './configs/bottomScripts.php';  ?>
</div>
 
<!-- Add this element where you want to display the success message -->
<script>
    const loaderContainer = document.getElementById('loader-container');
    const successMessageElement = document.getElementById('success-message');
 
    function showLoader() {
        loaderContainer.innerHTML = '';
        loaderContainer.classList.add('loader');
        // Clear the success message when showing the loader
        successMessageElement.textContent = '';
    }
 
    function hideLoader() {
        loaderContainer.classList.remove('loader');
    }
 
    function generateReqFile() {
        showLoader();
        const phpScriptUrl = '/hursPortal/configs/generate_csv.php';
 
        fetch(phpScriptUrl)
            .then(() => {
                hideLoader();
                displaySuccessMessage('File generated successfully!');
            })
            .catch(error => {
                console.error('Error:', error);
                hideLoader();
            });
    }
 
    function displaySuccessMessage(message) {
        if (successMessageElement) {
            successMessageElement.textContent = message;
        }
    }
 
    function downloadFile(fileUrl, fileName) {
    showLoader();
    const a = document.createElement('a');
    a.href = fileUrl;
    a.download = fileName || 'downloaded_file'; // Default filename if not provided
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
 
    // Call the PHP script after the file is downloaded
    fetch(fileUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.blob();
        })
        .then(() => {
            const phpScriptUrl = '/hursPortal/configs/remFle.php';
            return fetch(phpScriptUrl);
        })
        .then(() => {
            hideLoader();
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoader();
        });
	}
	
</script>
</body>
</html>