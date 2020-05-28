<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('config.php');
include('vendor/autoload.php');

$email_accounts = [
    'admin@autosending.ovh',
    'admin@deguelt2018.ovh',
    'admin@magic-postier.ovh',
    'admin@mailpros.ovh',
    'admin@marketsoft.ovh',
    'admin@mediapro.ovh',
    'admin@net-pro.ovh',
    'admin@netenvoi.ovh',
    'admin@planetenvoi.ovh',
    'admin@qualitesoft.ovh',
    'admin@topqualite.ovh',
    'contact@company-line.ovh',
    'contact@complanete.ovh',
    'contact@comsales.ovh',
    'contact@delaffcom.ovh',
    'contact@directions.ovh',
    'contact@directnet.ovh',
    'contact@directtarget.ovh',
    'contact@emproductive.ovh',
    'contact@envoiontop.ovh',
    'contact@facturetop.ovh',
    'contact@formatic.ovh',
    'contact@frenchline.ovh',
    'contact@lines345.ovh',
    'contact@magicenvoi.ovh',
    'contact@magicnet.ovh',
    'contact@marketer.ovh',
    'contact@marketings.ovh',
    'contact@marketpromo.ovh',
    'contact@marketwebline.ovh',
    'contact@myservtop.ovh',
    'contact@netpromotion.ovh',
    'contact@netsending.ovh',
    'contact@netservices.ovh',
    'contact@on-line-web.ovh',
    'contact@onservice.ovh',
    'contact@planet-web.ovh',
    'contact@pro-market.ovh',
    'contact@pro-sto.ovh',
    'contact@pro-webnet.ovh',
    'contact@productonline.ovh',
    'contact@proenvoi.ovh',
    'contact@profdeal.ovh',
    'contact@profonline.ovh',
    'contact@profstaff.ovh',
    'contact@proftop.ovh',
    'contact@proincharge.ovh',
    'contact@promoagent.ovh',
    'contact@promodeal.ovh',
    'contact@promosite.ovh',
    'contact@promosites.ovh',
    'contact@promoweb.ovh',
    'contact@proservices.ovh',
    'contact@prostor.ovh',
    'contact@provider.ovh',
    'contact@saleslive.ovh',
    'contact@serviceonntop.ovh',
    'contact@services-top.ovh',
    'contact@sponsor.ovh',
    'contact@theinfo.ovh',
    'contact@thesalesmarket.ovh',
    'contact@topdeals.ovh',
    'contact@topqualities.ovh',
    'contact@topserv.ovh',
    'contact@topspace.ovh',
    'contact@web-emploi.ovh',
    'contact@web-online.ovh',
    'contact@webagent.ovh',
    'contact@webmagic.ovh',
    'contact@webnetpro.ovh',
    'contact@webonlinetop.ovh',
    'contact@webplanetes.ovh',
    'contact@webpromotion.ovh',
    'contact@websendings.ovh',
    'support@acheteronline.ovh',
    'support@distribution.ovh',
    'support@marketsolution.ovh',
    'support@placeonline.ovh',
    'support@promo-actions.ovh',
    'support@promoactions.ovh',
    'support@promotop.ovh',
    'support@solde-top.ovh',
    'support@soldeonline.ovh',
    'support@top-services.ovh',
    'support@topcontract.ovh',

];

foreach($email_accounts as $email_account)
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';  

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    $mail->SMTPDebug = 0;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'ssl0.ovh.net';                         // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = $email_account;                     // SMTP username
    $mail->Password = 'qF3FC$skuQ2B5d';
    $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 465; 

    try 
    {
        $valid = $mail->SmtpConnect();
        if($valid)
            echo $email_account." login is valid\n";
        
        $mail->SmtpClose();
    }
    catch(Exception $error) 
    { 
        echo $email_account." could not login probably domain is blacklisted?\n";
    }
}
?>