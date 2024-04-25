<?php

namespace MobileStock\service;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Email{

    private PHPMailer $mail;

    public function __construct(string $titulo = 'Suporte Mobile')
    {
        $this->mail = new PHPMailer();

        $this->mail->IsSMTP();

        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->Port       = 465;  
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->SMTPAuth   = true;
        $this->mail->Priority = 1;
        $this->mail->SMTPOptions = ['ssl' => [
            'verify_peer' => false, 
            'verify_peer_name' => false, 
            'allow_self_signed' => true
            ]
        ];
        $this->mail->Username   = $_ENV['MAIL_USER'];
        $this->mail->Password   = $_ENV['MAIL_PASSWORD'];
        $this->mail->setLanguage('pt-br');
        $this->mail->CharSet = 'utf8';

        $this->mail->setFrom($_ENV['MAIL_USER'], $titulo);
    }
    public function enviar(
        string $email_destinatario, 
        string $nome_destinatario,
        string $motivo,
        string $corpo,
        string $texto_alternativo,
        string $caminho_html_corpo = ''
    ){

        if(strlen($email_destinatario) <= 0):
            throw new Exception("Defina um destinatario para enviar o email.",400);
        endif;
        if(strlen($caminho_html_corpo) <=0 and strlen($corpo) <= 0 and strlen($texto_alternativo) <= 0):
            throw new Exception("Para enviar um email vc deve adicionar o corpo de email ou sinalizar o caminho de um aquivo para ser enviado no corpo.",400);
        endif;

        $this->mail->addAddress($email_destinatario,$nome_destinatario); 
        $this->mail->Subject = $motivo;

        
        if(strlen($corpo)>0):
            $this->mail->Body = $corpo;
        endif;
        
        if(strlen($texto_alternativo)>0):
            $this->mail->AltBody = $texto_alternativo;
        endif;

        if(strlen($caminho_html_corpo)>0):
            $caminho_html_corpo = './src/view/mail/'.$caminho_html_corpo;

            $this->mail->msgHTML(file_get_contents($caminho_html_corpo), __DIR__);
        endif;

        $this->mail->send();
    }
}