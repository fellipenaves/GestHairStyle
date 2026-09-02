<?php

require_once 'conexao.php';

/* =========================================
   INDICADORES GERAIS
   ========================================= */

$totalClientes = $conexao
    ->query('SELECT COUNT(*) FROM CLIENTE')
    ->fetchColumn();

$totalAgendamentos = $conexao
    ->query('SELECT COUNT(*) FROM AGENDAMENTO')
    ->fetchColumn();

$totalBarbeiros = $conexao
    ->query('SELECT COUNT(*) FROM BARBEIRO')
    ->fetchColumn();

$totalServicos = $conexao
    ->query('SELECT COUNT(*) FROM SERVICO')
    ->fetchColumn();


/* =========================================
   AGENDAMENTOS DE HOJE
   Desconsidera cancelados
   ========================================= */

$sqlHoje = "
    SELECT COUNT(*)
    FROM AGENDAMENTO
    WHERE DATE(agend_data_hora) = CURDATE()
      AND agend_status <> 'cancelado'
";

$agendamentosHoje = $conexao
    ->query($sqlHoje)
    ->fetchColumn();


/* =========================================
   FATURAMENTO DO MÊS
   Apenas atendimentos concluídos
   ========================================= */

$sqlFaturamento = "
    SELECT COALESCE(SUM(agend_preco), 0)
    FROM AGENDAMENTO
    WHERE agend_status = 'concluido'
      AND YEAR(agend_data_hora) = YEAR(CURDATE())
      AND MONTH(agend_data_hora) = MONTH(CURDATE())
";

$faturamentoMes = $conexao
    ->query($sqlFaturamento)
    ->fetchColumn();


/* =========================================
   TICKET MÉDIO DO MÊS
   ========================================= */

$sqlTicket = "
    SELECT COALESCE(AVG(agend_preco), 0)
    FROM AGENDAMENTO
    WHERE agend_status = 'concluido'
      AND YEAR(agend_data_hora) = YEAR(CURDATE())
      AND MONTH(agend_data_hora) = MONTH(CURDATE())
";

$ticketMedio = $conexao
    ->query($sqlTicket)
    ->fetchColumn();


/* =========================================
   PRÓXIMOS ATENDIMENTOS
   ========================================= */

$sqlProximos = "
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

    WHERE a.agend_data_hora >= NOW()
      AND a.agend_status IN (
            'pendente',
            'confirmado'
      )

    GROUP BY
        a.agend_id,
        a.agend_data_hora,
        a.agend_status,
        a.agend_preco,
        c.cli_nome,
        b.barb_nome

    ORDER BY a.agend_data_hora ASC

    LIMIT 5
";

$consultaProximos = $conexao->query($sqlProximos);

$proximosAgendamentos =
    $consultaProximos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestHairStyle</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php
$paginaAtual = 'dashboard';
require 'menu.php';
?>

<div class="container">

    <div class="topo-dashboard">
        <div class="topo-texto">
            <span class="tag-painel">Painel de Gestão</span>
            <h1>GestHairStyle</h1>
            <p>
                Gerencie clientes, barbeiros, serviços e agendamentos
                de forma simples, rápida e organizada.
            </p>
        </div>

        <div class="acoes-rapidas">
            <a class="botao-cadastro" href="cadastrar_cliente.php">+ Novo cliente</a>
            <a class="botao-cadastro" href="agendamentos.php">Agendamentos</a>
            <a class="botao-cadastro" href="servicos.php">Serviços</a>
            <a class="botao-cadastro" href="barbeiros.php">Barbeiros</a>
        </div>
    </div>

    <?php if (($_GET['status'] ?? '') === 'excluido'): ?>
        <div class="aviso aviso-sucesso">
            Cliente excluído com sucesso!
        </div>
    <?php elseif (($_GET['status'] ?? '') === 'cliente_em_uso'): ?>
        <div class="aviso aviso-erro">
            Este cliente possui agendamentos e não pode ser excluído.
        </div>
    <?php elseif (isset($_GET['status'])): ?>
        <div class="aviso aviso-erro">
            Não foi possível excluir o cliente.
        </div>
    <?php endif; ?>

    <div class="grid-resumo">

    <div class="card-resumo">
        <div class="icone-card">📅</div>

        <div class="info-card">
            <span>Agendamentos hoje</span>

            <strong>
                <?= (int) $agendamentosHoje ?>
            </strong>

            <small>
                Atendimentos previstos para hoje
            </small>
        </div>
    </div>


    <div class="card-resumo">
        <div class="icone-card">💰</div>

        <div class="info-card">
            <span>Faturamento do mês</span>

            <strong>
                R$ <?= number_format(
                    $faturamentoMes,
                    2,
                    ',',
                    '.'
                ) ?>
            </strong>

            <small>
                Somente atendimentos concluídos
            </small>
        </div>
    </div>


    <div class="card-resumo">
        <div class="icone-card">📊</div>

        <div class="info-card">
            <span>Ticket médio</span>

            <strong>
                R$ <?= number_format(
                    $ticketMedio,
                    2,
                    ',',
                    '.'
                ) ?>
            </strong>

            <small>
                Valor médio dos atendimentos do mês
            </small>
        </div>
    </div>


    <div class="card-resumo">
        <div class="icone-card">👥</div>

        <div class="info-card">
            <span>Clientes</span>

            <strong>
                <?= (int) $totalClientes ?>
            </strong>

            <small>
                Total de clientes cadastrados
            </small>
        </div>
    </div>

