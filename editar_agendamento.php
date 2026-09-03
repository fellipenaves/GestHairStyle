<?php

require_once 'conexao.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die('Agendamento inválido.');
}


/* =========================================
   BUSCA O AGENDAMENTO
   ========================================= */

$consultaAgendamento = $conexao->prepare(
    'SELECT
        a.agend_id,
        a.agend_data_hora,
        a.cli_id,
        a.barb_id,
        (
            SELECT ags.serv_id
            FROM AGENDAMENTO_SERVICO AS ags
            WHERE ags.agend_id = a.agend_id
            ORDER BY ags.serv_id
            LIMIT 1
        ) AS serv_id
     FROM AGENDAMENTO AS a
     WHERE a.agend_id = :id'
);

$consultaAgendamento->execute([
    ':id' => $id
]);

$agendamento = $consultaAgendamento->fetch(
    PDO::FETCH_ASSOC
);

if (!$agendamento) {
    die('Agendamento não encontrado.');
}


/* =========================================
   DADOS DOS CAMPOS
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
   ATUALIZAÇÃO
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


            /* Valida data e horário */

            $dataObjeto =
                DateTime::createFromFormat(
                    'Y-m-d\TH:i',
                    $dataHora
                );

            if (!$dataObjeto) {
                throw new Exception(
                    'Data e horário inválidos.'
                );
            }


            $dataHoraBanco =
                $dataObjeto->format(
                    'Y-m-d H:i:s'
                );


            /* Calcula horário final */

            $tempoFinalObjeto =
                clone $dataObjeto;

            $tempoFinalObjeto->modify(
                '+'
                . (int) $servico['serv_duracao_min']
                . ' minutes'
            );

            $tempoFinal =
                $tempoFinalObjeto->format(
                    'Y-m-d H:i:s'
                );


            /* =========================================
               VERIFICA CONFLITO
               ========================================= */

            $verificarHorario =
                $conexao->prepare(
                    "SELECT COUNT(*)

                     FROM AGENDAMENTO

                     WHERE barb_id = :barbeiro_id

                       AND agend_id <> :agendamento_id

                       AND agend_status <> 'cancelado'

                       AND agend_data_hora
                           < :novo_tempo_final

                       AND agend_tempo_final
                           > :nova_data_hora"
                );


            $verificarHorario->execute([

                ':barbeiro_id' =>
                    $barbeiroId,

                ':agendamento_id' =>
                    $id,

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


                /* Atualiza o agendamento */

                $atualizarAgendamento =
                    $conexao->prepare(
                        'UPDATE AGENDAMENTO

                         SET
                            agend_data_hora = :data_hora,
                            agend_tempo_final = :tempo_final,
                            agend_preco = :preco,
                            cli_id = :cliente_id,
                            barb_id = :barbeiro_id

                         WHERE agend_id = :id'
                    );


                $atualizarAgendamento->execute([

                    ':data_hora' =>
                        $dataHoraBanco,

                    ':tempo_final' =>
                        $tempoFinal,

                    ':preco' =>
                        $servico['serv_preco'],

                    ':cliente_id' =>
                        $clienteId,

                    ':barbeiro_id' =>
                        $barbeiroId,

                    ':id' =>
                        $id

                ]);


                /* Remove vínculo antigo do serviço */

                $excluirServicos =
                    $conexao->prepare(
                        'DELETE
                         FROM AGENDAMENTO_SERVICO
                         WHERE agend_id = :agendamento_id'
                    );

                $excluirServicos->execute([
                    ':agendamento_id' => $id
                ]);


                /* Cria novo vínculo do serviço */

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
                        $id,

                    ':servico_id' =>
                        $servicoId

                ]);


                $conexao->commit();


                header(
                    'Location: agendamentos.php?status=editado'
                );

                exit;
            }

        } catch (Throwable $erro) {

            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $mensagem =
                'Não foi possível atualizar o agendamento.';
        }
    }
}


/* =========================================
   VALORES DO FORMULÁRIO
   ========================================= */

$clienteSelecionado =
    $_POST['cliente_id']
    ?? $agendamento['cli_id'];

$barbeiroSelecionado =
    $_POST['barbeiro_id']
    ?? $agendamento['barb_id'];

$servicoSelecionado =
    $_POST['servico_id']
    ?? $agendamento['serv_id'];

$dataHoraSelecionada =
    $_POST['data_hora']
    ?? date(
        'Y-m-d\TH:i',
        strtotime(
            $agendamento['agend_data_hora']
        )
    );

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
        Editar agendamento | GestHairStyle
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

            <h1>Editar agendamento</h1>

            <p>
                Atualize o cliente, profissional,
                serviço ou horário do atendimento.
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

                        <?php foreach ($clientes as $cliente): ?>

                            <option
                                value="<?= (int) $cliente['cli_id'] ?>"
                                <?= (
                                    (int) $clienteSelecionado
                                    ===
                                    (int) $cliente['cli_id']
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

                        <?php foreach ($barbeiros as $barbeiro): ?>

                            <option
                                value="<?= (int) $barbeiro['barb_id'] ?>"
                                <?= (
                                    (int) $barbeiroSelecionado
                                    ===
                                    (int) $barbeiro['barb_id']
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
                                    (int) $servicoSelecionado
                                    ===
                                    (int) $itemServico['serv_id']
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
                        Ao alterar o serviço,
                        o preço e a duração do agendamento
                        também serão atualizados.
                    </small>

                </div>


                <!-- RESUMO -->

                <div
                    class="
                        resumo-servico-agendamento
                        campo-formulario-grande
                    "
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


                <!-- DATA -->

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
                            $dataHoraSelecionada
                        ) ?>"
                        required
                    >

                    <small class="ajuda-campo">
                        O sistema verificará conflitos
                        com outros agendamentos do barbeiro.
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
                    Salvar alterações
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


    resumoPreco.textContent =
        Number(preco).toLocaleString(
            'pt-BR',
            {
                style: 'currency',
                currency: 'BRL'
            }
        );


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