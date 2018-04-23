<?php

include('config.php');

//update the progress
$fields = array(
  'emc_num_email_sent' => 10
);  
$fields_string = http_build_query($fields);

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => API_URL."email_campaign/4",
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

echo "<pre>";
print_r($response);
echo "</pre>";

?>