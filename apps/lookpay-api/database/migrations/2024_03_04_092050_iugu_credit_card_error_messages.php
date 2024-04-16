<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('iugu_credit_card_error_messages', function (Blueprint $table) {
            $table->id();
            $table->string('lr_code', 10);
            $table->string('message', 200)->nullable();
            $table->string('recommended_action', 200)->nullable();
        });

        DB::insert(
            "INSERT INTO iugu_credit_card_error_messages (
                iugu_credit_card_error_messages.id,
                iugu_credit_card_error_messages.lr_code,
                iugu_credit_card_error_messages.message,
                iugu_credit_card_error_messages.recommended_action
            ) VALUES
                (1, '0', 'Transação autorizada com sucesso.', NULL),
                (2, '00', 'Transação autorizada com sucesso.', NULL),
                (3, '1', 'Transação não autorizada. Referida (suspeita de fraude) pelo banco emissor.', NULL),
                (4, '2', 'Transação não autorizada. Referida (suspeita de fraude) pelo banco emissor.', NULL),
                (5, '3', 'Não foi possível processar a transação.', 'Entre com contato com a Loja Virtual.'),
                (6, '4', 'Transação não autorizada. Cartão bloqueado pelo banco emissor.', NULL),
                (7, '5', 'Transação não autorizada. Cartão inadimplente (Do not honor).', NULL),
                (8, '6', 'Transação não autorizada. Cartão cancelado.', NULL),
                (9, '7', 'Transação negada.', 'Reter cartão condição especial'),
                (10, '8', 'Transação não autorizada. Código de segurança inválido.', NULL),
                (11, '9', 'Transação cancelada parcialmente com sucesso.', NULL),
                (12, '11', 'Transação autorizada com sucesso para cartão emitido no exterior', NULL),
                (13, '12', 'Transação inválida, erro no cartão.', NULL),
                (14, '13', 'Transação não permitida. Valor da transação inválido.', NULL),
                (15, '14', 'Transação não autorizada. Cartão inválido', NULL),
                (16, '15', 'Banco emissor indisponível ou inexistente.', NULL),
                (17, '19', NULL, 'Refaça a transação ou tente novamente mais tarde.'),
                (18, '21', 'Cancelamento não efetuado. Transação não localizada.', NULL),
                (19, '22', 'Parcelamento inválido. Número de parcelas inválidas.', NULL),
                (20, '23', 'Transação não autorizada. Valor da prestação inválido.', NULL),
                (21, '24', 'Quantidade de parcelas inválido.', NULL),
                (22, '25', 'Pedido de autorização não enviou número do cartão', NULL),
                (23, '28', 'Arquivo temporariamente indisponível.', NULL),
                (24, '30', 'Transação não autorizada. Decline Message', NULL),
                (25, '39', 'Transação não autorizada. Erro no banco emissor.', NULL),
                (26, '41', 'Transação não autorizada. Cartão bloqueado por perda.', NULL),
                (27, '43', 'Transação não autorizada. Cartão bloqueado por roubo.', NULL),
                (28, '51', 'Transação não autorizada. Limite excedido/sem saldo.', NULL),
                (29, '52', 'Cartão com dígito de controle inválido.', NULL),
                (30, '53', 'Transação não permitida. Cartão poupança inválido', NULL),
                (31, '54', 'Transação não autorizada. Cartão vencido', NULL),
                (32, '55', 'Transação não autorizada. Senha inválida', NULL),
                (33, '56', 'NÚMERO CARTÃO NÃO PERTENCE AO EMISSOR | NÚMERO CARTÃO INVÁLIDO', NULL),
                (34, '57', 'Transação não permitida para o cartão', NULL),
                (35, '58', 'Transação não permitida. Opção de pagamento inválida.', NULL),
                (36, '59', 'Transação não autorizada. Suspeita de fraude.', NULL),
                (37, '60', 'Transação não autorizada.', NULL),
                (38, '61', 'Banco emissor indisponível.', NULL),
                (39, '62', 'Transação não autorizada. Cartão restrito para uso doméstico', NULL),
                (40, '63', 'Transação não autorizada. Violação de segurança', NULL),
                (41, '64', 'Transação não autorizada. Valor abaixo do mínimo exigido pelo banco emissor.', NULL),
                (42, '65', 'Transação não autorizada. Excedida a quantidade de transações para o cartão.', NULL),
                (43, '67', 'Transação não autorizada. Cartão bloqueado para compras hoje.', NULL),
                (44, '70', 'Transação não autorizada. Limite excedido/sem saldo.', NULL),
                (45, '72', 'Cancelamento não efetuado. Saldo disponível para cancelamento insuficiente.', NULL),
                (46, '74', 'Transação não autorizada. A senha está vencida.', NULL),
                (47, '75', 'Senha bloqueada. Excedeu tentativas de cartão.', NULL),
                (48, '76', 'Cancelamento não efetuado. Banco emissor não localizou a transação original', NULL),
                (49, '77', 'Cancelamento não efetuado. Não foi localizado a transação original', NULL),
                (50, '78', 'Transação não autorizada. Cartão bloqueado primeiro uso.', NULL),
                (51, '79', 'Transação não autorizada.', 'Entre em contato com o seu banco.'),
                (52, '80', 'Transação não autorizada. Divergencia na data de transação/pagamento.', NULL),
                (53, '81', 'Transação não autorizada. A senha está vencida.', NULL),
                (54, '82', 'Transação não autorizada. Cartão inválido.', NULL),
                (55, '83', 'Transação não autorizada. Erro no controle de senhas', NULL),
                (56, '85', 'Transação não permitida. Falha da operação.', NULL),
                (57, '86', 'Transação não permitida. Falha da operação.', NULL),
                (58, '88', 'Falha na criptografia dos dados.', NULL),
                (59, '89', 'Erro na transação.', NULL),
                (60, '90', 'Transação não permitida. Falha da operação.', NULL),
                (61, '91', 'Transação não autorizada. Banco emissor temporariamente indisponível.', NULL),
                (62, '92', 'Transação não autorizada. Tempo de comunicação excedido.', NULL),
                (63, '93', 'Transação não autorizada. Violação de regra, possível erro no cadastro.', NULL),
                (64, '94', 'Transação duplicada.', NULL),
                (65, '96', 'Falha no processamento.', NULL),
                (66, '97', 'Valor não permitido para essa transação.', NULL),
                (67, '98', 'Sistema/comunicação indisponível.', NULL),
                (68, '99', 'Sistema/comunicação indisponível.', NULL),
                (69, '75', 'Timeout de Cancelamento', NULL),
                (70, '999', 'Sistema/comunicação indisponível.', NULL),
                (71, 'A2', 'VERIFIQUE OS DADOS DO CARTÃO', NULL),
                (72, 'A3', 'ERRO NO CARTÃO', 'NÃO TENTE NOVAMENTE'),
                (73, 'A5', 'TRANSAÇÃO NÃO PERMITIDA', 'NÃO TENTE NOVAMENTE'),
                (74, 'A7', 'ERRO NO CARTÃO', 'NÃO TENTE NOVAMENTE'),
                (75, 'AA', 'Tempo Excedido', NULL),
                (76, 'AB', 'FUNÇÃO INCORRETA (DÉBITO)', NULL),
                (77, 'AC', 'Transação não permitida. Cartão de débito sendo usado com crédito.', 'Use a função débito.'),
                (78, 'AE', 'Tente Mais Tarde', NULL),
                (79, 'AF', 'Transação não permitida. Falha da operação.', NULL),
                (80, 'AG', 'Transação não permitida. Falha da operação.', NULL),
                (81, 'AH', 'Transação não permitida. Cartão de crédito sendo usado com débito.', 'Use a função crédito.'),
                (82, 'AI', 'Transação não autorizada. Autenticação não foi realizada.', NULL),
                (83, 'AJ', 'Transação não permitida. Transação de crédito ou débito em uma operação que permite apenas Private Label.', 'Tente novamente selecionando a opção Private Label.'),
                (84, 'AV', 'Transação não autorizada. Dados inválidos', NULL),
                (85, 'BD', 'Transação não permitida. Falha da operação.', NULL),
                (86, 'BL', 'Transação não autorizada. Limite diário excedido.', NULL),
                (87, 'BM', 'Transação não autorizada. Cartão inválido', NULL),
                (88, 'BN', 'Transação não autorizada. Cartão ou conta bloqueado.', NULL),
                (89, 'BO', 'Transação não permitida. Falha da operação.', NULL),
                (90, 'BP', 'Transação não autorizada. Conta corrente inexistente.', NULL),
                (91, '76', 'Transação não permitida.', NULL),
                (92, 'BV', 'Transação não autorizada. Cartão vencido', NULL),
                (93, 'CF', 'Transação não autorizada.C79:J79 Falha na validação dos dados.', NULL),
                (94, 'CG', 'Transação não autorizada. Falha na validação dos dados.', NULL),
                (95, 'DA', 'Transação não autorizada. Falha na validação dos dados.', NULL),
                (96, 'DF', 'Transação não permitida. Falha no cartão ou cartão inválido.', NULL),
                (97, 'DM', 'Transação não autorizada. Limite excedido/sem saldo.', NULL),
                (98, 'DQ', 'Transação não autorizada. Falha na validação dos dados.', NULL),
                (99, 'DS', 'Transação não permitida para o cartão', NULL),
                (100, 'EB', 'Transação não autorizada. Limite diário excedido.', NULL),
                (101, 'EE', 'Transação não permitida. Valor da parcela inferior ao mínimo permitido.', NULL),
                (102, 'EK', 'Transação não permitida para o cartão', NULL),
                (103, 'FA', 'Transação não autorizada.', NULL),
                (104, 'FC', 'Transação não autorizada. Ligue Emissor', NULL),
                (105, 'FD', 'Transação negada. Reter cartão condição especial', NULL),
                (106, 'FE', 'Transação não autorizada. Divergencia na data de transação/pagamento.', NULL),
                (107, 'FF', 'Cancelamento OK', NULL),
                (108, 'FG', 'Transação não autorizada. Ligue AmEx 08007285090.', NULL),
                (109, 'GA', 'Aguarde Contato', NULL),
                (110, 'GD', 'Transação não permitida.', NULL),
                (111, 'HJ', 'Transação não permitida. Código da operação inválido.', NULL),
                (112, 'IA', 'Transação não permitida. Indicador da operação inválido.', NULL),
                (113, 'JB', 'Transação não permitida. Valor da operação inválido.', NULL),
                (114, 'P5', 'TROCA DE SENHA / DESBLOQUEIO', NULL),
                (115, 'KA', 'Transação não permitida. Falha na validação dos dados.', NULL),
                (116, 'KB', 'Transação não permitida. Selecionado a opção incorrente.', NULL),
                (117, 'KE', 'Transação não autorizada. Falha na validação dos dados.', NULL),
                (118, 'N7', 'Transação não autorizada. Código de segurança inválido.', NULL),
                (119, 'R0', 'SUSPENSÃO DE PAGAMENTO RECORRENTE PARA UM SERVIÇO', NULL),
                (120, 'R1', 'Transação não autorizada. Cartão inadimplente (Do not honor)', NULL),
                (121, 'R2', 'TRANSAÇÃO NÃO QUALIFICADA PARA VISA PIN', NULL),
                (122, 'R3', 'SUSPENSÃO DE TODAS AS ORDENS DE AUTORIZAÇÃO', NULL),
                (123, 'U3', 'Transação não permitida. Falha na validação dos dados.', NULL),
                (124, 'N3', 'SAQUE NÃO DISPONÍVEL', NULL),
                (125, 'N8', 'DIFERENÇA. PRÉ AUTORIZAÇÃO', NULL),
                (126, 'NR', 'Transação não permitida.', 'Retentar a transação após 30 dias'),
                (127, 'RP', 'Transação não permitida.', 'Retentar a transação após 72 horas'),
                (128, '9A', 'Token não encontrado', NULL),
                (129, '9B', 'Sistema indisponível/Falha na comunicação', NULL),
                (130, '9C', 'Sistema indisponível/Exceção no processamento', NULL),
                (131, '9Z', 'Sistema indisponível/Retorno desconhecido', NULL),
                (132, 'TA', 'Timeout na requisição. O tempo para receber o retorno da requisição excedeu.', NULL),
                (133, '01', 'Recusado manualmente em análise antifraude', NULL),
                (134, '02', 'Recusado automaticamente em análise antifraude', NULL),
                (135, 'AF03', 'Recusado pelo antifraude da adquirente de crédito', 'Transação não permitida conforme análise de acusa por suspeita a fraude'),
                (136, '26', 'A data de validade do cartão de crédito é inválida', NULL),
                (137, 'AF01','Recusado manualmente em análise antifraude', NULL),
                (138, 'AF02', 'Recusado automaticamente em análise antifraude', NULL)"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iugu_credit_card_error_messages');
    }
};
