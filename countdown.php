<?php

	// include configuration settings
	require 'config.php';

	//create connection to the database
	$link = new mysqli($db_host,$db_user,$db_pass,$db);
	
	//grab the parameters posted by slack
	$command = $_POST['command'];
	$text = $_POST['text'];
	$token = $_POST['token'];
	$author = $_POST['user_name'];
	$channel = $_POST['channel_id'];
	$reply = '';
	$post = false;

	//validate that requests are coming from the correct slack team
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

		//extract command parameters and validate that the correct number of been passed
		$params = explode(' -', $text);

		if( substr($params[0], 1, 4) == 'list' ){
			$sql = "SELECT * from countdown where date >= CURDATE() and author='".$author."';";
			$result = $link->query($sql);

			if( $result->num_rows > 0 ){
				while( $row = $result->fetch_assoc()){
					$reply = $reply."\n".$row['id']." » ".$row['event']." » ".date("d/m/Y", strtotime($row['date']))." » ".$row['url']." » ".$row['channel']." » ".$row['author']."\n";
				}

				$result->free();

				$ch = curl_init($webhook_url);	//setup curl 

				//build json array
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
				echo "No countdowns set";
			}

		} elseif( substr($params[0], 1, 6) == 'delete' ){
			$toDelete = substr($params[0], 8);
			$sql = "DELETE FROM countdown where id=".$toDelete." and author='".$author."';";
			$link->query($sql);

			if( $link->affected_rows == 1){
				echo "Countdown with id: ".$toDelete." has been deleted\n";
			} else {
				echo "Error deleting countdown, please check id is correct and try again\n";
			}

			$result->free();

		} else {

			if(count($params) >= 2 && count($params) <= 3){
				$eventDate = substr($params[0], 6);
				$eventDescription = substr($params[1], 6);

				if(count($params) == 3){
					$imgURL = substr($params[2], 4);
				} else {
					$imgURL = "";
				}

			} else{
				$msg = "ERROR: usage /countdown -date dd/mm/yyyy -event description [-img img_url]";
				die($msg);
				echo($msg);
			}
			
			//insert new countdown into db
			$sql = "INSERT INTO countdown (event, date, url, channel, author) VALUES ('".$eventDescription."', STR_TO_DATE('".$eventDate."', '%d/%m/%Y'), '".$imgURL."', '".$channel."', '".$author."');";
			$link->query($sql);
			if( $link->affected_rows == 1){
				$post = true;
			}else{
				$post = false;
				$reply = " There was a problem creating your countdown, please try again later";
			}

			//if there was a successful insertion of a new countdown then post countdown to slack in channel it was created in
			if($post == true){
				$datetimeEvent = date_create_from_format('d/m/Y', $eventDate);
				$datetimeNow = date_create_from_format('d/m/Y', date("d/m/Y"));
				$datediff = $datetimeNow->diff($datetimeEvent);

				if( $datediff->format('%a') > 1 || $datediff->format('%a') == 0){
					$postText = $datediff->format('%a')." days to go until ".$eventDescription;
				} else {
					$postText = $datediff->format('%a')." day to go until ".$eventDescription;
				}

				$footer = 'countdown created by '.$author;

				$ch = curl_init($webhook_url);	//setup curl

				//build json array
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
