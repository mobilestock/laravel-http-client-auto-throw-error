<?php
require_once '../regras/alertas.php';
acessoUsuarioAdministrador();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/jquery-confirm.min.css">
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/9e8aef2f91.js" crossorigin="anonymous"></script>
    <title>Relatório de faturamentos do fornecedor</title>
</head>
<body>
    <style>
        .modal-relacao-notas-fiscais{
            margin: 20px;
        }
    </style>
    <div class="modal-relacao-notas-fiscais">
        <h2>Relatório de comissão sobre faturamentos</h2>
        <div class="faturamentos">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Pedido</th>
                        <th>Emissão</th>
                        <th>Fechamento</th>
                        <th>Valor Produtos</th>
                        <th>Pares</th>
                        <th>Cliente</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="tabela">
                </tbody>
            </table>
        </div>
    </div>
    <script src="../js/asyncAjax.js"></script>
    <script src="../js/relatorio-nota-fiscal-comissao.js"></script>
</body>
</html>