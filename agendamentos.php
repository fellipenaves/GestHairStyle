<?php

require_once 'conexao.php';

$filtroData = trim($_GET['filtro_data'] ?? '');

$filtroStatus = trim($_GET['filtro_status'] ?? '');

$filtroBarbeiro = filter_input(
    INPUT_GET,
    'filtro_barbeiro',
    FILTER_VALIDATE_INT
);

$filtroCliente = trim(
    $_GET['filtro_cliente'] ?? ''
);

$listaBarbeiros = $conexao
    ->query(
        'SELECT barb_id, barb_nome
         FROM BARBEIRO
         ORDER BY barb_nome'
    )
    ->fetchAll(PDO::FETCH_ASSOC);

    /* =========================================
   VISÃO DA AGENDA POR PROFISSIONAL
   ========================================= */

$dataReferencia =
    (
        $filtroData !== '' &&
        preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $filtroData
        )
    )
        ? $filtroData
        : date('Y-m-d');

/* =========================================
   HORÁRIOS DA GRADE DIÁRIA
   ========================================= */

$horariosGrade = [];

$inicioGrade =
    new DateTime(
        $dataReferencia . ' 08:00'
    );

$fimGrade =
    new DateTime(
        $dataReferencia . ' 21:00'
    );

$horarioGrade =
    clone $inicioGrade;


while ($horarioGrade < $fimGrade) {

    $horariosGrade[] =
        $horarioGrade->format('H:i');

    $horarioGrade->modify(
        '+30 minutes'
    );
}

$sqlAgendaProfissionais = "
    SELECT
        b.barb_id,
        b.barb_nome,

        COUNT(a.agend_id)
            AS total_agendamentos,

        SUM(
            CASE
                WHEN a.agend_status = 'pendente'
                THEN 1
                ELSE 0
            END
        ) AS pendentes,

        SUM(
            CASE
                WHEN a.agend_status = 'confirmado'
                THEN 1
                ELSE 0
            END
        ) AS confirmados,

        SUM(
            CASE
                WHEN a.agend_status = 'concluido'
                THEN 1
                ELSE 0
            END
        ) AS concluidos

    FROM BARBEIRO AS b

    LEFT JOIN AGENDAMENTO AS a
        ON a.barb_id = b.barb_id
        AND DATE(a.agend_data_hora) = :data_referencia
        AND a.agend_status <> 'cancelado'

    GROUP BY
        b.barb_id,
        b.barb_nome

    ORDER BY
        total_agendamentos DESC,
        b.barb_nome
";

$consultaAgendaProfissionais =
    $conexao->prepare(
        $sqlAgendaProfissionais
    );

$consultaAgendaProfissionais->execute([
    ':data_referencia' =>
        $dataReferencia
]);

$agendaProfissionais =
    $consultaAgendaProfissionais
        ->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   AGENDA VISUAL DO DIA
   ========================================= */

$sqlAgendaVisual = "
    SELECT
        a.agend_id,
        a.agend_data_hora,
        a.agend_tempo_final,
        a.agend_status,
        a.agend_preco,

        b.barb_id,
        b.barb_nome,

        c.cli_nome,

        GROUP_CONCAT(
            s.serv_nome
            ORDER BY s.serv_nome
            SEPARATOR ', '
        ) AS servicos

    FROM AGENDAMENTO AS a

    INNER JOIN BARBEIRO AS b
        ON b.barb_id = a.barb_id

    INNER JOIN CLIENTE AS c
        ON c.cli_id = a.cli_id

    LEFT JOIN AGENDAMENTO_SERVICO AS ags
        ON ags.agend_id = a.agend_id

    LEFT JOIN SERVICO AS s
        ON s.serv_id = ags.serv_id

    WHERE DATE(a.agend_data_hora)
        = :data_referencia
";

$parametrosAgendaVisual = [
    ':data_referencia' => $dataReferencia
];


/* STATUS */

