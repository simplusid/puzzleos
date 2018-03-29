<!DOCTYPE html>
<html>
	<head>
		<?php $tmpl->dumpHeaders(); ?>
		<title><?php echo $tmpl->title;?> - <?php echo __SITENAME;?></title>
		<style>
			html, body{
				width:100%;
				height:100%;
			}
			body{
				padding:0px 20px;
			}
		</style>
	</head>
	<body>
		<div style="float:right;"><?php $tmpl->navigation->loadView("login_bar");?></div>
		<?php if($tmpl->http_code == 200) $tmpl->app->loadMainView(); ?>
		<?php if($tmpl->http_code == 404) echo("404 Not Found"); ?>
		<?php if($tmpl->http_code == 403) redirect("users?redir=".$_SERVER['REQUEST_URI']); ?>
		<?php echo $tmpl->postBody;?>
		<?php Prompt::printPrompt(); ?>
	</body>
</html>
