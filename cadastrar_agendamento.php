<?php

require_once 'conexao.php';

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
    $clienteId = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $barbeiroId = filter_input(INPUT_POST, 'barbeiro_id', FILTER_VALIDATE_INT);
    $servicoId = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
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

            $dataHoraBanco = date(
                'Y-m-d H:i:s',
                strtotime($dataHora)
            );

            $tempoFinal = date(
                'Y-m-d H:i:s',
                strtotime(
                    $dataHoraBanco . ' +' .
                    (int) $servico['serv_duracao_min'] .
                    ' minutes'
                )
            );

            $verificarHorario = $conexao->prepare(
                "SELECT COUNT(*)
                 FROM AGENDAMENTO
                 WHERE barb_id = :barbeiro_id
                   AND agend_data_hora = :data_hora
                   AND agend_status <> 'cancelado'"
            );

            $verificarHorario->execute([
                ':barbeiro_id' => $barbeiroId,
                ':data_hora' => $dataHoraBanco
            ]);

            if ((int) $verificarHorario->fetchColumn() > 0) {
                $mensagem = 'Este barbeiro já possui um agendamento nesse horário.';
            } else {
                $conexao->beginTransaction();

                $inserirAgendamento = $conexao->prepare(
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
                    ':data_hora' => $dataHoraBanco,
                    ':tempo_final' => $tempoFinal,
                    ':preco' => $servico['serv_preco'],
                    ':cliente_id' => $clienteId,
                    ':barbeiro_id' => $barbeiroId
                ]);

                $agendamentoId = $conexao->lastInsertId();

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
                    ':agendamento_id' => $agendamentoId,
                    ':servico_id' => $servicoId
                ]);

                $conexao->commit();

                header('Location: agendamentos.php?status=criado');
                exit;
            }

        } catch (Throwable $erro) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $mensagem = 'Não foi possível criar o agendamento.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Novo agendamento | GestHairStyle</title>

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
    <h1>Novo agendamento</h1>

    <?php if ($mensagem !== ''): ?>
        <div class="erro">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="cliente_id">Cliente</label>
        <select id="cliente_id" name="cliente_id" required>
            <option value="">Selecione o cliente</option>

            <?php foreach ($clientes as $cliente): ?>
                <option value="<?= (int) $cliente['cli_id'] ?>">
                    <?= htmlspecialchars($cliente['cli_nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="barbeiro_id">Barbeiro</label>
        <select id="barbeiro_id" name="barbeiro_id" required>
            <option value="">Selecione o barbeiro</option>

            <?php foreach ($barbeiros as $barbeiro): ?>
                <option value="<?= (int) $barbeiro['barb_id'] ?>">
                    <?= htmlspecialchars($barbeiro['barb_nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="servico_id">Serviço</label>
        <select id="servico_id" name="servico_id" required>
            <option value="">Selecione o serviço</option>

            <?php foreach ($servicos as $servico): ?>
                <option value="<?= (int) $servico['serv_id'] ?>">
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
            required
        >

        <button type="submit">Criar agendamento</button>
    </form>

    <a class="voltar" href="agendamentos.php">
        ← Voltar aos agendamentos
    </a>
</div>

</body>
</html>