if (
    $filtroStatus !== '' &&
    in_array(
        $filtroStatus,
        [
            'pendente',
            'confirmado',
            'concluido',
            'cancelado'
        ],
        true
    )
) {

    $sqlAgendaVisual .= "
        AND a.agend_status = :status_visual
    ";

    $parametrosAgendaVisual[
        ':status_visual'
    ] = $filtroStatus;

} else {

    $sqlAgendaVisual .= "
        AND a.agend_status <> 'cancelado'
    ";
}


/* BARBEIRO */

if ($filtroBarbeiro) {

    $sqlAgendaVisual .= "
        AND a.barb_id = :barbeiro_visual
    ";

    $parametrosAgendaVisual[
        ':barbeiro_visual'
    ] = $filtroBarbeiro;
}


/* CLIENTE */

if ($filtroCliente !== '') {

    $sqlAgendaVisual .= "
        AND c.cli_nome LIKE :cliente_visual
    ";

    $parametrosAgendaVisual[
        ':cliente_visual'
    ] =
        '%' . $filtroCliente . '%';
}


$sqlAgendaVisual .= "
    GROUP BY
        a.agend_id,
        a.agend_data_hora,
        a.agend_tempo_final,
        a.agend_status,
        a.agend_preco,
        b.barb_id,
        b.barb_nome,
        c.cli_nome

    ORDER BY
        b.barb_nome,
        a.agend_data_hora ASC
";


$consultaAgendaVisual =
    $conexao->prepare(
        $sqlAgendaVisual
    );

$consultaAgendaVisual->execute(
    $parametrosAgendaVisual
);

$agendaVisual =
    $consultaAgendaVisual
        ->fetchAll(PDO::FETCH_ASSOC);


/* Organiza os atendimentos por barbeiro */

$agendaVisualPorBarbeiro = [];

foreach ($agendaVisual as $atendimento) {

    $barbeiroIdVisual =
        (int) $atendimento['barb_id'];


    if (
        !isset(
            $agendaVisualPorBarbeiro[
                $barbeiroIdVisual
            ]
        )
    ) {

        $agendaVisualPorBarbeiro[
            $barbeiroIdVisual
        ] = [

            'nome' =>
                $atendimento['barb_nome'],

            'atendimentos' => []

        ];
    }


    $agendaVisualPorBarbeiro[
        $barbeiroIdVisual
    ]['atendimentos'][] =
        $atendimento;
}

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

if ($filtroBarbeiro) {
    $sql .= ' AND a.barb_id = :filtro_barbeiro';
    $parametros[':filtro_barbeiro'] = $filtroBarbeiro;
}

if ($filtroCliente !== '') {

    $sql .= '
        AND c.cli_nome LIKE :filtro_cliente
    ';

    $parametros[':filtro_cliente'] =
        '%' . $filtroCliente . '%';
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

/* =========================================
   INDICADORES DE STATUS
   ========================================= */

$contagemStatus = [
    'pendente' => 0,
    'confirmado' => 0,
    'concluido' => 0,
    'cancelado' => 0
];

$sqlStatus = "
    SELECT
        agend_status,
        COUNT(*) AS total
    FROM AGENDAMENTO
    GROUP BY agend_status
";

$consultaStatus = $conexao->query($sqlStatus);

$resultadosStatus =
    $consultaStatus->fetchAll(PDO::FETCH_ASSOC);

foreach ($resultadosStatus as $resultado) {

    $status = $resultado['agend_status'];

    if (isset($contagemStatus[$status])) {
        $contagemStatus[$status] =
            (int) $resultado['total'];
    }
}

/* =========================================
   LINKS DOS CARDS DE STATUS
   Preservam os demais filtros
   ========================================= */

$criarLinkStatus = function ($status) use (
    $filtroData,
    $filtroBarbeiro,
    $filtroCliente
) {

    $parametros = [
        'filtro_status' => $status
    ];


    if (
        $filtroData !== '' &&
        preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $filtroData
        )
    ) {

        $parametros['filtro_data'] =
            $filtroData;
    }


    if ($filtroBarbeiro) {

        $parametros['filtro_barbeiro'] =
            $filtroBarbeiro;
    }


    if ($filtroCliente !== '') {

        $parametros['filtro_cliente'] =
            $filtroCliente;
    }


    return
        'agendamentos.php?'
        . http_build_query($parametros);
};

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agendamentos | GestHairStyle</title>

    <link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>"
    >
