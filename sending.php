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
//$filename = 'etc/send.lock';
//
//if(!file_exists($filename)){
//	$resource = fopen($filename, 'w');
//	fclose($resource);
//} else {
//	echo 'sending still in progress exiting...';
//	exit();
//}

//flusing output
flush();
ob_flush();

//count processing
$curl = curl_init();
curl_setopt_array($curl, array(  
  //CURLOPT_URL => API_URL."email_campaign?status=sending",
  CURLOPT_URL => API_URL."email_campaign?status=sending&ip=".getHostByName(getHostName()),
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
  if( count($data) >= SIMULTANEOUS && $data->message == '')
	  die('Sending still in progress ('.count($data).') exiting...');
}

//get ready status
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
		  'emc_server_sending' => getHostByName(getHostName()),
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
	  
	  $rotate_telephone = preg_split('/\|/', $data->telephone);
	  $rotate_email = preg_split('/\|/', $data->email);
	  $rotate_site_internet = preg_split('/\|/', $data->site_internet);
	  $rotate_text_repondre = preg_split('/\|/', $data->text_repondre);
	  $rotate_email_subject = preg_split('/\|/', $data->emc_email_subject);
	  $rotate_sender_name = preg_split('/\|/', $data->sender_name);

	  $sending = 0;
	  $rotate_number_telephone = 0;
	  $rotate_number_email = 0;
	  $rotate_number_site_internet = 0;
	  $rotate_number_text_repondre = 0;
	  $rotate_number_email_subject = 0;
	  $rotate_number_sender_name = 0;
	  $rotate_email_body = '';
	  
      foreach($emails as $email) {
		
		//default value without rotation
	    $sender_name = $data->sender_name;
		$email_subject = $data->emc_email_subject;
		
		if($sending % $data->rotate == 0 && $data->rotate != 0){			
			
			$rotate_email_body = str_replace( '[telephone]', $rotate_telephone[$rotate_number_telephone], $data->emc_email_body);
			$rotate_email_body = str_replace( '[email]', $rotate_email[$rotate_number_email], $rotate_email_body);
			$rotate_email_body = str_replace( '[site-internet]', $rotate_site_internet[$rotate_number_site_internet], $rotate_email_body);
			$rotate_email_body = str_replace( '[text-repondre]', $rotate_text_repondre[$rotate_number_text_repondre], $rotate_email_body);
			$email_subject = $rotate_email_subject[$rotate_number_email_subject];
			$sender_name = $rotate_sender_name[$rotate_number_sender_name];
			//echo $rotate_email_body."<hr><br><br>";
		    
			$rotate_number_telephone++;
         	$rotate_number_email++;
	        $rotate_number_site_internet++;
	        $rotate_number_text_repondre++;
			$rotate_number_email_subject++;
			$rotate_number_sender_name++;
			
			//reset rotate number if exceed than number of array count
			if($rotate_number_telephone == count($rotate_telephone)) $rotate_number_telephone = 0;
			if($rotate_number_email == count($rotate_email)) $rotate_number_email = 0;
			if($rotate_number_site_internet == count($rotate_site_internet)) $rotate_number_site_internet = 0;
			if($rotate_number_text_repondre == count($rotate_text_repondre)) $rotate_number_text_repondre = 0;
			if($rotate_number_email_subject == count($rotate_email_subject)) $rotate_number_email_subject = 0;
			if($rotate_number_sender_name == count($rotate_sender_name)) $rotate_number_sender_name = 0;
			
		}
		$sending++;
			
        try {        
            //Recipients
			//$sender_friendly_name = preg_split('/[@.]/', $data->ema_account);						
            //$mail->setFrom($data->ema_account, ucwords($sender_friendly_name[0]), ' ', ucwords($sender_friendly_name[1]));
			//$mail->setFrom($data->ema_account, "Macom de mairie");
			
			//Change to rotate sender name
			//$mail->setFrom($data->ema_account, $data->emc_sender_name);
			$mail->setFrom($data->ema_account, $sender_name);
			
			$mail->ClearAllRecipients();
            $mail->addAddress($email);       // Name is optional
            $mail->addReplyTo($data->ema_account);
            
            //Content
            $mail->isHTML(true);
            //$mail->Subject = $data->emc_email_subject;
			$mail->Subject = $email_subject;
            
			//no rotation message
			if($rotate_email_body == ''){
				$rotate_email_body = $data->emc_email_body;			   
			}
			
			//if type is text then convert new line into <br> tag
			if($data->type == "text"){
				$rotate_email_body = nl2br($rotate_email_body);
			}
			
            $mail->Body    = $rotate_email_body;
			$mail->AltBody = strip_tags($rotate_email_body);
			            
            $mail->XMailer = 'Microsoft Office Outlook 12.0';

            $mail->send();
            echo 'Message has been sent to '.$email.'<br>';
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
		//unlink($filename);  
		  
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
		
		//move into loop
		include("bounce_v2.php");
      }    
    }
  } else {
  	echo $data->message."\n";
	
	//deleting send.lock
	//it will release cron to call this script again
	//unlink($filename);
  }
}
?>