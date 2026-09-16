<?php

require_once 'conexao.php';


/* =========================================
   FILTROS
   ========================================= */

$filtroMes = trim(
    $_GET['mes'] ?? ''
);

$filtroCategoria = trim(
    $_GET['categoria'] ?? ''
);

$filtroStatus = trim(
    $_GET['filtro_status'] ?? ''
);


$categorias = [
    'Estrutura',
    'Utilidades',
    'Materiais',
    'Limpeza',
    'Marketing',
    'Manutenção',
    'Impostos',
    'Outros'
];


/* =========================================
   CONDIÇÕES DOS FILTROS
   ========================================= */

$condicoes = [];

$parametros = [];


/* Filtro por mês */

if (
    $filtroMes !== ''
    &&
    preg_match(
        '/^\d{4}-\d{2}$/',
        $filtroMes
    )
) {

    $condicoes[] =
        "DATE_FORMAT(
            desp_data,
            '%Y-%m'
        ) = :mes";

    $parametros[':mes'] =
        $filtroMes;
}


/* Filtro por categoria */

if (
    $filtroCategoria !== ''
    &&
    in_array(
        $filtroCategoria,
        $categorias,
        true
    )
) {

    $condicoes[] =
        'desp_categoria = :categoria';

    $parametros[':categoria'] =
        $filtroCategoria;
}


/* Filtro por status */

if (
    in_array(
        $filtroStatus,
        [
            'pago',
            'pendente'
        ],
        true
    )
) {

    $condicoes[] =
        'desp_status = :status';

    $parametros[':status'] =
        $filtroStatus;
}


/* =========================================
   BUSCA DAS DESPESAS
   ========================================= */

$sqlDespesas = "
    SELECT
        desp_id,
        desp_descricao,
        desp_categoria,
        desp_valor,
        desp_data,
        desp_status,
        desp_observacao

    FROM DESPESA
";


if (!empty($condicoes)) {

    $sqlDespesas .=
        ' WHERE '
        . implode(
            ' AND ',
            $condicoes
        );
}


$sqlDespesas .= "
    ORDER BY
        desp_data DESC,
        desp_id DESC
";


$consultaDespesas =
    $conexao->prepare(
        $sqlDespesas
    );


$consultaDespesas->execute(
    $parametros
);


$despesas =
    $consultaDespesas->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================
   TOTAIS DOS RESULTADOS FILTRADOS
   ========================================= */

$totalPagoFiltrado = 0;

$totalPendenteFiltrado = 0;


foreach ($despesas as $despesa) {

    $valor =
        (float) $despesa['desp_valor'];


    if (
        $despesa['desp_status']
        === 'pago'
    ) {

        $totalPagoFiltrado +=
            $valor;
    }


    if (
        $despesa['desp_status']
        === 'pendente'
    ) {

        $totalPendenteFiltrado +=
            $valor;
    }
}


$totalDespesas =
    count($despesas);

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
        Despesas | GestHairStyle
    </title>

    <link
        rel="stylesheet"
        href="style.css?v=<?= filemtime('style.css') ?>"
    >

</head>

<body>

<?php

$paginaAtual = 'despesas';

require 'menu.php';

?>


    <div class="container">

        <div class="topo-dashboard">

            <div class="topo-texto">

                <span class="tag-painel">
                    FINANCEIRO
                </span>

                <h1>Despesas</h1>

                <p>
                    Acompanhe os gastos gerais
                    da barbearia.
                </p>

            </div>

            <div class="acoes-rapidas">

        <a
            class="botao-cadastro"
            href="cadastrar_despesa.php"
        >
            + Nova despesa
        </a>

        </div>

    </div>

<?php if (($_GET['status'] ?? '') === 'criado'): ?>

    <div class="aviso aviso-sucesso">
        Despesa cadastrada com sucesso!
    </div>

<?php elseif (($_GET['status'] ?? '') === 'editado'): ?>

    <div class="aviso aviso-sucesso">
        Despesa atualizada com sucesso!
    </div>