</head>

<body>

<?php
$paginaAtual = 'agendamentos';
require 'menu.php';
?>

<div class="container">

    <div class="cabecalho-pagina">

    <div>

        <span class="subtitulo-dashboard">
            AGENDA
        </span>

        <h1>Agendamentos</h1>

        <p>
            Acompanhe os atendimentos,
            horários e status da agenda.
        </p>

    </div>


    <a
        href="cadastrar_agendamento.php"
        class="botao-destaque"
    >
        + Novo agendamento
    </a>

</div>


<div class="grid-resumo grid-status-agendamentos">

    <a
    href="<?= htmlspecialchars(
    $criarLinkStatus('pendente')
) ?>"
    class="card-resumo card-status card-pendente link-card-status
        <?= $filtroStatus === 'pendente' ? 'status-ativo' : '' ?>"
>

    <div class="icone-card">⏳</div>

    <div class="info-card">
        <span>Pendentes</span>

        <strong>
            <?= $contagemStatus['pendente'] ?>
        </strong>

        <small>
            Aguardando confirmação
        </small>
    </div>

</a>


    <a
    href="<?= htmlspecialchars(
    $criarLinkStatus('confirmado')
) ?>"
    class="card-resumo card-status card-confirmado link-card-status
        <?= $filtroStatus === 'confirmado' ? 'status-ativo' : '' ?>"
>

    <div class="icone-card">✓</div>

    <div class="info-card">
        <span>Confirmados</span>

        <strong>
            <?= $contagemStatus['confirmado'] ?>
        </strong>

        <small>
            Atendimentos confirmados
        </small>
    </div>

</a>


    <a
    href="<?= htmlspecialchars(
    $criarLinkStatus('concluido')
) ?>"
    class="card-resumo card-status card-concluido link-card-status
        <?= $filtroStatus === 'concluido' ? 'status-ativo' : '' ?>"
>

    <div class="icone-card">✂️</div>

    <div class="info-card">
        <span>Concluídos</span>

        <strong>
            <?= $contagemStatus['concluido'] ?>
        </strong>

        <small>
            Atendimentos realizados
        </small>
    </div>

</a>


    <a
    href="<?= htmlspecialchars(
    $criarLinkStatus('cancelado')
) ?>"
    class="card-resumo card-status card-cancelado link-card-status
        <?= $filtroStatus === 'cancelado' ? 'status-ativo' : '' ?>"
>

    <div class="icone-card">×</div>

    <div class="info-card">
        <span>Cancelados</span>

        <strong>
            <?= $contagemStatus['cancelado'] ?>
        </strong>

        <small>
            Atendimentos cancelados
        </small>
    </div>

</a>

</div>

<!-- =========================================
     AGENDA POR PROFISSIONAL
     ========================================= -->

<div class="painel-profissionais">

    <div class="cabecalho-agenda-profissionais">

        <div>

            <span class="subtitulo-dashboard">
                PROFISSIONAIS
            </span>

            <h2>Agenda por barbeiro</h2>

            <p>
                Visão dos atendimentos para
                <?= date(
                    'd/m/Y',
                    strtotime($dataReferencia)
                ) ?>.
            </p>

        </div>

    </div>


    <div class="grid-profissionais">

        <?php foreach (
            $agendaProfissionais as $profissional
        ): ?>

            <?php

$parametrosProfissional = [
    'filtro_data' => $dataReferencia,
    'filtro_barbeiro' => (int) $profissional['barb_id']
];

if (
    $filtroStatus !== '' &&
    in_array(
        $filtroStatus,
        $statusPermitidos,
        true
    )
) {
    $parametrosProfissional['filtro_status'] =
        $filtroStatus;
}

if ($filtroCliente !== '') {

    $parametrosProfissional['filtro_cliente'] =
        $filtroCliente;
}

