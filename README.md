# Slack Countdown

## Overview

This bot allows users to define a countdown in any slack channel which is then automatically notified every day until the countdown date has been reached. Users can optionally attach an image to the countdown.


## Screenshot

<p align="center">
<img src="https://raw.githubusercontent.com/Daemon-Solutions/slack-countdown/prod/Screenshot.png" />
</p>


## Requirements
You will need a Web Server with CURL, PHP and MySQL/MariaDB configured.  Your webserver will need a publicly accessible domain through which you will serve the countdown php pages.

## Installation
After cloning the repository place the countdown folder on your webserver in a location that the files can be served from.  Using the mysql_schema.sql file create the countdown database and create a user with the necessary privileges to access the countdown database.

#### Automated notifications
In order to enable automated notifications, you will ned to configure an entry in cron that runs at your preferred interval and makes a curl call to the appropriate url that will server looper.php

##### Example
`30 9 * * * /path/to/curl http://yourdomain.com/countdown/looper.php`

This will execute looper.php every morning at 09:30 (server time).  looper.php queries the database for active countdowns and then posts a countdown message to all the associated channels for those countdowns.s

## Configuration

### Slash command
* Configure a new slash command in Slack named 'countdown' (or an appropriate name of your choice), using the following settings:  

   URL:  set this to the appropriate url to serve the countdown.php page from your webserver  
   Method: POST  
   Name:  countdown-bot (or any name of your choosing)  
   Icon:  use the included image.png for the icon, or any image of your choosing  
   Description: 'Create a new countdown'  
   Usage hint: '-date dd/mm/yyyy -event description [-img img_url]'

Take a not of the Token that has been generated, you will need this later in the configuration.

### Incoming WebHook
* Configure a new Incoming Webhook, to allow the countdown scripts to post messages back to slack.  Use the following comfiguration:

   Post to Channel:  @slackbot  (defaults any messages to being posted in the slackbot channel)  
   Name:  countdown-bot (or any name of your choosing)  
   Icon:  use the included image.png for the icon, or any image of your choosing  

Take a note of the webhook url that has been generated, you will need this later in the configuration

### config.php
config.php contains configuration values for slack and your mysql instance, these values need to be set before the bot will function.  Configure the mysql database settings to reflect your particular setup.  Set the slack token and webhook values to be the values noted from when you created the slash command and incoming webhook.


## Usage

Once you have stepped through the above, you should now have a slash command that you can run to generate a countdown like the one in the screenshot above.

The commands you can run are as follows:

#### Normal Usage
Create a new countdown:		/countdown **-date** *dd/mm/yyyy* **-event** *description for your event* **-img** *image_url (this is optional)*

#### Admin usage
List countdowns created by 'you':	/countdown **-list**  
Delete countdown:	/countdown **-delete** *countdown_id*   


