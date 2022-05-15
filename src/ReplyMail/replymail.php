<?php

namespace blacksenator\ReplyMail;

/**
 * class replymail delivers a simple function based on PHPMailer
 *
 * Copyright (c) 2021 Volker Püschel
 * @license MIT
 */

use blacksenator\ConvertTrait;
use PHPMailer\PHPMailer\PHPMailer;
use Sabre\VObject;

class replymail
{
    use ConvertTrait;

    const EMAIL_SBJCT = 'Newer contact was found in phonebook: ';
    const EMAIL_TEXT = <<<EOD
    carddav2fb found the attached contact in your Fritz!Box telephone book, but not in your upload data.

    Please check if you would like to keep this information and maybe add it to your contacts on the CardDAV server:

    EOD;
    CONST VIP_INFO = 'This contact was marked as important.\nSuggestion: assign to a VIP category or group.';

    private $mail;

    public function __construct()
    {
        date_default_timezone_set('Etc/UTC');
        $this->mail = new PHPMailer(true);
        $this->mail->CharSet = 'UTF-8';
    }

    /**
     * get a new simple vCard according to FRITZ!Box phonebook data
     *
     * @param string $name
     * @param array $numbers
     * @param array $emails
     * @param string $vip
     * @return Document
     */
    public function getvCard(string $name, array $numbers, array $emails = [], string $vip = '')
    {
        $vCard = new VObject\Component\VCard;
        $vCard->VERSION = '3.0';          // the default VERSION:4.0 causes problems with umlauts at Apple
        $parts = $this->getNameParts($name);
        if (empty($parts['company'])) {
            $vCard->add('FN', $parts['firstname'] . ' ' . $parts['lastname']);
            $vCard->add('N', [$parts['lastname'], $parts['firstname']]);
        } else {
            $vCard->add('FN', $parts['company']);
            $vCard->add('ORG', $parts['company']);
        }
        foreach ($numbers as $number => $type) {
            $vCard->add('TEL', $number, ['type' => $this->getvCardType($type)]);
        }
        foreach ($emails as $email) {
            $vCard->add('EMAIL', $email);
        }
        if ($vip == 1) {
            $vCard->add('NOTE', self::VIP_INFO);
        }

        return $vCard;
    }

    /**
     * set SMTP credetials
     *
     * @param array $account
     * @return void
     */
    public function setSMTPcredentials($account)
    {
        $this->mail->isSMTP();                                  // tell PHPMailer to use SMTP
        $this->mail->SMTPDebug  = $account['debug'];
        $this->mail->Host       = $account['url'];              // set the hostname of the mail server
        $this->mail->Port       = $account['port'];             // set the SMTP port number - likely to be 25, 465 or 587
        $this->mail->SMTPSecure = $account['secure'];
        $this->mail->SMTPAuth   = true;                         // whether to use SMTP authentication
        $this->mail->Username   = $account['user'];             // username to use for SMTP authentication
        $this->mail->Password   = $account['password'];         // password to use for SMTP authentication
        $this->mail->setFrom($account['user'], 'carddav2fb');   // set who the message is to be sent fromly-to address
        $this->mail->addAddress($account['receiver']);          // set who the message is to be sent to
    }

    /**
     * send reply mail
     *
     * @param string $phonebook
     * @param string $attachment
     * @param string $label
     * @return bool
     */
    public function sendReply($phonebook, $attachment, $label)
    {
        $this->mail->clearAttachments();
        $this->mail->Subject = self::EMAIL_SBJCT . $phonebook;      //Set the subject line
        $this->mail->Body = self::EMAIL_TEXT;
        $this->mail->addStringAttachment($attachment, $label, 'quoted-printable', 'text/x-vcard');
        if (!$this->mail->send()) {                                 // send the message, check for errors
            echo 'Mailer Error: ' . $this->mail->ErrorInfo;
            return false;
        }

        return true;
    }
}
