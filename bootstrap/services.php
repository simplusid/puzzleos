<?php

/**
 * PuzzleOS
 * Build your own web-based application
 * 
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2019 PT SIMUR INDONESIA
 */

foreach (AppManager::getList() as $data) {
	if (!empty($data["services"])) {
		$app = iApplication::preload($data["rootname"]);
		// AppManager::migrateTable($app->rootname);
		foreach ($data["services"] as $service) {
			if ($service == "") continue;
			if (!$app->loadContext($service)) {
				throw new PuzzleError("Cannot start '" . $data['name'] . "' services!", "Please recheck the existence of " . $data["dir"] . "/" . $service);
			}
		}
	}
}
