class Pagamento
{
    criaFormaPagamento(formPagamento) {

        this._ = document.querySelector.bind(document);

        // let tipoPagamento = pedidoZerado?'deposito':_('.tipo_tabela:checked').value;
        this._formPagamento = formPagamento;
        let pagamentos = {cartao:1,deposito:2,boleto:3,credito:4};
        this._tipoPagamento = pagamentos[tipoPagamento];
        taxaJuros = existeCreditoManual==1 && creditoManual >= 500 ? 0 : taxaJuros;

        this._formPagamento.append('tipoPagamento',this._tipoPagamento);
        this._formPagamento.append('nomePagamento',tipoPagamento);
        this._formPagamento.append('existeCreditoManual',existeCreditoManual);
        this._formPagamento.append('valorFrete',valor_frete);
        this._formPagamento.append('valorProdutos',valor_produtos);
        this._formPagamento.append('creditos_manual',creditoManual);
        this._formPagamento.append('juros',taxaJuros);
        let pay;

        switch (this._tipoPagamento) {

            //cartao
            case 1:
                pay = this.fillCard();
                this._formPagamento.append("holder_name", holder_name);
                this._formPagamento.append("card_number", card_number);
                this._formPagamento.append("parcelas", parcelas);
                this._formPagamento.append("expiration_month", expiration_month);
                this._formPagamento.append("expiration_year", expiration_year);
                this._formPagamento.append("secure_code", secure_code);
                  
                // this._formPagamento.append("holderName",holder_name);
                // this._formPagamento.append("cardNumber",card_number);
                // this._formPagamento.append("parcelas",parcelas);
                // this._formPagamento.append("expirationMonth",expiration_month);
                // this._formPagamento.append("expirationYear",expiration_year);
                // this._formPagamento.append("secureCode",secure_code);
                break;

            //deposito
            case 2:
                pay = this.fillBank();
                this._formPagamento.append("contaBancaria",pay.bank);
                break;

            //boleto
            case 3:
                break;

            case 5:
                this._formPagamento.append("adicional",true);
                break;

            //default - credito
            default:
                this._formPagamento.append("creditoAproveitado",true);
                break;
        }
        
        return this.formPagamento;
    }

    fillCard(){
        let values = {
            'holder_name':_("#cc-name").value,
            'card_number':_("#cc-number").value,
            'parcelas':_("#parcelas").value,
            'expiration_month':_("#cc-month").value,
            'expiration_year':_("#cc-year").value,
            'secure_code':_("#cc-cvv").value
        }
        return this.validValues(values);
    }

    fillBank(){
        let values = {
            'bank':'dinheiro'
        }
        return this.validValues(values);
    }

    validValues(values){
        let valida = [];
        for(var [key,value] of Object.entries(values)){
            if(value==''){
                valida.push(this.filtraNome(key));
            }
        }

        if(valida.length>0){
            respostaErro({'error' : `Os campos <span class="text-danger">${Object.keys(valida).map((key)=>valida[key]).join(', ')}</span> não devem ficar vazios.`});
            return;
        }
        return values;
    }

    get formPagamento()
    {
        return this._formPagamento;
    }

    filtraNome(nome){
        let traducao = {
            'holder_name':'Nome no cartão',
            'card_number':'Número no cartão',
            'expiration_month':'Mês de vencimento',
            'expiration_year':'Ano de vencimento',
            'bank':'Conta bancaria'
        }
        return traducao[nome]!=='undefinied'?traducao[nome]:nome;
    }

}