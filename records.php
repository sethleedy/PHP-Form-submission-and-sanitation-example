<?php


// Setup Vars

$errorY=false;
$dataSubmitted=false;

// $_REQUEST needs to be sanitized before use.
$theSubmittedData=$_REQUEST; // Hold copy of incoming data from $_REQUEST


// If the passed variable exists, return it. If not return the default or nothing
function issetor(&$var, $default = false) {
    return isset($var) ? $var : $default;
}

// Check the submitted form for errors
function checkForm(&$errorY, &$dataSubmitted, &$theSubmittedData) {
	
	// Setup some variables and colors and text
	$out["backGroundColor2"]="black"; // Second color in gradient
	$out["links"]='';
	$emptyKey="";
	$out["backGroundColor1"]="#08689C"; // Normal color in gradient unless changed by errors
	$out["subText"]=""; // Default Error is empty string
	
	// Check if we have form submission
	if (isset($_REQUEST["regFrmBtnSubmit"])) {
		
		//// Check for submission errors
		
		$dataSubmitted=true;		
		
		// Check that all required fields are filled in
		$RequiredEntries = array_filter($theSubmittedData, function($v, $n) { // Compare to amount left after checking for required filled in entries.
			// If the variable has the string appended of "_required", then check and see if it is filled in, else return FALSE
			if (strpos($n, "_required") !== false) {
				return true;
			} else {
				return false;
			}
		}, ARRAY_FILTER_USE_BOTH);
		$currCountOfRequired=count($RequiredEntries);
		
		$filteredRequiredEmpties = array_filter($theSubmittedData, function($v, $n) { // Compare to amount left after checking for required filled in entries.
			// If the variable has the string appended of "_required", then check and see if it is filled in, else return FALSE
			//echo $n; echo $v;
			if (strpos($n, "_required") !== false && $v != "") {
				return true;
			} else {
				//echo $v;
				return false;
			}
		}, ARRAY_FILTER_USE_BOTH);
		$countOfFilteredRequiredEmpties=count($filteredRequiredEmpties); // Get count of the ones that are not empty
		
		//var_dump($currCountOfRequired);
		//var_dump($filteredRequiredEmpties);
		
		// Check if the count amount is different. If equal, then all required amounts are filled in.
		if ($currCountOfRequired != $countOfFilteredRequiredEmpties) {
			$errorY=true;
			$emptyKey=array();
			
			// Find all the ones that should be filled, but are empty, and down below, trim off the "_required" text
			$emptyKey=array_filter($theSubmittedData, function($v, $k) {
				
				if (strpos($k, "_required") !== false && $v == "" ) {
					//echo "true";
					return true;
				} else {
					//echo "false";
					return false;
				}
			}, ARRAY_FILTER_USE_BOTH);
			
			// Start of Error Message.
			$out["subText"]="<span id='errorDisplay'>Error: Please fill in:";
			// Append inputs that are still to be filled in
			
			foreach ($emptyKey as $key => $value) {
				//echo "1".$key;
				$newKey=strtoupper(str_replace("_required", "", $key)); // trim off the "_required" text
				$out["subText"] .= " " . $newKey;
				
			}
			
			
		} else {
			
			
			/*// Check for mis matched passwords
			if ($theSubmittedData["password"] != $theSubmittedData["secondPassword"]) {
				$errorY=true;
				$emptyKey="password";
				$out["backGroundColor1"]="#A30305"; // Error color in gradient
				$out["subText"]="<span id='errorDisplay'>Error: The passwords do not match.</span>"; // Error message
				
			} elseif (substr_count($theSubmittedData["email"], "@") != 1) {
				// Check for email @ in email address
				$errorY=true;
				$emptyKey="password";
				$out["backGroundColor1"]="#A30305"; // Error color in gradient
				$out["subText"]="<span id='errorDisplay'>Error: The email address does not have an @ within it.</span>"; // Error message
			
			}*/
		}

		// If we have errors...
		if ($errorY === true) {
			$out["title"]="Errors";
			$out["subText"] = "<span id='successDisplay'>Please check for errors on the form below and resend</span>";  // Overwrite any previous assignment
			$out["backGroundColor1"]="#A30305"; // Error color in gradient
			
		} else { // No errors ? Good !
			
			// Display Success
			$out["backGroundColor1"]="green"; // Error color in gradient
			$out["title"]="Submission Accepted !";
			$out["subText"] = "<span class='successDisplay'><a href='records.php'>Start</a></span>";  // Overwrite any previous assignment
			$out["subText"] .= "<span class='successDisplay'><a href='records.php?readFile'>Read File</a></span>"; // Do not overwrite
			
		}
		
		
		
	} else { // Display the main page with a form for submission
		
		$out["title"]="New Contact";
		$out["subText"] = "<span class='successDisplay'>Please input information on the form below to submit it.</span>";  // Overwrite any previous assignment
		
	}
	
	return $out;
}

