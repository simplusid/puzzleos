<?php
defined("__POSEXEC") or die("No direct access allowed!");
__requiredSystem("1.2.2") or die("You need to upgrade the system");
/**
 * PuzzleOS
 * Build your own web-based application
 * 
 * @package      maral.puzzleos.core.upload_img_ajax
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2017 MARAL INDUSTRIES
 * 
 * @software     Release: 1.1.3
 */

/**
 * This is part of upload_img_ajax app
 */
class ImageUploader{
	public static $appProp;
	
	/**
	 * Print input file HTML Form
	 * @param string $key
	 * @param string $label
	 * @param string $bootstrap_style
	 * @param string $preview_selector
	 */
	public static function dumpForm($key, $label, $bootstrap_style = "default", $preview_selector = ""){
		if(isset($_SESSION["ImageUploader"][$key])){
			UserData::remove($_SESSION["ImageUploader"][$key]);
			unset($_SESSION["ImageUploader"][$key]);
		}
		include("view/input.php");
	}
	
	/**
	 * Get file name. e.g. "/user_data/path/file.ext"
	 * @param string $key
	 * @return string
	 */
	public static function getFileName($key){
		return(UserData::getPath($_SESSION["ImageUploader"][$key]));
	}
}

ImageUploader::$appProp = $appProp;
?>