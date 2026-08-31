<?php

require_once 'conexao.php';

$filtroData = trim($_GET['filtro_data'] ?? '');
$filtroStatus = trim($_GET['filtro_status'] ?? '');

$statusPermitidos = [
    'pendente',
    'confirmado',
    'concluido',
    'cancelado'
];

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
    WHERE 1 = 1
";

$parametros = [];

if (
    $filtroData !== '' &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroData)
) {
    $sql .= ' AND DATE(a.agend_data_hora) = :filtro_data';
    $parametros[':filtro_data'] = $filtroData;
}

if (in_array($filtroStatus, $statusPermitidos, true)) {
    $sql .= ' AND a.agend_status = :filtro_status';
    $parametros[':filtro_status'] = $filtroStatus;
}

$sql .= "
    GROUP BY
        a.agend_id,
        a.agend_data_hora,
        a.agend_status,
        a.agend_preco,
        c.cli_nome,
        b.barb_nome
    ORDER BY a.agend_data_hora DESC
";

$consulta = $conexao->prepare($sql);
$consulta->execute($parametros);

$agendamentos = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agendamentos | GestHairStyle</title>

    <link rel="stylesheet" href="style.css">
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

    <?php elseif (($_GET['status'] ?? '') === 'editado'): ?>
        <div class="mensagem-sucesso">
            Agendamento atualizado com sucesso!
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

    <form method="GET" class="form-filtros">
    <div class="campo-filtro">
        <label for="filtro_data">Data</label>

        <input
            type="date"
            id="filtro_data"
            name="filtro_data"
            value="<?= htmlspecialchars($filtroData) ?>"
        >
    </div>

    <div class="campo-filtro">
        <label for="filtro_status">Status</label>

        <select id="filtro_status" name="filtro_status">
            <option value="">Todos</option>

            <option
                value="pendente"
                <?= $filtroStatus === 'pendente' ? 'selected' : '' ?>
            >
                Pendente
            </option>

            <option
                value="confirmado"
                <?= $filtroStatus === 'confirmado' ? 'selected' : '' ?>
            >
                Confirmado
            </option>

            <option
                value="concluido"
                <?= $filtroStatus === 'concluido' ? 'selected' : '' ?>
            >
                Concluído
            </option>

            <option
                value="cancelado"
                <?= $filtroStatus === 'cancelado' ? 'selected' : '' ?>
            >
                Cancelado
            </option>
        </select>
    </div>

    <button type="submit" class="botao-filtrar">
        Filtrar
    </button>

    <a href="agendamentos.php" class="limpar-filtros">
        Limpar filtros
    </a>
</form>

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
                            <a
                                href="editar_agendamento.php?id=<?= (int) $agendamento['agend_id'] ?>"
                                class="link-editar"
                            >
                                Editar agendamento
                            </a>

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