<?
	//this function is called every time a page is rendered: it is specified as a callback
	//to be executed when the output buffer is flushed. it receives the contents of the
	//buffer, and replaces placeholder strings with content specific to every page, such
	//as the page's title
	function outputBufferCallback($bufferContents) {
		$bufferContents = str_replace("[TITLE]", generatePageTitle(), $bufferContents);
		$bufferContents = str_replace("[STYLES]", insertStyles(), $bufferContents);
		$bufferContents = str_replace("[SCRIPTS]", insertScripts(), $bufferContents);
		$bufferContents = str_replace("[RESULTS]", insertResults(), $bufferContents);

		return $bufferContents;
	}

	//this function is invoked by the output buffer callback, and it creates and formats
	//the title to be inserted into the <head> section of the page
	function generatePageTitle() {
		global $settings, $pageTitle;

		//if the pageTitle global variable is not defined
		if (!isSet($pageTitle)) {
			//define the variable and set it to the default page title
			$pageTitle = $settings["defaultPageTitle"];
		}

		//replace the title placeholder with this page's title in the page title format setting
		$pageTitle = str_replace("[TITLE]", $pageTitle, $settings["pageTitleFormat"]);

		return $pageTitle;
	}

	//use this function to insert the destination page into the page being rendered.
	//if the page can't be included (doesn't exist or bad file path), then an error
	//is logged and the error page is inserted instead
	function insertDestinationPage() {
		global $destination, $settings, $inputs, $pageTitle, $results, $modules;

		//build the full path to the destination page
		$destinationPage = $settings["pagesDirectory"] . $destination . ".php";

		//check and see if the requested file exists on the server
		if (file_exists($destinationPage)) {
			//if it does, include it
			require($destinationPage);
		}
		else {
			//if the file doesn't exist, log an error (with code and message) and
			//include the error page instead
			logError(1, "Page could not be found at \"$destinationPage\"");
		}
	}

	//use this function to insert any results that may be in the results array into
	//the page being rendered. html to structure the results is specified here
	function insertResults() {
		global $results;

		$resultsOutput = "";

		//$status will hold "success" or "error" depending on whether everything
		//up to this point has gone well
		if ($results[0] == "true") {
			//if all went well, set the status to "success"
			$status = "success";
		}
		elseif ($results[0] == "false") {
			//but if there was an error with the inputs or the execution of the
			//functions, set the status to "error"
			$status = "error";
		}
		else {
			//if, for some ungodly reason, it's neither true nor false, set to "null"
			$status = "null";
		}

		if (($status != "null") && (count($results) > 1)) {
			//if we have a status (success or error), and there is more than 1 value
			//in the results array
			$resultsOutput = "<div class=\"results results_$status\"><ul>";

			foreach ($results as $thisResult) {
				//for each value in the results array
				if ($thisResult == $results[0]) {
					//this is the first value in the array, that is, the true or false
					//skip it, we only want the messages
					continue;
				}

				//remove any backslashes inserted when making the inputs safe for sql use
				$thisResult = stripSlashes($thisResult);

				//output this message as a new list item
				$resultsOutput .= "<li>" . $thisResult . "</li>";
			}

			$resultsOutput .= "</ul></div>";
		}

		return $resultsOutput;
	}

	//this function retrieves all the .css files in the styles directory, and generates
	//the xhtml to reference these files in the page to replace the styles placeholder.
	//priority can be given to certain styles via the priorityStyles setting
	function insertStyles() {
		global $settings;

		//store in an array the paths to all the css files in the styles directory
		$stylesArray = glob($settings["assetsDirectory"] . "styles/*.css");

		//if a priority styles setting is defined, and it is not empty
		if (isSet($settings["priorityStyles"]) && ($settings["priorityStyles"] != "")) {
			//explode the setting into an array, at the pipes
			$priorityArray = explode("|", $settings["priorityStyles"]);

			//for each css file defined in the priority setting (in reverse)
			foreach (array_reverse($priorityArray) as $priorityStyle) {
				$priorityStylePosition = false;

				//and for each style in the style directory
				foreach($stylesArray as $stylesFile) {
					//if the priority string appears in the style's name
					if (strpos($stylesFile, $priorityStyle) !== false) {
						//then get the position of the style in the array of styles
						$priorityStylePosition = array_search($stylesFile, $stylesArray);

						//and store the path of this style
						$priorityStyleString = $stylesFile;

						//and exit out of this loop
						break;
					}
				}

				//if we couldn't find this prioritized style in the array of styles
				if ($priorityStylePosition === false) {
					//skip over it and try at the next one
					continue;
				}
				else {
					//now if we found this prioritized style in the array of styles
					//delete this style from the array of styles
					unSet($stylesArray[$priorityStylePosition]);

					//and insert it back at the top of the style array
					array_unshift($stylesArray, $priorityStyleString);
				}
			}
		}

		//loop over each .css file in the styles directory
		foreach ($stylesArray as $stylesFile) {
			//strip out the up-one-directory part of the assets directory setting (for the browser)
			$stylesFile = str_replace("../", "", $stylesFile);

			//add the xhtml to reference this .css file to the markup to add to the page
			$styles .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"/" . $stylesFile . "\" />\n";
		}

		return $styles;
	}

	//this function retrieves all the .js files in the scripts directory, and generates
	//the xhtml to reference these files in the page to replace the scripts placeholder.
	//priority can be given to certain scripts via the priorityScripts setting
	function insertScripts() {
		global $settings;

		//store in an array the paths to all the js files in the scripts directory
		$scriptsArray = glob($settings["assetsDirectory"] . "scripts/*.js");

		//if a priority scripts setting is defined, and it is not empty
		if (isSet($settings["priorityScripts"]) && ($settings["priorityScripts"] != "")) {
			//explode the setting into an array, at the pipes
			$priorityArray = explode("|", $settings["priorityScripts"]);

			//for each js file defined in the priority setting (we're going in reverse here)
			foreach (array_reverse($priorityArray) as $priorityScript) {
				$priorityScriptPosition = false;

				//and for each script in the script directory
				foreach ($scriptsArray as $scriptsFile) {
					//if the script's name appears in the priority setting
					if (strpos($scriptsFile, $priorityScript) !== false) {
						//then get the position of the script in the array of scripts
						$priorityScriptPosition = array_search($scriptsFile, $scriptsArray);

						//and store the path of this script
						$priorityScriptString = $scriptsFile;

						//and exit out of this loop
						break;
					}
				}

				//if we couldn't find this prioritized script in the array of scripts
				if ($priorityScriptPosition === false) {
					//skip over it and try at the next one
					continue;
				}
				else {
					//now if we found this prioritized script in the array of scripts
					//delete this script from the array of scripts
					unSet($scriptsArray[$priorityScriptPosition]);

					//and insert it back at the top of the script array
					array_unshift($scriptsArray, $priorityScriptString);
				}
			}
		}

		//loop over each .js file in the scripts directory
		foreach ($scriptsArray as $scriptsFile) {
			//strip out the up-one-directory part of the assets directory setting (for the browser)
			$scriptsFile = str_replace("../", "", $scriptsFile);

			//add the xhtml to reference this .js file to the markup to add to the page
			$scripts .= "<script type=\"text/javascript\" src=\"/" . $scriptsFile . "\"></script>\n";
		}

		return $scripts;
	}
?>