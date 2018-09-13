<?php
defined("__POSEXEC") or die("No direct access allowed!");
__requiredSystem("2.0.2") or die("You need to upgrade the system");
/**
 * PuzzleOS
 * Build your own web-based application
 * 
 * @package      maral.puzzleos.core.phpmailer
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2018 MARAL INDUSTRIES
 * 
 * @software     Release: 2.0.2
 */

if(__getURI("app") == $appProp->appname) redirect("");

require(my_dir("/class/PHPMailerAutoload.php"));
 
/*
 * Mailer Instance
 */
class Mailer extends PHPMailer{
	/*
	 * This mailer class uses PHPMailer. Access the $instance for more advanced features from PHPMailer
	 * like DKIM, signing etc. Mailer class already set some things up from config.php like SMTP, auth, etc.
	 */
	
	public function __set($name,$value) {
		switch($name){
		case "addRecipient":
			$this->addAddress($value);
			break;
		case "addReplyTo":
			$this->addReplyTo($value);
			break;
		case "addCC":
			$this->addCC($value);
			break;
		case "addBCC":
			$this->addBCC($value);
			break;
		case "attachFile":
			$this->addAttachment(IO::physical_path($value));
			break;
		case "subject":
			$this->Subject = $value;
			break;
		case "body":
			$this->Body = $value;
			break;
		case "altBody":
			$this->AltBody = $value;
			break;
		}
	}
	
	public function __get($value){
		switch($value){
		case "error":
			return($this->ErrorInfo);
		}
	}
	
	function __construct() {
		$this->setFrom(POSConfigMailer::$From,POSConfigMailer::$Sender);
		
		$lang = explode("-",LangManager::getDisplayedNow())[0];
		$this->setLanguage($lang);
		
		//Future, configure SMTP here
		if(!POSConfigMailer::$UsePHP){
			$this->isSMTP();
			$this->Host = POSConfigMailer::$smtp_host;
			$this->SMTPAuth = POSConfigMailer::$smtp_use_auth;
			$this->Username = POSConfigMailer::$smtp_username;
			$this->Password = POSConfigMailer::$smtp_password;
			if($smtp["Encryption"]!="none")	$this->SMTPSecure = POSConfigMailer::$smtp_encryption;
			$this->Port = POSConfigMailer::$smtp_port;
		}
		
		//DKIM Stuff
		//See https://github.com/PHPMailer/PHPMailer/blob/master/examples/DKIM_gen_keys.phps for generating DKIM keys
		if(defined("__POSDKIM")){
			$this->DKIM_domain = __POSDKIM_DOMAIN;
			$this->DKIM_private = __POSDKIM_PEM;
			$this->DKIM_selector = __POSDKIM_SELECTOR;
			$this->DKIM_passphrase = __POSDKIM_PASS;
			$this->DKIM_identity = $this->From;
			$this->mailer->DKIM_copyHeaderFields = false;
			
			if(defined("__POSDKIM_EXTRA")){
				//Optionally you can add extra headers for signing to meet special requirements
				$this->mailer->DKIM_extraHeaders = ['List-Unsubscribe', 'List-Help'];
			}
		}
	}
	
	/*
	 * Send HTML based email also with AltBody for non HTML client
	 * @return bool
	 */
	public function sendHTML(){
		$this->isHTML(true);
		$r = $this->send();
		if(!$r){
			try{
				throw new PuzzleError("Mail:".$this->ErrorInfo);
			}catch($e){}
		}
	}
	
	/*
	 * Send plain-text email
	 * @return bool
	 */
	public function sendPlain(){
		$this->isHTML(false);
		$r = $this->send();
		if(!$r){
			try{
				throw new PuzzleError("Mail:".$this->ErrorInfo);
			}catch($e){}
		}
	}
}
?>
