<?php

require_once 'conexao.php';

$mensagem = '';


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

            $inserir =
                $conexao->prepare(
                    'INSERT INTO DESPESA (

                        desp_descricao,
                        desp_categoria,
                        desp_valor,
                        desp_data,
                        desp_status,
                        desp_observacao

                    )

                    VALUES (

                        :descricao,
                        :categoria,
                        :valor,
                        :data,
                        :status,
                        :observacao

                    )'
                );


            $inserir->execute([

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
                        : null

            ]);


            header(
                'Location: despesas.php?status=criado'
            );

            exit;


        } catch (Throwable $erro) {

            $mensagem =
                'Não foi possível cadastrar a despesa.';
        }
    }
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
        Nova despesa | GestHairStyle
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

            <h1>Nova despesa</h1>

            <p>
                Cadastre um gasto geral da barbearia.
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
                            $_POST['descricao'] ?? ''
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

                        <option value="">
                            Selecione
                        </option>

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
                                    ($_POST['categoria'] ?? '')
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
                            $_POST['valor'] ?? ''
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
                            $_POST['data']
                            ?? date('Y-m-d')
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
                                ($_POST['status'] ?? 'pago')
                                === 'pago'
                            ) ? 'selected' : '' ?>
                        >
                            Pago
                        </option>

                        <option
                            value="pendente"
                            <?= (
                                ($_POST['status'] ?? '')
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
                        $_POST['observacao'] ?? ''
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
                    Salvar despesa
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>