// Write data to a text file via appension.
function appendDataToFile(&$theSubmittedData, $out) {
	
	// Open file, if it exists and is write permitted.
	if (is_writable("records.txt")) {
		if (($handle = fopen("records.txt", "ab")) !== FALSE) {
			
			// Prepare data for insertion to file
			// Line ending set to Windows - \r\n
			$newData=$theSubmittedData["firstName_required"]. "|" .$theSubmittedData["lastName_required"]. "|" .$theSubmittedData["address_required"]. "|" .$theSubmittedData["city_required"]. "|" .$theSubmittedData["state_required"]. "|" .$theSubmittedData["zip"]. "\r\n";
			
			if (fwrite($handle, $newData) === FALSE) {				
				die("Cannot append to the file!");				
			}			
			
		} else {
			die("Could not open file to append!");
		}
	} else {
		die("File is not writable");
	}
	
	$out["subText"] .= "<span class='successDisplay'>Data: Written</span>";  // Append any previous assignment
	
	fclose($handle);
	
	return $out;
}

// Read entire file and format the data for output
function readFileData($out) {
	
	// Fill in this variable to display all the records later - $out["writtenRecords"]
	
	// Open file, if it exists and is read permitted.
	if (($handle = fopen("records.txt", "r")) !== FALSE) {
		// Loop file for all records
		$out["writtenRecords"] = '<table id="dataTable">';
		$everyThree=0;
		
		while (($fd = fgetcsv($handle,0,"|")) !== FALSE) {
			
			$everyThree++;
			
			if ($everyThree == 4) {
				if ($everyThree == 1) {$out["writtenRecords"] .= "</tr>";}
				$everyThree=1;
			}
			
			if ($everyThree == 1) {$out["writtenRecords"] .= "<tr>";} // Spit out a new TR row for the table after every three entries in a row.
			
			$out["writtenRecords"] .= "<td>".$fd[1] ." ". $fd[0] ."<br>"; // First / Last Name
			$out["writtenRecords"] .= $fd[2] ."<br>"; // Address 
			$out["writtenRecords"] .= $fd[3] ."<br>"; // City 
			$out["writtenRecords"] .= $fd[4] ."<br>"; // State 
			$out["writtenRecords"] .= $fd[5]."</td>"; // ZipCode 
			
			
		}
		
		// Set some final variables
		$out["title"]="Records";
		$out["writtenRecords"] .= '</table>';
		$out["subText"] = "<span class='successDisplay'><a href='records.php'>Start</a></span>"; // Overwrite any previous assignment
	
	} else {
		die("Could not open the file for Reading.");
	}
		
	return $out;
}

//// Start using the functions

// Grab the data and check it for errors after submission
$data=checkForm($errorY, $dataSubmitted, $theSubmittedData);

// Append data to file
if ($errorY===false && $dataSubmitted) {
	$data=appendDataToFile($theSubmittedData, $data); // Write data to file
}

// if URL is passed readFile from clicking the link to read the file
if (isset($theSubmittedData["readFile"])) {
	
	$data=readFileData($data); // Read entire file and output in nice tables..
}

//var_dump($data);



//// END PHP

