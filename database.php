<?php
defined("__POSEXEC") or diedefined("__POSEXEC") or die("No direct access allowed!");
/**
 * PuzzleOS
 * Build your own web-based application
 * 
 * @package      maral.puzzleos.core
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2017 MARAL INDUSTRIES
 * 
 * @software     Release: 1.2.3
 */

/**
 * Insert data to database
 * Call this class from Database::newRowAdvanced();
 */
class DatabaseRowInput{
	
	private $rowStructure = array();
	
	public function __construct(){
		return $this;
	}
	
	/* Set Column field 
	 * @param string $column_name
	 * @param $value
	 */
	public function setField($column_name, $value){
		if($column_name == "") throw new DatabaseError("Column name cannot be empty");
		$this->rowStructure[$column_name] = $value;
		return $this;
	}
	
	public function getStructure(){
		return $this->rowStructure;
	}
	
	public function clearStructure(){
		$this->rowStructure = array();
		return $this;
	}
}

/**
 * Build database and table structure
 */
class DatabaseTableBuilder{
	private $arrayStructure = array();
	private $rowStructure = array();
	private $selectedColumn;
	private $needToDrop = false;
	
	/* Create structure along with initial record 
	 * If the table already have some record, than this data will not be inserted
	 * @param array $structure
	 */
	public function newInitialRow(...$structure){
		$this->rowStructure["simple"][] = $structure;
	}
	
	/* Create structure along with initial record 
	 * If the table already have some record, than this data will not be inserted
	 * @param DatabaseRowInput $structure
	 */
	public function newInitialRowAdvanced($structure){
		if(!is_a($structure,"DatabaseRowInput")) throw new DatabaseError("Please use DatabaseRowInput as a structure!");
		$this->rowStructure["advance"][] = clone $structure;
		$structure->clearStructure();
	}
	
	/* DANGER: This function will DROP the table and start new fresh table */
	public function dropTable(){
		$this->needToDrop = true;
	}
	
	public function addColumn($name, $type="TEXT"){
		if(strlen($name) > 50) throw new DatabaseError("Max length for column name is 50 chars");
		$this->selectedColumn = $name;
		$this->arrayStructure[$this->selectedColumn] = array($type,false,false,"");
		return $this;
	}
	
	public function __construct(){
		return $this;
	}
	
	public function selectColumn($name){
		if(strlen($name) > 50) throw new DatabaseError("Max length for column name is 50 chars");
		if(!isset($this->arrayStructure[$name])) throw new DatabaseError("Column not found");
		$this->selectedColumn = $name;
		return $this;
	}
	
	public function setAsPrimaryKey(){
		if($this->selectedColumn == "") throw new DatabaseError("Please select the column!");
		foreach($this->arrayStructure as $key=>$data){
			$this->arrayStructure[$key][1] = ($this->selectedColumn == $key);
		}
		return $this;
	}
	
	public function removePrimaryKey(){
		if($this->selectedColumn == "") throw new DatabaseError("Please select the column!");
		$this->arrayStructure[$this->selectedColumn][1] = false;
		return $this;
	}
	
	public function allowNull($bool){
		if($this->selectedColumn == "") throw new DatabaseError("Please select the column!");
		$this->arrayStructure[$this->selectedColumn][2] = $bool;
		return $this;
	}
	
	public function defaultValue($str){
		if($this->selectedColumn == "") throw new DatabaseError("Please select the column!");
		$this->arrayStructure[$this->selectedColumn][3] = $str;
		return $this;
	}
	
	public function setType($type){
		if($this->selectedColumn == "") throw new DatabaseError("Please select the column!");
		$this->arrayStructure[$this->selectedColumn][0] = $type;
		return $this;
	}
	
	public function getStructure(){
		return($this->arrayStructure);
	}
	
	public function getInitialRow(){
		return($this->rowStructure);
	}
	
	public function needToDropTable(){
		return($this->needToDrop);
	}
}

/**
 * Database operation. 
 * Use it as Procedural style.
 */ 
class Database{
	private static $cache = array();
	
