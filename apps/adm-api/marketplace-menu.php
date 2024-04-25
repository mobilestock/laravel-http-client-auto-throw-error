<?php

require_once __DIR__ . '/cabecalho.php';
require_once __DIR__ . '/vendor/autoload.php';

acessoUsuarioAdministrador();

?>
<div class="container-fluid body-novo">
    <h2>MarketPlace Transações</h2>
    <table class="table table-hover">
        <thead class="table-dark">
            <tr>
                <th>data</th>
                <th>id</th>
                <th>valor</th>
            </tr>
        </thead>
        <tbody id="corpo">

        </tbody>
    </table>
</div>
<script src="js/marketplace-menu.js<?= $versao; ?>"></script>