<?php elseif (($_GET['status'] ?? '') === 'excluido'): ?>

    <div class="aviso aviso-sucesso">
        Despesa excluída com sucesso!
    </div>

<?php elseif (($_GET['status'] ?? '') === 'erro'): ?>

    <div class="aviso aviso-erro">
        Não foi possível excluir a despesa.
    </div>

<?php endif; ?>

<!-- FILTROS -->

<form
    method="GET"
    class="card-formulario filtros-despesas"
>

    <div class="grid-formulario">

        <div class="campo-formulario">

            <label for="mes">
                Mês
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


        <div class="campo-formulario">

            <label for="categoria">
                Categoria
            </label>

            <select
                id="categoria"
                name="categoria"
            >

                <option value="">
                    Todas as categorias
                </option>

                <?php foreach (
                    $categorias as $categoria
                ): ?>

                    <option
                        value="<?= htmlspecialchars(
                            $categoria
                        ) ?>"
                        <?= (
                            $filtroCategoria
                            === $categoria
                        ) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            $categoria
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <div class="campo-formulario">

            <label for="filtro_status">
                Status
            </label>

            <select
                id="filtro_status"
                name="filtro_status"
            >

                <option value="">
                    Todos
                </option>

                <option
                    value="pago"
                    <?= (
                        $filtroStatus === 'pago'
                    ) ? 'selected' : '' ?>
                >
                    Pago
                </option>

                <option
                    value="pendente"
                    <?= (
                        $filtroStatus === 'pendente'
                    ) ? 'selected' : '' ?>
                >
                    Pendente
                </option>

            </select>

        </div>

    </div>


    <div class="acoes-formulario">

        <a
            href="despesas.php"
            class="botao-secundario"
        >
            Limpar filtros
        </a>

        <button
            type="submit"
            class="botao-destaque"
        >
            Filtrar
        </button>

    </div>

</form>

    <!-- RESUMO -->

    <div class="grid-resumo">

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
                        $totalPagoFiltrado,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Valores pagos no período filtrado
                </small>

            </div>

        </div>


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
                        $totalPendenteFiltrado,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Valores pendentes no período filtrado
                </small>

            </div>

        </div>


        <div class="card-resumo">

            <div class="icone-card">
                🧾
            </div>

            <div class="info-card">

                <span>
                    Registros
                </span>

                <strong>
                    <?= $totalDespesas ?>
                </strong>

                <small>
                    Total de registros encontrados
                </small>

            </div>

        </div>

    </div>


    <!-- TABELA -->

    <div class="tabela-container">

        <table>

            <thead>

                <tr>

                    <th>Data</th>

                    <th>Descrição</th>

                    <th>Categoria</th>

                    <th>Valor</th>

                    <th>Status</th>

                    <th>Observação</th>

                    <th>Ações</th>

                </tr>

            </thead>

            <tbody>

                <?php if (count($despesas) > 0): ?>

                    <?php foreach ($despesas as $despesa): ?>

                        <tr>

                            <td>
                                <?= date(
                                    'd/m/Y',
                                    strtotime(
                                        $despesa['desp_data']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $despesa[
                                        'desp_descricao'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $despesa[
                                        'desp_categoria'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                R$ <?= number_format(
                                    $despesa[
                                        'desp_valor'
                                    ],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?= ucfirst(
                                    htmlspecialchars(
                                        $despesa[
                                            'desp_status'
                                        ]
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $despesa[
                                        'desp_observacao'
                                    ]
                                    ?? '—'
                                ) ?>
                            </td>

                            <td>

                                    <a
                                        href="editar_despesa.php?id=<?= (int)
                                            $despesa['desp_id']
                                        ?>"
                                    >
                                        Editar
                                    </a>


                                    <form
                                        action="excluir_despesa.php"
                                        method="POST"
                                        class="form-excluir"
                                        onsubmit="
                                            return confirm(
                                                'Deseja realmente excluir esta despesa?'
                                            );
                                        "
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)
                                            $despesa['desp_id']
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="botao-excluir"
                                    >
                                        Excluir
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            style="text-align: center;"
                        >
                            Nenhuma despesa cadastrada.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>