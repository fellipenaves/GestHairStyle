<?php

require_once 'conexao.php';


/* =========================================
   MÊS SELECIONADO
   ========================================= */

$filtroMes = trim(
    $_GET['mes'] ?? date('Y-m')
);


/* Valida o formato AAAA-MM */

$dataFiltro =
    DateTime::createFromFormat(
        '!Y-m',
        $filtroMes
    );


if (
    !$dataFiltro
    ||
    $dataFiltro->format('Y-m')
        !== $filtroMes
) {

    $filtroMes =
        date('Y-m');

    $dataFiltro =
        DateTime::createFromFormat(
            '!Y-m',
            $filtroMes
        );
}


/* Início e fim do mês */

$inicioMesObjeto =
    clone $dataFiltro;

$inicioMesObjeto->setTime(
    0,
    0,
    0
);


$fimMesObjeto =
    clone $inicioMesObjeto;

$fimMesObjeto->modify(
    'first day of next month'
);


$inicioMes =
    $inicioMesObjeto->format(
        'Y-m-d H:i:s'
    );

$fimMes =
    $fimMesObjeto->format(
        'Y-m-d H:i:s'
    );


/* =========================================
   FATURAMENTO DO MÊS
   Apenas atendimentos concluídos
   ========================================= */

$consultaFaturamento =
    $conexao->prepare(
        "SELECT
            COALESCE(
                SUM(agend_preco),
                0
            )

         FROM AGENDAMENTO

         WHERE agend_status = 'concluido'

           AND agend_data_hora >= :inicio

           AND agend_data_hora < :fim"
    );


$consultaFaturamento->execute([

    ':inicio' =>
        $inicioMes,

    ':fim' =>
        $fimMes

]);


$faturamento =
    (float)
    $consultaFaturamento
        ->fetchColumn();


/* =========================================
   CUSTOS DOS SERVIÇOS
   ========================================= */

$consultaCustos =
    $conexao->prepare(
        "SELECT
            COALESCE(
                SUM(
                    ags.agenser_custo
                ),
                0
            )

         FROM AGENDAMENTO AS a

         INNER JOIN
            AGENDAMENTO_SERVICO AS ags

            ON ags.agend_id =
               a.agend_id

         WHERE
            a.agend_status = 'concluido'

            AND a.agend_data_hora
                >= :inicio

            AND a.agend_data_hora
                < :fim"
    );


$consultaCustos->execute([

    ':inicio' =>
        $inicioMes,

    ':fim' =>
        $fimMes

]);


$custosServicos =
    (float)
    $consultaCustos
        ->fetchColumn();


/* =========================================
   COMISSÕES
   ========================================= */

$consultaComissoes =
    $conexao->prepare(
        "SELECT
            COALESCE(
                SUM(
                    agend_comissao_valor
                ),
                0
            )

         FROM AGENDAMENTO

         WHERE agend_status = 'concluido'

           AND agend_data_hora >= :inicio

           AND agend_data_hora < :fim"
    );


$consultaComissoes->execute([

    ':inicio' =>
        $inicioMes,

    ':fim' =>
        $fimMes

]);


$comissoes =
    (float)
    $consultaComissoes
        ->fetchColumn();


/* =========================================
   DESPESAS PAGAS
   ========================================= */

$consultaDespesasPagas =
    $conexao->prepare(
        "SELECT
            COALESCE(
                SUM(desp_valor),
                0
            )

         FROM DESPESA

         WHERE desp_status = 'pago'

           AND desp_data >= :inicio

           AND desp_data < :fim"
    );


$consultaDespesasPagas->execute([

    ':inicio' =>
        $inicioMesObjeto->format(
            'Y-m-d'
        ),

    ':fim' =>
        $fimMesObjeto->format(
            'Y-m-d'
        )

]);


$despesasPagas =
    (float)
    $consultaDespesasPagas
        ->fetchColumn();


/* =========================================
   DESPESAS PENDENTES
   ========================================= */

$consultaDespesasPendentes =
    $conexao->prepare(
        "SELECT
            COALESCE(
                SUM(desp_valor),
                0
            )

         FROM DESPESA

         WHERE desp_status = 'pendente'

           AND desp_data >= :inicio

           AND desp_data < :fim"
    );


$consultaDespesasPendentes->execute([

    ':inicio' =>
        $inicioMesObjeto->format(
            'Y-m-d'
        ),

    ':fim' =>
        $fimMesObjeto->format(
            'Y-m-d'
        )

]);


