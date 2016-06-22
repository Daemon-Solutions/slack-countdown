<?php
	// ****************************************
	//	
	// Author: Sandeep Jassal, Daemon Solutions Ltd.
	// 
	// checkout.php - this php script responds to a slack slash command to create a countdown message in slack
	//
	// ****************************************

	// include configuration settings
	require 'config.php';

	// create connection to the database
	$link = new mysqli($db_host,$db_user,$db_pass,$db);
	
	// grab the parameters posted by slack, and setup up some default variables
	$command = $_POST['command'];  //retrieve the slash command that was run
	$text = $_POST['text'];  // retrieve the parameters passed to the slash command
	$token = $_POST['token'];  // retrieve the slash command token
	$author = $_POST['user_name'];  // retrieve the user that ran the slash command
	$channel = $_POST['channel_id']; // retrieve the channel id in which the command was run
	$reply = '';  // set reply to empty string
	$post = false;  // set post flag to false

	// validate that requests are coming from the correct slack team by comparing
	// the token received against the token specified in the config file
	if($token !== $countdown_token){
		$msg = "You are not authorised to use this command";
		die($msg);
		echo($msg);
	} else {
		if($text == ''){
			$msg = "You didn't specify any countdown details";
			die($msg);
			echo($msg);
		}

		//  extract command parameters and validate that the correct number of been passed
		$params = explode(' -', $text);

		// parse parameters to check whether list, delete or a new countdown command is being run
		if( substr($params[0], 1, 4) == 'list' ){
			$sql = "SELECT * from countdown where date >= CURDATE() and author='".$author."';";  // query to select all active countdowns created by current user
			$result = $link->query($sql);  // execute query

			if( $result->num_rows > 0 ){
				while( $row = $result->fetch_assoc()){  //iterate over result set if rows are returned
					//build up list of countdowns found by the query to send back to slack
					$reply = $reply."\n".$row['id']." » ".$row['event']." » ".date("d/m/Y", strtotime($row['date']))." » ".$row['url']." » ".$row['channel']." » ".$row['author']."\n";
				}

				$result->free();

				$ch = curl_init($webhook_url);	//setup curl 

				//build json array to transmit back to slack
				$payload = array(
					'channel' => '@'.$author,
					'username' => 'countdown-bot',
					'attachments' => array(
						array(
							'fallback' => $reply,
							'title' => 'Countdowns',
							'text' => $reply
						)
					),
				);

				$payloadEncoded = json_encode($payload);	//encode array into valid json string
				curl_setopt($ch, CURLOPT_POST, 1);	//specify that we want to send a POST request
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadEncoded);	// attached JSON string to POST field
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));	//set the content type to application/json

				$result = curl_exec($ch);	//execute curl to post message to slack and save any response codes

			} else {
				//response of no countdowns are found for current users
				echo "No countdowns set";
			}

		} elseif( substr($params[0], 1, 6) == 'delete' ){
			$toDelete = substr($params[0], 8);  //extract row id for countdown that is to be deleted
			$sql = "DELETE FROM countdown where id=".$toDelete." and author='".$author."';";  //query to delete specified countdown where the author matches the current user
			$link->query($sql);  //execute query

			if( $link->affected_rows == 1){
				echo "Countdown with id: ".$toDelete." has been deleted\n";  //respond with success message
			} else {
				echo "Error deleting countdown, please check id is correct and try again\n";  //respond with error message
			}

			$result->free();

		} else {  //assume that user is wishing to create a new countdown

			//check that correct number of parameters are being sent
			if(count($params) >= 2 && count($params) <= 3){
				$eventDate = substr($params[0], 6);  //extract countdown target date from parameters
				$eventDescription = substr($params[1], 6);  //extract countdown event description from parameters

				if(count($params) == 3){
					$imgURL = substr($params[2], 4);  //extract countdown image url if it has been specified
				} else {
					$imgURL = "";  // set image url to be empty string if no url has been specified
				}

			} else{ //respond with error if the correct number of parameters are not being passed
				$msg = "ERROR: usage /countdown -date dd/mm/yyyy -event description [-img img_url]";
				die($msg);
				echo($msg);
			}
			
			// query to insert new countdown into the database
			$sql = "INSERT INTO countdown (event, date, url, channel, author) VALUES ('".$eventDescription."', STR_TO_DATE('".$eventDate."', '%d/%m/%Y'), '".$imgURL."', '".$channel."', '".$author."');";
			$link->query($sql); //execute query
			if( $link->affected_rows == 1){
				$post = true;  //set post flag to true if success
			}else{
				$post = false;  //set post flag to false if error
				$reply = " There was a problem creating your countdown, please try again later";  //respond with error message
			}

			
			if($post == true){  //if there was a successful insertion of a new countdown then post countdown to slack in channel it was created in
				$datetimeEvent = date_create_from_format('d/m/Y', $eventDate);  //initialise datetime object with countdown target date
				$datetimeNow = date_create_from_format('d/m/Y', date("d/m/Y"));  //initialise datetime object with todays date
				$datediff = $datetimeNow->diff($datetimeEvent);  // work out the number of days from today to the taget date

				if( $datediff->format('%a') > 1 || $datediff->format('%a') == 0){  //check if the number of days is greater 1 or equal to 0
					$postText = $datediff->format('%a')." days to go until ".$eventDescription;  //format countdown message with correct grammar
				} else {
					$postText = $datediff->format('%a')." day to go until ".$eventDescription;  //format countdown message grammer when there is 1 day left on the countdown
				}

				$footer = 'countdown created by '.$author;  //create footer message notifying users who created the countdown

				$ch = curl_init($webhook_url);	//setup curl

				//build json array to transmit back to slack
				$payload = array(
					'channel' => $channel,
					'username' => 'countdown-bot',
					'attachments' => array(
						array(
							'title' => $postText,
							'image_url' => $imgURL,
							'footer' => $footer
						)
					),
				);

				$payloadEncoded = json_encode($payload);	//encode array into valid json string
				curl_setopt($ch, CURLOPT_POST, 1);			//specify that it is a POST request
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadEncoded);	//attach json string to POST field
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));	//set content type to application/json

				$result = curl_exec($ch);	//execute curl and save any response codes
			}

			//clear variables
			unset($postText);
			unset($payload);
			unset($ch);
			
			//respond to slack with success or failure of checkin, this iwll be visible to the user only
			echo $reply;
		}
	}

	//close connection to the database
	$link->close();

?>
