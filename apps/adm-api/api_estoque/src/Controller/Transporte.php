<?php

namespace api_estoque\Controller;

use api_estoque\Models\Request_m;
use Exception;
use MobileStock\database\Conexao;
use MobileStock\helper\ConversorStrings;
use MobileStock\helper\Validador;
use MobileStock\repository\FotosRepository;
use MobileStock\service\TransporteService;

class Transporte extends Request_m
{
    public function __construct()
    {
        $this->nivelAcesso = Request_m::AUTENTICACAO_TOKEN;
        parent::__construct();
        $this->conexao = Conexao::criarConexao();
    }
    public function solicitaCadastroTransportadora()
    {
        try {
            $this->conexao->beginTransaction();

            Validador::validar($_FILES, [
                'foto_documento_habilitacao' => [Validador::OBRIGATORIO],
                'foto_documento_veiculo' => [Validador::OBRIGATORIO],
            ]);

            $situacao = TransporteService::verificaCadastroTransportadora($this->conexao, $this->idColaborador);
            if (isset($situacao)) {
                throw new Exception('Cadastro já solicitado!');
            }

            $fotoHabilitacao = FotosRepository::salvarFotoAwsS3(
                $_FILES['foto_documento_habilitacao'],
                'FOTO_HABILITACAO_' . $_ENV['AMBIENTE'] . '_' . $this->idColaborador . '_' . rand(),
                'ARQUIVOS_PRIVADOS',
                true
            );
            $fotoDocumentoVeiculo = FotosRepository::salvarFotoAwsS3(
                $_FILES['foto_documento_veiculo'],
                'FOTO_DOCUMENTO_' . $_ENV['AMBIENTE'] . '_' . $this->idColaborador . '_' . rand(),
                'ARQUIVOS_PRIVADOS',
                true
            );

            $transportadora = new TransporteService();
            $transportadora->id_colaborador = $this->idColaborador;
            $transportadora->foto_documento_habilitacao = $fotoHabilitacao;
            $transportadora->foto_documento_veiculo = $fotoDocumentoVeiculo;
            $transportadora->situacao = 'PE';
            $transportadora->salva($this->conexao);

            $this->status = 200;
            $this->retorno['status'] = true;
            $this->retorno['message'] = 'Solicitação realizada com sucesso!';
            $this->conexao->commit();
        } catch (\PDOException $pdoException) {
            $this->conexao->rollBack();
            $this->status = 500;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $pdoException->getMessage();
            $this->retorno['message'] = ConversorStrings::trataRetornoBanco($pdoException->getMessage());
        } catch (\Throwable $ex) {
            $this->conexao->rollBack();
            $this->status = 400;
            $this->retorno['status'] = false;
            $this->retorno['message'] = $ex->getMessage();
        } finally {
            $this->respostaJson
                ->setData($this->retorno)
                ->setStatusCode($this->status)
                ->send();
        }
    }
    public function listaFretesACaminho()
    {
        $fretes = TransporteService::buscaFretesACaminho();

        return $fretes;
    }
    public function listaFretesEntregues()
    {
        $fretes = TransporteService::buscaFretesEntregues();

        return $fretes;
    }
    public function listaFretes()
    {
        $fretes = TransporteService::listaFretesDisponiveis();

        return $fretes;
    }
}
