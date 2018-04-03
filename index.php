<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('vendor/autoload.php');
Eden\Core\Control::i();

//thedestination@promoeu.pw
//[h/dS:;8,KCyN+GA


/*
$imap = eden('mail')->imap(
    'mail.gandi.net', 
    'themorning@promoeu.pw', 
    'themorning123!@#', 
    993, 
    true);

$mailboxes = $imap->getMailboxes(); 

echo "<pre>";
print_r($mailboxes);

$imap->setActiveMailbox('INBOX')->getActiveMailbox(); //--> INBOX 
$emails = $imap->getEmails(0, 3); 
$count = $imap->getEmailTotal(); 

print_r($emails);
print_r($count);
echo "</pre>";
*/

$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'mail.gandi.net';  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = 'themorning@promoeu.pw';                 // SMTP username
    $mail->Password = 'themorning123!@#';                           // SMTP password
    $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 465;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('themorning@promoeu.pw', 'Themorning Promoeu');
    $mail->addAddress('thedestination@promoeu.pw');      	// Name is optional
    $mail->addReplyTo('themorning@promoeu.pw');
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    //Attachments
    //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Here is the subject';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
	$mail->XMailer = 'Microsoft Office Outlook 12.0';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}
?>