	/**
	 * Mysql database link
	 * @var mysqli
	 */
	public static $link;
	
	private static function query($query){		
		$new_query = "";
		$last_occurence = 0;
		foreach(func_get_args() as $key => $param){
			if($key == 0) continue;
			$occurence = strpos($query, "?",$last_occurence);
			if($occurence !== false){
				$temp = substr($query,$last_occurence,$occurence - $last_occurence + 1);
				$temp = str_replace("?",mysqli_real_escape_string(self::$link,$param),$temp);
				$new_query .= $temp;			
				$last_occurence = $occurence + 1;
			}else
				break;
		}
		if($last_occurence < strlen($query))
			$new_query .= substr($query,$last_occurence);
		
		$query = $new_query;
		unset($new_query);
		
		switch(strtoupper(explode(" ",$query)[0])){
			case "SELECT":
			case "SHOW":
				break;
			default:
				self::$cache = array();	
				if(defined("DB_DEBUG")){
					file_put_contents(__ROOTDIR . "/db.log","CACHE PURGED\r\n",FILE_APPEND);
				}
		}
		//See Database caching performance
		if(defined("DB_DEBUG")){
			file_put_contents(__ROOTDIR . "/db.log","$query\r\n",FILE_APPEND);
		}
		$r = mysqli_query(self::$link,$query);
		if(!$r){
			if(mysqli_errno(self::$link) == "2014"){
				/* Perform a reconnect */
				mysqli_close(self::$link);
				
				self::$link = mysqli_connect(ConfigurationDB::$host,ConfigurationDB::$username,ConfigurationDB::$password,ConfigurationDB::$database_name);
				if(!self::$link)
					throw new DatabaseError(mysqli_connect_error(), "Anyway, PuzzleOS only support MySQL server. Please re-configure database information in config.php");
				self::$link->set_charset("utf8");
				
				self::$cache = array();
				
				$r = mysqli_query(self::$link,$query);
				if(!$r){
					throw new DatabaseError('Could not execute('.mysqli_errno(self::$link).'): ' . mysqli_error(self::$link), $query);
				}
			}else
				throw new DatabaseError('Could not execute('.mysqli_errno(self::$link).'): ' . mysqli_error(self::$link), $query);
		}
		return ($r);
	}

	private static function verifyExecCaller($filename,$query){
		//This function is a protection to prevent database violation
		$filename = str_replace(__ROOTDIR,"",str_replace("\\","/",$filename));
		if($filename == "/applications/admin/installer.php") return(true); //Special Request
		if($filename == "/debug.php") return(true); //Special Request
		$filename = explode("/",$filename);
		$directory = $filename[1];
		switch($directory){
			case "debug.php":
			case "appFramework.php":
			case "services.php":
			case "database.php":
			case "systables.php":
				return(true);
			case "configman.php":
				if((preg_match('/`multidomain_config`/',$query))) return(true); break;
			case "userdata.php":
			case "defines.php":
				if((preg_match('/`userdata`/',$query))) return(true); break;
			case "applications":
				$appname = isset($filename[2])?$filename[2]:"";
				
				if(!file_exists(__ROOTDIR . "/applications/$appname/manifest.ini")) throw new DatabaseError("Application do not have manifest!");
				$manifest = parse_ini_file(__ROOTDIR . "/applications/$appname/manifest.ini");
				
				$appname = $manifest["rootname"];
				
				if((preg_match('/app_'.$appname.'_/',$query))) return(true);
			default:
				return(false);
		}
		return(false);
	}
	private static function verifyCaller($filename,$table){
		//This function is a protection to prevent database violation
		$filename = str_replace(__ROOTDIR,"",str_replace("\\","/",$filename));
		if($filename == "/applications/admin/installer.php") return(true); //Special Request
		if($filename == "/debug.php") return(true); //Special Request
		$filename = explode("/",$filename);
		$directory = $filename[1];
		switch($directory){
			case "debug.php":
			case "appFramework.php":
			case "services.php":
			case "database.php":
			case "systables.php":
				return(true);
			case "configman.php":
				if($table == "multidomain_config") return(true); break;
			case "userdata.php":
			case "defines.php":
				if($table == "userdata") return(true); break;
			case "applications":
				$appname = isset($filename[2])?$filename[2]:"";
				
				if(!file_exists(__ROOTDIR . "/applications/$appname/manifest.ini")) throw new DatabaseError("Application do not have manifest!");
				$manifest = parse_ini_file(__ROOTDIR . "/applications/$appname/manifest.ini");
				
				$appname = $manifest["rootname"];
				if((preg_match('/app_'.$appname.'_/',$table))) return(true);
			default:
				return(false);
		}
		return(false);
	}

