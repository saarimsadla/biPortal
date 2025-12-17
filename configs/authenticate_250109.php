
<!--authenticate.php-->

<?php
// mysql connections example
// Uncomment the following code to connect to your MySQL database
// and check if the provided username and password are valid.
 
/*
$servername = "your_server";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";
 
$conn = new mysqli($servername, $username, $password, $dbname);
 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
// Retrieve username and password from the form submission
$user = $_POST['username'];
$pass = $_POST['password'];
 
// Uncomment the following code to query the database for the user
// and check if the password matches the stored hash.
 
/*
$sql = "SELECT * FROM users WHERE username = '$user'";
$result = $conn->query($sql);
 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $storedPasswordHash = $row['password'];
 
    if (password_verify($pass, $storedPasswordHash)) {
        // Redirect to the report page after successful login
        header("Location: report.php");
        exit();
    } else {
        echo "Incorrect password.";
    }
} else {
    echo "User not found.";
}
 
$conn->close();
*/
  
  
// LDAP connections example  
// Uncomment the following code for LDAP authentication
 

// Retrieve username and password from the form submission
/*$user = $_POST['username'];
$pass = $_POST['password'];

echo "uname. ".$user;
echo "   ";
echo "pass. ".$pass;
 
// LDAP server configuration
//$ldapServer = "ldap://ldap1.planetecosystems.com:389"; // Use ldaps:// for LDAPS (LDAP over SSL/TLS)
$ldapServer = "ldap://ldap1.pecosys.com"; // Use ldaps:// for LDAPS (LDAP over SSL/TLS)
 
// Attempt LDAP bind
$ldapConn = ldap_connect($ldapServer,389);
echo "   trying ldap con res.   ";
var_dump($ldapConn);

echo "   \n\n";


if ($ldapConn) {
    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
	ldap_set_option($ldapConn, LDAP_OPT_DEBUG_LEVEL, 7);
	
	$s = ldap_search($ldapConn, "dc=pecosys", "uid=$user");
	echo($s."\n");
 
    $ldapBind = ldap_bind($ldapConn, "uid=$user,ou=people,dc=pecosys,dc=com", $pass); //"uid=$user,ou=people,dc=pecosys,dc=com"
 
    if ($ldapBind) {
        // Authentication successful
        session_start();
        // Store additional user information in session if needed
        $_SESSION['username'] = $user;
        $_SESSION['authenticated'] = true;
 
        // Redirect to the report page after successful login
        header("Location: dashboard.php");
        exit();
    } else {         
		// Display LDAP error        
		$ldapError = ldap_error($ldapConn);         
		echo "LDAP bind failed: $ldapError";         
		$_SESSION['authenticated'] = false;     
	}

    ldap_close($ldapConn);
} else {
    echo "Unable to connect to the LDAP server.";
}
*/
 
 // For now, let's simulate a successful login without database interaction.
 
// Start a session
//session_start();
//$_SESSION['authenticated'] = true;
//header("Location: dashboard.php");
//exit();

 
// mongo connections example

//run : composer require mongodb/mongodb ;P.S works only for php version 7.1 ^
//run : for php version 7.1 lower document
// create composer.json file as below
/*
{
	"require": {
		"mongodb/mongodb": "1.2.0"
	}
}
*/
// run: composer update

/*./authConfirm sasadla sasadla
$_SESSION['authenticated'] = false; 
 // Redirect to the report page after successful login
        header("Location: report.php");
		$_SESSION['authenticated'] = false; 
        exit();
*/

function getWlData($valueToGet){
	$database_name = "hursPortal";
	$collection_name = "client_details.";
 
	// Fetch initial data to populate select options
	$initial_query_json = '{}';
 
	//print("ini query: ");
	//print_r($initial_query_json);
	//print(" ");
	$initial_command = "python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query.py $database_name $collection_name '$initial_query_json' 'q' '[]'";
	$initial_output = shell_exec($initial_command);
	//print_r($initial_command);
	$wlIdent = json_decode($initial_output, true);
	//print_r($wlIdent);
	$retVal ="";
	if ($valueToGet == "wlArray"){
		return $wlIdent;
	}
	
	if ($valueToGet == "wlList"){
		$retVal = "";
		foreach ($wlIdent as $item) {
			$retVal = $retVal.$item['wlId'].",";
		}
		$retVal = ltrim($retVal, ',');
		return $retVal;
	}
	
	return $retVal;
}


