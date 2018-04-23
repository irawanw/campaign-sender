<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('config.php');
include('vendor/autoload.php');
Eden\Core\Control::i();

//file lock checking
//if there is .lock file then we are still sending
//so just exit the script
//if no .lock file exist then continue
$filename = 'etc/send.lock';

if(!file_exists($filename)){
	$resource = fopen($filename, 'w');
	fclose($resource);
} else {
	echo 'sending still in progress exiting...';
	exit();
}

//flusing output
flush();
ob_flush();

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => API_URL."email_campaign?status=ready&limit=1",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 60,
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

    	$curl = curl_init();
		
		$fields = array(
          'emc_status' => 'sending',
          'emc_date_start' => date('Y-m-d H:i:s'),
        );  
        $fields_string = http_build_query($fields);

    	//set status to sending
    	curl_setopt_array($curl, array(
    	  CURLOPT_URL => API_URL."email_campaign/".$data->emc_id,
    	  CURLOPT_RETURNTRANSFER => true,
    	  CURLOPT_ENCODING => "",
    	  CURLOPT_MAXREDIRS => 10,
    	  CURLOPT_TIMEOUT => 60,
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
    	
      //send mail
      $mail = new PHPMailer(true);
	  $mail->CharSet = 'UTF-8';  

      $mail->SMTPDebug = 2;                                 // Enable verbose debug output
      $mail->isSMTP();                                      // Set mailer to use SMTP
      $mail->Host = $data->ema_smtp_addr;  // Specify main and backup SMTP servers
      $mail->SMTPAuth = true;                               // Enable SMTP authentication
      $mail->Username = $data->ema_account;                 // SMTP username
      $mail->Password = $data->ema_password;                           // SMTP password
      $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
      $mail->Port = $data->ema_smtp_port; 

      
      $emails = explode("\n", $data->emc_email_target);
      $delay = (int)$data->emc_delay;
      $total_sent = 0;
      $total_success_sent = 0;

      foreach($emails as $email) {
        try {        
            //Recipients
			$sender_friendly_name = preg_split('/[@.]/', $data->ema_account);			
			
            //$mail->setFrom($data->ema_account, ucwords($sender_friendly_name[0]), ' ', ucwords($sender_friendly_name[1]));
			$mail->setFrom($data->ema_account, "Macom de mairie");
			$mail->ClearAllRecipients();
            $mail->addAddress($email);       // Name is optional
            $mail->addReplyTo($data->ema_account);
            
            //Content
            $mail->isHTML(true);
            $mail->Subject = $data->emc_email_subject;
            $mail->Body    = $data->emc_email_body;
            $mail->AltBody = strip_tags($data->emc_email_body);
            $mail->XMailer = 'Microsoft Office Outlook 12.0';

            $mail->send();
            echo 'Message has been sent to'.$email;   
            $total_success_sent += 1;         
        } catch (Exception $e) {
            echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
        }

        $total_sent += 1;
        sleep($delay);
		
		flush();
		ob_flush();
		
		//update the progress
		$fields = array(
          'emc_num_email_sent' => $total_sent
        );  
        $fields_string = http_build_query($fields);

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => API_URL."email_campaign/".$data->emc_id,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 60,
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

      //set status to completed
      if($total_sent == count($emails)) {
		  
		//deleting send.lock
		//it will release cron to call this script again
		unlink($filename);  
		  
        //set status to complete
        $fields = array(
          'emc_status' => 'completed',
          'emc_date_sent' => date('Y-m-d H:i:s'),
          'emc_num_email_sent' => $total_success_sent
        );  
        $fields_string = http_build_query($fields);

        $curl = curl_init();
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
		
		include("bounce.php");
      }    
	  
	  //deleting send.lock
	  //it will release cron to call this script again
	  //unlink($filename);
    }
  } else {
  	echo $data->message."\n";
	
	//deleting send.lock
	//it will release cron to call this script again
	unlink($filename);
  }
}
?>