$despesasPendentes =
    (float)
    $consultaDespesasPendentes
        ->fetchColumn();


/* =========================================
   RESULTADOS DO MÊS
   ========================================= */

$resultadoAtendimentos =
    $faturamento
    - $custosServicos
    - $comissoes;


$resultadoLiquido =
    $resultadoAtendimentos
    - $despesasPagas;


/* =========================================
   GRÁFICO FINANCEIRO
   MÊS SELECIONADO + 5 MESES ANTERIORES
   ========================================= */

$dadosGrafico = [];

$maiorValorGrafico = 0;


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


for ($i = 5; $i >= 0; $i--) {

    /*
     * Usa o mês escolhido no filtro
     * como referência.
     */

    $mesGrafico =
        clone $dataFiltro;

    $mesGrafico->modify(
        "-{$i} months"
    );


    $inicioGraficoObjeto =
        clone $mesGrafico;

    $inicioGraficoObjeto->setTime(
        0,
        0,
        0
    );


    $fimGraficoObjeto =
        clone $inicioGraficoObjeto;

    $fimGraficoObjeto->modify(
        'first day of next month'
    );


    $inicioGrafico =
        $inicioGraficoObjeto->format(
            'Y-m-d H:i:s'
        );

    $fimGrafico =
        $fimGraficoObjeto->format(
            'Y-m-d H:i:s'
        );


    /* =====================================
       FATURAMENTO DO MÊS DO GRÁFICO
       ===================================== */

    $consultaGraficoFaturamento =
        $conexao->prepare(
            "SELECT
                COALESCE(
                    SUM(agend_preco),
                    0
                )

             FROM AGENDAMENTO

             WHERE
                agend_status = 'concluido'

                AND agend_data_hora
                    >= :inicio

                AND agend_data_hora
                    < :fim"
        );


    $consultaGraficoFaturamento
        ->execute([

            ':inicio' =>
                $inicioGrafico,

            ':fim' =>
                $fimGrafico

        ]);


    $faturamentoGrafico =
        (float)
        $consultaGraficoFaturamento
            ->fetchColumn();


    /* =====================================
       CUSTOS DOS SERVIÇOS
       ===================================== */

    $consultaGraficoCustos =
        $conexao->prepare(
            "SELECT
                COALESCE(
                    SUM(
                        ags.agenser_custo
                    ),
                    0
                )

             FROM AGENDAMENTO AS a

             INNER JOIN
                AGENDAMENTO_SERVICO AS ags

                ON ags.agend_id =
                   a.agend_id

             WHERE
                a.agend_status = 'concluido'

                AND a.agend_data_hora
                    >= :inicio

                AND a.agend_data_hora
                    < :fim"
        );


    $consultaGraficoCustos
        ->execute([

            ':inicio' =>
                $inicioGrafico,

            ':fim' =>
                $fimGrafico

        ]);


    $custosGrafico =
        (float)
        $consultaGraficoCustos
            ->fetchColumn();


    /* =====================================
       COMISSÕES
       ===================================== */

    $consultaGraficoComissoes =
        $conexao->prepare(
            "SELECT
                COALESCE(
                    SUM(
                        agend_comissao_valor
                    ),
                    0
                )

             FROM AGENDAMENTO

             WHERE
                agend_status = 'concluido'

                AND agend_data_hora
                    >= :inicio

                AND agend_data_hora
                    < :fim"
        );


    $consultaGraficoComissoes
        ->execute([

            ':inicio' =>
                $inicioGrafico,

            ':fim' =>
                $fimGrafico

        ]);


    $comissoesGrafico =
        (float)
        $consultaGraficoComissoes
            ->fetchColumn();


    /* =====================================
       DESPESAS PAGAS
       ===================================== */

    $consultaGraficoDespesas =
        $conexao->prepare(
            "SELECT
                COALESCE(
                    SUM(desp_valor),
                    0
                )

             FROM DESPESA

             WHERE
                desp_status = 'pago'

                AND desp_data >= :inicio

                AND desp_data < :fim"
        );


    $consultaGraficoDespesas
        ->execute([

            ':inicio' =>
                $inicioGraficoObjeto
                    ->format(
                        'Y-m-d'
                    ),

            ':fim' =>
                $fimGraficoObjeto
                    ->format(
                        'Y-m-d'
                    )

        ]);


    $despesasGrafico =
        (float)
        $consultaGraficoDespesas
            ->fetchColumn();


    /* =====================================
       RESULTADO LÍQUIDO
       ===================================== */

    $resultadoGrafico =
        $faturamentoGrafico
        - $custosGrafico
        - $comissoesGrafico
        - $despesasGrafico;


    /* Nome do mês */

    $numeroMes =
        (int)
        $mesGrafico->format('n');


    $rotuloMes =
        $nomesMeses[$numeroMes]
        . '/'
        . $mesGrafico->format('Y');


    /* Guarda os dados */

    $dadosGrafico[] = [

        'mes' =>
            $rotuloMes,

        'faturamento' =>
            $faturamentoGrafico,

        'despesas' =>
            $despesasGrafico,

        'resultado' =>
            $resultadoGrafico

    ];


    /*
     * Maior valor usado para calcular
     * proporcionalmente as barras.
     */

    $maiorValorGrafico =
        max(

            $maiorValorGrafico,

            $faturamentoGrafico,

            $despesasGrafico,

            abs(
                $resultadoGrafico
            )

        );
}


