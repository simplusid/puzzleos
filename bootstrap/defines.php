<?php
/**
 * PuzzleOS
 * Build your own web-based application
 *
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2019 PT SIMUR INDONESIA
 */

/***********************************
 * DONT CHANGE THIS FILE
 * This file generate the "defines"
 * needed by PuzzleOS to work.
 ***********************************/

define("START_TIME", time());
defined("__SYSTEM_NAME") or define("__SYSTEM_NAME", "PuzzleOS");
define("__POS_VERSION", "3.2.17");

/**
 * Return /path/to/qualified/root/directory
 */
define("__ROOTDIR", str_replace("\\", "/", dirname(__DIR__)));

/**
 * Return /path/to/qualified/root/directory/storage/logs
 */
define("__LOGDIR", __ROOTDIR . "/storage/logs");

/**
 * Return the name of public directory generated by index.php
 * Usually it's "public" or "public_html"
 */
defined("__PUBLICDIR") or define("__PUBLICDIR", "public");

/**
 * Return something.com 
 */
define("__HTTP_HOST", $_SERVER["HTTP_HOST"]);

/**
 * Return TRUE on secure connection
 */
define("__HTTP_SECURE", (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443));

/**
 * Return "https://" or "http://"
 */
define("__HTTP_PROTOCOL", __HTTP_SECURE ? "https://" : "http://");

/**
 * Return http://something.com
 */
define("__SITEURL", __HTTP_PROTOCOL . $_SERVER['HTTP_HOST'] . str_replace("/index.php", "", $_SERVER["SCRIPT_NAME"]));

/**
 * Return applications/yourapp/assets/base_1.gif?my=you
 */
define("__HTTP_REQUEST", urldecode(ltrim(str_replace(__SITEURL, "", str_replace(str_replace("/index.php", "", $_SERVER["SCRIPT_NAME"]), "", $_SERVER['REQUEST_URI'])), "/")));

/**
 * Return applications/yourapp/assets/base_1.gif
 */
define("__HTTP_URI", urldecode(explode("?", __HTTP_REQUEST)[0]));

define("APP_DEFAULT", 1);
define("APP_NOT_DEFAULT", 0);
define("APP_CANNOT_DEFAULT", 3);

define("T_DAY", 86400);
define("T_HOUR", 3600);
define("T_MINUTE", 60);
define("TODAY", strtotime(date("Y/m/d", time())));

define("APP_ERROR_NOTFOUND", 1);
define("APP_ERROR_FORBIDDEN", 2);
define("APP_ERROR_NOVIEW", 3);
define("APP_ERROR_NOTRUNNING", 4);

define("__WORKERDIR", __ROOTDIR . "/storage/worker");
