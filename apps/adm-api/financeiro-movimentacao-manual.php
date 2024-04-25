<?php
require_once 'cabecalho.php';

acessoUsuarioFinanceiro();

?>
<input type="hidden" value="<?= $_SESSION['id_usuario'] ?>" id="idColaborador">
<input type="hidden" value="<?= date('Y-m-d') ?>" id="hoje">
<div class="container-fluid">

    <h2><b>Caixa</b></h2>
    <br />
    <div class="col-12 btn card bg-light" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
        <br>
        <p>
        <h4>Inserir Entrada/Saída Manual</h4>
        </p>
        <br>
    </div>
    <div class="collapse" id="collapseExample">
        <div class="card card-body">
            <form id='formulario'>
                <div class=" row">
                    <div class="col-sm-2">
                        Tipo:
                        <select class="form-control" id="tipo" name="tipo" required>
                            <option value="">-- Tipo</option>
                            <option value="E">Entrada</option>
                            <option value="S">Saída</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        Valor:
                        <input class="form-control" type="number" step=".01" id="valor" name="valor" required>
                    </div>
                    <div class="col-sm-8">
                        Motivo:
                        <input class="form-control" type="text" name="motivo" id="motivo" required>
                    </div>
                </div>
                <br />
                <div class="row">
                    <div class="col-sm-2">
                        Responsável:
                        <select name="responsavel" id="responsavel" class='form-control'>
                            <option value="">-- Responsável</option>
                            <option value="356">Fábio</option>
                            <option value="526">Larissa</option>
                            <option value="8">Admin</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                    </div>
                    <div class="col-sm-2">
                        <br>
                        <button type="submit" id="adicionar" class="btn btn-primary btn-block">
                            <b>ADICIONAR</b>
                        </button>
                    </div>
                </div>
                <br />
            </form>
        </div>
    </div>
    <div class="card-header">
        <div class="row">
            <div class="col-sm-4">
                <label>Data Início:</label>
                <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="">
            </div>
            <div class="col-sm-4">
                <label>Data Final:</label>
                <input type="date" id="data_final" name="data_final" class="form-control" value="">
            </div>
            <div class="col-sm-2">
            </div>
            <div class="col-sm-2">
                <br>
                <button class="btn btn-block btn-dark pesquisar"><b>PESQUISAR</b></button>
            </div>
        </div>
        <br>
        <br>
        <div class="row">
            <div class="col-sm-4">
                <h2>Saldo Total: <b><label id="saldo"></label></b></h2>
            </div>
            <div class="col-sm-3">
                <button class="btn btn-success btn-block" id="inicio"><b>INICIAR CAIXA</b></button>
            </div>
            <div class="col-sm-3">
                <button class="btn btn-dark btn-block" id="fechamento"><b>FECHAMENTO DE CAIXA</b></button>
            </div>
            <div class="col-sm-2">
                <a class="btn btn-danger btn-block" href="marketplace.php">
                    <b>VOLTAR</b>
                </a>
            </div>
        </div>
    </div>

    <div id="movimentacoes"></div>
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Valor Inicial do Caixa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="valor_inicio" class="col-form-label">Valor Inicial:</label>
                        <input class="form-control" type="number" step=".01" id="valor_inicio">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary iniciar" data-dismiss="modal">Iniciar</button>
                </div>
            </div>
        </div>
    </div>
    <script src='js/financeiro-movimentacao-manual.js<?= $versao ?>'></script>