//// Start HTML
?>

	<!DOCTYPE html>
	<html lang="en">

	<head>
		<title>Week 6 - Form Submission</title>
	
		<style>
			body {
				background-color: black;
				font-family: serif;
				font-size: 24px;
			}

			hr {
				border-color: #29353C;
			}

			a,
			a:active,
			a:visited {
				transition: color 1s ease;
				color: black;
			}

			a:hover {
				color: forestgreen;
			}

			#titleHead {
				font-size: xx-large;
			}

			#main {
				background: linear-gradient(to bottom, <?php echo $data["backGroundColor1"];
				?> 0%, <?php echo $data["backGroundColor2"];
				?> 100%);
				display: flex;
				align-items: flex-start;
				justify-content: center;
				height: 100vh;
			}

			#windowBox {
				display: inline-block;
				margin: 0 auto;
				margin-top: 5%;
				padding: 15px;
				background-color: cadetblue;
				border-radius: 10px;
				border-color: aqua;
				min-height: 30%;
				min-width: 750px;
				max-width: 60%;
				box-shadow: 10px 10px 5px black;
			}

			#formsDiv {
				text-align: center;
			}

			form {
				font-size: 16px;
				text-align: left;
			}

			input,
			textarea {
				width: 250px;
			}

			input[type="submit"] {
				width: 50%;
			}

			#regFrm td:last-child {
				text-align: center;
			}

			textarea {
				height: 50px;
			}

			#errorDisplay {
				background-color: #A30305;
				display: inline-block;
				border-radius: 10px;
				padding: 5px;
				color: azure;
			}

			.successDisplay {
				transition: background-color 1s ease;
				background-color: forestgreen;
				display: inline-block;
				border-radius: 10px;
				padding: 8px;
				color: azure;
				font-size: smaller;
				margin-bottom: 10px;
				margin-right: 10px;
			}

			.successDisplay:hover {
				background-color: black;
			}

			#dataTable {
				width: 100%;
			}

			#dataTable td {
				text-align: center;
				font-size: 18px;
				border: 1px green dotted;
			}

			#footer {
				margin-top: 20px;
			}
		</style>
	</head>
	
	<body>
		<div id="main">
			<div id="windowBox">
				<?php
					echo "<span id='titleHead'>". $data["title"] ."</span>";
					echo "<hr>";
					//var_dump($data);
					echo $data["subText"];
					
					if ($dataSubmitted && $errorY === false || isset($theSubmittedData["readFile"])) {

					if (isset($data["writtenRecords"])) {
						echo $data["writtenRecords"];
					}

						} else { ?>

					<?php if ($errorY) {  ?>
						Note: fields marked with '*' are required
						<?php } ?>

							<div id="formsDiv">
								<form name="regFrm" id="regFrm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
									<table>
										<tr>
											<td><span id="rfvfname">* First Name:</span></td>
											<td>
												<input required type="text" name="firstName_required" value="<?php echo issetor($theSubmittedData['firstName_required'], ''); ?>" />
											</td>
										</tr>
										<tr>
											<td><span id="rfvlname">* Last Name:</span></td>
											<td>
												<input required type="text" name="lastName_required" value="<?php echo issetor($theSubmittedData['lastName_required'], ''); ?>" />
											</td>
										</tr>
										<tr>
											<td><span id="rfveaddress">* Address:</span></td>
											<td>
												<input required type="text" name="address_required" value="<?php echo issetor($theSubmittedData['address_required'], ''); ?>" />
											</td>
										</tr>

										<!-- Test php input submission with CITY -->
										<tr>
											<td><span id="rfvcity">* City:</span></td>
											<td>
												<input type="text" name="city_required" value="<?php echo issetor($theSubmittedData['city_required'], ''); ?>" />
											</td>
										</tr>

										<!-- Test php input submission with STATE -->
										<tr>
											<td><span id="rfvstate">* State:</span></td>
											<td>
												<input type="text" name="state_required" value="<?php echo issetor($theSubmittedData['state_required'], ''); ?>" />
										</tr>

										<tr>
											<td><span id="rfvzip">Zip Code:</span></td>
											<td>
												<input type="text" name="zip" value="<?php echo issetor($theSubmittedData['zip'], ''); ?>" />
										</tr>

										<tr>
											<td>&nbsp;</td>
											<td>
												<input type="submit" value="Submit" id="regFrmBtnSubmit" name="regFrmBtnSubmit" />
											</td>
										</tr>
									</table>
								</form>
							</div>

							<?php } ?>

			</div>

		</div>

	</body>

	</html>