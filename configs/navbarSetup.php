<?php
//this class prints the navbar for the webpage
//session_start();  // as session laways started on page navbar is called no need to call it here
//if(isset($_SESSION['id']))
//{
$selectedWhiteLabel = '';
$wl = isset($_SESSION['whiteLabels']) ? $_SESSION['whiteLabels'] : null;
$wlArr = isset($_SESSION['whiteLabelArr']) ? $_SESSION['whiteLabelArr'] : null;
$whtLblArray = [];
$whtLblArray = explode(",", $wl);
$switchToSelectedWL = isset($_POST['switchToSelectedWL']) ? $_POST['switchToSelectedWL'] : $defaultWhiteLabel;

function getWlData($wlAR,$wlValue, $valueToGet){
    $retVal = "";
	//print_r($wlAR);
    foreach ($wlAR as $item) {
        if ($item['wlId'] == $wlValue) {
            $retVal = $item[$valueToGet];
            break;
        }
    }
    return $retVal;
}
?>
    <script>
        //Get the button
        var mybutton = document.getElementById("myBtn");
        // When the user scrolls down 20px from the top of the document, show the button
        window.onscroll = function() {scrollFunction()};
        function scrollFunction() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                mybutton.style.display = "block";
            } else {
                mybutton.style.display = "none";
            }
        }
        // When the user clicks on the button, scroll to the top of the document
        function topFunction() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }


        // when the user clicks on the item from drop down list for swtich white Label
        function handleDropdownItemClick(selectedValue) {
            console.log('selectedValue of White Label: ' + selectedValue);
            // Update the displayed text
            //document.getElementById("selectedWhiteLabel").textContent = "White Label: " + selectedValue;
            // You can use AJAX to send a request to update the PHP session variable
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "dashboard.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // The response from the PHP file
                    var response = xhr.responseText;
                    //console.log("PHP session update response: " + response);
                    // Optionally, you can perform additional actions after updating the session variable
                }
            };

            // Send the data to the PHP file to update the session variable
            xhr.send("switchToSelectedWL=" + encodeURIComponent(selectedValue));

            // Reload the page
            location.reload();
        }
        // Programmatically trigger a click on the sidebarToggle button after the page has loaded
        window.onload = function() {
            var sidebarToggle = document.getElementById("sidebarToggle");
            if (sidebarToggle) {
                sidebarToggle.click();
            }

            // Programmatically trigger a click on the pagesDropdown button after the page has loaded
            var pagesDropdown = document.querySelector("a#pagesDropdown.nav-link");
            if (pagesDropdown) {
                pagesDropdown.click();
            }
        };


    </script>

    <nav class="navbar navbar-expand navbar-dark bg-dark static-top" id="navBar">
        <div class="d-flex align-items-center">
            <a href="dashboard.php" class="mr-2">
                <img src="images/favicon.ico" style="width: 30px; height: 30px;" alt="company logo">
            </a>
            <a class="navbar-brand" href="dashboard.php">BI Portal</a>
            <button class="btn btn-link btn-sm text-white order-1 order-sm-0" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="d-flex justify-content-center align-items-center flex-grow-1">
            <div class="centered-div">
                <ul class="navbar-nav ml-auto">
                    <!-- Switch White Label -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="dashboard.php" id="pagesDropdown2" role="button" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                            <!-- <i class="fas fa-fw fa-user-edit"></i> -->
                            <span id="selectedWhiteLabel"><?php
                                switch ($defaultWhiteLabel) {
                                    case 0:
                                        echo "Client: Please Select";
                                        break;
                                    default:
                                        //print_r("def ".$defaultWhiteLabel." ");
                                        //print_r($wlArr);

                                        echo "Client: " . getWlData($wlArr,$defaultWhiteLabel, 'clientName');
                                        break;
                                }

                                ?></span> <i class="fa fa-angle-down"></i>
                        </a>
                        <style>
                            /* Additional styling for list items (optional) */
                            ul {
                                list-style: none;
                                padding: 0;
                            }

                            li {
                                margin: 5px 0;
                            }

                            .dropdown-menu {
                                background-color: #1d2124;
                                border: none;
                                box-shadow: none;
                            }

                            .dropdown-item {
                                color: #9EA8B0;
                                cursor: pointer;
                            }

                            .dropdown-item:hover {
                                background-color: #34383d;
                            }
                        </style>

                        <div class="dropdown-menu" aria-labelledby="pagesDropdown2">
                            <?php
                            sort($wlArr);
                            foreach ($wlArr as $whtLbl) :  if ($whtLbl['wlId']!== 44){?>
                                <a class="dropdown-item" href="#" onclick="handleDropdownItemClick('<?php echo $whtLbl['wlId']; ?>')">
                                    <?php
                                    echo "Client: ".$whtLbl['clientName'];
                                    ?>
                                </a>
                            <?php }
                            endforeach; ?>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown no-arrow">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img style="width: 30px; height: 30px;" src="<?php echo isset($_SESSION['usrImage']) ? $_SESSION['usrImage'] : 'images/lgn.png'; ?>" alt="User Avatar"> <?php echo $_SESSION['uname'] ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                    <?php if(in_array(5,$rightsArray)) {echo '<a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">Logout</a>';}?>
                    <?php if(in_array(4,$rightsArray)) {echo '<a class="dropdown-item" href="rstPass.php">Reset Password</a>';} ?>
                </div>
            </li>
        </ul>
    </nav>

    <div id="wrapper" class="toggled">

        <!-- Sidebar -->
        <ul class="sidebar navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-chart-area"></i>
                    <span>Dashboard</span></a>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link" href="#" id="pagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Reports<i class="fa fa-angle-down"></i></span>
                </a>
                <style>
                    .dropdown-item {
                        position: relative !important;
                        z-index: 1000;
                    }
                </style>
                <!-- Xcel Exclusions report -->
                <div style="background-color: #1d2124" class="dropdown-menu2" aria-labelledby="pagesDropdown">
                    <?php if(in_array(1,$rightsArray)) {echo '<a style ="color: #9EA8B0;" class="dropdown-item" href="pcpiFrontEnd.php?mode=new">&nbsp;&nbsp;&nbsp;Exclusions Report</a>'; } ?>
                </div>
                <!-- Xcel BDR report -->
                <div style="background-color: #1d2124" class="dropdown-menu2" aria-labelledby="pagesDropdown">
                    <?php if(in_array(7,$rightsArray)) {echo '<a style ="color: #9EA8B0;" class="dropdown-item" href="bdrReporting.php?mode=new">&nbsp;&nbsp;&nbsp;BDR Overview</a>'; } ?>
                </div>
                <!-- DCS Processing Report -->
                <div style="background-color: #1d2124" class="dropdown-menu2" aria-labelledby="pagesDropdown">
                    <?php if(in_array(10,$rightsArray)) {echo '<a style ="color: #9EA8B0;" class="dropdown-item" href="monthlyHersProcessing.php?mode=new">&nbsp;&nbsp;&nbsp;DCS Report</a>'; } ?>
                </div>
            </li>
			<?php 
				if (in_array(11, $rightsArray)) {
					echo '<li class="nav-item dropdown">
							<a class="nav-link" href="#" id="pagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="fas fa-fw fa-book"></i>
								<span>QA System<i class="fa fa-angle-down"></i></span>
							</a>
							<style>
								.dropdown-item {
									position: relative !important;
									z-index: 1000;
								}
							</style>
							<!-- Sampling System -->
							<div style="background-color: #1d2124" class="dropdown-menu2" aria-labelledby="pagesDropdown">';
					if (in_array(12, $rightsArray)) {
						echo '<a style="color: #9EA8B0;" class="dropdown-item" href="sampling_system.php?mode=new">&nbsp;&nbsp;&nbsp;Sampling System</a>';
					}
					echo '</div>
						</li>';
				}
			?>
            
            <!-- Dom Participation list -->
            <li class="nav-item">
                <?php   If ($defaultWhiteLabel == 54){
                    if(in_array(6,$rightsArray)) {echo '<a class="nav-link" href="ParticipationList.php">
                                                                                                                                        <i class="fas fa-fw fa-chart-area"></i>
                                                                                                                                        <span>Monthly Treatment</span>
                                                                                                                                </a>'; }
                }
                ?>
            </li>
        </ul>
        <div id="content-wrapper">

            <!-- Scroll to Top Button-->
            <a class="scroll-to-top rounded" onclick="topFunction()" id="myBtn">
                <i class="fas fa-angle-up" style="color: white;font-size: 35px;padding-top:5px"></i>
            </a>

            <!-- Logout Modal-->
            <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <div class="modal-body">Are you sure you want to Logout</div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                            <a class="btn btn-primary" href="logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
<?php

if (isset($_POST['switchToSelectedWL'])) {
    // Get the selected value from the AJAX request
    $switchToSelectedWL = $_POST['switchToSelectedWL'];
    $wls =  ($switchToSelectedWL == 0) ? 46 : $switchToSelectedWL;
    // Update the session variable
    $_SESSION['defWhitelabel'] = $wls;
}

?>
