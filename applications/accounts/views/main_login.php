<?php 
defined("__POSEXEC") or die("No direct access allowed!");
__requiredSystem("1.2.2") or die("You need to upgrade the system");
/**
 * PuzzleOS
 * Build your own web-based application
 * 
 * @package      maral.puzzleos.core.users
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2017 MARAL INDUSTRIES
 * 
 * @software     Release: 1.1.1
 */

$language = new Language; $language->app = "users";
if(!isset($_GET["redir"])) $_GET["redir"] = "";
?>
<div style="display:table;width:100%;height:100%;max-width:480px;margin: auto;">
<div id="loginCtn" style="display:table-cell;vertical-align:middle;padding:20px;">
	<div style="font-weight:300;margin-bottom:20px;">
	<span style="font-size:20pt;font-weight:500;"><?php $language->dump("login")?></span>
	</div>
	<form onsubmit="$(this).find('button').prop('disabled',true);$(this).find('input').trigger('blur')" action="<?php echo __SITEURL?>/users/login" method="post" style="text-align:center;">
		<div class="input-group">
		  <span class="input-group-addon" id="sizing-addon1"><i class="fa fa-user"></i></span>
		  <input required name="user" autocomplete="off" value="<?php echo $_POST["user"]?>" <?php if($_POST["user"] == ""):?>autofocus<?php endif;?> type="text" class="form-control" placeholder="<?php $language->dump("username")?>" aria-describedby="sizing-addon1">
		</div><br>
		<div class="input-group">
		  <span class="input-group-addon" id="sizing-addon2"><i class="fa fa-key"></i></span>
		  <input required name="pass" autocomplete="off" type="password" class="form-control" <?php if($_POST["user"] != ""):?>autofocus<?php endif;?> placeholder="<?php $language->dump("password")?>" aria-describedby="sizing-addon2">
		</div><br>							
		<input type="hidden" name="redir" value="<?php echo ($_GET["redir"]!=""?htmlentities($_GET["redir"]):htmlentities($_POST["redir"]));?>">
		<input type="hidden" name="trueLogin" value="1">
		<button title="<?php $language->dump("login")?>" type="submit" class="btn btn-info"><?php $language->dump("login")?></button>
		<a href="<?php echo __SITEURL?>/users/forgot"><button title="<?php $language->dump("f_pass")?>" type="button" class="btn btn-link"><?php $language->dump("nh")?></button></a>
	</form><br><br>
</div>
</div>