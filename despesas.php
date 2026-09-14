<?php

require_once 'conexao.php';


/* =========================================
   LISTA DE DESPESAS
   ========================================= */

$consultaDespesas =
    $conexao->query(
        "SELECT
            desp_id,
            desp_descricao,
            desp_categoria,
            desp_valor,
            desp_data,
            desp_status,
            desp_observacao
         FROM DESPESA
         ORDER BY desp_data DESC, desp_id DESC"
    );

$despesas =
    $consultaDespesas->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================
   TOTAL PAGO NO MÊS
   ========================================= */

$sqlPagoMes = "
    SELECT COALESCE(SUM(desp_valor), 0)
    FROM DESPESA
    WHERE desp_status = 'pago'
      AND YEAR(desp_data) = YEAR(CURDATE())
      AND MONTH(desp_data) = MONTH(CURDATE())
";

$totalPagoMes =
    (float) $conexao
        ->query($sqlPagoMes)
        ->fetchColumn();


/* =========================================
   TOTAL PENDENTE NO MÊS
   ========================================= */

$sqlPendenteMes = "
    SELECT COALESCE(SUM(desp_valor), 0)
    FROM DESPESA
    WHERE desp_status = 'pendente'
      AND YEAR(desp_data) = YEAR(CURDATE())
      AND MONTH(desp_data) = MONTH(CURDATE())
";

$totalPendenteMes =
    (float) $conexao
        ->query($sqlPendenteMes)
        ->fetchColumn();


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
        href="style.css"
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

    <!-- RESUMO -->

    <div class="grid-resumo">

        <div class="card-resumo">

            <div class="icone-card">
                💸
            </div>

            <div class="info-card">

                <span>
                    Despesas pagas no mês
                </span>

                <strong>
                    R$ <?= number_format(
                        $totalPagoMes,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Valores já pagos neste mês
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
                        $totalPendenteMes,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Valores pendentes neste mês
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
                    Total de despesas cadastradas
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