$linkProfissional =
    'agendamentos.php?'
    . http_build_query($parametrosProfissional);

?>

<a
    href="<?= htmlspecialchars($linkProfissional) ?>"
    class="card-profissional"
>

                <div class="topo-card-profissional">

                    <div class="avatar-profissional">
                        ✂
                    </div>

                    <div>

                        <strong>
                            <?= htmlspecialchars(
                                $profissional['barb_nome']
                            ) ?>
                        </strong>

                        <small>
                            <?= (int)
                                $profissional[
                                    'total_agendamentos'
                                ]
                            ?>
                            atendimento<?= (int)
                                $profissional[
                                    'total_agendamentos'
                                ] !== 1
                                    ? 's'
                                    : ''
                            ?>
                        </small>

                    </div>

                </div>


                <div class="status-profissional">

                    <span>
                        <small>Pend.</small>

                        <strong>
                            <?= (int)
                                $profissional['pendentes']
                            ?>
                        </strong>
                    </span>


                    <span>
                        <small>Conf.</small>

                        <strong>
                            <?= (int)
                                $profissional['confirmados']
                            ?>
                        </strong>
                    </span>


                    <span>
                        <small>Concl.</small>

                        <strong>
                            <?= (int)
                                $profissional['concluidos']
                            ?>
                        </strong>
                    </span>

                </div>

            </a>

        <?php endforeach; ?>

    </div>

</div>

<!-- =========================================
     AGENDA VISUAL DO DIA
     ========================================= -->

