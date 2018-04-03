<?php
include('config.php');
include('vendor/autoload.php');
Eden\Core\Control::i();

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => API_URL."email_campaign?status=completed&limit=1",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
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
  $data = json_decode($response);

  if(!isset($data->status)) {
  	if(count($data) > 0) {
    	$data = $data[0];
    	$mail_addr = $data->ema_account;
    	$mail_pass = $data->ema_password;
    	$imap_addr = $data->ema_imap_addr;
    	$imap_port = (int)$data->ema_imap_port;

    	$imap = eden('mail')->imap(
		    $imap_addr, 
		    $mail_addr, 
		    $mail_pass, 
		    $imap_port, 
	    	true
	    );

	    $imap->setActiveMailbox('INBOX')->getActiveMailbox(); //--> INBOX 
	    $emails = $imap->getEmails(0, 3);
		$count = $imap->getEmailTotal(); 

		// echo '<pre>';
		// print_r($emails);
		// echo '</pre>';
		// die();

		$fields = array(
			'emc_num_bounce' => $count,
		    'emc_num_manual_bounce' => $count,
		    'emc_num_bounce_error' => $count,
		    'emc_num_bounce_unknown' => $count,
		);
		$fields_string = http_build_query($fields);

		$curl = curl_init();

    	//set number of bounce mail
    	curl_setopt_array($curl, array(
    	  CURLOPT_URL => API_URL."email_campaign/".$data->emc_id,
    	  CURLOPT_RETURNTRANSFER => true,
    	  CURLOPT_ENCODING => "",
    	  CURLOPT_MAXREDIRS => 10,
    	  CURLOPT_TIMEOUT => 30,
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

    	//delete all email in inbox via imap
    	foreach($emails as $email) {
    		$mail_id = (int)$email['uid'];
    		echo $mail_id."<br>";
    		$imap->remove($mail_id, true); 
    	}

    	$imap->disconnect();
    }
  }
}