</div>
<script>
    function saldo(filtro = 0) {
        var form = new FormData;
        if (filtro > 0) {
            var inicio = $('#data_inicio').val();
            var final = $('#data_final').val();
            if (inicio) {
                form.append('data_inicio', inicio)
            }
            if (final) {
                form.append('data_final', final)
            }
        }
        form.append('acao', 'totalCaixa');
        fetch('src/controller/movimentacaoManualCaixaController.php', {
                method: 'POST',
                body: form
            }).then(resp => resp.json())
            .then(resultado => {
                saldo = resultado.body;
                valor_total = saldo;
                $('#saldo').html('R$' + (saldo ? saldo.toFixed(2) : '0.00'));
                $('#inicio').prop("disabled", true);
            })
            .catch(erro => {
                $('#saldo').html(' R$ ' + '0.00');
                $('#inicio').prop("disabled", false);

            });
    };
    $(document).ready(function() {
        saldo();
    })

    document.getElementById('formulario').addEventListener('submit', function(e) {
        e.preventDefault()
        const tipo = document.querySelector('#tipo').value
        const valor = document.querySelector('#valor').value
        const motivo = document.querySelector('#motivo').value
        const responsavel = document.querySelector('#responsavel').value

        const form = new FormData
        form.append('tipo', tipo)
        form.append('valor', valor)
        form.append('motivo', motivo)
        form.append('responsavel', responsavel)
        form.append('acao', 'criaMovimentacaoManual')
        fetch('src/controller/movimentacaoManualCaixaController.php', {
            method: 'POST',
            body: form
        }).then(() => {
            saldo();
            buscaDadosParaTabela('#movimentacoes', 'buscarTodos');
        })
        window.location.reload();
    })
    $('.pesquisar').click(function() {
        var form = new FormData;

        var inicio = $('#data_inicio').val();
        var final = $('#data_final').val();
        if (inicio) {
            form.append('data_inicio', inicio)
        }
        if (final) {
            form.append('data_final', final)
        }
        form.append('acao', 'totalCaixa');
        fetch('src/controller/movimentacaoManualCaixaController.php', {
                method: 'POST',
                body: form
            }).then(resp => resp.json())
            .then(resultado => {
                saldo = resultado.body;
                valor_total = saldo;
                $('#saldo').html('R$' + (saldo ? saldo.toFixed(2) : '0.00'));
                $('#inicio').prop("disabled", true);
            })
            .catch(erro => {
                $('#saldo').html(' R$ ' + '0.00');
                $('#inicio').prop("disabled", false);

            });
        buscaDadosParaTabela('#movimentacoes', 'buscarTodos', 2);

    })
    $(document).on('click', '.conferir', function() {
        const id = $(this).attr('id')
        const idColaborador = document.querySelector('#idColaborador').value

        const form = new FormData
        form.append('id', id)
        form.append('idColaborador', idColaborador)
        form.append('acao', 'atualizaMovimentacaoManual')
        fetch('src/controller/movimentacaoManualCaixaController.php', {
            method: 'POST',
            body: form
        }).then(() => {
            buscaDadosParaTabela('#movimentacoes', 'buscarTodos');
            saldo();
        })
    })


    var valor_total = 0.00;


    $('.iniciar').click(function() {
        var valor = $('#valor_inicio').val();
        var data = $('#hoje').val();
        var responsavel = $('#idColaborador').val();
        const form = new FormData
        form.append('tipo', 'E')
        form.append('conferido_em', data)
        form.append('valor', valor)
        form.append('motivo', 'Início de Caixa')
        form.append('responsavel', responsavel)
        form.append('conferido_por', responsavel)
        form.append('acao', 'criaMovimentacaoManual')
        fetch('src/controller/movimentacaoManualCaixaController.php', {
            method: 'POST',
            body: form
        }).then(() => {
            saldo();
            buscaDadosParaTabela('#movimentacoes', 'buscarTodos');
        })
    })
    $('#inicio').click(function() {
        $('#exampleModal').modal('show');
    });

    $('#fechamento').click(function() {
        saldo = valor_total;
        $.confirm({
            title: 'Atenção!',
            content: 'Esta ação irá zerar o caixa atual de R$' + saldo.toFixed(2) + '. Tem certeza que deseja fechar o caixa do dia?',
            buttons: {
                sair: function() {

                },

                fechar: {
                    text: 'FECHAMENTO DE CAIXA',
                    btnClass: 'btn-danger',
                    keys: ['enter', 'shift'],
                    action: function() {
                        responsavel = $('#idColaborador').val();
                        const form = new FormData
                        form.append('tipo', 'S')
                        form.append('valor', saldo)
                        form.append('motivo', 'Fechamento de Caixa')
                        form.append('responsavel', responsavel)
                        form.append('acao', 'criaMovimentacaoManual')
                        fetch('src/controller/movimentacaoManualCaixaController.php', {
                            method: 'POST',
                            body: form
                        }).then(() => {
                            buscaDadosParaTabela('#movimentacoes', 'buscarTodos');
                            $('#inicio').prop("disabled", false);
                            window.location.reload();
                        })

                    }
                }
            }
        });
    });
</script>

<?php
require_once 'rodape.php';
?>