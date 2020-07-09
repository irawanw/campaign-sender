<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('config.php');
include('vendor/autoload.php');
Eden\Core\Control::i();

//flusing output
flush();
ob_flush();

exec("ps aux | grep php", $process);
$process = count($process) - 3;
if($process >= SIMULTANEOUS)
	die('Sending still in progress ('.$process.') exiting...');

//get ready status
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => API_URL."email_campaign?status=ready&limit=1",
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
  $data = json_decode($response);
  
  if(!isset($data->status)) 
  {

    if(count($data) > 0) 
    {
    	$data = $data[0];
		
      //calculate number of slot
      //split email sending between multiple slot using multiple server_sending
      //so we divide email according to slot
      $slot = preg_split("#,#", $data->emc_email_account);
      $emails = explode("\n", $data->emc_email_target);
      $servers = explode("|", $data->emc_server_sending);		
      $email_per_slot = ceil(count($emails)/count($slot));
      
      echo "Emailing starting #".$data->emc_id."\n";
      echo "Send out to ".count($emails)." recipients\n";
      
      //remove empty array
      $slot = array_filter($slot);
      $emails_origin = array_filter($emails);
      $emails = array_filter($emails);
      $servers = array_filter($servers);
      
      $current_slot = count($servers);
      //if($servers[0] == '' && count($servers) == 1){
      //	$current_slot = 0;
      //	$servers = '';
      //}
      
      $start_line = ($current_slot) * $email_per_slot;
          
      //last slot
      //last line will used last item of emails
      if(count($slot) == count($servers)+1){
        $stop_line = count($emails)-1; 
      } else {
        $stop_line = $start_line + ($email_per_slot-1); 
      }
      
      $list_emails = array();
      for($i=$start_line ; $i<=$stop_line; $i++){
        if($emails[$i] != '');
          $list_emails[] = $emails[$i];
      }
      
      //change emails variable using list emails
      $emails = $list_emails;
      
      //print_r($data);
      //echo "per slot email : ".($email_per_slot)."<br>\n";
      //echo "count server still on sending : ".($current_slot)."<br>\n";
      //echo "count email account will use : ".count($slot)."<br>\n";
      //echo "start line : ".$start_line."<br>\n";
      //echo "stop line : ".$stop_line."<br>\n";
      
      //get email account details
      $email_account_data = explode("|", $slot[$current_slot]);
      $data->ema_account = $email_account_data[0];
      $data->ema_password = $email_account_data[1];
      $data->ema_smtp_addr = $email_account_data[2];
      $data->ema_smtp_port = $email_account_data[3];
      $data->ema_imap_addr = $email_account_data[4];
      $data->ema_imap_port = $email_account_data[5];
      $data->ema_pop3_addr = $email_account_data[6];
      $data->ema_pop3_port = $email_account_data[7];
      
      //echo "<pre>";
      //echo $current_slot."<br>";
      //print_r($slot);
      //print_r($email_account_data);
      //echo "</pre>";

      //old buggy data
      //will force to complete
      //and set slot full to 1
      if(count($slot) < count($servers)+1)
      {
        if($servers == '')
          $update_servers = SERVER_IP;
        else
          $update_servers = trim(implode("\n", $servers))."\n".SERVER_IP;
        
        $fields = array(
          'emc_status' => 'completed',		  
          'emc_date_sent' => date('Y-m-d H:i:s'),
          'emc_slot_full' => 1
        );  
        
        $fields_string = http_build_query($fields);

        //set status to sending
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => API_URL."email_campaign/".$data->emc_id,
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
      
      //echo "<pre>";
      //print_r($data);
      //echo "</pre>";
		
      //echo "checking ema_account<br>\n";
      if($data->ema_account == '' || $data->ema_password == '')
      {					
        
        $fields['emc_status'] = 'email account empty/blacklisted/login failed';			
        $fields_string = http_build_query($fields);

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => API_URL."email_campaign/".$data->emc_id,
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
        
        echo "Error when using email account and password.. (empty)\n";
        //die();
        
      }
      else
      {

        $curl = curl_init();
        
        if($servers == '')
          $update_servers = SERVER_IP;
        else
          $update_servers = trim(implode("\n", $servers))."\n".SERVER_IP;
        
        $fields = array(
          'emc_status' => 'sending',		  
          'emc_date_start' => date('Y-m-d H:i:s'),
          'emc_server_sending' => $update_servers,
          'emc_last_email' => $stop_line,
        );  
        
        if(count($slot) == count($servers)+1)
          $fields['emc_slot_full'] = 1;
        
        $fields_string = http_build_query($fields);

        //set status to sending
        curl_setopt_array($curl, array(
        CURLOPT_URL => API_URL."email_campaign/".$data->emc_id,
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
          
        //send mail
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';  

        $mail->SMTPDebug = 0;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = $data->ema_smtp_addr;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = $data->ema_account;                 // SMTP username
        $mail->Password = $data->ema_password;                           // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $data->ema_smtp_port; 


        $delay = (int)$data->emc_delay;
        $total_sent = 0;
        $total_success_sent = 0;

        $rotate_telephone = preg_split('/\|/', $data->telephone);
        $rotate_telephone_2 = preg_split('/\|/', $data->telephone_2);
        $rotate_fixed_telephone = preg_split('/\|/', $data->fixed_telephone);
        $rotate_email = preg_split('/\|/', $data->email);
        $rotate_site_internet = preg_split('/\|/', $data->site_internet);
        $rotate_text_repondre = preg_split('/\|/', $data->text_repondre);
        $rotate_email_subject = preg_split('/\|/', $data->emc_email_subject);
        $rotate_sender_name = preg_split('/\|/', $data->sender_name);
        $rotate_unsubscribe_text = preg_split('/\|/', $data->unsubscribe_text);
        $rotate_number_change = preg_split('/\|/', $data->number_change);
        $rotate_dont_reply = preg_split('/\|/', $data->dont_reply);

        $sending = 0;
        $rotate_number_telephone = 0;
        $rotate_number_telephone_2 = 0;
        $rotate_number_fixed_telephone = 0;
        $rotate_number_email = 0;
        $rotate_number_site_internet = 0;
        $rotate_number_text_repondre = 0;
        $rotate_number_email_subject = 0;
        $rotate_number_sender_name = 0;
        $rotate_number_unsubscribe_text = 0;
        $rotate_number_number_change = 0;
        $rotate_number_dont_reply = 0;
        $rotate_email_body = '';
        $concurrent_fail = 0;
        
        echo "start sending to all recipient\n";
        //echo "<pre>";
        //print_r($email);
        //echo "</pre>";
        
        foreach($emails as $index=>$email) 
        {
          
          if($email == '') 
          {
            echo "empty recipient..next loop\n";
            continue;
          }
        
          //default value without rotation
          $sender_name = $data->sender_name;
          $email_subject = $data->emc_email_subject;
          
          if($data->rotate == 0) $data->rotate == 1000;
          
          if($sending % $data->rotate == 0 && $data->rotate != 0){			
            
            $rotate_email_body = str_replace( '[telephone]', $rotate_telephone[$rotate_number_telephone], $data->emc_email_body);
            $rotate_email_body = str_replace( '[telephone-2]', $rotate_telephone_2[$rotate_number_telephone_2], $rotate_email_body);
            $rotate_email_body = str_replace( '[fixed-telephone]', $rotate_fixed_telephone[$rotate_number_fixed_telephone], $rotate_email_body);
            $rotate_email_body = str_replace( '[email]', $rotate_email[$rotate_number_email], $rotate_email_body);
            $rotate_email_body = str_replace( '[site-internet]', $rotate_site_internet[$rotate_number_site_internet], $rotate_email_body);
            $rotate_email_body = str_replace( '[text-repondre]', $rotate_text_repondre[$rotate_number_text_repondre], $rotate_email_body);
            $rotate_email_body = str_replace( '[unsubscribe-text]', $rotate_unsubscribe_text[$rotate_number_unsubscribe_text], $rotate_email_body);
            $rotate_email_body = str_replace( '[text-number-change]', $rotate_number_change[$rotate_number_number_change], $rotate_email_body);
            $rotate_email_body = str_replace( '[text-dont-reply]', $rotate_dont_reply[$rotate_number_dont_reply], $rotate_email_body);
            $email_subject = $rotate_email_subject[$rotate_number_email_subject];
            $sender_name = $rotate_sender_name[$rotate_number_sender_name];
            //echo $rotate_email_body."<hr><br><br>";
            
            
            $global_monitoring['gmo_server_ip'] = SERVER_IP;
            $global_monitoring['gmo_domain'] = $data->ema_account;
            $global_monitoring['gmo_sender_name'] = $rotate_sender_name[$rotate_number_sender_name];
            $global_monitoring['gmo_subject'] = $rotate_email_subject[$rotate_number_email_subject];
            $global_monitoring['gmo_email'] = $rotate_email[$rotate_number_email];
            $global_monitoring['gmo_telephone1'] = $rotate_telephone[$rotate_number_telephone];
            $global_monitoring['gmo_telephone2'] = $rotate_telephone_2[$rotate_number_telephone_2];
            $global_monitoring['gmo_portable'] = $rotate_fixed_telephone[$rotate_number_fixed_telephone];
            $global_monitoring['gmo_url'] = $rotate_site_internet[$rotate_number_site_internet];
            $global_monitoring['gmo_unsub_link'] = $rotate_unsubscribe_text[$rotate_number_unsubscribe_text];
            $global_monitoring['gmo_dont_reply'] = $rotate_dont_reply[$rotate_number_dont_reply];
            $global_monitoring['gmo_number_changed'] = $rotate_number_change[$rotate_number_number_change];
            
            
            $rotate_number_telephone++;
            $rotate_number_telephone_2++;
            $rotate_number_fixed_telephone++;
            $rotate_number_email++;
            $rotate_number_site_internet++;
            $rotate_number_text_repondre++;
            $rotate_number_email_subject++;
            $rotate_number_sender_name++;
            $rotate_number_unsubscribe_text++;
            $rotate_number_number_change++;
            $rotate_number_dont_reply++;
            
            //reset rotate number if exceed than number of array count
            if($rotate_number_telephone == count($rotate_telephone)) $rotate_number_telephone = 0;
            if($rotate_number_telephone_2 == count($rotate_telephone_2)) $rotate_number_telephone_2 = 0;
            if($rotate_number_fixed_telephone == count($rotate_fixed_telephone)) $rotate_number_fixed_telephone = 0;
            if($rotate_number_email == count($rotate_email)) $rotate_number_email = 0;
            if($rotate_number_site_internet == count($rotate_site_internet)) $rotate_number_site_internet = 0;
            if($rotate_number_text_repondre == count($rotate_text_repondre)) $rotate_number_text_repondre = 0;
            if($rotate_number_email_subject == count($rotate_email_subject)) $rotate_number_email_subject = 0;
            if($rotate_number_sender_name == count($rotate_sender_name)) $rotate_number_sender_name = 0;
            if($rotate_number_unsubscribe_text == count($rotate_unsubscribe_text)) $rotate_number_unsubscribe_text = 0;
            if($rotate_number_number_change == count($rotate_number_change)) $rotate_number_number_change = 0;
            if($rotate_number_dont_reply == count($rotate_dont_reply)) $rotate_number_dont_reply = 0;				
                    
          }
          $sending++;
            
          try 
          {        
            $mail->setFrom($data->ema_account, $sender_name);	
            $mail->ClearAllRecipients();
            $mail->addAddress($email);       // Name is optional
            $mail->addReplyTo($data->ema_account);
            
            //Content
            $mail->isHTML(true);
            $mail->Subject = $email_subject;
            
            //echo $sender_name."\n";
            //echo $email_subject;
            //die();
            
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

            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
            //echo $email."<br>";
            //echo $rotate_email_body."<br>";
            //die();
            
            echo 'send to '.$email."\n";
            $mail->send();
            echo 'success sent to '.$email."\n";
            $total_success_sent += 1;      

            //count processing and sent
            $curl = curl_init();
            curl_setopt_array($curl, array(  
              CURLOPT_URL => API_URL."email_campaign/".$data->emc_id.'?action=success_sent',
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
            
            //update global monitoring list
            //update global monitoring list
            //update global monitoring list
            $global_monitoring['gmo_deliverance'] = 'delivered';
            $global_monitoring['gmo_hash_key'] =  md5(  $global_monitoring['gmo_server_ip'] .
                                                    $global_monitoring['gmo_domain'] .
                                                    $global_monitoring['gmo_sender_name'] .
                                                    $global_monitoring['gmo_subject'] .
                                                    $global_monitoring['gmo_email'] .
                                                    $global_monitoring['gmo_telephone1'] .
                                                    $global_monitoring['gmo_telephone2'] .
                                                    $global_monitoring['gmo_portable'] .
                                                    $global_monitoring['gmo_url'] . 
                                                    $global_monitoring['gmo_unsub_link'] .
                                                    $global_monitoring['gmo_dont_reply'] .
                                                    $global_monitoring['gmo_number_changed'] .
                                                    $global_monitoring['gmo_hash_key']);
            
            //echo "<pre>";
            //print_r($global_monitoring);
            //echo "</pre>";
            
            $fields_string = http_build_query($global_monitoring);
            $curl = curl_init();
            
            curl_setopt_array($curl, array(  
              CURLOPT_URL => API_URL."global_monitoring/",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 10,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => $fields_string,
              CURLOPT_HTTPHEADER => array(	    
              "content-type: application/x-www-form-urlencoded",
              "x-api-key: ".API_KEY
              ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);  
            //end update global monitoring list
            //end update global monitoring list
            //end update global monitoring list            
            
            $concurrent_fail = 0;
            
          } 
          catch (Exception $e) 
          {
            $concurrent_fail++;
            
            echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
            
            
            //update global monitoring list
            //update global monitoring list
            //update global monitoring list
            $global_monitoring['gmo_deliverance'] = $mail->ErrorInfo;
            $global_monitoring['hash_key'] =  md5(  $global_monitoring['gmo_server_ip'] .
                                                    $global_monitoring['gmo_domain'] .
                                                    $global_monitoring['gmo_sender_name'] .
                                                    $global_monitoring['gmo_subject'] .
                                                    $global_monitoring['gmo_email'] .
                                                    $global_monitoring['gmo_telephone1'] .
                                                    $global_monitoring['gmo_telephone2'] .
                                                    $global_monitoring['gmo_portable'] .
                                                    $global_monitoring['gmo_url'] . 
                                                    $global_monitoring['gmo_unsub_link'] .
                                                    $global_monitoring['gmo_dont_reply'] .
                                                    $global_monitoring['gmo_number_changed'] .
                                                    $global_monitoring['gmo_hash_key']);
                                                    
            $fields_string = http_build_query($global_monitoring);
            $curl = curl_init();
            
            curl_setopt_array($curl, array(  
              CURLOPT_URL => API_URL."global_monitoring",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 10,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => $fields_string,
              CURLOPT_HTTPHEADER => array(	    
              "content-type: application/x-www-form-urlencoded",
              "x-api-key: ".API_KEY
              ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);  
            //end update global monitoring list
            //end update global monitoring list
            //end update global monitoring list
            
            
            $fields = array(
              'emc_failed_sending' => $email.'|'.date("Y-m-d H:i:s").'|'.$data->ema_account.' : '.$mail->ErrorInfo."\n"
            );  
        
            $fields_string = http_build_query($fields);
        
            //count processing
            $curl = curl_init();
            curl_setopt_array($curl, array(  
              CURLOPT_URL => API_URL."email_campaign/".$data->emc_id.'?action=fail_sent',
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

            //updating status of email account
            if(	$concurrent_fail >= 10 ||
              preg_match('/blacklist/i', $mail->ErrorInfo) ||
              preg_match('/smtp connect\(\) failed/i', $mail->ErrorInfo)
            )				
            {
              //default status
              $fields = array('ema_status' => 'fail');
              
              if(preg_match('/blacklist/i', $mail->ErrorInfo))
                $fields = array('ema_status' => 'blacklisted');  
              elseif(preg_match('/smtp connect\(\) failed/i', $mail->ErrorInfo))
                $fields = array('ema_status' => 'login failed');  

              $fields_string = http_build_query($fields);

              //count processing
              $curl = curl_init();
              curl_setopt_array($curl, array(  
                CURLOPT_URL => API_URL."email_account/".urlencode($data->ema_account),
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

          $total_sent += 1;
          flush();
          ob_flush();
          
          sleep($delay);
          
        }

        echo "end of loop sending mail\n";
        
        //set status to completed
        if($total_sent == count($emails)) {
          
          //set status to complete
          $fields = array(
            'emc_date_sent' => date('Y-m-d H:i:s'),
          );  
          
          
          if(count($slot) == count($servers)+1){
            $fields['emc_status'] = 'completed';
          } else {
            //nothing
          }
          
          $fields_string = http_build_query($fields);

          $curl = curl_init();
          curl_setopt_array($curl, array(
            CURLOPT_URL => API_URL."email_campaign/".$data->emc_id,
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
          
          //move into loop
          include("bounce_v2.php");
        }    
      }
    }
  } else {
  	echo $data->message."\n";
  }
}
?>
