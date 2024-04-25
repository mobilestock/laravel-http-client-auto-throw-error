class MarketplaceFaturamento
{
    constructor(json)
    {
        this.faturamento = {
            id : json.id,
            cliente : json.cliente,
            data : json.dataEmissao,
            tipoPagamento: json.tipoPagamento,
        }
    }
}