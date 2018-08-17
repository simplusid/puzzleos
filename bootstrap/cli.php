<?php
defined("__POSEXEC") or die("No direct access allowed!");
/**
 * PuzzleOS
 * Build your own web-based application
 * 
 * @package      maral.puzzleos.core
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2018 MARAL INDUSTRIES
 * 
 * @software     Release: 2.0.2
 */

class PuzzleCLI{
    private static $list=[];
    private static function init(){
		$caller = debug_backtrace()[1]["file"];
		$filenameStr = str_replace(__ROOTDIR,"",btfslash($caller));
		$filename = explode("/",$filenameStr);
        if($filename[1] != "applications") throw new PuzzleError("CLI command registration must be called from applications");
		$appDir = $filename[2];
		$appname = AppManager::getNameFromDirectory($appDir);
		return($appname);
	}
	
	/**
	 * Register a function to be called from CLI
	 * @param function $F($in,$out)
	 */
	public static function register($F){
		if(!__isCLI()) return false;
		$app = self::init();
		if(isset(self::$list[$app])) throw new PuzzleError("One app  allowed to register one CLI commands function!");
		if(!is_callable($F)) throw new PuzzleError("Incorrect parameter");
		self::$list[$app] = &$F;
	}
	
	/**
	 * Run CLI routine, calling PuzzleOS from CLI will automatically
	 * authenticate user as USER_AUTH_SU
	 * 
	 * @see accounts/class.php
	 * @param array $a
	 */
	public static function run($a){
		if(!__isCLI()) return false;
		error_reporting(0);
		ini_set('max_execution_time',0); //Disable PHP timeout
		if($a[0] != "puzzleos") throw new PuzzleError("Please use 'sudo -u www-data php puzzleos'\n\n");
		
		/* Generating Argument list */
		reset($a);
		next($a);
		$p = next($a);
		$arg = [];
		while(1){
			if($p === FALSE) break;
			if(substr($p,0,2) == "--"){
				$arg[$p] = next($a);
			}else{
				$arg[$p] = true;
			}
			
			$p = next($a);
			if($p === FALSE) break;
		}
		
		/* Loading app */
		$app = $a[1];
		if(strpos($app,"sys/") === false){
			$appProp = new Application($a[1]);
			if($app == "") exit;
			if(!isset(self::$list[$app])) 
				throw new PuzzleError("Application doesn't register handler for CLI");
			
			$io = new PObject([
				"in" => function() { 
					if (PHP_OS == 'WINNT')
					  $line = stream_get_line(STDIN, 1024, PHP_EOL);
					else
					  $line = readline();
					  
					return $line;
				},
				"out" => function($o) { 
					echo trim($o,"\t");
				}
			]);
			
			$f = self::$list[$app];
			$f($io,$arg);
		}else{
			/* This is part of the system */
			$sys = explode("sys/",$app)[1];
			if($sys == "cron"){
				if($arg["run"]) 
					CronJob::run();
				else 
					throw new PuzzleError("Invalid action");
			}elseif($sys == "cache"){
				if($arg["flush"]){
					IO::remove_r("/public/cache");
					IO::remove_r("/public/res");
				}
				else 
					throw new PuzzleError("Invalid action");
			}else
				throw new PuzzleError("Invalid parameter");
		}
	}
}

?>
