<?php

use BounceMailHandler\BounceMailHandler;

/*~ index.php
.---------------------------------------------------------------------------.
|  Software: PHPMailer-BMH (Bounce Mail Handler)                            |
|   Version: 5.5-dev                                                        |
|   Contact: codeworxtech@users.sourceforge.net                             |
|      Info: http://phpmailer.codeworxtech.com                              |
| ------------------------------------------------------------------------- |
|    Author: Andy Prevost andy.prevost@worxteam.com (admin)                 |
| Copyright (c) 2002-2009, Andy Prevost. All Rights Reserved.               |
| ------------------------------------------------------------------------- |
|   License: Distributed under the General Public License (GPL)             |
|            (http://www.gnu.org/licenses/gpl.html)                         |
| This program is distributed in the hope that it will be useful - WITHOUT  |
| ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
| FITNESS FOR A PARTICULAR PURPOSE.                                         |
| ------------------------------------------------------------------------- |
| This is a update of the original Bounce Mail Handler script               |
| http://sourceforge.net/projects/bmh/                                      |
| The script has been renamed from Bounce Mail Handler to PHPMailer-BMH     |
| ------------------------------------------------------------------------- |
| We offer a number of paid services:                                       |
| - Web Hosting on highly optimized fast and secure servers                 |
| - Technology Consulting                                                   |
| - Oursourcing (highly qualified programmers and graphic designers)        |
'---------------------------------------------------------------------------'
/*
 * This is an example script to work with PHPMailer-BMH (Bounce Mail Handler).
 */
$time_start = microtime_float();
include('config.php');
include('vendor/autoload.php');

// Use ONE of the following -- all echo back to the screen
require_once 'callback_echo.php';
//require_once('callback_database.php'); // NOTE: Requires modification to insert your database settings
//require_once('callback_csv.php');      // NOTE: Requires creation of a 'logs' directory and making writable
//testing examples

$bmh = new BounceMailHandler();
$bmh->actionFunction = 'callbackAction'; // default is 'callbackAction'
$bmh->verbose = BounceMailHandler::VERBOSE_SIMPLE; //BounceMailHandler::VERBOSE_SIMPLE; //BounceMailHandler::VERBOSE_REPORT; //BounceMailHandler::VERBOSE_DEBUG; //BounceMailHandler::VERBOSE_QUIET; // default is BounceMailHandler::VERBOSE_SIMPLE
//$bmh->useFetchStructure  = true; // true is default, no need to specify
//$bmh->testMode           = false; // false is default, no need to specify
//$bmh->debugBodyRule      = false; // false is default, no need to specify
//$bmh->debugDsnRule       = false; // false is default, no need to specify
//$bmh->purgeUnprocessed   = false; // false is default, no need to specify
//$bmh->disableDelete = true; // false is default, no need to specify

/*
 * for local mailbox (to process .EML files)
 */
//$bmh->openLocal('/home/email/temp/mailbox');
//$bmh->processMailbox();

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => API_URL."/email_campaign?date_sent=".(time()-TIMEFRAME)."&server_sending=".SERVER_IP,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 10,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(    
    "x-api-key: ".API_KEY
  ),
));

$response = curl_exec($curl);

