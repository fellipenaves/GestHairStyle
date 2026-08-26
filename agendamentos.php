<?php

require_once 'conexao.php';

$sql = "
    SELECT
        a.agend_id,
        a.agend_data_hora,
        a.agend_status,
        a.agend_preco,
        c.cli_nome,
        b.barb_nome,
        GROUP_CONCAT(
            s.serv_nome
            ORDER BY s.serv_nome
            SEPARATOR ', '
        ) AS servicos
    FROM AGENDAMENTO AS a
    INNER JOIN CLIENTE AS c
        ON c.cli_id = a.cli_id
    INNER JOIN BARBEIRO AS b
        ON b.barb_id = a.barb_id
    LEFT JOIN AGENDAMENTO_SERVICO AS ags
        ON ags.agend_id = a.agend_id
    LEFT JOIN SERVICO AS s
        ON s.serv_id = ags.serv_id
    GROUP BY
        a.agend_id,
        a.agend_data_hora,
        a.agend_status,
        a.agend_preco,
        c.cli_nome,
        b.barb_nome
    ORDER BY a.agend_data_hora DESC
";

$consulta = $conexao->query($sql);
$agendamentos = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agendamentos | GestHairStyle</title>

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
            max-width: 1100px;
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

        .botao {
            display: inline-block;
            margin: 10px 8px 20px 0;
            padding: 12px 18px;
            border-radius: 5px;
            background-color: #17202a;
            color: white;
            text-decoration: none;
        }

        .botao:hover {
            background-color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            color: white;
            background-color: #17202a;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: bold;
        }

        .pendente {
            color: #856404;
            background-color: #fff3cd;
        }

        .confirmado {
            color: #004085;
            background-color: #cce5ff;
        }

        .cancelado {
            color: #721c24;
            background-color: #f8d7da;
        }

        .concluido {
            color: #155724;
            background-color: #d4edda;
        }

        @media (max-width: 800px) {
            .tabela-container {
                overflow-x: auto;
            }
        }

        .mensagem-sucesso {
            margin: 15px 0;
            padding: 12px;
            border-radius: 5px;
            color: #155724;
            background-color: #d4edda;
        }

        .form-status {
            display: flex;
            gap: 6px;
        }

        .form-status select {
            padding: 7px;
            border: 1px solid #bbb;
            border-radius: 4px;
            background-color: white;
        }

        .form-status button {
            padding: 7px 10px;
            border: none;
            border-radius: 4px;
            background-color: #17202a;
            color: white;
            cursor: pointer;
        }

        .form-status button:hover {
            background-color: #2c3e50;
        }

        .mensagem-erro {
            margin: 15px 0;
            padding: 12px;
            border-radius: 5px;
            color: #721c24;
            background-color: #f8d7da;
        }

    </style>
</head>

<body>

<div class="container">
    <h1>Agendamentos</h1>

    <?php if (($_GET['status'] ?? '') === 'criado'): ?>
        <div class="mensagem-sucesso">
            Agendamento criado com sucesso!
        </div>

    <?php elseif (($_GET['status'] ?? '') === 'atualizado'): ?>
        <div class="mensagem-sucesso">
            Status atualizado com sucesso!
        </div>

    <?php elseif (isset($_GET['status'])): ?>
        <div class="mensagem-erro">
            Não foi possível atualizar o status.
        </div>
    <?php endif; ?>

    <a class="botao" href="index.php">
        Voltar aos clientes
    </a>

    <a class="botao" href="cadastrar_agendamento.php">
        Novo agendamento
    </a>

    <div class="tabela-container">
        <table>
            <thead>
                <tr>
                    <th>Data e horário</th>
                    <th>Cliente</th>
                    <th>Barbeiro</th>
                    <th>Serviço</th>
                    <th>Preço</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($agendamentos as $agendamento): ?>
                    <tr>
                        <td>
                            <?= date(
                                'd/m/Y H:i',
                                strtotime($agendamento['agend_data_hora'])
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($agendamento['cli_nome']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($agendamento['barb_nome']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $agendamento['servicos'] ?? 'Não informado'
                            ) ?>
                        </td>

                        <td>
                            <?php if ($agendamento['agend_preco'] !== null): ?>
                                R$ <?= number_format(
                                    $agendamento['agend_preco'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="status <?= htmlspecialchars(
                                $agendamento['agend_status']
                            ) ?>">
                                <?= ucfirst(htmlspecialchars(
                                    $agendamento['agend_status']
                                )) ?>
                            </span>
                        </td>

                        <td>
                            <form
                                action="atualizar_status_agendamento.php"
                                method="POST"
                                class="form-status"
                            >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $agendamento['agend_id'] ?>"
                                >

                                <select name="status">
                                    <?php
                                    $opcoesStatus = [
                                        'pendente' => 'Pendente',
                                        'confirmado' => 'Confirmado',
                                        'concluido' => 'Concluído',
                                        'cancelado' => 'Cancelado'
                                    ];
                                    ?>

                                    <?php foreach ($opcoesStatus as $valor => $texto): ?>
                                        <option
                                            value="<?= $valor ?>"
                                            <?= $agendamento['agend_status'] === $valor
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= $texto ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit">Atualizar</button>
                            </form>
                        </td>

                    </tr>
                <?php endforeach; ?>

                <?php if (count($agendamentos) === 0): ?>
                    <tr>
                        <td colspan="7">
                            Nenhum agendamento encontrado.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>