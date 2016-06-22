# Slack Countdown

## Overview

This bot allows users to define a countdown in any slack channel which is then automatically notified every day until the countdown date has been reached. Users can optionally attach an image to the countdown.


## Screenshot

<p align="center">
<img src="https://raw.githubusercontent.com/Daemon-Solutions/slack-countdown/prod/Screenshot.png" />
</p>


## Requirements
You will need a Web Server with PHP and MySQL/MariaDB configured.  Your webserver will need a publicly accessible domain through which you will serve the countdown php pages.

## Installation
After cloning the repository place the countdown folder on your webserver in a location that the files can be served from.  Using the mysql_schema.sql file create the countdown database and create a user with the necessary privileges to access the countdown database.

## Configuration

### Slash command
* Configure a new slash command in Slack named 'countdown' (or an appropriate name of your choice), using the following settings:  

   URL:  set this to the appropriate url to serve the countdown.php page from your webserver  
   Method: POST  
   Name:  countdown-bot (any name of your choosing)  
   Icon:  use the included image.png for the icon, or any image of your choosing  
   Description: 'Create a new countdown'  
   Usage hint: '-date dd/mm/yyyy -event description [-img img_url]'  



## Usage



