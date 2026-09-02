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

/* =========================================
   FATURAMENTO - ÚLTIMOS 6 MESES
   ========================================= */

$nomesMeses = [
    1 => 'Jan',
    2 => 'Fev',
    3 => 'Mar',
    4 => 'Abr',
    5 => 'Mai',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Ago',
    9 => 'Set',
    10 => 'Out',
    11 => 'Nov',
    12 => 'Dez'
];

$inicioPeriodo = new DateTime('first day of -5 months');
$inicioPeriodo->setTime(0, 0, 0);

$sqlFaturamento6Meses = "
    SELECT
        DATE_FORMAT(agend_data_hora, '%Y-%m') AS mes,
        COALESCE(SUM(agend_preco), 0) AS total
    FROM AGENDAMENTO
    WHERE agend_status = 'concluido'
      AND agend_data_hora >= :inicio_periodo
    GROUP BY DATE_FORMAT(agend_data_hora, '%Y-%m')
    ORDER BY mes
";

$consultaFaturamento6Meses =
    $conexao->prepare($sqlFaturamento6Meses);

$consultaFaturamento6Meses->execute([
    ':inicio_periodo' =>
        $inicioPeriodo->format('Y-m-d H:i:s')
]);

$resultadoFaturamento6Meses =
    $consultaFaturamento6Meses->fetchAll(PDO::FETCH_ASSOC);


/* Cria os 6 meses, inclusive os que não possuem faturamento */

$faturamentoPorMes = [];

for ($i = 5; $i >= 0; $i--) {

    $dataMes = new DateTime(
        "first day of -{$i} months"
    );

    $chave = $dataMes->format('Y-m');

    $numeroMes = (int) $dataMes->format('n');

    $faturamentoPorMes[$chave] = [
        'rotulo' => $nomesMeses[$numeroMes],
        'valor' => 0
    ];
}


/* Preenche os valores encontrados no banco */

foreach ($resultadoFaturamento6Meses as $registro) {

    if (isset(
        $faturamentoPorMes[$registro['mes']]
    )) {

        $faturamentoPorMes[
            $registro['mes']
        ]['valor'] = (float) $registro['total'];
    }
}


/* Maior valor para calcular as barras do gráfico */

$valoresFaturamento = array_column(
    $faturamentoPorMes,
    'valor'
);

$maiorFaturamento =
    !empty($valoresFaturamento)
        ? max($valoresFaturamento)
        : 0;

if ($maiorFaturamento <= 0) {
    $maiorFaturamento = 1;
}


/* =========================================
   SERVIÇOS MAIS PROCURADOS
   ========================================= */

$sqlServicosPopulares = "
    SELECT
        s.serv_nome,
        COUNT(*) AS quantidade
    FROM AGENDAMENTO_SERVICO AS ags

    INNER JOIN SERVICO AS s
        ON s.serv_id = ags.serv_id

    INNER JOIN AGENDAMENTO AS a
        ON a.agend_id = ags.agend_id

    WHERE a.agend_status <> 'cancelado'

    GROUP BY
        s.serv_id,
        s.serv_nome

    ORDER BY quantidade DESC, s.serv_nome ASC

    LIMIT 5
";

$consultaServicosPopulares =
    $conexao->query($sqlServicosPopulares);

$servicosPopulares =
    $consultaServicosPopulares->fetchAll(
        PDO::FETCH_ASSOC
    );

$maiorQuantidadeServico =
    !empty($servicosPopulares)
        ? (int) $servicosPopulares[0]['quantidade']
        : 1;

if ($maiorQuantidadeServico <= 0) {
    $maiorQuantidadeServico = 1;
}

/* =========================================
   RESUMO DO DIA
   ========================================= */

$sqlResumoHoje = "
    SELECT
        SUM(
            CASE
                WHEN agend_status = 'confirmado'
                THEN 1
                ELSE 0
            END
        ) AS confirmados,

        SUM(
            CASE
                WHEN agend_status = 'concluido'
                THEN 1
                ELSE 0
            END
        ) AS concluidos,

        SUM(
            CASE
                WHEN agend_status = 'cancelado'
                THEN 1
                ELSE 0
            END
        ) AS cancelados,

        COALESCE(
            SUM(
                CASE
                    WHEN agend_status = 'concluido'
                    THEN agend_preco
                    ELSE 0
                END
            ),
            0
        ) AS faturamento

    FROM AGENDAMENTO

    WHERE DATE(agend_data_hora) = CURDATE()
";

$resumoHoje = $conexao
    ->query($sqlResumoHoje)
    ->fetch(PDO::FETCH_ASSOC);

$confirmadosHoje =
    (int) ($resumoHoje['confirmados'] ?? 0);

$concluidosHoje =
    (int) ($resumoHoje['concluidos'] ?? 0);

$canceladosHoje =
    (int) ($resumoHoje['cancelados'] ?? 0);

