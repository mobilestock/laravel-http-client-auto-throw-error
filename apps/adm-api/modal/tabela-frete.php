<html>

<body>
    <div class="container-fluid body-novo"><br>
        <p style="font-size:16px;font-weight:bold;">Quanto maior a quantidade comprada, maior o desconto do frete por par. Confira a simulação logo abaixo:</p>
        <p>
            <label>UF: <b><span class="uf_tabela"></span></b></label>
            <br>
            <label>Pares: <b><span class="pares_tabela"></span></b></label>
        </p>
        <div class="form-group">
            <div class="row cabecalho2">
                <div class="col-sm-2 col-2">Pares</div>
                <div class="col-sm-5 col-10">Frete por par: <br>A Vista</div>
            </div>
            <div id="tabela-frete">
            </div>
        </div>
        <p>
            Para compras acima de <strong>R$ <label id="label_frete_gratis_modal"></label></strong> o frete é <strong>GRÁTIS</strong>.
        </p>
    </div>


    <script src="js/tabela-frete.js"></script>
</body>

</html>