	private static function colAI($table){
		if(isset(self::$cache["colAI"][$table])) return self::$cache["colAI"][$table];
		$res = self::query("show columns from `?` where extra like '%auto_increment%';", $table);
		if(! $res ){
		  throw new DatabaseError('DB -> Could not get data: ' . mysqli_error($res));
		}
		$n = 0;
		$arrayL = array();		
		while($row = mysqli_fetch_array($res))
		{
			$arrayL[$n] = $row[0];
			$n++;
		}
		self::$cache["colAI"][$table] = $arrayL;
		return($arrayL);
	}
	
	private static function colNum($table){
		if(isset(self::$cache["colNum"][$table])) return self::$cache["colNum"][$table];
		$res = self::query("show columns from `?`;", $table);
		if(! $res ){
		  throw new DatabaseError('DB -> Could not get data: ' . mysqli_error($res));
		}
		$count = mysqli_num_rows($res);
		self::$cache["colNum"][$table] = $count;
		return($count);
	}
	
	private static function colList($table){
		if(isset(self::$cache["colList"][$table])) return self::$cache["colList"][$table];
		$res = self::query("show columns from `?`;", $table);
		if(! $res ){
		  throw new DatabaseError('DB -> Could not get data: ' . mysqli_error($res));
		}
		$n = 0;
		$arrayL;
		while($row = mysqli_fetch_array($res))
		{
			$arrayL[$n] = $row[0];
			$n++;
		}
		self::$cache["colList"][$table] = $arrayL;
		return($arrayL);
	}
	
	private static function colType($table,$col){
		if(isset(self::$cache["colType"][$table.$col])) return self::$cache["colType"][$table.$col];		
		$res = self::query("show columns from `?` WHERE `Field`='?';", $table, $col);
		if(! $res ){
		  throw new DatabaseError('DB -> Could not get data: ' . mysqli_error($res));
		}
		$n = 0;
		$type;
		while($row = mysqli_fetch_array($res))
		{
			$type = $row["Type"];
			break;
		} 
		self::$cache["colType"][$table.$col] = $type;
		return($type);
	}
	
	private static function colDefVal($table,$col){
		if(isset(self::$cache["colDefVal"][$table.$col])) return self::$cache["colDefVal"][$table.$col];	
		$res = self::query("show columns from `?` WHERE `Field`='?';", $table, $col);
		if(! $res ){
		  throw new DatabaseError('DB -> Could not get data: ' . mysqli_error(self::$link));
		}
		$n = 0;
		$type;
		while($row = mysqli_fetch_array($res))
		{
			$type = $row["Default"];
			break;
		} 
		self::$cache["colDefVal"][$table.$col] = $type;
		return($type);
	}
	