$faturamentoHoje =
    (float) ($resumoHoje['faturamento'] ?? 0);

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

<!-- INDICADORES PRINCIPAIS -->

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
     RESUMO DE HOJE
     ========================================= -->

<div class="resumo-hoje">

    <div class="cabecalho-resumo-hoje">

        <div>
            <span class="subtitulo-dashboard">
                HOJE
            </span>

            <h2>Resumo do dia</h2>

            <p>
                Situação atual dos atendimentos de hoje.
            </p>
        </div>

        <a
            href="agendamentos.php?filtro_data=<?= date('Y-m-d') ?>"
            class="link-ver-todos"
        >
            Ver agenda de hoje →
        </a>

    </div>


    <div class="grid-resumo-hoje">

        <div class="item-resumo-hoje">

            <span class="indicador-hoje indicador-confirmado"></span>

            <div>
                <small>Confirmados</small>

                <strong>
                    <?= $confirmadosHoje ?>
                </strong>
            </div>

        </div>


        <div class="item-resumo-hoje">

            <span class="indicador-hoje indicador-concluido"></span>

            <div>
                <small>Concluídos</small>

                <strong>
                    <?= $concluidosHoje ?>
                </strong>
            </div>

        </div>


        <div class="item-resumo-hoje">

            <span class="indicador-hoje indicador-cancelado"></span>

            <div>
                <small>Cancelados</small>

                <strong>
                    <?= $canceladosHoje ?>
                </strong>
            </div>

        </div>


        <div class="item-resumo-hoje faturamento-hoje">

            <span class="indicador-hoje indicador-faturamento"></span>

            <div>
                <small>Faturamento de hoje</small>

                <strong>
                    R$ <?= number_format(
                        $faturamentoHoje,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>
            </div>

        </div>

    </div>

</div>

<!-- =========================================
     VISÃO GERENCIAL
     ========================================= -->

<div class="grid-gerencial">


    <!-- FATURAMENTO -->

    <div class="card-gerencial card-faturamento">

        <div class="cabecalho-card-gerencial">

            <div>

                <span class="subtitulo-dashboard">
                    DESEMPENHO
                </span>

                <h2>Faturamento</h2>

                <p>
                    Atendimentos concluídos nos últimos 6 meses.
                </p>

            </div>

        </div>


        <div class="grafico-faturamento">

            <?php foreach ($faturamentoPorMes as $mes): ?>

                <?php

                $altura = $mes['valor'] > 0
                    ? max(
                        5,
                        round(
                            ($mes['valor'] / $maiorFaturamento)
                            * 100
                        )
                    )
                    : 2;

                ?>

                <div class="coluna-grafico">

                    <div class="valor-grafico">

                        <?php if ($mes['valor'] > 0): ?>

                            R$ <?= number_format(
                                $mes['valor'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        <?php else: ?>

                            R$ 0

                        <?php endif; ?>

                    </div>


                    <div class="area-barra">

                        <div
                            class="barra-faturamento"
                            style="height: <?= $altura ?>%;"
                        ></div>

                    </div>


                    <span class="mes-grafico">
                        <?= htmlspecialchars($mes['rotulo']) ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </div>


    <!-- SERVIÇOS MAIS PROCURADOS -->

    <div class="card-gerencial">

        <div class="cabecalho-card-gerencial">

            <div>

                <span class="subtitulo-dashboard">
                    POPULARIDADE
                </span>

                <h2>Serviços mais procurados</h2>

                <p>
                    Ranking baseado nos agendamentos registrados.
                </p>

            </div>

        </div>


        <?php if (count($servicosPopulares) > 0): ?>

            <div class="ranking-servicos">

                <?php foreach (
                    $servicosPopulares as $indice => $servico
                ): ?>

                    <?php

                    $percentual =
                        ((int) $servico['quantidade']
                        / $maiorQuantidadeServico)
                        * 100;

                    ?>

                    <div class="item-ranking">

                        <div class="linha-ranking">

                            <div class="nome-ranking">

                                <span class="posicao-ranking">
                                    <?= $indice + 1 ?>
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $servico['serv_nome']
                                    ) ?>
                                </strong>

                            </div>


                            <span class="quantidade-ranking">

                                <?= (int) $servico['quantidade'] ?>

                                atendimento<?= (int)
                                    $servico['quantidade'] !== 1
                                    ? 's'
                                    : ''
                                ?>

                            </span>

                        </div>


                        <div class="trilho-ranking">

                            <div
                                class="progresso-ranking"
                                style="width: <?= $percentual ?>%;"
                            ></div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


        <?php else: ?>

            <div class="estado-vazio-gerencial">

                <span>✂️</span>

                <div>

                    <strong>
                        Ainda não há dados suficientes.
                    </strong>

                    <p>
                        O ranking aparecerá conforme
                        os serviços forem agendados.
                    </p>

                </div>

            </div>

        <?php endif; ?>

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