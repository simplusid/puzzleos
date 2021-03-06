<?php

/**
 * PuzzleOS
 * Build your own web-based application
 *
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2020 MARAL INDUSTRIES
 */

// Change your public directory name here as pointed by the webserver
define("__PUBLICDIR", "public");

if (version_compare(PHP_VERSION, "7.3.0") < 0) {
    die("ERROR:\tPlease upgrade your PHP version at least to 7.3.0");
}

echo "PuzzleOS Reload Console...\n";
define("__ROOTDIR", str_replace("\\", "/", dirname(__DIR__)));
chdir(__ROOTDIR);

/***********************************
 * Enabling maintenance mode
 ***********************************/
echo "Enabling maintenance mode...";
touch(__ROOTDIR . DIRECTORY_SEPARATOR . "site.offline");
echo "OK\n";

/***********************************
 * Small bootstrapping
 * Including basic helper function
 ***********************************/
require "helper.php";

/***********************************
 * Creating directory
 ***********************************/
echo "Creating directories...";
preparedir(__ROOTDIR . "/storage");
preparedir(__ROOTDIR . "/storage/logs");
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
echo "OK\n";

/***********************************
 * Listing all applications
 ***********************************/
echo "Scanning application...\n";

$scanned_app = [];
$composer_json_app = [];
foreach (scandir(__ROOTDIR . "/applications") as $dir) {
    if (!is_dir(__ROOTDIR . "/applications/$dir")) continue;
    if ($dir != "." && $dir != "..") {
        if (file_exists(__ROOTDIR . "/applications/$dir/manifest.ini")) {
            $manifest = parse_ini_file(__ROOTDIR . "/applications/$dir/manifest.ini");
            if ($manifest["rootname"] == "") continue;
            if (isset($scanned_app[$manifest["rootname"]])) continue;
            if (strlen($manifest["rootname"]) > 50) continue;

            #Filter pre-reserved rootname
            switch ($manifest["rootname"]) {
                case "assets":
                case "res":
                case "security":
                case "reload":
                    continue;
                default:
                    break;
            }

            $manifest["rootname"] = strtolower($manifest["rootname"]);
            $parsed_man = [
                "rootname" => $manifest["rootname"],
                "dir" => __ROOTDIR . "/applications/$dir",
                "dir_name" => $dir,
                "title" => $manifest["title"],
                "desc" => $manifest["description"],
                "level" => $manifest["permission"],
                "canBeDefault" => $manifest["canBeDefault"],
                "services" => explode(",", trim($manifest["services"])),
                "menus" => explode(",", trim($manifest["menus"])),
            ];

            if ($parsed_man["services"][0] == "") $parsed_man["services"] = [];
            if ($parsed_man["menus"][0] == "") $parsed_man["menus"] = [];

            $scanned_app[] = $parsed_man;

            echo "  " . $manifest["rootname"] . "\n";
            if (file_exists($parsed_man["dir"] . "/composer.json")) {
                $composer_req = json_decode(file_get_contents($parsed_man["dir"] . "/composer.json"), true, 512, JSON_THROW_ON_ERROR);
                $composer_json_app = array_merge_recursive($composer_json_app, $composer_req);
                echo "    Found composer.json\n";
            }
        }
    }
}

file_put_contents(__ROOTDIR . "/configs/application_manifest.php", "<?php return " . var_export($scanned_app, true) . ";");
echo "Done...\n";

/***********************************
 * Running composer install
 ***********************************/
if ($argv[2] != "skip-composer") {
    echo "Merging composer file...";
    $system_composer_req = json_decode(file_get_contents(__ROOTDIR . "/includes/composer.sys.json"), true, JSON_THROW_ON_ERROR);
    $merged_composer = array_merge_recursive($system_composer_req, $composer_json_app);
    $encoded_json_composer = json_encode($merged_composer, JSON_PRETTY_PRINT);
    echo "OK\n";
    echo $encoded_json_composer . PHP_EOL;
    file_put_contents(__ROOTDIR . "/includes/composer.json", $encoded_json_composer);

    chdir(__ROOTDIR . DIRECTORY_SEPARATOR . "includes");
    if (file_exists(getcwd() . DIRECTORY_SEPARATOR . "composer.lock")) {
        echo "Running composer update\n==\n";
        passthru("composer update --optimize-autoloader --no-dev");
    } else {
        echo "Running composer install\n==\n";
        passthru("composer install --optimize-autoloader --no-dev");
    }
    chdir(__ROOTDIR);
    echo "==\n";
}

/***********************************
 * Listing all template
 ***********************************/
echo "Scanning Templates...\n";

$scanned_tmpl = [];
foreach (scandir(__ROOTDIR . "/templates") as $dir) {
    if (!is_dir(__ROOTDIR . "/templates/$dir")) continue;
    if ($dir != "." && $dir != ".." && $dir != "system") {
        if (file_exists(__ROOTDIR . "/templates/$dir/manifest.ini")) {
            $manifest = parse_ini_file(__ROOTDIR . "/templates/$dir/manifest.ini");
            $scanned_tmpl[$dir] = [
                "name" => $dir,
                "title" => $manifest["title"],
                "controller" => $manifest["controller"],
                "dir" => __ROOTDIR . "/templates/$dir"
            ];
            echo "  " . $dir . "\n";
        }
    }
}

file_put_contents(__ROOTDIR . "/configs/template_manifest.php", "<?php return " . var_export($scanned_tmpl, true) . ";");
echo "Done...\n";

/***********************************
 * Migrate Table
 ***********************************/
if (file_exists(__ROOTDIR . "/configs/root.sys.php")) {
    passthru("php puzzleos sys/db migrate");
} else {
    echo "Installation not found... Skipping table migration!\n";
}

/***********************************
 * Disabling maintenance mode
 ***********************************/
echo "\nDisabling maintenance mode...";
@unlink(__ROOTDIR . DIRECTORY_SEPARATOR . "site.offline");
echo "OK\n";
