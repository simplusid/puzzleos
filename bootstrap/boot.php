<?php
/**
 * PuzzleOS
 * Build your own web-based application
 *
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2018 MARAL INDUSTRIES
 */

(function () {
define("DISABLE_MINIFY", 1);
define("TIME_LIMIT", 30);
define("ENV_WIN", (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'));
define("IO_STREAM_BUFFER", 102400);
//define("DB_DEBUG",1);

/***********************************
 * Initial Checking
 ***********************************/
if (!version_compare(PHP_VERSION, "7.0.0", ">=")) die("PuzzleOS need PHP7 in order to work!");
if (PHP_SAPI == "cli")
	if (!defined("__POSCLI") && !defined("__POSWORKER")) die("Please use \"sudo -u www-data php puzzleos\"\n");
error_reporting(0);

/***********************************
 * Define the global variables
 ***********************************/
defined("__SYSTEM_NAME") or define("__SYSTEM_NAME", "PuzzleOS");
define("__POS_VERSION", "2.0.10");

//Return /path/to/qualified/root/directory
define("__ROOTDIR", str_replace("\\", "/", dirname(__DIR__)));

defined("__PUBLICDIR") or define("__PUBLICDIR", "public");

//Return something.com
define("__HTTP_HOST", $_SERVER["HTTP_HOST"]);

//Return "https://" or "http://"
define("__HTTP_PROTOCOL", (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://");

//Return http://something.com
define("__SITEURL", __HTTP_PROTOCOL . $_SERVER['HTTP_HOST'] . str_replace("/index.php", "", $_SERVER["SCRIPT_NAME"]));

//Return applications/yourapp/assets/base_1.gif?my=you
define("__HTTP_REQUEST", ltrim(str_replace(__SITEURL, "", str_replace(str_replace("/index.php", "", $_SERVER["SCRIPT_NAME"]), "", $_SERVER['REQUEST_URI'])), "/"));

//Return applications/yourapp/assets/base_1.gif
define("__HTTP_URI", explode("?", __HTTP_REQUEST)[0]);

set_time_limit(TIME_LIMIT);
require("exception.php");

/***********************************
 * Maintenance Mode Handler
 *
 * To enter maintenance mode,
 * create "site.offline" file
 * in the root directory
 ***********************************/
if (file_exists(__ROOTDIR . "/site.offline")) {
	header($_SERVER["SERVER_PROTOCOL"] . ' 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 300');

	include(__ROOTDIR . "/templates/system/503.php");
	exit;
}

/***********************************
 * Load helper functions
 ***********************************/
require("defines.php");
require("helper.php");

/***********************************
 * Prepare all directories
 ***********************************/
preparedir(__ROOTDIR . "/storage");
preparedir(__ROOTDIR . "/storage/dbcache");
preparedir(__ROOTDIR . "/storage/data");
preparedir(__ROOTDIR . "/storage/cache");
preparedir(__ROOTDIR . "/storage/cache/applications");
preparedir(__ROOTDIR . "/storage/cache/bootstrap");
preparedir(__ROOTDIR . "/" . __PUBLICDIR . "/assets");
preparedir(__ROOTDIR . "/" . __PUBLICDIR . "/res");
preparedir(__ROOTDIR . "/" . __PUBLICDIR . "/cache", function () {
	file_put_contents(__ROOTDIR . "/" . __PUBLICDIR . "/cache/.htaccess", 'Header set Cache-Control "max-age=2628000, public"');
});

/***********************************
 * Get the configuration files
 ***********************************/
require('configman.php');
error_reporting(POSConfigGlobal::$error_code);
define("__SITENAME", POSConfigGlobal::$sitename);
define("__SITELANG", POSConfigGlobal::$default_language);
define("__TIMEZONE", POSConfigGlobal::$timezone);

/***********************************
 * Registering Autoloader
 * This is bundled Library that 
 * shipped with PuzzleOS.
 * 
 * This will speed up loading time
 ***********************************/
require("autoload.php");

/***********************************
 * Removing installation directory
 ***********************************/
if (file_exists(__ROOTDIR . "/" . __PUBLICDIR . "/install")) {
	$r = IO::remove_r("/" . __PUBLICDIR . "/install");
	if (!$r) throw new PuzzleError("Please remove /" . __PUBLICDIR . "/install directory manually for security purpose");
}

/***********************************
 * Feature that must be loaded
 * without autoloader
 ***********************************/
require("session.php");
require("time.php");
require("appFramework.php");
require("services.php");

/***********************************
 * Writing session to cookie
 ***********************************/
PuzzleSession::writeCookie();

/***********************************
 * Process private file if requested
 * from browser. Public file handled
 * by Webserver directly
 ***********************************/
if (__getURI(0) == "assets" && !__isCLI()) {
	$f = "/" . str_replace("assets/", "storage/data/", __HTTP_URI);
	$d = Database::readAll("userdata", "where `physical_path`='?'", $f)->data[0];
	$appProp = $d["app"];
	if ($appProp != "") {
		try {
			$appProp = new Application($appProp);
			if (!$appProp->isForbidden) {
				if (file_exists($appProp->path . "/authorize.userdata.php")) {
				//Isolating superglobal vars from auth file
					$fa = function ($file_key, $file_mime) use ($appProp) {
						return include($appProp->path . "/authorize.userdata.php");
					};
					if ($fa($d["identifier"], $d["mime_type"])) IO::streamFile($f);
				} else {
					IO::streamFile($f);
				}
			}
		} catch (AppStartError $e) {
		}
	}
}
})();
?>