$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {

	$email_data = json_decode($response);
	
	if(is_array($email_data)){
		
		foreach($email_data as $bounce_data){		
		
			$slot = preg_split("#,#", $bounce_data->emc_email_account);
			$servers = explode("|", $bounce_data->emc_server_sending);		
			
			//remove empty array
			$slot = array_filter($slot);
			$servers = array_filter($servers);
			
			$i = 0;
			foreach($servers as $server)
			{
				if($server == SERVER_IP)
					$email_account_data = $slot[$i];
				$i++;
			}
			//get email account details
			$email_account_data = explode("|", $email_account_data);
			$bounce_data->ema_account = $email_account_data[0];
			$bounce_data->ema_password = $email_account_data[1];
			$bounce_data->ema_smtp_addr = $email_account_data[2];
			$bounce_data->ema_smtp_port = $email_account_data[3];
			$bounce_data->ema_imap_addr = $email_account_data[4];
			$bounce_data->ema_imap_port = $email_account_data[5];
			$bounce_data->ema_pop3_addr = $email_account_data[6];
			$bounce_data->ema_pop3_port = $email_account_data[7];
			
			if($bounce_data->ema_account == '' || $bounce_data->ema_password == '')
				die('Error when using email account and password.. its empty');
			
			$mail_addr = $bounce_data->ema_account;
			$mail_pass = $bounce_data->ema_password;
			
			if($bounce_data->ema_imap_addr != ''){
				$imap_host = $bounce_data->ema_imap_addr;
				$imap_port = (int)$bounce_data->ema_imap_port;
				$imap_service = 'imap';
			} elseif($bounce_data->ema_pop3_addr != '') {
				$imap_host = $bounce_data->ema_pop3_addr;
				$imap_port = (int)$bounce_data->ema_pop3_port;
				$imap_service = 'pop3';				
			}
		
			/*
			 * for remote mailbox
			 */

			$bmh->mailhost = $imap_host; 		// your mail server
			$bmh->mailboxUserName = $mail_addr; // your mailbox username
			$bmh->mailboxPassword = $mail_pass; // your mailbox password
			$bmh->port = $imap_port; 			// the port to access your mailbox, default is 143
			$bmh->service = $imap_service; 		// the service to use (imap or pop3), default is 'imap'
			$bmh->serviceOption = 'ssl'; 		// the service options (none, tls, notls, ssl, etc.), default is 'notls'
			$bmh->boxname = 'INBOX'; 			// the mailbox to access, default is 'INBOX'
			//$bmh->moveHard           = true; // default is false
			//$bmh->hardMailbox        = 'INBOX.hardtest'; // default is 'INBOX.hard' - NOTE: must start with 'INBOX.'
			//$bmh->moveSoft           = true; // default is false
			//$bmh->softMailbox        = 'INBOX.softtest'; // default is 'INBOX.soft' - NOTE: must start with 'INBOX.'
			//$bmh->deleteMsgDate      = '2009-01-05'; // format must be as 'yyyy-mm-dd'			
			
			/*
			 * rest used regardless what type of connection it is
			 */
			$bmh->openMailbox();
			$bmh->processMailbox();
			echo '<hr style="width:200px;" />';
			$time_end = microtime_float();
			$time = $time_end - $time_start;
			echo 'Seconds to process: ' . $time . '<br />';
			
			echo $unsubscribe_detail;			
			
			//load bounce type and bounce reason
			//then unserialze them to get value each array
			//final data is old data + new data
			$original_bounce_type = unserialize($bounce_data->emc_num_bounce_type);
			$original_bounce_reason = unserialize($bounce_data->emc_num_bounce_reason);
			
			if(is_array($original_bounce_type))
			{
				$final_bounce_type['blocked'] = $original_bounce_type['blocked'] + $bounce_type['blocked'];
				$final_bounce_type['autoreply'] = $original_bounce_type['autoreply'] + $bounce_type['autoreply'];
				$final_bounce_type['soft'] = $original_bounce_type['soft'] + $bounce_type['soft'];
				$final_bounce_type['hard'] = $original_bounce_type['hard'] + $bounce_type['hard'];
				$final_bounce_type['temporary'] = $original_bounce_type['temporary'] + $bounce_type['temporary'];
				$final_bounce_type['generic'] = $original_bounce_type['generic'] + $bounce_type['generic'];
				$final_bounce_type['unknown'] = $original_bounce_type['unknown'] + $bounce_type['unknown'];
			} else {
				$final_bounce_type = $bounce_type;
			}
			
			if(is_array($original_bounce_reason))
			{
				$final_bounce_reason['antispam'] = $original_bounce_reason['antispam'] + $bounce_reason['antispam'];
				$final_bounce_reason['autoreply'] = $original_bounce_reason['autoreply'] + $bounce_reason['autoreply'];
				$final_bounce_reason['concurrent'] = $original_bounce_reason['concurrent'] + $bounce_reason['concurrent'];
				$final_bounce_reason['content_reject'] = $original_bounce_reason['content_reject'] + $bounce_reason['content_reject'];
				$final_bounce_reason['command_reject'] = $original_bounce_reason['command_reject'] + $bounce_reason['command_reject'];
				$final_bounce_reason['internal_error'] = $original_bounce_reason['internal_error'] + $bounce_reason['internal_error'];
				$final_bounce_reason['defer'] = $original_bounce_reason['defer'] + $bounce_reason['defer'];
				$final_bounce_reason['delayed'] = $original_bounce_reason['delayed'] + $bounce_reason['delayed'];
				$final_bounce_reason['dns_loop'] = $original_bounce_reason['dns_loop'] + $bounce_reason['dns_loop'];
				$final_bounce_reason['dns_unknown'] = $original_bounce_reason['dns_unknown'] + $bounce_reason['dns_unknown'];
				$final_bounce_reason['full'] = $original_bounce_reason['full'] + $bounce_reason['full'];
				$final_bounce_reason['inactive'] = $original_bounce_reason['inactive'] + $bounce_reason['inactive'];
				$final_bounce_reason['latin_only'] = $original_bounce_reason['latin_only'] + $bounce_reason['latin_only'];
				$final_bounce_reason['other'] = $original_bounce_reason['other'] + $bounce_reason['other'];
				$final_bounce_reason['oversize'] = $original_bounce_reason['oversize'] + $bounce_reason['oversize'];
				$final_bounce_reason['outofoffice'] = $original_bounce_reason['outofoffice'] + $bounce_reason['outofoffice'];
				$final_bounce_reason['unknown'] = $original_bounce_reason['unknown'] + $bounce_reason['unknown'];
				$final_bounce_reason['unrecognized'] = $original_bounce_reason['unrecognized'] + $bounce_reason['unrecognized'];
				$final_bounce_reason['user_reject'] = $original_bounce_reason['user_reject'] + $bounce_reason['user_reject'];
				$final_bounce_reason['warning'] = $original_bounce_reason['warning'] + $bounce_reason['warning'];
			} else {
				$final_bounce_reason = $bounce_reason;
			}
			
			
			//updating email campaign			
			$fields = array(
				'emc_num_bounce_type' => serialize($final_bounce_type),
				'emc_num_bounce_reason' => serialize($final_bounce_reason),
			);
			$fields_string = http_build_query($fields);

			$curl = curl_init();

			//set number of bounce mail
			curl_setopt_array($curl, array(
			  CURLOPT_URL => API_URL."email_campaign/".$bounce_data->emc_id,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 10,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "PUT",
			  CURLOPT_POSTFIELDS => $fields_string,
			  CURLOPT_HTTPHEADER => array(	    
				"content-type: application/x-www-form-urlencoded",
				"x-api-key: ".API_KEY
			  ),
			));

			$response = curl_exec($curl);
			
			//updating email campaign	
			//without no replace
			$fields = array(
				'emc_bounce_report' => trim($bounce_detail),
				'emc_unsubscribe_list' => trim($unsubscribe_detail),
			);
			$fields_string = http_build_query($fields);

			$curl = curl_init();

			//set number of bounce mail
			curl_setopt_array($curl, array(
			  CURLOPT_URL => API_URL."email_campaign/".$bounce_data->emc_id.'?noreplace=yes',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 10,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "PUT",
			  CURLOPT_POSTFIELDS => $fields_string,
			  CURLOPT_HTTPHEADER => array(	    
				"content-type: application/x-www-form-urlencoded",
				"x-api-key: ".API_KEY
			  ),
			));

			$response = curl_exec($curl);
			
			$err = curl_error($curl);
			curl_close($curl);
		}
	}
}		


/**
 * @return float
 */
function microtime_float()
{
  list($usec, $sec) = explode(' ', microtime());
  return ((float)$usec + (float)$sec);
}