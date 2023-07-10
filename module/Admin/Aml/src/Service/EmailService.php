<?php

declare(strict_types=1);

namespace Aml\Service;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mail\Transport\Sendmail;

class EmailService
{
    private $config;
    private $email_from = 'devs@realvia.sk';
    private $email_from_name = 'AML Service, Carbon';
    private $email_subject = 'AML service error log';
    private $email_to = 'sysadmin@webex.sk';
    // private $email_to = 'barta@webex.sk'; // TEST
    private $email_to_name = 'Sysadmins';

    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    ///////////////////////////////////////
    ////////// Getters & Setters //////////
    ///////////////////////////////////////

    private function getConfig(): array
    {
        return $this->config;
    }
    private function setConfig(array $input): void
    {
        $this->config = $input;
    }

    ////////////////////////////////////
    ////////// Public Methods //////////
    ////////////////////////////////////

    public function send(array $input): void
    {
        $text = json_encode($input);

        $mail = new Message();
        $mail->setBody($text);
        $mail->setFrom($this->email_from, $this->email_from_name);
        $mail->addTo($this->email_to, $this->email_to_name);
        $mail->setSubject($this->email_subject);

        $transport = new Smtp();
        $transport->setOptions(new SmtpOptions($this->getConfig()['email']));
        $transport->send($mail);
    }
}
