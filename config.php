<?
	//all settings are stored inside the "settings" array
	$settings = array();

	//MySQL database connection options (server to connect to, username, password and the database name)
	$settings["server"] = "localhost";
	$settings["username"] = "root";
	$settings["password"] = "";
	$settings["database"] = "platform";

	//miscellaneous settings
	$settings["contactEmail"] = "glencrawford@windowslive.com";
	$settings["siteName"] = "Platform";
	$settings["rootDirectory"] = "C:/Program Files/xampp/root/platform/";
	$settings["defaultPageTitle"] = "Construction in progress";
	$settings["pageTitleFormat"] = $settings["siteName"] . " >> [TITLE]";

	//directory paths (relative to the index file)
	$settings["layoutDirectory"] = "../layout/";
	$settings["pagesDirectory"] = "../pages/";
	$settings["pageletsDirectory"] = "../pagelets/";
	$settings["assetsDirectory"] = "../assets/";

	//error logging settings
	$settings["errorPage"] = $settings["layoutDirectory"] . "error.php";
	$settings["errorLog"] = "logs/errors.txt";
	$settings["errorFormat"] = "[TIME] | [CODE] | [MESSAGE]";

	//script and style priority settings (just file names [no extensions], separated by a pipe)
	$settings["priorityScripts"] = "jquery-1.4.min";
	$settings["priorityStyles"] = "reset";

	//SMTP and outgoing email address settings for sending emails
	$settings["SMTP"] = "smtp.xtra.co.nz";
	$settings["smtp_port"] = "25";
	$settings["outboundEmailAddress"] = "noreply@" . $settings["siteName"] . ".com";
?>