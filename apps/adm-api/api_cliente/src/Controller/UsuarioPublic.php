<?php

namespace api_cliente\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use MobileStock\helper\Validador;
use MobileStock\model\ColaboradorEndereco;
use MobileStock\model\ColaboradorModel;
use MobileStock\model\Origem;
use MobileStock\model\UsuarioModel;
use MobileStock\service\ColaboradoresService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UsuarioPublic
{
    public function adicionaUsuario(Origem $origem)
    {
        DB::beginTransaction();
        $dadosJson = Request::all();
        $dadosJson['telefone'] = Request::telefone();

        Validador::validar($dadosJson, [
            'nome' => [Validador::OBRIGATORIO, Validador::SANIZAR],
            'seller' => [Validador::SE(Validador::OBRIGATORIO, [Validador::BOOLEANO])],
            'email' => [Validador::SE(!empty($dadosJson['seller']), [Validador::OBRIGATORIO, Validador::EMAIL])],
            'senha' => [
                Validador::SE(!empty($dadosJson['seller']), [Validador::OBRIGATORIO, Validador::TAMANHO_MINIMO(6)]),
            ],
            'id_cidade' => [Validador::OBRIGATORIO, Validador::NUMERO],
        ]);

        $dadosJson['senha'] ??= null;
        $dadosJson['email'] ??= null;
        $dadosJson['seller'] ??= false;
        $existeTelefone = ColaboradorModel::existeTelefone($dadosJson['telefone']);
        if ($existeTelefone) {
            throw new ConflictHttpException('Já existe um cadastro com este telefone');
        }
        if (isset($dadosJson['email']) && ColaboradorModel::existeEmail($dadosJson['email'])) {
            throw new ConflictHttpException('Email já cadastrado!');
        }

        $colaborador = ColaboradorModel::create([
            'razao_social' => $dadosJson['nome'],
            'telefone' => $dadosJson['telefone'],
            'email' => $dadosJson['email'],
            'regime' => 3,
            'bloqueado_repor_estoque' => $dadosJson['seller'] ? 'F' : 'T',
        ]);
        $colaborador->buscaOuGeraUsuarioMeulook($colaborador->id);

        $usuario = UsuarioModel::create([
            'nome' => $colaborador->usuario_meulook,
            'senha' => is_null($dadosJson['senha']) ? null : password_hash($dadosJson['senha'], PASSWORD_ARGON2ID),
            'email' => $colaborador->email,
            'telefone' => $colaborador->telefone,
            'id_colaborador' => $colaborador->id,
            'nivel_acesso' => $dadosJson['seller'] ? 30 : 10,
            'tipos' => $dadosJson['seller'] ? 'F' : 'U',
            'permissao' => $dadosJson['seller'] ? '10,30' : '10',
        ]);

        Auth::setUser($usuario);

        $colaboradoresEndereco = new ColaboradorEndereco();
        $colaboradoresEndereco->salvarIdCidade($dadosJson['id_cidade']);

        if ($origem->ehMl()) {
            try {
                ColaboradoresService::enviaAtalhoLogin($colaborador->telefone, $colaborador->razao_social);
            } catch (\Throwable $exception) {
                Log::error('Erro ao enviar mensagem whatsapp de cadastro', [
                    'exception' => $exception,
                    'usuario' => [
                        'id_colaborador' => $colaborador->id,
                        'id_usuario' => $usuario->id,
                        'razao_social' => $colaborador->razao_social,
                        'id_cidade' => $dadosJson['id_cidade'],
                        'eh_seller' => $dadosJson['seller'],
                        'telefone' => $colaborador->telefone,
                    ],
                ]);
            }
        }

        DB::commit();
        return new JsonResponse(['id_colaborador' => $colaborador->id], Response::HTTP_CREATED);
    }
}
