<?php

namespace api_administracao\Controller;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use MobileStock\helper\Globals;
use MobileStock\service\ExtratoService;
use MobileStock\service\Lancamento\LancamentoConsultas;

class LancamentoRelatorio
{
    public function extratoLancamento()
    {
        $caminhoImagens = '';
        $s3 = new S3Client(Globals::S3_OPTIONS('FISCAL_PDF'));
        $consulta = LancamentoConsultas::buscaLancamentosDia();
        if (!$consulta) {
            throw new Exception('Não foi possível encontrar lançamentos para realizar extrato!');
        }
        $nome_arquivo = __DIR__ . '/../../../downloads/extrato-' . date('Y-m-d') . '.html';
        $arquivo = fopen($nome_arquivo, 'w');
        if ($arquivo == false) {
            throw new Exception('Não foi possível criar o arquivo.');
        }

        $url = $_ENV['URL_MOBILE'];
        fwrite(
            $arquivo,
            '
                            <html lang="pt-br" style="height: auto;" onclick="menu()">
                            <head>
                                <meta charset="utf-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1">
                                <meta http-equiv="x-ua-compatible" content="ie=edge">
                                <meta name="theme-color" content="#C5273D">
                                <meta name="apple-mobile-web-app-status-bar-style" content="#C5273D">
                                <meta name="msapplication-navbutton-color" content="#C5273D">
                                <!-- Importações do cabecalho antigo -->
                                <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
                                <!-- <link rel="stylesheet" href="/resources/demos/style.css"> -->
                                <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
                                <link href="https://cdn.jsdelivr.net/npm/vuesax@4.0.1-alpha.25/dist/vuesax.min.css" rel="stylesheet">
                                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.0.0/animate.min.css">
                                <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
                                <link href="https://fonts.googleapis.com/css?family=Fugaz+One" rel="stylesheet">
                                <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
                                <link href="https://fonts.googleapis.com/css?family=Lobster&display=swap" rel="stylesheet">
                                <link href="https://fonts.googleapis.com/css?family=Audiowide&display=swap" rel="stylesheet">
                                <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
                                <link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
                                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400&display=swap" rel="stylesheet">
                                <link href="https://fonts.googleapis.com/css2?family=Titillium+Web&display=swap" rel="stylesheet">
                                <link rel="stylesheet" href="css/layoutJS.css">
                            </head>
                            <!-- REQUIRED SCRIPTS -->
                            <!-- Font Awesome -->
                            <script src="https://kit.fontawesome.com/9e8aef2f91.js" crossorigin="anonymous"></script>
                            <!-- jQuery -->
                            <script src="' .
                $url .
                'js/jquery-3.4.1.min.js"></script>
                            <!-- DatePicker -->
                            <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
                            <!-- AJAX -->
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
                            <!-- Bootstrap 4 -->
                            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
                            <!-- AdminLTE App -->
                            <script src="' .
                $url .
                'js/adminlte.min.js"></script>
                            <!-- VUE.js -->
                            <script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
                            <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
                            <script src="' .
                $url .
                'js/cabecalho.js<?= $versao; ?>"></script>
                            <script src="' .
                $url .
                'js/layoutJS.js<?= $versao; ?>"></script>
                            <script src="' .
                $url .
                'js/jquery-confirm.min.js"></script>
                            <script src="' .
                $url .
                'js/clipboard.min.js"></script>
                            <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.min.js"></script>
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js" integrity="sha512-pHVGpX7F/27yZ0ISY+VVjyULApbDlD0/X0rgGbTqCE7WFW5MezNTWG/dnhtbBuICzsd0WQPgpE4REBLv+UqChw==" crossorigin="anonymous"></script>
                            <script src="' .
                $url .
                'js/lazysizes.min.js" async=""></script>
                            <script src="https://cdn.jsdelivr.net/npm/vuesax@4.0.1-alpha.25/dist/vuesax.min.js"></script>

                            <body>
                                <div class="content-fluid">
                                <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css" rel="stylesheet">
                                <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
                                <link href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css" rel="stylesheet">


                                <h1 class="text-center">Relatório Lançamentos - ' .
                date('Y-m-d') .
                '</h1>
                                    <table id="tabela" class="table table-striped table-bordered dt-responsive" style="width:100%">
                                        <thead>
                                            <tr> '
        );
        foreach ($consulta[0] as $key => $c) {
            fwrite($arquivo, '<th scope="col"> ' . $key . ' </th>');
        }
        fwrite(
            $arquivo,
            '
                                            </tr>
                                        </thead>
                                    <tbody>
                                    '
        ); //Fechamos o arquivo após escrever nele fclose($arquivo);
        foreach ($consulta as $key => $c) {
            fwrite(
                $arquivo,
                '
                                            <tr>'
            );
            foreach ($consulta[0] as $indice => $value) {
                if ($indice === 'id') {
                    fwrite($arquivo, '<th scope="row">  ' . $c[$indice] . ' </th>');
                } else {
                    fwrite($arquivo, '<td> ' . $c[$indice] . ' </td>');
                }
            }
            fwrite(
                $arquivo,
                '
                                            </tr>'
            );
        }
        fwrite(
            $arquivo,
            '
                                    </tbody>
                                </table>
                            </body>
                        </div>
                        <script src="../../../js/jquery-3.4.1.min.js"></script>
                        <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
                        <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
                        <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
                        <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>
                        <script>
                        $(document).ready(function() {
                            $("#tabela").DataTable({
                                    searching: {
                                        "Search": "jose"
                                    },
                                    "language": {
                                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
                                    },
                                    paging: true,
                                    lengthChange: false,
                                    paginate: true,
                                    serverSide: false
                                });
                            });

                            </script>
                            </html>
                        '
        );
        fclose($arquivo);
        try {
            $result = $s3->putObject([
                'Bucket' => 'mobilestock-pdf',
                'Key' => $nome_arquivo,
                'SourceFile' => $nome_arquivo,
            ]);
        } catch (S3Exception $e) {
            throw new Exception($e->getMessage());
        }
        unlink($nome_arquivo);
        $caminhoImagens = 'https://s3-sa-east-1.amazonaws.com/mobilestock-pdf/' . $nome_arquivo;
        $extrato = new ExtratoService();
        $extrato->extrato = $caminhoImagens;
        $extrato->inserir();
    }
}

?>
