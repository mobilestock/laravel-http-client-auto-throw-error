<?php require_once 'cabecalho.php';?>
<div class="container">
    <form action="envia-contato.php" method="post" id='formulario'>
        <h2>Contato </h2>
        <table class="table">
            <tr>
                <td>Nome</td>
                <td><input type="text" name="nome" class="form-control"></td>
            </tr>
            <tr>
                <td>Email</td>
                <td><input type="text" name="email" class="form-control"></td>
            </tr>
            <tr>
                <td>Mensagem</td>
                <td><input type="text" name="mensagem" class="form-control"></td>
            </tr>
            <tr>
                <td><button class="btn btn-primary">Enviar</button></td>
            <tr>
        </table>
    </form>
</div>
<?php require_once 'rodape.php';?>