<div class="painel-agenda-dia">

    <div class="cabecalho-agenda-dia">

        <div>

            <span class="subtitulo-dashboard">
                VISÃO DO DIA
            </span>

            <h2>
                Agenda de
                <?= date(
                    'd/m/Y',
                    strtotime($dataReferencia)
                ) ?>
            </h2>

            <p>
                Atendimentos organizados
                por profissional e horário.
            </p>

        </div>

    </div>


    <?php if (
        count($agendaVisualPorBarbeiro) > 0
    ): ?>


        <div class="grid-agenda-dia">

            <?php foreach (
                $agendaVisualPorBarbeiro
                as $barbeiroVisual
            ): ?>


                <div class="coluna-agenda-barbeiro">


                    <div class="titulo-agenda-barbeiro">

                        <div class="avatar-profissional">
                            ✂
                        </div>

                        <div>

                            <strong>
                                <?= htmlspecialchars(
                                    $barbeiroVisual['nome']
                                ) ?>
                            </strong>

                            <small>
                                <?= count(
                                    $barbeiroVisual[
                                        'atendimentos'
                                    ]
                                ) ?>
                                atendimento<?= count(
                                    $barbeiroVisual[
                                        'atendimentos'
                                    ]
                                ) !== 1
                                    ? 's'
                                    : ''
                                ?>
                            </small>

                        </div>

                    </div>


                    <div class="grade-horarios-dia">

    <?php foreach ($horariosGrade as $horaGrade): ?>

        <?php

        $inicioSlot = new DateTime(
            $dataReferencia . ' ' . $horaGrade
        );

        $fimSlot = clone $inicioSlot;

        $fimSlot->modify('+30 minutes');


        $atendimentoNoHorario = null;

        $inicioAtendimentoNesteHorario = false;


        foreach (
            $barbeiroVisual['atendimentos']
            as $atendimentoGrade
        ) {

            $inicioAtendimento =
                new DateTime(
                    $atendimentoGrade[
                        'agend_data_hora'
                    ]
                );

            $fimAtendimento =
                new DateTime(
                    $atendimentoGrade[
                        'agend_tempo_final'
                    ]
                );


            if (
                $inicioAtendimento < $fimSlot &&
                $fimAtendimento > $inicioSlot
            ) {

                $atendimentoNoHorario =
                    $atendimentoGrade;


                $inicioAtendimentoNesteHorario =
                    (
                        $inicioAtendimento >= $inicioSlot &&
                        $inicioAtendimento < $fimSlot
                    );


                break;
            }
        }

        ?>


        <?php if (
            $atendimentoNoHorario &&
            $inicioAtendimentoNesteHorario
        ): ?>

            <div class="
                linha-horario-dia
                horario-dia-ocupado
            ">

                <div class="hora-grade-dia">

                    <?= htmlspecialchars(
                        $horaGrade
                    ) ?>

                </div>


                <div class="conteudo-grade-dia">

                    <div class="topo-grade-atendimento">

                        <strong>
                            <?= htmlspecialchars(
                                $atendimentoNoHorario[
                                    'cli_nome'
                                ]
                            ) ?>
                        </strong>


                        <span
                            class="status <?= htmlspecialchars(
                                $atendimentoNoHorario[
                                    'agend_status'
                                ]
                            ) ?>"
                        >
                            <?= ucfirst(
                                htmlspecialchars(
                                    $atendimentoNoHorario[
                                        'agend_status'
                                    ]
                                )
                            ) ?>
                        </span>

                    </div>


                    <span class="servico-grade-dia">

                        <?= htmlspecialchars(
                            $atendimentoNoHorario[
                                'servicos'
                            ]
                            ??
                            'Serviço não informado'
                        ) ?>

                    </span>


                    <small>

                        <?= date(
                            'H:i',
                            strtotime(
                                $atendimentoNoHorario[
                                    'agend_data_hora'
                                ]
                            )
                        ) ?>

                        —

                        <?= date(
                            'H:i',
                            strtotime(
                                $atendimentoNoHorario[
                                    'agend_tempo_final'
                                ]
                            )
                        ) ?>

                    </small>


                    <a
                        href="editar_agendamento.php?id=<?= (int)
                            $atendimentoNoHorario[
                                'agend_id'
                            ]
                        ?>"
                        class="link-grade-editar"
                    >
                        Editar
                    </a>

                </div>

            </div>


        <?php elseif ($atendimentoNoHorario): ?>

            <div class="
                linha-horario-dia
                horario-dia-continuacao
            ">

                <div class="hora-grade-dia">

                    <?= htmlspecialchars(
                        $horaGrade
                    ) ?>

                </div>


                <div class="conteudo-grade-dia">

                    <span class="indicador-ocupado">

                        Ocupado até

                        <?= date(
                            'H:i',
                            strtotime(
                                $atendimentoNoHorario[
                                    'agend_tempo_final'
                                ]
                            )
                        ) ?>

                    </span>

                </div>

            </div>


        <?php else: ?>

            <div class="
                linha-horario-dia
                horario-dia-livre
            ">

                <div class="hora-grade-dia">

                    <?= htmlspecialchars(
                        $horaGrade
                    ) ?>

                </div>


                <div class="conteudo-grade-dia">

                    <span class="indicador-livre">
                        Livre
                    </span>

                </div>

            </div>


        <?php endif; ?>


    <?php endforeach; ?>

</div>


</div>


<?php endforeach; ?>


</div>


<?php else: ?>


    <div class="agenda-dia-vazia">

        Nenhum atendimento encontrado
        para esta data.

    </div>


<?php endif; ?>


</div>

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

    <div class="campo-filtro">

    <label for="filtro_barbeiro">
        Barbeiro
    </label>

    <select
        id="filtro_barbeiro"
        name="filtro_barbeiro"
    >

        <option value="">
            Todos
        </option>

        <?php foreach ($listaBarbeiros as $barbeiro): ?>

            <option
                value="<?= (int) $barbeiro['barb_id'] ?>"
                <?= (int) $filtroBarbeiro ===
                    (int) $barbeiro['barb_id']
                    ? 'selected'
                    : ''
                ?>
            >

                <?= htmlspecialchars(
                    $barbeiro['barb_nome']
                ) ?>

            </option>

        <?php endforeach; ?>

    </select>

</div>

<div class="campo-filtro campo-filtro-cliente">

    <label for="filtro_cliente">
        Cliente
    </label>

    <input
        type="text"
        id="filtro_cliente"
        name="filtro_cliente"
        placeholder="Digite o nome..."
        value="<?= htmlspecialchars(
            $filtroCliente
        ) ?>"
    >

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