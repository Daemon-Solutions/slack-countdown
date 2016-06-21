<?php

	// include configuration settings
	require 'config.php';

	//create connection to the database
	$link = new mysqli($db_host,$db_user,$db_pass,$db);
	
	//grab the parameters posted by slack
	$command = $_POST['command'];
	$text = $_POST['text'];
	$token = $_POST['token'];
	$user = $_POST['user_name'];
	$rowDate = '';
	$reply = '';

	//validate that requests are coming from the correct slack team
	if($token !== $whereis_token && $token !== $wherewas_token){
		$msg = "You are not authorised to use this command";
		die($msg);
		echo($msg);
	} else {

		//test if request is for location of all checked in users or for a specific user/wildcard
		if( strpos($text,'everyone') !== false){
			if( $command == '/wherewas'){
				$sql = "SELECT user, loc, loc_date from location where loc_date between subdate(curdate(), 7) and curdate() order by loc_date asc, user asc";
			} else {
				$sql = "SELECT user, loc, loc_date FROM location where loc_date = CURDATE() order by user";	//build sql to retrieve all check ins for today
			}
			$result = $link->query($sql); 	//execute sql and assign recordset output to var
			if( $result->num_rows > 0){		//test if any records were found
				//for each check in retrieved append to the response
				while( $row = $result->fetch_assoc()){
					if($rowDate != $row['loc_date']){
						$rowDate = $row['loc_date'];
						$reply = $reply."\n\n".date("d/m/Y", strtotime($row['loc_date']));
					}
					$reply = $reply."\n".$row['user']."\t»\t".$row['loc'];
				}
			}else {
				$reply = "No locations have been logged";		//set response if no check ins retrieved
			}
		}else {
			if( $command == '/wherewas'){
				$sql = "SELECT user, loc, loc_date from location where loc_date between subdate(curdate(), 7) and CURDATE() and user like '%".$text."%' order by loc_date asc";
			} else {
				// run wildcard query if request is for a specific user/wildcard and set responses appropriately
				$sql = "SELECT user, loc from location where loc_date = CURDATE() and user like '%".$text."%' order by loc_date asc, user asc";
			}
			$result = $link->query($sql);
			if( $result->num_rows > 0){
				while( $row = $result->fetch_assoc()){
					if($rowDate != $row['loc_date']){
						$rowDate = $row['loc_date'];
						$reply = $reply."\n\n".date("d/m/Y", strtotime($row['loc_date']));
					}
					$reply = $reply."\n".$row['user']."\t»\t".$row['loc'];
				}
			}else {
				$reply = "No locations have been logged for ".$text;
			}
		}

		$result->free();	//free resultset, good practice

		$ch = curl_init($webhook_url);	//setup curl 
	
		//create json response array with richly formatted attachment
		$payload = array(
			'channel' => '@'.$user,
			'username' => 'whereis_bot',
			'attachments' => array(
				array(
					'fallback' => $reply,
					'pretext' => ' ',
					'title' => 'Locations for '.$text,
					'fields' => array(
						array(
							'title' => 'Locations',
							'value' => $reply,
							'short' => true
						)
					),
					'color' => '#14928A'
				)
			),
		);

		$payloadEncoded = json_encode($payload);	//encode array into valid json string
		curl_setopt($ch, CURLOPT_POST, 1);	//specify that we want to send a POST request
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadEncoded);	// attached JSON string to POST field
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));	//set the content type to application/json

		$result = curl_exec($ch);	//execute curl to post message to slack and save any response codes
	
		//clear variables
		unset($rowDate);
		unset($reply);
		unset($payload);
		unset($ch);
	}

	//close db connection
	$link->close();
?>