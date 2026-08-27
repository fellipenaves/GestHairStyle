<?php

require_once 'conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Agendamento inválido.');
}

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

$consultaAgendamento->execute([':id' => $id]);
$agendamento = $consultaAgendamento->fetch(PDO::FETCH_ASSOC);

if (!$agendamento) {
    die('Agendamento não encontrado.');
}

$clientes = $conexao
    ->query('SELECT cli_id, cli_nome FROM CLIENTE ORDER BY cli_nome')
    ->fetchAll(PDO::FETCH_ASSOC);

$barbeiros = $conexao
    ->query('SELECT barb_id, barb_nome FROM BARBEIRO ORDER BY barb_nome')
    ->fetchAll(PDO::FETCH_ASSOC);

$servicos = $conexao
    ->query(
        'SELECT serv_id, serv_nome, serv_preco, serv_duracao_min
         FROM SERVICO
         ORDER BY serv_nome'
    )
    ->fetchAll(PDO::FETCH_ASSOC);

$mensagem = '';

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

    $dataHora = trim($_POST['data_hora'] ?? '');

    if (!$clienteId || !$barbeiroId || !$servicoId || $dataHora === '') {
        $mensagem = 'Preencha todos os campos.';
    } else {
        try {
            $consultaServico = $conexao->prepare(
                'SELECT serv_preco, serv_duracao_min
                 FROM SERVICO
                 WHERE serv_id = :id'
            );

            $consultaServico->execute([':id' => $servicoId]);
            $servico = $consultaServico->fetch(PDO::FETCH_ASSOC);

            if (!$servico) {
                throw new Exception('Serviço não encontrado.');
            }

            $dataObjeto = DateTime::createFromFormat(
                'Y-m-d\TH:i',
                $dataHora
            );

            if (!$dataObjeto) {
                throw new Exception('Data e horário inválidos.');
            }

            $dataHoraBanco = $dataObjeto->format('Y-m-d H:i:s');

            $tempoFinalObjeto = clone $dataObjeto;
            $tempoFinalObjeto->modify(
                '+' . (int) $servico['serv_duracao_min'] . ' minutes'
            );

            $tempoFinal = $tempoFinalObjeto->format('Y-m-d H:i:s');

            $verificarHorario = $conexao->prepare(
                "SELECT COUNT(*)
                 FROM AGENDAMENTO
                 WHERE barb_id = :barbeiro_id
                   AND agend_id <> :agendamento_id
                   AND agend_status <> 'cancelado'
                   AND agend_data_hora < :novo_tempo_final
                   AND agend_tempo_final > :nova_data_hora"
            );

            $verificarHorario->execute([
                ':barbeiro_id' => $barbeiroId,
                ':agendamento_id' => $id,
                ':nova_data_hora' => $dataHoraBanco,
                ':novo_tempo_final' => $tempoFinal
            ]);

            if ((int) $verificarHorario->fetchColumn() > 0) {
                $mensagem =
                    'Este barbeiro já possui um agendamento nesse período.';
            } else {
                $conexao->beginTransaction();

                $atualizarAgendamento = $conexao->prepare(
                    'UPDATE AGENDAMENTO
                     SET agend_data_hora = :data_hora,
                         agend_tempo_final = :tempo_final,
                         agend_preco = :preco,
                         cli_id = :cliente_id,
                         barb_id = :barbeiro_id
                     WHERE agend_id = :id'
                );

                $atualizarAgendamento->execute([
                    ':data_hora' => $dataHoraBanco,
                    ':tempo_final' => $tempoFinal,
                    ':preco' => $servico['serv_preco'],
                    ':cliente_id' => $clienteId,
                    ':barbeiro_id' => $barbeiroId,
                    ':id' => $id
                ]);

                $excluirServicos = $conexao->prepare(
                    'DELETE FROM AGENDAMENTO_SERVICO
                     WHERE agend_id = :agendamento_id'
                );

                $excluirServicos->execute([
                    ':agendamento_id' => $id
                ]);

                $inserirServico = $conexao->prepare(
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
                    ':preco' => $servico['serv_preco'],
                    ':agendamento_id' => $id,
                    ':servico_id' => $servicoId
                ]);

                $conexao->commit();

                header('Location: agendamentos.php?status=editado');
                exit;
            }

        } catch (Throwable $erro) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $mensagem = 'Não foi possível atualizar o agendamento.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar agendamento | GestHairStyle</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #222;
        }

        .container {
            max-width: 600px;
            margin: auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12);
        }

        h1 {
            margin-top: 0;
            color: #17202a;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        select,
        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #bbb;
            border-radius: 5px;
            background-color: white;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: #17202a;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #2c3e50;
        }

        .erro {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
            color: #721c24;
            background-color: #f8d7da;
        }

        .voltar {
            display: inline-block;
            margin-top: 20px;
            color: #17202a;
        }
    </style>
</head>

<body>

<div class="container">
    <h1>Editar agendamento</h1>

    <?php if ($mensagem !== ''): ?>
        <div class="erro">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="cliente_id">Cliente</label>

        <select id="cliente_id" name="cliente_id" required>
            <?php foreach ($clientes as $cliente): ?>
                <option
                    value="<?= (int) $cliente['cli_id'] ?>"
                    <?= (int) $agendamento['cli_id'] ===
                        (int) $cliente['cli_id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($cliente['cli_nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="barbeiro_id">Barbeiro</label>

        <select id="barbeiro_id" name="barbeiro_id" required>
            <?php foreach ($barbeiros as $barbeiro): ?>
                <option
                    value="<?= (int) $barbeiro['barb_id'] ?>"
                    <?= (int) $agendamento['barb_id'] ===
                        (int) $barbeiro['barb_id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($barbeiro['barb_nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="servico_id">Serviço</label>

        <select id="servico_id" name="servico_id" required>
            <?php foreach ($servicos as $servico): ?>
                <option
                    value="<?= (int) $servico['serv_id'] ?>"
                    <?= (int) $agendamento['serv_id'] ===
                        (int) $servico['serv_id'] ? 'selected' : '' ?>
                >
                    <?= htmlspecialchars($servico['serv_nome']) ?>
                    — R$ <?= number_format(
                        $servico['serv_preco'],
                        2,
                        ',',
                        '.'
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="data_hora">Data e horário</label>

        <input
            type="datetime-local"
            id="data_hora"
            name="data_hora"
            value="<?= date(
                'Y-m-d\TH:i',
                strtotime($agendamento['agend_data_hora'])
            ) ?>"
            required
        >

        <button type="submit">
            Salvar alterações
        </button>
    </form>

    <a class="voltar" href="agendamentos.php">
        ← Voltar aos agendamentos
    </a>
</div>

</body>
</html>