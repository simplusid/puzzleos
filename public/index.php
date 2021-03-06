<?php
/**
 * PuzzleOS
 * Build your own web-based application
 *
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2019 PT SIMUR INDONESIA
 */

define('__POSEXEC', 1);
define('__PUBLICDIR', str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', __DIR__));

require('../bootstrap/boot.php');

// Run the requested app and resources
AppManager::startApp();

// This will load the UI for the client
Template::loadTemplate();