</div>

<!-- =========================================
     PRÓXIMOS ATENDIMENTOS
     ========================================= -->

<div class="painel-agenda">

    <div class="cabecalho-agenda">
        <div>
            <span class="subtitulo-dashboard">AGENDA</span>

            <h2>Próximos atendimentos</h2>

            <p>
                Confira os próximos clientes com horário marcado.
            </p>
        </div>

        <a href="agendamentos.php" class="link-ver-todos">
            Ver agenda completa →
        </a>
    </div>


    <?php if (count($proximosAgendamentos) > 0): ?>

        <div class="lista-agenda">

            <?php foreach ($proximosAgendamentos as $agendamento): ?>

                <div class="item-agenda">

                    <div class="data-agenda">

                        <span class="dia-agenda">
                            <?= date(
                                'd',
                                strtotime($agendamento['agend_data_hora'])
                            ) ?>
                        </span>

                        <span class="mes-agenda">
                            <?= date(
                                'm',
                                strtotime($agendamento['agend_data_hora'])
                            ) ?>
                        </span>

                    </div>


                    <div class="horario-agenda">

                        <span>
                            <?= date(
                                'H:i',
                                strtotime($agendamento['agend_data_hora'])
                            ) ?>
                        </span>

                        <small>
                            <?= date(
                                'd/m/Y',
                                strtotime($agendamento['agend_data_hora'])
                            ) ?>
                        </small>

                    </div>


                    <div class="cliente-agenda">

                        <strong>
                            <?= htmlspecialchars(
                                $agendamento['cli_nome']
                            ) ?>
                        </strong>

                        <small>
                            <?= htmlspecialchars(
                                $agendamento['servicos']
                                ?? 'Serviço não informado'
                            ) ?>
                        </small>

                    </div>


                    <div class="barbeiro-agenda">

                        <span>Profissional</span>

                        <strong>
                            <?= htmlspecialchars(
                                $agendamento['barb_nome']
                            ) ?>
                        </strong>

                    </div>


                    <div class="valor-agenda">

                        <?php if ($agendamento['agend_preco'] !== null): ?>

                            <strong>
                                R$ <?= number_format(
                                    $agendamento['agend_preco'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </strong>

                        <?php else: ?>

                            <strong>—</strong>

                        <?php endif; ?>

                    </div>


                    <div class="status-agenda">

                        <span class="badge-status <?= htmlspecialchars(
                            $agendamento['agend_status']
                        ) ?>">

                            <?= ucfirst(
                                htmlspecialchars(
                                    $agendamento['agend_status']
                                )
                            ) ?>

                        </span>

                    </div>


                    <div class="acao-agenda">

                        <a
                            href="editar_agendamento.php?id=<?= (int)
                                $agendamento['agend_id']
                            ?>"
                        >
                            Editar
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="agenda-vazia">

            <div class="icone-agenda-vazia">📅</div>

            <div>
                <strong>Nenhum atendimento próximo.</strong>

                <p>
                    Quando houver novos agendamentos,
                    eles aparecerão aqui.
                </p>
            </div>

        </div>

    <?php endif; ?>

</div>

</body>
</html>