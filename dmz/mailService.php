<?php


//From PHPMailer examples/gmail.phps.
//Send email for DMZ through Gmail SMTP, is called by dmzServer.php in send_email.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//req mailloader
require_once '/usr/share/php/libphp-phpmailer/autolaod.php'

//function to read email configuration in githappensEmail.ini

function readEmailIni()
{
    $emailPath = __DIR__ . "/../shared/githappensEmail.ini";

    if(!file_exists($emailPath))
        {
               return false;
    }

    $emailConfig = parse_ini_file($emailPath, true);   // true = keep section names

    if ($emailConfig === false || !isset($emailConfig['email']))
    {
        return false;
    }

    return $emailConfig['email'];
}

//send email function, sends one email returns the contract array.

function sendEmail($emailDest, $emailSubject, $emailBody)

{
    $emailConfig = readEmailIni();

    if($emailConfig === false
    || !isset($emailConfig['HOST'],, $emailConfig['PORT'], $emailConfig['USER'],
                  $emailConfig['PASSWORD'], $emailConfig['FROM_NAME']))
    {
        error_log("send_email: email configuration (ini) missing or incomplete");
        return array("success" => false, "message" => "Mailer is not configured", "code" => 9);
    }

    $mail = new PHPMailer(true);   //new mailer object

    try
    {
        $mail->isSMTP();
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->Host = $emailConfig['HOST'];
        $mail->Port = (int)$emailConfig['PORT'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['USER']
        $mail->Password = $emailConfig['PASSWORD']


    }
    
    

}