if ($maiorValorGrafico <= 0) {

    $maiorValorGrafico = 1;
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
        Relatório Financeiro | GestHairStyle
    </title>

    <link
        rel="stylesheet"
        href="style.css?v=<?= filemtime(
            'style.css'
        ) ?>"
    >

</head>

<body>


<?php

$paginaAtual =
    'relatorio_financeiro';

require 'menu.php';

?>


<div class="container">


    <!-- =====================================
         CABEÇALHO
         ===================================== -->

    <div class="topo-dashboard">

        <div class="topo-texto">

            <span class="tag-painel">
                FINANCEIRO
            </span>

            <h1>
                Relatório financeiro
            </h1>

            <p>
                Analise o desempenho financeiro
                da barbearia por mês.
            </p>

        </div>

    </div>


    <!-- =====================================
         FILTRO
         ===================================== -->

    <form
        method="GET"
        class="
            card-formulario
            filtros-despesas
        "
    >

        <div class="campo-formulario">

            <label for="mes">
                Mês de referência
            </label>

            <input
                type="month"
                id="mes"
                name="mes"
                value="<?= htmlspecialchars(
                    $filtroMes
                ) ?>"
            >

        </div>


        <div class="acoes-formulario">

            <button
                type="submit"
                class="botao-destaque"
            >
                Consultar
            </button>

        </div>

    </form>


    <!-- =====================================
         INDICADORES
         ===================================== -->

    <div class="grid-resumo">


        <!-- FATURAMENTO -->

        <div class="card-resumo">

            <div class="icone-card">
                💰
            </div>

            <div class="info-card">

                <span>
                    Faturamento
                </span>

                <strong>
                    R$ <?= number_format(
                        $faturamento,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Atendimentos concluídos
                </small>

            </div>

        </div>


        <!-- CUSTOS -->

        <div class="card-resumo">

            <div class="icone-card">
                🧾
            </div>

            <div class="info-card">

                <span>
                    Custos dos serviços
                </span>

                <strong>
                    R$ <?= number_format(
                        $custosServicos,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Custos diretos dos atendimentos
                </small>

            </div>

        </div>


        <!-- COMISSÕES -->

        <div class="card-resumo">

            <div class="icone-card">
                %
            </div>

            <div class="info-card">

                <span>
                    Comissões
                </span>

                <strong>
                    R$ <?= number_format(
                        $comissoes,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Comissões dos profissionais
                </small>

            </div>

        </div>


        <!-- RESULTADO ATENDIMENTOS -->

        <div class="card-resumo">

            <div class="icone-card">
                📈
            </div>

            <div class="info-card">

                <span>
                    Resultado dos atendimentos
                </span>

                <strong
                    class="<?= $resultadoAtendimentos >= 0
                        ? 'valor-positivo'
                        : 'valor-negativo'
                    ?>"
                >
                    R$ <?= number_format(
                        $resultadoAtendimentos,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Faturamento menos custos
                    e comissões
                </small>

            </div>

        </div>


        <!-- DESPESAS PAGAS -->

        <div class="card-resumo">

            <div class="icone-card">
                💸
            </div>

            <div class="info-card">

                <span>
                    Despesas pagas
                </span>

                <strong>
                    R$ <?= number_format(
                        $despesasPagas,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Despesas gerais pagas
                </small>

            </div>

        </div>


        <!-- DESPESAS PENDENTES -->

        <div class="card-resumo">

            <div class="icone-card">
                ⏳
            </div>

            <div class="info-card">

                <span>
                    Despesas pendentes
                </span>

                <strong>
                    R$ <?= number_format(
                        $despesasPendentes,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Valores ainda não pagos
                </small>

            </div>

        </div>


        <!-- RESULTADO LÍQUIDO -->

        <div class="card-resumo">

            <div class="icone-card">
                💵
            </div>

            <div class="info-card">

                <span>
                    Resultado líquido
                </span>

                <strong
                    class="<?= $resultadoLiquido >= 0
                        ? 'valor-positivo'
                        : 'valor-negativo'
                    ?>"
                >
                    R$ <?= number_format(
                        $resultadoLiquido,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Após custos, comissões
                    e despesas pagas
                </small>

            </div>

        </div>


    </div>


    <!-- =====================================
         GRÁFICO FINANCEIRO
         ===================================== -->

    <div
        class="
            card-gerencial
            relatorio-grafico
        "
    >

        <div class="cabecalho-card-gerencial">

            <div>

                <span class="subtitulo-dashboard">
                    EVOLUÇÃO
                </span>

                <h2>
                    Desempenho financeiro
                </h2>

                <p>
                    Mês selecionado e
                    cinco meses anteriores.
                </p>

            </div>

        </div>


        <!-- LEGENDA -->

        <div class="legenda-financeira">

            <span>

                <i
                    class="
                        legenda-faturamento
                    "
                ></i>

                Faturamento

            </span>


            <span>

                <i
                    class="
                        legenda-despesas
                    "
                ></i>

                Despesas gerais pagas

            </span>


            <span>

                <i
                    class="
                        legenda-resultado
                    "
                ></i>

                Resultado líquido

            </span>

        </div>


        <!-- BARRAS -->

        <div class="grafico-financeiro">


            <?php foreach (
                $dadosGrafico as $item
            ): ?>


                <?php

                $alturaFaturamento =
                    (
                        $item['faturamento']
                        / $maiorValorGrafico
                    )
                    * 100;


                $alturaDespesas =
                    (
                        $item['despesas']
                        / $maiorValorGrafico
                    )
                    * 100;


                $alturaResultado =
                    (
                        abs(
                            $item['resultado']
                        )
                        / $maiorValorGrafico
                    )
                    * 100;
                
                $alturaFaturamentoCss =
    $item['faturamento'] > 0
        ? max(
            3,
            min(
                100,
                $alturaFaturamento
            )
        )
        : 0;


$alturaDespesasCss =
    $item['despesas'] > 0
        ? max(
            3,
            min(
                100,
                $alturaDespesas
            )
        )
        : 0;


$alturaResultadoCss =
    abs($item['resultado']) > 0
        ? max(
            3,
            min(
                100,
                $alturaResultado
            )
        )
        : 0;

                ?>


                <div class="grupo-financeiro">


                    <div class="barras-financeiras">


                        <!-- FATURAMENTO -->

                        <div
    class="
        barra-financeira
        barra-financeira-faturamento
    "
    <?= 'style="height: '
        . $alturaFaturamentoCss
        . '%;"'
    ?>
    title="Faturamento: R$ <?= number_format(
        $item['faturamento'],
        2,
        ',',
        '.'
    ) ?>"
></div>


                        <!-- DESPESAS -->

                        <div
    class="
        barra-financeira
        barra-financeira-despesas
    "
    <?= 'style="height: '
        . $alturaDespesasCss
        . '%;"'
    ?>
    title="Despesas gerais pagas: R$ <?= number_format(
        $item['despesas'],
        2,
        ',',
        '.'
    ) ?>"
></div>


                        <!-- RESULTADO -->

                        <div
    class="
        barra-financeira
        barra-financeira-resultado
        <?= $item['resultado'] < 0
            ? 'resultado-negativo'
            : ''
        ?>
    "
    <?= 'style="height: '
        . $alturaResultadoCss
        . '%;"'
    ?>
    title="Resultado líquido: R$ <?= number_format(
        $item['resultado'],
        2,
        ',',
        '.'
    ) ?>"
></div>

                    </div>


                    <strong>
                        <?= htmlspecialchars(
                            $item['mes']
                        ) ?>
                    </strong>

                </div>


            <?php endforeach; ?>


        </div>

    </div>


</div>

</body>

</html>