	/**
	 * Get last Id or any value from specific column
	 * @param string $table Table Name
	 * @param string $col Column Name
	 * @param string $arg Additional custom parameter
	 * @param string $param Additional custom parameter
	 * @return string
	 */
	public static function getLastId($table,$col,$arg = "",...$param){		
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);				
		unset($caller);
		if(!isset(self::$cache["getLastId"][$table.$col])){
			$r = self::query("SELECT MAX(`?`) FROM `?` $arg",$col,$table,...$param);
			if( !$r ) throw new DatabaseError('DB -> Could not get data: ' . mysqli_error(self::$link));
			while($row = mysqli_fetch_array($r, MYSQLI_ASSOC)){
				self::$cache["getLastId"][$table.$col] = $row;
				return($row["MAX(`".$col."`)"]);
			}
		}else{
			return(self::$cache["getLastId"][$table.$col]["MAX(`".$col."`)"]);
		}
	}
	
	/**
	 * Read a single record.
	 * @param string $table Table Name
	 * @param string $column Column Name
	 * @param string $findByCol Column need to be matched
	 * @param string $findByVal Value inside $findByCol need to be matched
	 * @return string
	 */ 
	public static function read($table,$column,$findByCol,$findByVal){
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);				
		unset($caller);
		if(!isset(self::$cache["read"][$table.$findByCol.$findByVal])){
			$retval = self::query("SELECT * FROM `?` WHERE `?`='?' LIMIT 1;", $table, $findByCol, $findByVal);
			if( !$retval ) throw new DatabaseError('DB -> Could not get data: ' . mysqli_error(self::$link));
			while($row = mysqli_fetch_array($retval, MYSQLI_ASSOC)){
				self::$cache["read"][$table.$findByCol.$findByVal] = $row;
				return($row[$column]);
			}
		}else{
			return(self::$cache["read"][$table.$findByCol.$findByVal][$column]);
		}
	}
	
	/**
	 * Read a single record with custom argument.
	 * @param string $table Table Name
	 * @param string $column Column Name
	 * @param string $arg Additional custom parameter
	 * @param string $param Additional custom parameter
	 * @return string
	 */ 
	public static function readArg($table,$column,$arg = "",...$param){
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		$c_param = serialize($param);
		if(!isset(self::$cache["readArg"][$table.$arg.$c_param])){
			$retval = self::query("SELECT * FROM `?` ".$arg.";", $table, ...$param);
			if(!$retval) throw new DatabaseError('DB -> Could not get data: ' . mysqli_error(self::$link));
			while($row = mysqli_fetch_array($retval, MYSQLI_ASSOC)){
				self::$cache["readArg"][$table.$arg.$c_param] = $row;
				return(self::$cache["readArg"][$table.$arg.$c_param][$column]);
			}
			self::$cache["readArg"][$table.$arg.$c_param] = [];
		}else{
			return(self::$cache["readArg"][$table.$arg.$c_param][$column]);
		}
	}
		
	/**
	 * Write a new record
	 * @param string $table Table Name
	 * @param array $array array(field1,field2,field3,..); Field will be discarded if column has default value.
	 * @return bool
	 */ 
	public static function newRow($table,...$array){
		if(!is_array($array[0])){
			//Check for another input
			if(func_num_args()<2) throw new PuzzleError("Input must be more than two argument");
		}else{
			$array = $array[0];
		}		
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		//$table : The table name
		//$array : {field1,field2,field3,...}; Based on the column available.
		//AUTO_INCREACEMENT column will be ignored
		//If Default on while field is empty will be discarded too.
		//If default on while field isn't empty, won't be discarded.
		$fList = "";
		$cList = "";
		$temp = self::colList($table);
		$AIcol = self::colAI($table); //Coloumn that enables AUTO_INCREACEMENT
		for($i = 0, $n=0; $i<self::colNum($table); $i++){
			if(!in_array($temp[$i],$AIcol)){
				if(!(($array[$n] == "") && (self::colDefVal($table,$temp[$i])!=""))){
					if($array[$n] === NULL){
						$fList .= "NULL,";
					}else
						$fList .= "'".self::escapeStr($array[$n])."',";
					$cList .= "`".$temp[$i]."`,";
				}
				$n++;
			}
		}
		return (self::query("INSERT INTO `".$table."` (".rtrim($cList, ",").") VALUES (".rtrim($fList, ",").");"));
	}
	
	/**
	 * Update database row using DatabaseRowInput
	 * @param string $table 
	 * @param DatabaseRowInput $row_input 
	 * @param string $findByCol 
	 * @param string $findByVal 
	 * @return bool
	 */
	public static function updateRowAdvanced($table,$row_input,$findByCol,$findByVal){
		if(!is_a($row_input,"DatabaseRowInput")) throw new DatabaseError("Please use DatabaseRowInput for the structure!");
		
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		
		$findByCol = self::escapeStr($findByCol);
		$findByVal = self::escapeStr($findByVal);
		$structure = $row_input->getStructure();
		
		$data = "";
		
		foreach($structure as $column=>$value){
			if($column == "") continue;
			if($value === NULL)
				$data .= "`$column`=NULL,";
			else
				$data .= "`$column`='".self::escapeStr($value)."',";
			
		}
		
		$data =	rtrim($data,",");
		
		return (self::query("UPDATE `$table` SET $data WHERE `$findByCol`='$findByVal'"));
	}
	
	/**
	 * Write a new record by specifiying column name
	 * @param string $table Table Name
	 * @param DatabaseRowInput $row_input
	 * @return bool
	 */ 
	public static function newRowAdvanced($table,$row_input){
		if(!is_a($row_input,"DatabaseRowInput")) throw new DatabaseError("Please use DatabaseRowInput for the structure!");
		
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		
		$structure = $row_input->getStructure();
		$col_list = "";
		$value_list = "";
		foreach($structure as $column=>$value){
			if($column == "") continue;
			$col_list .= "`$column`, ";
			if($value === NULL)
				$value_list .= "NULL, ";
			else
				$value_list .= "'".self::escapeStr($value)."', ";
		}
		
		$col_list = rtrim($col_list,", ");
		$value_list = rtrim($value_list,", ");
		
		$row_input->clearStructure();
		
		return (self::query("INSERT INTO `".$table."` ($col_list) VALUES ($value_list);"));
	}
	
	/**
	 * Delete a record
	 * @param string $table Table Name
	 * @param string $findByCol Column need to be matched
	 * @param string $findByVal Value inside $findByCol need to be matched
	 * @return bool
	 */ 
	public static function deleteRow($table,$findByCol,$findByVal){
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		//$val = preg_match('/int/',self::colType($table,$findByCol)) ? $findByVal : "'".$findByVal."'";
		return(self::query("DELETE FROM `?` WHERE `?`='?';", $table, $findByCol, $findByVal));
	}

	/**
	 * Delete a record with custom argument
	 * @param string $table Table Name
	 * @param string $arg Table Name
	 * @param array ...$param
	 * @return bool
	 */ 
	public static function deleteRowArg($table,$arg,...$param){
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		return(self::query("DELETE FROM `?` ".$arg.";", $table, ...$param));
	}
	
	/**
	 * NOTE: BE CAREFUL! CANNOT BE UNDONE!
	 * Drop a table. 
	 * @param string $table Table Name
	 * @return bool
	 */ 
	public static function dropTable($table){
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		return(self::query("DROP TABLE `?`;", $table));
	}
	
	/**
	 * Execute a single query.
	 * @param string $query For better security, use '?' as a mark for each parameter.
	 * @param object ...$param Will replace the '?' as parameterized queries
	 * @return bool
	 */ 
	public static function exec($query, ...$param){
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyExecCaller($f,$query); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		if(!isset(self::$cache["exec"][$query.serialize($param)])){
			self::$cache["exec"][$query.serialize($param)] = self::query($query, ...$param);
		}
		//return (self::query($query, ...$param));
		return self::$cache["exec"][$query.serialize($param)];
	}
	
	/**
	 * Escape a string for database query
	 * @param string $str For better security, use '?' as a mark for each parameter.
	 * @return string
	 */ 
	public static function escapeStr($str){
		return(mysqli_real_escape_string(self::$link,$str));
	}
	
	/**
	 * Check if table is exists
	 * @param string $table Table name
	 * @return bool
	 */ 
	public static function isTableExist($table){
		if($table == "") throw new DatabaseError("Please fill the table name!");
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		
		$q = mysqli_query(self::$link,"SHOW TABLES");
		while($r = mysqli_fetch_array($q)){
			if($r[0] == strtolower($table)) return true;
		}
		return false;
	}
	
	/**
	 * Read all record in a table
	 * @param string $table Table name
	 * @param string $options Additional queries syntax. e.g. "SORT ASC BY `id`"
	 * @param array $param 
	 * @return array
	 */ 
	public static function readAll($table,$options = "", ...$param){
		if($table == "") throw new DatabaseError("Please fill the table name!");
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
		if(!isset(self::$cache["readAll"][$table.$options.serialize($param)])){
			$array = new stdClass();
			$array->data = array();
			$array->num = 0;
			$retval = self::query("SELECT * FROM `$table` $options;", ...$param);
			if(! $retval ){
			  throw new DatabaseError('DB -> Could not get data: ' . mysqli_error(self::$link));
			}
			while($row = mysqli_fetch_array($retval, MYSQLI_ASSOC)){ 
				$array->data[$array->num] = $row;
				$array->num++;
			}
			self::$cache["readAll"][$table.$options.serialize($param)] = $array;
			return($array);
		}else{
			return(self::$cache["readAll"][$table.$options.serialize($param)]);
		}
	}	
	
	/**
	 * Create or change table structure
	 * @param string $table Table name
	 * @param DatabaseTableBuilder $structure
	 * @return array
	 */ 
	public static function newStructure($table,$structure){
		set_time_limit(30);
		if(is_a($structure,"DatabaseTableBuilder")){
			$initialData = $structure->getInitialRow();
			$needToDrop = $structure->needToDropTable();
			$structure = $structure->getStructure();
		}else{
			throw new DatabaseError("Please use DatabaseTableBuilder for the structure!");
		}
		
		$caller = (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1));
		$f = $caller[0]["file"];
		$r = self::verifyCaller($f,$table); if(!$r) throw new DatabaseError("Database access denied! " . $f . " on line " . $caller[0]["line"]);
		unset($caller);
				
		if(!isset(self::$cache['Checksum'])){
			if(file_exists(__ROOTDIR . "/cache/database_track.json")){
				self::$cache['Checksum'] = json_decode(file_get_contents(__ROOTDIR . "/cache/database_track.json"),true);
			}else{
				self::$cache['Checksum'] = array();
			}
		}

		/* Checking checksum */
		$old_checksum = self::$cache['Checksum'][$table];
		$current_checksum = hash("sha256",serialize($structure));
		$write_cache_file = false;
		if($old_checksum != ""){
			//Old table, new structure
			if($current_checksum == $old_checksum){
				if(self::isTableExist($table)) {
					set_time_limit(30);
					return true;
				}
				//Checksum is found, but table is not exists
				$q = false;
				$insertData = true;
			}else{
				self::$cache['Checksum'][$table] = $current_checksum;
				$write_cache_file = true;
				$q = self::isTableExist($table);
				/* Drop table if necessary */
				if($needToDrop && $q){
					self::query("DROP TABLE `?`;", $table);
					$insertData = true;
					$q = false;
				}
			}
		}else{
			//Brand new table
			self::$cache['Checksum'][$table] = $current_checksum;
			$write_cache_file = true;
			$insertData = true;
			$q = self::isTableExist($table);
			if($q) $insertData = false;
		}
		
		if(!$q){			
			//Create Table
			$query = "CREATE TABLE `".$table."` ( ";
			foreach($structure as $k=>$d){
				if($d[1]) $pkey = $k;
				$ds = (($d[3] !== "0" && empty($d[3])) ? "" : "DEFAULT ".$d[3]);
				$ds = ($d[3] === "AUTO_INCREMENT"? "AUTO_INCREMENT" : $ds);
				
				$query .= "`".$k."` ".strtoupper($d[0])." ".($d[2] === true ? "NULL" : "NOT NULL")." ".$ds.",";
			}
			if($pkey!=""){
				$query .= "PRIMARY KEY (`".$pkey."`)";
			}else{
				$query = rtrim($query,",");
			}
			$query .= ") COLLATE='utf8_general_ci' ENGINE=InnoDB;";
			
			if(defined("DB_DEBUG")){
				file_put_contents(__ROOTDIR . "/db.log","$query\r\n",FILE_APPEND);
			}
			if(mysqli_query(self::$link,$query)){
				if($insertData){
					foreach($initialData["simple"] as $row){
						self::newRow($table,$row);
					}
					foreach($initialData["advance"] as $row){
						self::newRowAdvanced($table,$row);
					}
				}
				if($write_cache_file) 
					file_put_contents(__ROOTDIR . "/cache/database_track.json",json_encode(self::$cache['Checksum']));
				set_time_limit(30);
				return true;
			}else{
				throw new DatabaseError(mysqli_error(self::$link), $query);
			}
		}else{
			//Update Tables
			$query = "";
			$a = [];
			$cpkey = "";
			//Fetching table data, and remove auto_incement column
			$q = mysqli_query(self::$link,"show columns from `$table`");
			while($d = mysqli_fetch_array($q)){
				$fd = ($d["Extra"]=="auto_increment" ? "AUTO_INCREMENT" : $d["Default"]);
				$a[$d["Field"]] = array(strtoupper($d["Type"]),($d["Null"] == "YES"?true:false),1,$fd,false);
				if($d["Key"] == "PRI") $cpkey = $d["Field"];
				if($fd == "AUTO_INCREMENT"){
					$query .= "ALTER TABLE `".$table."` CHANGE COLUMN `".$d["Field"]."` `".$d["Field"]."` ".strtoupper($d["Type"])." ".($d["Null"] === true ? "NULL" : "NOT NULL")." ;\n";
					$a[$d["Field"]][4] = true;
				}
			}
			$pkey = "";
			//DROP Column
			foreach($a as $k=>$b){
				if(!isset($structure[$k])){
					if($cpkey == $k) $cpkey = "";
					$query .= "ALTER TABLE `".$table."` DROP COLUMN `".$k."`;\n";
				}
			}
			//ALTER Table
			foreach($structure as $k=>$d){
				if($d[1]) $pkey = $k;
				if($a[$k][2] != 1){
					//Add new column					
					$ds = (($d[3] !== "0" && empty($d[3])) ? "" : "DEFAULT '".$d[3]."'");
					$ds = ($d[3] === "AUTO_INCREMENT"? "AUTO_INCREMENT" : $ds);
					if($d[3] === "AUTO_INCREMENT"){
						//In this part, we don't care much about null value, since auto_increment always not_null.
						//Auto increment value, will be automatially be the primary key
						if($cpkey != "") {
							$query .= "ALTER TABLE `".$table."` DROP PRIMARY KEY;\n";
							$cpkey = "";
						}
						$pkey = "";
						$query .= "ALTER TABLE `".$table."` ADD COLUMN `".$k."` ".strtoupper($d[0])." NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY(`$k`);\n";
					}else{
						$query .= "ALTER TABLE `".$table."` ADD COLUMN `".$k."` ".strtoupper($d[0])." ".($d[2] === true ? "NULL" : "NOT NULL")." $ds;\n";
					}
					unset($query1);
				}else{
					//Change old column
					$st = preg_match("/".strtoupper($d[0])."/",$a[$k][0]);
					$an = $d[2] == $a[$k][1];
					$def = $a[$k][3] == strtoupper($d[3]);
					if(!$st || !$an || !$def || $a[$k][4]){
						$ds = (($d[3] !== "0" && empty($d[3])) ? "" : "DEFAULT '".$d[3]."'");
						$ds = ($d[3] === "AUTO_INCREMENT"? "AUTO_INCREMENT" : $ds);
					
						if($d[3] === "AUTO_INCREMENT"){
							//In this part, we don't care much about null value, since auto_increment always not_null.
							//Auto increment value, will be automatially be the primary key
							if($cpkey != "") {
								if($cpkey != $k) $query .= "ALTER TABLE `".$table."` DROP PRIMARY KEY, ADD PRIMARY KEY(`$k`);\n";
								$cpkey = "";
							}else{
								$query .= "ALTER TABLE `".$table."` ADD PRIMARY KEY(`$k`);\n";
							}
							$pkey = "";
							$query .= "ALTER TABLE `".$table."` CHANGE COLUMN `".$k."` `".$k."` ".strtoupper($d[0])." NOT NULL AUTO_INCREMENT;\n";
						}else{
							$query .= "ALTER TABLE `".$table."` CHANGE COLUMN `".$k."` `".$k."` ".strtoupper($d[0])." ".($d[2] === true ? "NULL" : "NOT NULL")." $ds;\n";
						}
												
					}
				}
			}
			//Change Primary Key
			/* Correct order
			 * Add primary key first then add AI
			 * When removing primary key, make sure that table is not AI anymore
			 */
			if($cpkey != $pkey){
				//Check if db have primary key
				$needtodropkey = ($cpkey != "");
				if($needtodropkey) {
					if($pkey != "") $addpk = ", ADD PRIMARY KEY (`".$pkey."`)";
					$pri_ai_col = mysqli_fetch_array(mysqli_query(self::$link,"show columns from `?` where `Extra` = 'auto_increment' AND `Key`='PRI';",$table));
					if(count($pri_ai_col) < 1)
						$query .= "ALTER TABLE `".$table."` DROP PRIMARY KEY$addpk;\n";
					else{
						//Addition is disabling the AUTO_INCREMENT before DROP PRIMARY Key.
						$addition = "ALTER TABLE `$table` CHANGE COLUMN `".$pri_ai_col["Field"]."` `".$pri_ai_col["Field"]."` ".$pri_ai_col["Type"]." ".($pri_ai_col["Null"] = "NO"?"NOT NULL":"NULL").";\n";
						$query .= $addition . "ALTER TABLE `".$table."` DROP PRIMARY KEY$addpk;\n";
					}
				}else{
					if($pkey != "") $query .= "ALTER TABLE `".$table."` ADD PRIMARY KEY (`".$pkey."`)";
				}
			}
			//Execution
			if($query != ""){
				if(defined("DB_DEBUG")){
					file_put_contents(__ROOTDIR . "/db.log","$query\r\n",FILE_APPEND);
				}
				if(!mysqli_multi_query(self::$link,$query)) throw new DatabaseError(mysqli_error(self::$link), $query);
				do {
					if($result = mysqli_store_result(self::$link)){
						mysqli_free_result($result);
					}
				} while(mysqli_next_result(self::$link));
			}

			//Reorder column position
			$query = "";
			$request = mysqli_query(self::$link,"show columns from `$table`");
			$tableContent = array();
			while($r = mysqli_fetch_array($request)){
				$tableContent[$r["Field"]] = $r;
			}
			$firstCol = true;
			$prevCol;
			foreach($structure as $k=>$d){
				$i = $tableContent[$k];
				$type = $i["Type"];
				$nullon = ($i["Null"] == "YES" ? "NULL" : "NOT NULL");
				$def = ($i["Default"] != "" ? "DEFAULT '".$i["Default"]."'" : $i["Extra"]);
				$pos = ($firstCol ? "FIRST" : "AFTER `$prevCol`");
				$query .= "ALTER TABLE `$table` CHANGE COLUMN `$k` `$k` $type $nullon $def $pos;\n";
				$firstCol = false;
				$prevCol = $k;
			}
			if(defined("DB_DEBUG")){
				file_put_contents(__ROOTDIR . "/db.log","$query\r\n",FILE_APPEND);
			}
			if(!mysqli_multi_query(self::$link,$query)) throw new DatabaseError(mysqli_error(self::$link), $query);
			do {
				if($result = mysqli_store_result(self::$link)){
					mysqli_free_result($result);
				}
			} while(mysqli_next_result(self::$link));
			
			if($write_cache_file) 
				file_put_contents(__ROOTDIR . "/cache/database_track.json",json_encode(self::$cache['Checksum']));
			set_time_limit(30);
			return true;
		}
	}
}

Database::$link = mysqli_connect(ConfigurationDB::$host,ConfigurationDB::$username,ConfigurationDB::$password,ConfigurationDB::$database_name);
if(!Database::$link)
	throw new DatabaseError(mysqli_connect_error(), "Anyway, PuzzleOS only support MySQL server. Please re-configure database information in config.php");
?>