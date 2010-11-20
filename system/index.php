<?
	require("../config.php");

	//and now establish a persistent connection to the MySQL server, and select the database to operate on
	mysql_pconnect($settings["server"], $settings["username"], $settings["password"]);
	mysql_select_db($settings["database"]);

	//include all the files in the library directory
	foreach (glob("library/*.php") as $libraryFile) {
		require($libraryFile);
	}

	//this block includes all the php files in the modules directory, retrieves the name of the class
	//(from the file name), and creates an object of the class inside that module file, which is then
	//stored inside the global array "modules"
	foreach (glob("modules/*.php") as $moduleFile) {
		require($moduleFile);

		$moduleName = substr($moduleFile, (strrpos($moduleFile, "/", 0) + 1), (strlen($moduleFile) - strlen(substr($moduleFile, strrpos($moduleFile, ".", 0), strlen($moduleFile))) - strlen(substr($moduleFile, 0, strrpos($moduleFile, "/", 0) + 1))));

		eval("$" . "modules[\"" . $moduleName . "\"] = new " . ucfirst($moduleName) . "();");
	}

	//if there is a "pagelet" paramater in the URL, include the pagelet and exit this script (will still process the pagelet)
	//all parameters after the pagelet in the URL will be intact, and the pagelet one itself, but we're deleting the "page" one (not going to a page)
	if (isSet($_GET["pagelet"])) {
		unSet($_GET["page"]);

		$pageletPath = $settings["pageletsDirectory"] . $_GET["pagelet"] . ".php";

		//first check if the pagelet even exists. include it if it does
		if (file_exists($pageletPath)) {
			require($pageletPath);
		}
		else {
			//if the pagelet is not present, output an error message
			echo "Pagelet not found";
		}

		exit;
	}

	//store the path of the requested page (may be altered by a function later on)
	if (isSet($_GET["page"])) {
		$destination = $_GET["page"];
	}

	//take the posted action (if any) and split it up into module and function
	if ((isSet($_POST["action"])) && ($_POST["action"] != "")) {
		$module = substr($_POST["action"], 0, strlen($_POST["action"]) - (strlen(strrchr($_POST["action"], "."))));
		$function = substr($_POST["action"], (strlen($module) + 1), (strlen($_POST["action"]) - strlen($module)));

		//check the posted inputs to make sure all required fields were filled out, and all values are of the right data type
		parseInputs();

		//if there were no errors found with the inputs
		if ($results[0] == "true") {
			//run the specified function inside the module
			eval("$" . "modules[\"" . $module . "\"]->" . $function . "();");
		}
		//but if we found problems with the inputs, send the user back to where they came from
		else {
			$destination = $inputs["page"];
		}
	}

	//if there is still no destination specified, send the user to the home page
	if ($destination == "") {
		$destination = "home";
	}

	//now replace periods in the destination with slashes, so we can find the file in its directory
	$destination = str_replace(".", "/", $destination);

	//enable output buffering, specifying a function to be called before the buffer contents are flushed
	ob_start("outputBufferCallback");

	//and begin building the page, starting with the template
	require($settings["layoutDirectory"] . "template.php");

	//flush the output buffer's contents out to the client
	ob_end_flush();
?>