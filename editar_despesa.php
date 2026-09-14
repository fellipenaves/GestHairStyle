<?php

require_once 'conexao.php';


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die('Despesa inválida.');
}


/* =========================================
   BUSCA A DESPESA
   ========================================= */

$consulta =
    $conexao->prepare(
        'SELECT *
         FROM DESPESA
         WHERE desp_id = :id'
    );

$consulta->execute([
    ':id' => $id
]);

$despesa =
    $consulta->fetch(
        PDO::FETCH_ASSOC
    );

if (!$despesa) {
    die('Despesa não encontrada.');
}


$mensagem = '';


/* =========================================
   ATUALIZAÇÃO
   ========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $descricao = trim(
        $_POST['descricao'] ?? ''
    );

    $categoria = trim(
        $_POST['categoria'] ?? ''
    );

    $valorInformado = str_replace(
        ',',
        '.',
        trim($_POST['valor'] ?? '')
    );

    $valor = filter_var(
        $valorInformado,
        FILTER_VALIDATE_FLOAT
    );

    $data = trim(
        $_POST['data'] ?? ''
    );

    $status = trim(
        $_POST['status'] ?? ''
    );

    $observacao = trim(
        $_POST['observacao'] ?? ''
    );


    if (
        $descricao === ''
        ||
        $categoria === ''
        ||
        $valor === false
        ||
        $valor <= 0
        ||
        $data === ''
        ||
        !in_array(
            $status,
            ['pago', 'pendente'],
            true
        )
    ) {

        $mensagem =
            'Preencha os campos corretamente.';

    } else {

        try {

            $atualizar =
                $conexao->prepare(
                    'UPDATE DESPESA

                     SET
                        desp_descricao = :descricao,
                        desp_categoria = :categoria,
                        desp_valor = :valor,
                        desp_data = :data,
                        desp_status = :status,
                        desp_observacao = :observacao

                     WHERE desp_id = :id'
                );


            $atualizar->execute([

                ':descricao' =>
                    $descricao,

                ':categoria' =>
                    $categoria,

                ':valor' =>
                    $valor,

                ':data' =>
                    $data,

                ':status' =>
                    $status,

                ':observacao' =>
                    $observacao !== ''
                        ? $observacao
                        : null,

                ':id' =>
                    $id

            ]);


            header(
                'Location: despesas.php?status=editado'
            );

            exit;

        } catch (Throwable $erro) {

            $mensagem =
                'Não foi possível atualizar a despesa.';
        }
    }
}


/* =========================================
   VALORES DO FORMULÁRIO
   ========================================= */

$descricaoSelecionada =
    $_POST['descricao']
    ?? $despesa['desp_descricao'];

$categoriaSelecionada =
    $_POST['categoria']
    ?? $despesa['desp_categoria'];

$valorSelecionado =
    $_POST['valor']
    ?? $despesa['desp_valor'];

$dataSelecionada =
    $_POST['data']
    ?? $despesa['desp_data'];

$statusSelecionado =
    $_POST['status']
    ?? $despesa['desp_status'];

$observacaoSelecionada =
    $_POST['observacao']
    ?? $despesa['desp_observacao']
    ?? '';

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
        Editar despesa | GestHairStyle
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


<div class="container container-formulario">

    <div class="cabecalho-formulario">

        <div>

            <span class="subtitulo-dashboard">
                FINANCEIRO
            </span>

            <h1>Editar despesa</h1>

            <p>
                Atualize os dados da despesa.
            </p>

        </div>

    </div>


    <?php if ($mensagem !== ''): ?>

        <div class="mensagem erro">

            <?= htmlspecialchars($mensagem) ?>

        </div>

    <?php endif; ?>


    <div class="card-formulario">

        <form method="POST">

            <div class="grid-formulario">


                <div class="campo-formulario">

                    <label for="descricao">
                        Descrição
                    </label>

                    <input
                        type="text"
                        id="descricao"
                        name="descricao"
                        value="<?= htmlspecialchars(
                            $descricaoSelecionada
                        ) ?>"
                        required
                    >

                </div>


                <div class="campo-formulario">

                    <label for="categoria">
                        Categoria
                    </label>

                    <select
                        id="categoria"
                        name="categoria"
                        required
                    >

                        <?php

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

                        ?>

                        <?php foreach ($categorias as $categoria): ?>

                            <option
                                value="<?= htmlspecialchars(
                                    $categoria
                                ) ?>"
                                <?= (
                                    $categoriaSelecionada
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

                    <label for="valor">
                        Valor
                    </label>

                    <input
                        type="number"
                        id="valor"
                        name="valor"
                        min="0.01"
                        step="0.01"
                        value="<?= htmlspecialchars(
                            $valorSelecionado
                        ) ?>"
                        required
                    >

                </div>


                <div class="campo-formulario">

                    <label for="data">
                        Data
                    </label>

                    <input
                        type="date"
                        id="data"
                        name="data"
                        value="<?= htmlspecialchars(
                            $dataSelecionada
                        ) ?>"
                        required
                    >

                </div>


                <div class="campo-formulario">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        required
                    >

                        <option
                            value="pago"
                            <?= (
                                $statusSelecionado
                                === 'pago'
                            ) ? 'selected' : '' ?>
                        >
                            Pago
                        </option>

                        <option
                            value="pendente"
                            <?= (
                                $statusSelecionado
                                === 'pendente'
                            ) ? 'selected' : '' ?>
                        >
                            Pendente
                        </option>

                    </select>

                </div>


                <div
                    class="
                        campo-formulario
                        campo-formulario-grande
                    "
                >

                    <label for="observacao">
                        Observação
                    </label>

                    <textarea
                        id="observacao"
                        name="observacao"
                        rows="4"
                    ><?= htmlspecialchars(
                        $observacaoSelecionada
                    ) ?></textarea>

                </div>

            </div>


            <div class="acoes-formulario">

                <a
                    href="despesas.php"
                    class="botao-secundario"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="
                        botao-destaque
                        botao-salvar
                    "
                >
                    Salvar alterações
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>