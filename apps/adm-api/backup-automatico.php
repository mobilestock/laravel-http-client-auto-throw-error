<?php

require_once 'classes/conexao.php';
require_once 'classes/configuracoes.php';
require_once 'vendor/autoload.php';

use Ifsnop\Mysqldump as IMysqldump;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use MobileStock\helper\Globals;

$configuracoes = buscaConfiguracoes();
$horasBackup = $configuracoes['horas_backup'];

date_default_timezone_set('America/Sao_Paulo');
$horaAtual = date('H');
$minutoAtual = date('i');

if ($horasBackup != $horaAtual) {

  $dataAtual = DATE('d-m-Y_H-i-s');
  atualizaHorasBackup($horaAtual);
  try {
    $dump = new IMysqldump\Mysqldump('mysql:host=mobilestock.cwlisnj4go4t.sa-east-1.rds.amazonaws.com;dbname=mobile_stock', 'mobilestock', 'gnOkQGZ%1W%n');
    $dump->start('mobile_stock' . $dataAtual . '.sql');
  } catch (\Exception $e) {
    echo 'mysqldump-php error: ' . $e->getMessage();
  }

  $arquivo = 'mobile_stock' . $dataAtual . '.sql';

  try {
    $s3 = S3Client::factory(Globals::S3_OPTIONS('BACKUP_AUTOMATICO'));
  } catch (Exception $e) {
    die("Error " . $e->getMessage());
  }

  try {
    $result = $s3->putObject([
      'Bucket' => 'mobilestockbackup',
      'Key' => $arquivo,
      'SourceFile' => $arquivo,
    ]);
  } catch (S3Exception $e) {
    echo $e->getMessage();
  }

  unlink($arquivo);
}
