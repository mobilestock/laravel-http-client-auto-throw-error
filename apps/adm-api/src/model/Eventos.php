<?php
namespace MobileStock\model;

use MobileStock\service\Lancamento\LancamentoService;
use MobileStock\service\SplitService;
use PDO;

class Eventos
{
    private $conexao;

    public function __construct(PDO $conexao, string $metodo, array $recebiveis) {
        $this->conexao = $conexao;
        $this->$metodo($recebiveis);
    }

    public function created(array $recebiveis)
    {
        // $lancService = new LancamentoService();
        // $lancService->criaLancamentosDeRecebiveis($this->conexao, $recebiveis['payload']['object']);

    }

    public function paid(array $recebiveis)
    {
        // $lancService = new LancamentoService();
        // $lancService->atualiza(
        //     ['SET'=>
        //         ['bloqueado'=>'0','data_liquidacao'=>['payload']['object']['paid_at']],
        //     'WHERE'=>
        //         ['id_split'=>$recebiveis['payload']['object']['split_rule'],'origem'=>'SP']]);
        // $lancService->atualiza(
        //     ['SET'=>
        //         ['bloqueado'=>'0','valor_pago'=>'valor(CAMPO)','data_liquidacao'=>['payload']['object']['paid_at']],
        //     'WHERE'=>['cod_transacao'=>$recebiveis['payload']['object']['transaction'],'origem'=>'FA']]);
    }

}