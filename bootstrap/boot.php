<?php
defined("__POSEXEC") or die("No direct access allowed!");
/**
 * PuzzleOS
 * Build your own web-based application
 *
 * @package      maral.puzzleos.core
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2017 MARAL INDUSTRIES
 *
 * @software     Release: 2.0.0
 */

define("DISABLE_MINIFY",1);
//define("DB_DEBUG",1);

/***********************************
 * Initial Checking
 ***********************************/
if(!version_compare(PHP_VERSION,"7.0.0",">=")) die("PuzzleOS need PHP7 in order to work!");
if(PHP_SAPI == "cli" && !defined("__POSCLI")) die("\nPlease use\n     sudo -u www-data php puzzleos\n\n");
if(PHP_SAPI != "cli" && defined("__POSCLI")) die("Please use index.php as Directory Main File!");
error_reporting(0);

/***********************************
 * Define the global variables
 ***********************************/
define("__SYSTEM_NAME", "PuzzleOS");
define("__POS_VERSION", "2.0.0");

//Return /path/to/directory
define("__ROOTDIR", str_replace("\\","/",dirname(__DIR__)));

//Return something.com
define("__HTTP_HOST",$_SERVER["HTTP_HOST"]);

//Return "https://" or "http://"
define("__HTTP_PROTOCOL",(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://");

//Return http://something.com
define("__SITEURL", __HTTP_PROTOCOL . $_SERVER['HTTP_HOST'] . str_replace("/index.php","",$_SERVER["SCRIPT_NAME"]));

//Return applications/dompetdinar/assets/base_1.gif?my=you
define("__HTTP_REQUEST",ltrim(str_replace(__SITEURL,"",str_replace(str_replace("/index.php","",$_SERVER["SCRIPT_NAME"]) , "" , $_SERVER['REQUEST_URI'])),"/"));

//Return applications/dompetdinar/assets/base_1.gif
define("__HTTP_URI", explode("?",__HTTP_REQUEST)[0]);

set_time_limit(30);
require_once("runtime_error.php");

/***********************************
 * Maintenance Mode Handler
 *
 * To enter maintenance mode,
 * create "site.offline" file
 * in the root directory
 ***********************************/
if(file_exists(__ROOTDIR . "/site.offline")){
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 300');

	include(__ROOTDIR . "/templates/system/503.php");
	exit;
}

/***********************************
 * Define global functions
 ***********************************/
require_once("functions.php");

/***********************************
 * Get the configuration files
 ***********************************/
require_once('configman.php');
error_reporting(POSConfigGlobal::$error_code);
define("__SITENAME", POSConfigGlobal::$sitename);
define("__SITELANG", POSConfigGlobal::$default_language);
define("__TIMEZONE", POSConfigGlobal::$timezone);

/***********************************
 * Configuring user session
 ***********************************/
require_once("session.php");
POSGlobal::$session->write_cookie();

/***********************************
 * Prepare all directories
 ***********************************/
preparedir(__ROOTDIR . "/storage");
preparedir(__ROOTDIR . "/storage/dbcache");
preparedir(__ROOTDIR . "/storage/data");
preparedir(__ROOTDIR . "/public/assets");
preparedir(__ROOTDIR . "/public/res");
preparedir(__ROOTDIR . "/public/cache",function(){
	file_put_contents(__ROOTDIR . "/public/cache/.htaccess",'Header set Cache-Control "max-age=2628000, public"');
});

/***********************************
 * Process incoming Request
 ***********************************/
POSGlobal::$uri = explode("/",__HTTP_URI);
POSGlobal::$uri["APP"] = POSGlobal::$uri[0];
if(POSGlobal::$uri["APP"] == "") POSGlobal::$uri["APP"] = POSConfigMultidomain::$default_application;
POSGlobal::$uri["ACTION"] = (isset(POSGlobal::$uri[1]) ? POSGlobal::$uri[1] : "");

require_once("iosystem.php");
require_once("fastcache.php");
require_once("message.php");
require_once("userdata.php");
require_once("language.php");

/***********************************
 * Removing installation directory
 ***********************************/
if(IO::exists("/public/install")){
	$r = IO::remove_r("/public/install");
	if(!$r) throw new PuzzleError("Please remove /public/install directory manually for security purpose");
}

/***********************************
 * Loading another features
 ***********************************/
require_once("templates.php");
require_once("time.php");
require_once("appFramework.php");
require_once("cron.php");
require_once("cli.php");
require_once("services.php");

/***********************************
 * Process private file if requested
 * from browser. Public file handled
 * by Webserver directly
 ***********************************/
if(__getURI(0) == "assets"){
	$f = "/" . str_replace("assets/","storage/data/",__HTTP_URI);
	$d = Database::readAll("userdata","where `physical_path`='?'",$f)->data[0];
	$app = $d["app"];
	if($app != ""){
		try{
			$app = new Application($app);
			if(!$app->isForbidden){
				if(file_exists($app->path . "/authorize.userdata.php")){
					$file_key = $d["identifier"];
					$result = include($app->path . "/authorize.userdata.php");
					if($result) IO::streamFile($f);
				}else{
					IO::streamFile($f);
				}
			}
		}catch(AppStartError $e){}
	}
}
?>