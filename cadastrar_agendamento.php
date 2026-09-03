<?php

require_once 'conexao.php';


/* =========================================
   DADOS PARA OS CAMPOS
   ========================================= */

$clientes = $conexao
    ->query(
        'SELECT cli_id, cli_nome
         FROM CLIENTE
         ORDER BY cli_nome'
    )
    ->fetchAll(PDO::FETCH_ASSOC);


$barbeiros = $conexao
    ->query(
        'SELECT barb_id, barb_nome
         FROM BARBEIRO
         ORDER BY barb_nome'
    )
    ->fetchAll(PDO::FETCH_ASSOC);


$servicos = $conexao
    ->query(
        'SELECT
            serv_id,
            serv_nome,
            serv_preco,
            serv_duracao_min
         FROM SERVICO
         ORDER BY serv_nome'
    )
    ->fetchAll(PDO::FETCH_ASSOC);


$mensagem = '';


/* =========================================
   CRIAÇÃO DO AGENDAMENTO
   ========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $clienteId = filter_input(
        INPUT_POST,
        'cliente_id',
        FILTER_VALIDATE_INT
    );

    $barbeiroId = filter_input(
        INPUT_POST,
        'barbeiro_id',
        FILTER_VALIDATE_INT
    );

    $servicoId = filter_input(
        INPUT_POST,
        'servico_id',
        FILTER_VALIDATE_INT
    );

    $dataHora = trim(
        $_POST['data_hora'] ?? ''
    );


    if (
        !$clienteId ||
        !$barbeiroId ||
        !$servicoId ||
        $dataHora === ''
    ) {

        $mensagem =
            'Preencha todos os campos.';

    } else {

        try {

            /* Busca preço e duração do serviço */

            $consultaServico =
                $conexao->prepare(
                    'SELECT
                        serv_preco,
                        serv_duracao_min
                     FROM SERVICO
                     WHERE serv_id = :id'
                );

            $consultaServico->execute([
                ':id' => $servicoId
            ]);

            $servico =
                $consultaServico->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$servico) {
                throw new Exception(
                    'Serviço não encontrado.'
                );
            }


            /* Converte data e horário */

            $dataHoraBanco = date(
                'Y-m-d H:i:s',
                strtotime($dataHora)
            );


            /* Calcula o horário final */

            $tempoFinal = date(
                'Y-m-d H:i:s',
                strtotime(
                    $dataHoraBanco
                    . ' +'
                    . (int) $servico['serv_duracao_min']
                    . ' minutes'
                )
            );


            /* =========================================
               VERIFICA CONFLITO DE HORÁRIO
               ========================================= */

            $verificarHorario =
                $conexao->prepare(
                    "SELECT COUNT(*)

                     FROM AGENDAMENTO

                     WHERE barb_id = :barbeiro_id

                       AND agend_status <> 'cancelado'

                       AND agend_data_hora
                           < :novo_tempo_final

                       AND agend_tempo_final
                           > :nova_data_hora"
                );


            $verificarHorario->execute([

                ':barbeiro_id' =>
                    $barbeiroId,

                ':nova_data_hora' =>
                    $dataHoraBanco,

                ':novo_tempo_final' =>
                    $tempoFinal

            ]);


            if (
                (int) $verificarHorario
                    ->fetchColumn() > 0
            ) {

                $mensagem =
                    'Este barbeiro já possui um agendamento nesse período.';

            } else {


                /* =========================================
                   TRANSAÇÃO
                   ========================================= */

                $conexao->beginTransaction();


                /* Cria o agendamento */

                $inserirAgendamento =
                    $conexao->prepare(
                        "INSERT INTO AGENDAMENTO (

                            agend_data_hora,
                            agend_status,
                            agend_tempo_final,
                            agend_preco,
                            cli_id,
                            barb_id

                        )

                        VALUES (

                            :data_hora,
                            'pendente',
                            :tempo_final,
                            :preco,
                            :cliente_id,
                            :barbeiro_id

                        )"
                    );


                $inserirAgendamento->execute([

                    ':data_hora' =>
                        $dataHoraBanco,

                    ':tempo_final' =>
                        $tempoFinal,

                    ':preco' =>
                        $servico['serv_preco'],

                    ':cliente_id' =>
                        $clienteId,

                    ':barbeiro_id' =>
                        $barbeiroId

                ]);


                $agendamentoId =
                    $conexao->lastInsertId();


                /* Liga o serviço ao agendamento */

                $inserirServico =
                    $conexao->prepare(
                        'INSERT INTO AGENDAMENTO_SERVICO (

                            agenser_preco,
                            agend_id,
                            serv_id

                        )

                        VALUES (

                            :preco,
                            :agendamento_id,
                            :servico_id

                        )'
                    );


                $inserirServico->execute([

                    ':preco' =>
                        $servico['serv_preco'],

                    ':agendamento_id' =>
                        $agendamentoId,

                    ':servico_id' =>
                        $servicoId

                ]);


                $conexao->commit();


                header(
                    'Location: agendamentos.php?status=criado'
                );

                exit;
            }


        } catch (Throwable $erro) {

            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $mensagem =
                'Não foi possível criar o agendamento.';
        }
    }
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Novo agendamento | GestHairStyle
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>

<?php

$paginaAtual = 'agendamentos';

require 'menu.php';

?>


