<?
	//this function is called when data has been posted from a form. it stores this
	//data in the inputs array, and performs a number of checks to make sure that
	//all the required inputs were filled in, and that the values are of the right
	//data type
	function parseInputs() {
		global $inputs, $results;

		//create the results array, with true as the default
		$results[0] = "true";

		//catch the posted inputs and store them in the inputs array
		foreach ($_POST as $field => $value) {
			$inputs[$field] = $value;
		}

		//perform misc functions to make sure the inputs won't do anything
		//malicious (such as trimming, stripping html tags, and preventing
		//sql injections)
		foreach ($inputs as $field => $value) {
			$inputs[$field] = trim($value);
			$inputs[$field] = htmlspecialchars($value);
			$inputs[$field] = strip_tags($value);
			$inputs[$field] = nl2br($value);
			$inputs[$field] = mysql_real_escape_string($value);
		}

		//make sure any inputs that are marked as required aren't empty, and 
		//that all fields conform to the proper data type, if one is indicated
		foreach ($inputs as $field => $value) {
			//reset these flags for each input
			$isRequired = false;
			$foundError = false;

			//make sure required fields aren't empty
			if (strrpos($field, '$R', 0) != false) {
				//this field is required
				$isRequired = true;

				if ($value == "") {
					//this field is required, but it's empty. log an error
					addErrorMessage("\"" . restoreFieldName($field) . "\" is a required field, you must enter a value");

					//set this so we don't check this field for data type
					$foundError = true;
				}
			}

			//we only care about the data type if the field has a value, and 
			//we haven't already found a problem with this input
			if (($value != "") && (!$foundError)) {
				//make sure the value is of the proper data type
				$dollar_position = strrpos($field, '$', 0);
				if ($dollar_position != false) {
					//there is a $ in the name of this field (position of it stored in $dollar_position)
					if ((substr($field, ($dollar_position + 1), 2) == "is") || (substr($field, ($dollar_position + 2), 2) == "is")) {
						//this field name has "is" either right after the $, or 1 char after the $ (allowing for the R)
						//this means that this field must conform to a specific data type
						$dataType = substr($field, (strpos($field, '$', 0) + 1), strlen($field));
						$dataType = substr($dataType, (strpos($dataType, "is", 0) + 2), strlen($dataType));
						$dataType = strToLower($dataType);

						if (!isCorrectDataType($value, $dataType)) {
							//the value does not conform to the data type. log an error
							addErrorMessage("\"" . restoreFieldName($field) . "\" must be of the " . $dataType . " data type");

							//and now empty this value in the array, so that if this gets sent back and 
							//preloaded into the form, the faulty value won't go with it
							$inputs[$field] = "";
						}
					}
				}
			}
		}

		//lastly, remove the $ and everything after it from the keys of the inputs array
		foreach ($inputs as $key => $value) {
			$oldKey = null;
			$newKey = null;

			//store the place of the dollar sign in the key (if any)
			$dollar_position = strpos($key, "$", 0);

			if ($dollar_position != false) {
				//there is a $ in the name of this field
				//store the current key
				$oldKey = $key;

				//the new key is the old key from the start up to the position of the dollar sign
				$newKey = substr($oldKey, 0, $dollar_position);

				//create a new entry in the array with the new key and the same value as the old one
				$inputs[$newKey] = $value;

				//and remove the old (with the $) entry in the array
				unset($GLOBALS["inputs"][$oldKey]);
			}
		}
	}

	//returns true if the value conforms to the indicated data type, false for otherwise
	function isCorrectDataType($value, $dataType) {
		$result = false;

		switch ($dataType) {
			case "numeric":
				//check that the value is a numeric string
				if (is_numeric($value)) {
					$result = true;
				}
				break;
			case "integer":
				//ensure that the value is a whole number
				if ((stripos($value, ".", 0) == false) && (is_numeric($value))) {
					$result = true;
				}
				break;
			case "decimal":
				//ensure that the value is a number, with decimal points
				if ((stripos($value, ".", 0) != false) && (is_numeric($value))) {
					$result = true;
				}
				break;
			case "email":
				//check that the value is a valid email address
				if (preg_match("/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/", $value)) {
					$result = true;
				}
				break;
			default:
				//any other data type, we'll assume the value is valid
				$result = true;
				break;
		}

		return $result;
	}

	//called when we want to make a field name readable again, this function 
	//replaces underscores with spaces and removes the $ and every character 
	//after it, if one is present
	function restoreFieldName($fieldName) {
		if (strrpos($fieldName, '$', 0) != false) {
			//if there is a $ in the field name, strip it and every character after it out
			$fieldName = substr($fieldName, 0, (strlen($fieldName) - strlen(substr($fieldName, stripos($fieldName, "$", 0), (strlen($fieldName) - stripos($fieldName, "$", 0))))));
		}

		//now replace any underscores with spaces
		$fieldName = str_replace("_", " ", $fieldName);

		return $fieldName;
	}
?>