$user = isset($_POST['username']) ? $_POST['username'] : null;
$pass = isset($_POST['password']) ? $_POST['password'] : null;

//$user = "admin";
//$pass = "";

echo " $user $pass "; 


$osName = PHP_OS;

echo "os is :$osName";

$operatingDir = "";

if (strtoupper(substr($osName,0,3)) === "WIN"){
	shell_exec('cd ..');
	$operatingDir = '\goScripts\authConfirm ';
}elseif (strtoupper($osName) === "LINUX"){
	$operatingDir = '../goScripts/authConfirm ';
}
elseif (strtoupper($osName) === "DARWIN"){
	$operatingDir = '../goScripts/authConfirm ';
}

// $result = shell_exec('dir');
 
// echo $result;
// Use shell_exec to call the Go program with arguments
$result = shell_exec($operatingDir . escapeshellarg($user) . ' ' . escapeshellarg($pass));
 
// Print the result from the Go program
//echo " Result from Go program: $result ";



if (strpos($result, 'User not found') !== false){
	echo "<script>alert('User not found'); window.location.href = '../logout.php';</script>";
	//header("Location: logout.php");
	exit();
}
else{
	$jsonObject = $result;
	 
	// Decode the JSON object
	$data = json_decode($jsonObject, true);
	 
	// Check if decoding was successful
	if ($data !== null) {
		// Extract values from the decoded data
		$userFound = $data['User_Found'];
		$passwordMatched = $data['Password_Matched'];
		$defWhitLbl = $data['defWhiteLabel'];
	 
		// Extract values from the 'rolse' array
		$roles = $data['rolse'];
	 
		// Initialize variables to store values
		$allWhiteLabels = [];
		$allRights = [];
	 
		// Iterate through each role
		foreach ($roles as $role) {
			// Iterate through each key-value pair in the current role
			foreach ($role as $key => $value) {
				// Append the values to the respective arrays based on the key
				if ($key === 'whiteLabels') {
					$allWhiteLabels[] = $value;
				} elseif ($key === 'rights') {
					$allRights[] = $value;
				}
			}
		}
	 
		// Join distinct values for "whiteLabels" and "rights" into comma-separated strings
		$finalWhiteLabels = implode(', ', array_unique(explode(', ', implode(', ', $allWhiteLabels))));
		//$finalWhiteLabels = getWlData("wlList");
		$whiteLabelARR = getWlData("wlArray");
		$finalRights = implode(', ', array_unique(explode(', ', implode(', ', $allRights))));
		$finalRights = implode(', ', array_unique(explode(', ', implode(', ', $allRights))));
	 
		// Output the final result after processing all roles
	  
		echo "User Found: $userFound\n";
		echo "Password Matched: $passwordMatched\n";
		echo "White Labels: $finalWhiteLabels\n";
		echo "Rights: $finalRights\n";
		echo "Def whitelabel: $defWhitLbl\n";
		
		
		if ($passwordMatched == "Y") {
			echo "password matched";
			session_start();
			$_SESSION['authenticated'] 	= true;
			$_SESSION['unser']			= $user;
			$_SESSION['uname']			= $userFound;
			$_SESSION['whiteLabels']	= $finalWhiteLabels;
			$_SESSION['rghts']			= $finalRights;
			$_SESSION['defWhitelabel']	= $defWhitLbl;//46;//explode(",",$finalWhiteLabels)[1];
			$_SESSION['whiteLabelArr'] 	= $whiteLabelARR;
			//$_SESSION['usrImage']	= "images/lgn.png";//image link here
			
			header("Location: ../dashboard.php");
			exit();
		} else {
			//echo "<script>alert('password does not match'); window.location.href = '../logout.php';</script>";
			header("Location: logout.php");
			exit();
		}
		
	} else {
		// Handle JSON decoding error
				echo "<script>alert('Error decoding JSON object.'); window.location.href = '../logout.php';</script>";
	}
}
 
?> 

