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
	$post = false;

	//validate that requests are coming from the correct slack team
	if($token !== $checkin_token){
		$msg = "You are not authorised to use this command";
		die($msg);
		echo($msg);
	} else {
		if($text == ''){
			$msg = "You didn't specify a location";
			die($msg);
			echo($msg);
		}

		$params = explode('-', $text);

		if(count($params) == 3 && $user == 'sandeep'){
			echo "checkin for another user\n";
			$user = substr($params[1], 5);
			$text = substr($params[2], 9);
		} elseif (count($params) > 1){
			$msg = "ERROR: usage /checkin [location]";
			die($msg);
			echo($msg);
		}

		//retrieve any existing check ins for the current day
		$sql = "SELECT * FROM location where loc_date = CURDATE() and user = '".$user."'";
		$result = $link->query($sql);

		//test if any results were returned
		if( $result->num_rows > 0){
			$row = $result->fetch_assoc();

			//check if current checkin request already exists in the db
			if( $row['loc'] == $text){
				$post = false;
				$reply = " You have already checked into this location";
			}else{
				//update existing checkin if the location has changed
				$sql = "UPDATE location set loc = '".$text."' where id = ".$row['id'];
				$result->free();
				$link->query($sql);
				if( $link->affected_rows == 1){
					$post = true;
					$reply = " Check in Updated";
				}else{
					$post = false;
					$reply = " There was a problem updating your location, please try again later";
				}
			}
		}else{
			//insert new checkin to db
			$sql = "INSERT INTO location (loc_date, user, loc) VALUES (CURDATE(), '".$user."', '".$text."')";
			$link->query($sql);
			if( $link->affected_rows == 1){
				$post = true;
				$reply = " Location Logged";
			}else{
				$post = false;
				$reply = " There was a problem checking in your location, please try again later";
			}
		}

		//if there was a successful update or insertion of a new checkin then post checkin to slack
		if($post == true){
			$log = date("d/m/Y")." - ".$text;	//build message to post to slack

			$ch = curl_init($webhook_url);	//setup curl

			//build json array
			$payload = array(
				'username' => $user,
				'text' => $log
			);

			$payloadEncoded = json_encode($payload);	//encode array into valid json string
			curl_setopt($ch, CURLOPT_POST, 1);			//specify that it is a POST request
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadEncoded);	//attach json string to POST field
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));	//set content type to application/json

			$result = curl_exec($ch);	//execute curl and save any response codes
		}

		//clear variables
		unset($log);
		unset($payload);
		unset($ch);
		
		//respond to slack with success or failure of checkin, this iwll be visible to the user only
		echo $reply;
	}

	//close connection to the database
	$link->close();

?>