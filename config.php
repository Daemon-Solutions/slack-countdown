<?php
	// ****************************************
	//	
	// Author: Sandeep Jassal, Daemon Solutions Ltd.
	// 
	// config.php - this php script contains configurable settings for the countdown.php/looper.php script
	//				amend this file to reflect your mysql and slack setup
	//
	// ****************************************

	$db_user 	=	"db_user";			// db user
	$db_pass 	=	"db_user_pas";			// db pass
	$db_host 	=	"localhost";		// db hostname
	$db 		=	"countdown";			// db
	$countdown_token = 'slack_token';	//token for coutdown slash command
	$webhook_url = 'webhook url';  //slack incoming webhook url
?>
