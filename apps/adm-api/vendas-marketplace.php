<?php

require_once 'cabecalho.php';
?>

<style>

    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    body {
        background:#F4F4F4;
    }
    
    #vendas-marketplace{
        padding:1rem
    }

    #vendas-marketplace h3{
        font-family: Montserrat;
        font-style: normal;
        font-weight: bold;
        font-size: .9em;
        line-height: 1.1em;

    }
    #vendas-marketplace h6{
        font-family: Montserrat;
        font-style: normal;
        font-weight: bold;
        font-size: .7em;
        line-height: .8em;

    }
    #vendas-marketplace h1{
        font-size:1.7em;
        margin-bottom:2rem;
        font-family: Montserrat;
        font-style: normal;
        font-weight: bold;
        font-size: 1.5em;
        line-height: 2em;
    }






    .cartao-branco{
        display:flex;
        flex-direction: column;
        min-width: 12rem;
        min-height: 10rem;
        background-color:#fff;
        border-radius:5px;
        justify-content:space-around;
        padding:10px;
    }


    
    #recebedor
    #agencia
    #conta
    #banco
    {
        font-family: 'Poppins', sans-serif;
        font-style: normal;
        font-weight: normal;
        font-size: .7em;
        line-height: .9em;
    }
    #banco{
        max-width: 10rem;
    }
    #vendas-marketplace section button{
        font-style: normal;
        font-weight: 400;
        font-size: 1em;
        line-height: 1.1em;
    }
    #receber,
    #saldo
    {
        font-family: 'Poppins', sans-serif;
        font-style: normal;
        font-weight: normal;
        font-size: 1em;
        line-height: 2em;
    }

    #alterar-banco{
        height:2.6rem
    }
    .cartao-branco-receber{
        display:flex;
        min-width: 12rem;
        background-color:#fff;
        border-radius:5px;
    justify-content:space-around;
    align-items: center;
    min-height: 3rem;
    padding:10px;
}

@media (min-width:1300px){
    .cartao-branco-receber{
        min-height: 10rem;
    }
        .cartao-branco{
            display:flex;
            flex-direction: column;
            min-width: 23rem;
            min-height: 10rem;
            background-color:#fff;
            border-radius:5px;
            justify-content:space-around;
            padding:10px;
        }
    }
</style>

<main class="container-fluid" id="vendas-marketplace">
    <header>
        <h1>
            <i class="fas fa-wallet"></i>
            Carteira
        </h1>
        <div class="row  justify-content-start">

        <div class="col-6 col-sm-3">

            <button class="btn btn-sm btn-block bg-danger">
                Voltar
            </button>
        </div>
        </div>
    </header>
    <section>

    <div class="row p-1 justify-content-center align-items-center">

        <div class="col-12 col-sm-4 m-1 cartao-branco-receber shadow-sm">
            <div class="esquerda">
                <h3>A receber</h3>
            </div>
            <div class="centro">
                <div id="receber">
                    R$ 1 000,00
                </div>
            </div>
            <!-- <div class="direita">
                <button id="detalhes" class="btn  bg-light">
                    Detalhes
                </button>
            </div> -->
        </div>


        <div class="cartao-branco col-12 m-1 col-sm-7 shadow-sm m-0 " >
            <div class="row d-flex justify-content-between">
                <div class=" col-auto esquerdo">
                    <h6>Recebedor</h6>
                    <div id="recebedor">
                        Mobile Stock
                    </div>
                </div>
                <div class="col-auto">
                    <div class="row">
                        <div class="col-auto">
                            <h6>Agência</h6>
                            <div id="agencia">
                                000000
                            </div>
                        </div>
                        <div class="col-auto">
                            <h6>Conta</h6>
                            <div id="conta">
                                000000000000-0
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                
            <hr class="m-0 p-0">
            
            <div class="row d-flex justify-content-between">
                <div class="col-auto esquerdo">
                    <h6>Banco</h6>
                    <div id="banco">
                        000 - Nenhum banco informado
                    </div>
                </div>
                
                <div class="col-auto centro">
                    <button id="alterar-banco" class="btn bg-light">
                        Alterar Banco
                    </button>
                </div>
            </div>
        </div>
    </div>

    </section>

</main>


<script>

    window.onload = function(){

        const alterarBanco = document.getElementById('alterar-banco')
        const sacar = document.getElementById('sacar')
        const detalhes = document.getElementById('detalhes')



        alterarBanco && alterarBanco.addEventListener('click', function(){

            $.confirm({
                title:"Alterar Banco",
                content: 'url:teste.php',
                onContentReady: function () {
                    var self = this;
                },
                type:"dark",
                buttons:{
                    ok:{
                        text:"Salvar",
                        btnClass:"btn-green",
                        action:function(){
                            var name = this.$content.find('#selecione-conta-Bancaria').val();
                            $.alert(name)
                        }
                    },
                    cancel:{
                        text:"Cancelar",
                        action:function(){
                        }
                    }
                }
            })
        })

        detalhes && detalhes.addEventListener('click', function(){
            $.confirm({
                title:"detalhes",
                content:"escolha um banco",
                buttons:{
                    ok:{
                        text:"ola",
                        btnClass:"btn-red",
                        action:function(){
                            $.alert("alterado")
                        }
                    }
                }
            })
        })

        sacar && sacar.addEventListener('click', function(){
            $.confirm({
                title:"Sacar",
                content: 'url:teste.php',
                onContentReady: function () {
                    var self = this;
                },
                type:"dark",
                buttons:{
                    ok:{
                        text:"Salvar",
                        btnClass:"btn-green",
                        action:function(){
                            var name = this.$content.find('#selecione-conta-Bancaria').val();
                            $.alert(name)
                        }
                    },
                    cancel:{
                        text:"Cancelar",
                        action:function(){
                        }
                    }
                }
            })
        })
    }
</script>

<?php
require_once 'rodape.php';