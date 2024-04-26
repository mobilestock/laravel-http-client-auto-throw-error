<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once 'classes/historico.php';

function removeParesExpirados()
{
  // enviarEmailCompras();
  // atualizarDataExpirar();
}

function enviaEmailFornecedor(string $email, string $fornecedor, int $compra)
{
  $mail = new PHPMailer(true);
  try {
    //Server settings
    $mail->SMTPDebug = 3;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
    $mail->Host       = gethostbyname('smtp.gmail.com');                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'mobilestock.ti@gmail.com';                     // SMTP username
    $mail->Password   = 'sKZ3Bm6l';                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
    $mail->Port       = 587;                                    // TCP port to connect to

    //Recipients
    $mail->setFrom('mobilestock.ti@gmail.com', 'Mobile Stock');
    $mail->addAddress($email, $email);     // Add a recipient

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = "Voce recebeu um novo pedido de compra do MobileStock. ";
    $mail->Body    = "Olá {$fornecedor}. ";
    $mail->Body    .= "Você acaba de receber um novo pedido. ";
    $mail->Body    .= "Entre em https://www.mobilestock.com.br e acesse o pedido nº {$compra}. ";
    $mail->Body    .= "Atenção: caso não venha com as etiquetas unitárias e coletivas o pedido está sujeito a devolução. ";

    $mail->send();
    return true;
  } catch (Exception $e) {
    return false;
  }
}