<div class="container container-formulario">


    <!-- CABEÇALHO -->

    <div class="cabecalho-formulario">

        <div>

            <span class="subtitulo-dashboard">
                AGENDA
            </span>

            <h1>Novo agendamento</h1>

            <p>
                Selecione o cliente, profissional,
                serviço e horário do atendimento.
            </p>

        </div>

    </div>


    <!-- MENSAGEM -->

    <?php if ($mensagem !== ''): ?>

        <div class="mensagem erro">

            <?= htmlspecialchars($mensagem) ?>

        </div>

    <?php endif; ?>


    <!-- FORMULÁRIO -->

    <div class="card-formulario">

        <form method="POST">


            <div class="grid-formulario">


                <!-- CLIENTE -->

                <div class="campo-formulario">

                    <label for="cliente_id">
                        Cliente
                    </label>

                    <select
                        id="cliente_id"
                        name="cliente_id"
                        required
                    >

                        <option value="">
                            Selecione o cliente
                        </option>


                        <?php foreach ($clientes as $cliente): ?>

                            <option
                                value="<?= (int) $cliente['cli_id'] ?>"
                                <?= (
                                    ($_POST['cliente_id'] ?? '')
                                    == $cliente['cli_id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $cliente['cli_nome']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- BARBEIRO -->

                <div class="campo-formulario">

                    <label for="barbeiro_id">
                        Barbeiro
                    </label>

                    <select
                        id="barbeiro_id"
                        name="barbeiro_id"
                        required
                    >

                        <option value="">
                            Selecione o barbeiro
                        </option>


                        <?php foreach ($barbeiros as $barbeiro): ?>

                            <option
                                value="<?= (int) $barbeiro['barb_id'] ?>"
                                <?= (
                                    ($_POST['barbeiro_id'] ?? '')
                                    == $barbeiro['barb_id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $barbeiro['barb_nome']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- SERVIÇO -->

                <div
                    class="
                        campo-formulario
                        campo-formulario-grande
                    "
                >

                    <label for="servico_id">
                        Serviço
                    </label>

                    <select
                        id="servico_id"
                        name="servico_id"
                        required
                    >

                        <option value="">
                            Selecione o serviço
                        </option>


                        <?php foreach ($servicos as $itemServico): ?>

                            <option
                                value="<?= (int) $itemServico['serv_id'] ?>"

                                data-preco="<?= htmlspecialchars(
                                    $itemServico['serv_preco']
                                ) ?>"

                                data-duracao="<?= (int)
                                    $itemServico['serv_duracao_min']
                                ?>"

                                <?= (
                                    ($_POST['servico_id'] ?? '')
                                    == $itemServico['serv_id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars(
                                    $itemServico['serv_nome']
                                ) ?>

                                — R$ <?= number_format(
                                    $itemServico['serv_preco'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                                — <?= (int)
                                    $itemServico['serv_duracao_min']
                                ?> min

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <small class="ajuda-campo">
                        O preço do agendamento será baseado
                        no serviço selecionado.
                    </small>

                </div>


                <!-- RESUMO DO SERVIÇO -->

                <div
                    class="
                        resumo-servico-agendamento
                        campo-formulario-grande
                    "
                    id="resumo_servico"
                >

                    <div>

                        <span>
                            Valor do serviço
                        </span>

                        <strong id="resumo_preco">
                            —
                        </strong>

                    </div>


                    <div>

                        <span>
                            Duração prevista
                        </span>

                        <strong id="resumo_duracao">
                            —
                        </strong>

                    </div>

                </div>


                <!-- DATA E HORÁRIO -->

                <div
                    class="
                        campo-formulario
                        campo-formulario-grande
                    "
                >

                    <label for="data_hora">
                        Data e horário
                    </label>

                    <input
                        type="datetime-local"
                        id="data_hora"
                        name="data_hora"
                        value="<?= htmlspecialchars(
                            $_POST['data_hora'] ?? ''
                        ) ?>"
                        required
                    >

                    <small class="ajuda-campo">
                        O sistema verifica automaticamente
                        se o barbeiro já possui atendimento
                        nesse período.
                    </small>

                </div>


            </div>


            <!-- AÇÕES -->

            <div class="acoes-formulario">

                <a
                    href="agendamentos.php"
                    class="botao-secundario"
                >
                    Cancelar
                </a>


                <button
                    type="submit"
                    class="
                        botao-destaque
                        botao-salvar
                    "
                >
                    Criar agendamento
                </button>

            </div>


        </form>

    </div>


</div>


<script>

const campoServico =
    document.getElementById('servico_id');

const resumoPreco =
    document.getElementById('resumo_preco');

const resumoDuracao =
    document.getElementById('resumo_duracao');


function atualizarResumoServico() {

    const opcao =
        campoServico.options[
            campoServico.selectedIndex
        ];

    const preco =
        opcao.dataset.preco;

    const duracao =
        opcao.dataset.duracao;


    if (!preco || !duracao) {

        resumoPreco.textContent = '—';
        resumoDuracao.textContent = '—';

        return;
    }


    const precoFormatado =
        Number(preco).toLocaleString(
            'pt-BR',
            {
                style: 'currency',
                currency: 'BRL'
            }
        );


    resumoPreco.textContent =
        precoFormatado;

    resumoDuracao.textContent =
        duracao + ' min';
}


campoServico.addEventListener(
    'change',
    atualizarResumoServico
);


atualizarResumoServico();

</script>


</body>

</html>