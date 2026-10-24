<?php


//From PHPMailer examples/gmail.phps.
//Send email for DMZ through Gmail SMTP, is called by dmzServer.php in send_email.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//request mailloader
require_once '/usr/share/php/libphp-phpmailer/autoload.php';

//function to read email configuration in githappensEmail.ini
//will return settings array, or false if section is missing.

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

    //stop early if ini missing or not conifgured correctly
    if($emailConfig === false
    || !isset($emailConfig['HOST'], $emailConfig['PORT'], $emailConfig['USER'],
                  $emailConfig['PASSWORD'], $emailConfig['FROM_NAME']))
    {
        error_log("send_email: email configuration (ini) missing or incomplete");
        return array("success" => false, "message" => "Mailer is not configured", "code" => 9);
    }

    $mail = new PHPMailer(true);   //new mailer object, setting true makes it throw exception on failure.

    try
    {
        $mail->isSMTP();
        $mail->SMTPDebug = SMTP::DEBUG_OFF;   //prints login so when on only for testing
        $mail->Host = $emailConfig['HOST'];
        $mail->Port = (int)$emailConfig['PORT'];  //ini provides text by default so specifying int
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  //Port 587 uses STARTTLS
        
        //Gmail login, passowrd is Gmail app password (requires 2 step verification)
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['USER'];
        $mail->Password = $emailConfig['PASSWORD'];

        $mail->setFrom($emailConfig['USER'], $emailConfig['FROM_NAME']);   // gmail only allows its own address
        $mail->addAddress($emailDest);
        $mail->Subject = $emailSubject;
        $mail->Body = $emailBody;   

        $mail->send();
        return array("success" => true, "message" => "Email sent");
    }
    //runs if send() fails
    catch (Exception $e)
    {
        error_log("send_email failed: " . $mail->ErrorInfo);   // failure reason sent to logs
        return array("success" => false, "message" => "Email was not able to be sent", "code" => 9);
    }
}
?>