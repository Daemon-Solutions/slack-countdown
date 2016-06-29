<?php

	// ****************************************
	//	
	// Author: Sandeep Jassal, Daemon Solutions Ltd.
	// 
	// looper.php - this php script is to be executed by cron, it will check which countdowns are still 
	//				currently active and post a notification to the associated channel
	//
	// ****************************************

	// include configuration settings
	require 'config.php';

	date_default_timezone_set('Europe/London');  //set default timezone to suppress php warnings about using system timezone

	//create connection to the database
	$link = new mysqli($db_host,$db_user,$db_pass,$db);

	$datetimeNow = date_create_from_format('d/m/Y', date("d/m/Y"));  // initialise datetime object with todays date

	if( $datetimeNow->format('N') >= 6 ){

		
		// sql to retrieve all active countdowns
		$sql = "SELECT * from countdown where date >= CURDATE();";

		$result = $link->query($sql);  // execute query

		if( $result->num_rows > 0){
			// iterate over resultset if 1 or more rows are returned
			while( $row = $result->fetch_assoc()){
				$datetimeEvent = date_create_from_format('Y-m-d', date('Y-m-d', strtotime($row['date'])));  //initialise datetime object with countdown target date
				$datediff = $datetimeNow->diff($datetimeEvent);  // work out the number of days from today to the taget date 

				if( $datediff->format('%a') > 1 || $datediff->format('%a') == 0){  //check if the number of days is greater 1 or equal to 0
					$postText = $datediff->format('%a')." days to go until ".$row['event'];  //format countdown message with correct grammar
				} else {
					$postText = $datediff->format('%a')." day to go until ".$row['event'];  //format countdown message grammer when there is 1 day left on the countdown
				}

				$footer = 'countdown created by '.$row['author'];  //create footer message notifying users who created the countdown

				//build json array to transmit back to slack
				$payload = array(
					'channel' => $row['channel'],
					'username' => 'countdown-bot',
					'attachments' => array(
						array(
							'fallback' => $postText,
							'title' => $postText,
							'image_url' => $row['url'],
							'footer' => $footer
						)
					),
				);

				$ch = curl_init($webhook_url);  //setup curl

				$payloadEncoded = json_encode($payload);	//encode array into valid json string
				curl_setopt($ch, CURLOPT_POST, 1);			//specify that it is a POST request
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadEncoded);	//attach json string to POST field
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));	//set content type to application/json

				$curlresult = curl_exec($ch);	//execute curl and save any response codes

			}

			// clear variables
			unset($datetimeEvent);
			unset($datediff);
			unset($postText);
			unset($footer);
			unset($payload);
			unset($ch);
		}
	}
	$link->close();  //close connection to the database


?>