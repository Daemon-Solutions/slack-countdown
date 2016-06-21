<?php

	// include configuration settings
	require 'config.php';

	date_default_timezone_set('Europe/London');

	//create connection to the database
	$link = new mysqli($db_host,$db_user,$db_pass,$db);

	$datetimeNow = date_create_from_format('d/m/Y', date("d/m/Y"));
	
	//retrieve all future countdowns
	$sql = "SELECT * from countdown where date >= CURDATE();";

	$result = $link->query($sql);

	if( $result->num_rows > 0){
		while( $row = $result->fetch_assoc()){
			$datetimeEvent = date_create_from_format('Y-m-D', date('Y-m-D', strtotime($row['date'])));
			$datediff = $datetimeNow->diff($datetimeEvent);

			if( $datediff->format('%a') > 1 || $datediff->format('%a') == 0){
				$postText = $datediff->format('%a')." days to go until ".$row['event'];
			} else {
				$postText = $datediff->format('%a')." day to go until ".$row['event'];
			}

			$footer = 'countdown created by '.$row['author'];

			$payload = array(
				'channel' => $row['channel'],
				'username' => 'countdown-bot',
				'attachments' => array(
					array(
						'title' => $postText,
						'image_url' => $row['url'],
						'footer' => $footer
					)
				),
			);

			$ch = curl_init($webhook_url);

			$payloadEncoded = json_encode($payload);	//encode array into valid json string
			curl_setopt($ch, CURLOPT_POST, 1);			//specify that it is a POST request
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadEncoded);	//attach json string to POST field
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));	//set content type to application/json

			$curlresult = curl_exec($ch);	//execute curl and save any response codes

		}

		unset($datetimeEvent);
		unset($datediff);
		unset($postText);
		unset($footer);
		unset($payload);
		unset($ch);
	}

	$link->close();


?>