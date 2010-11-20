<?
	//called when an error is found in the inputs, or a function encounters 
	//an error while executing that the user needs to know about.
	function addErrorMessage($errorMessage) {
		global $results;

		//make sure that the result is set to false, to indicate an error
		$results[0] = "false";

		//add the passed-in error message to the end of the structure
		array_push($results, $errorMessage);
	}

	//called when a function wants to give the user a confirmation message
	function addConfirmationMessage($confirmationMessage) {
		global $results;

		//make sure that the result is set to true
		$results[0] = "true";

		//add the passed-in confirmation message to the end of the structure
		array_push($results, $confirmationMessage);
	}

	//use this function to log the current time and the passed-in code and message
	//to the error log text file, and then include the error page
	function logError($code, $message) {
		global $settings, $pageTitle;

		//store and format the current time
		$time = date("H:i:s d/m/y", time());

		//retrieve the format for an error string, and replace the time placeholder with the time
		$errorString = str_replace("[TIME]", $time, $settings["errorFormat"]);

		//replace the code and message placeholders with the passed-in code and message
		$errorString = str_replace("[CODE]", $code, $errorString);
		$errorString = str_replace("[MESSAGE]", $message, $errorString);

		//append a new line to the end of the error string
		$errorString .= "\n";

		//open up the error log file, with the cursor at the end of the file
		$fileHandle = fopen($settings["errorLog"], "a");

		//now write the finished error string to the file
		fwrite($fileHandle, $errorString);

		//and close the file
		fclose($fileHandle);

		//and include the error page
		require($settings["errorPage"]);
	}

	//use this function to send emails from the website. the recipient, sender, subject
	//and message are passed in, and the function returns a boolean to indicate whether
	//the email was sent or not. if no "from" address is specified, the outbound email
	//address defined in the settings is used instead
	function sendEmail($to, $from, $subject, $message) {
		global $settings;

		//if the from parameter is null or empty, use the outbound email address defined in the settings
		if (($from == null) || ($from == "")) {
			$from = $settings["outboundEmailAddress"];
		}

		//if any of the to, subject, and message parameters are null or empty
		if (($to == "") || ($subject == "") || ($message == "") || ($to == null) || ($subject == null) || ($message == null)) {
			return false;
		}

		//attach an additional "From" header to the email
		$additionalHeaders = "From: " . $from;

		//update the values of the following two configuration options from the settings
		ini_set("SMTP", $settings["SMTP"]);
		ini_set("smtp_port", $settings["smtp_port"]);

		//and attempt to send the email using the passed-in parameters and the additional header
		$result = mail($to, $subject, $message, $additionalHeaders);

		return $result;
	}
?>