<?php

require_once 'conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    $duracao = filter_input(
        INPUT_POST,
        'duracao',
        FILTER_VALIDATE_INT
    );

    $precoInformado = str_replace(
        ',',
        '.',
        trim($_POST['preco'] ?? '')
    );

    $preco = filter_var(
        $precoInformado,
        FILTER_VALIDATE_FLOAT
    );


    if (
        $nome === '' ||
        !$duracao ||
        $duracao <= 0 ||
        $preco === false ||
        $preco < 0
    ) {

        $mensagem =
            'Preencha corretamente todos os campos obrigatórios.';

    } else {

        try {

            $comando = $conexao->prepare(
                'INSERT INTO SERVICO (
                    serv_nome,
                    serv_descricao,
                    serv_duracao_min,
                    serv_preco
                )
                VALUES (
                    :nome,
                    :descricao,
                    :duracao,
                    :preco
                )'
            );

            $comando->execute([
                ':nome' => $nome,

                ':descricao' =>
                    $descricao !== ''
                        ? $descricao
                        : null,

                ':duracao' => $duracao,

                ':preco' => $preco
            ]);

            header(
                'Location: servicos.php?status=criado'
            );

            exit;

        } catch (PDOException $erro) {

            $mensagem =
                'Não foi possível cadastrar o serviço.';
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
        Novo serviço | GestHairStyle
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>

<?php

$paginaAtual = 'servicos';

require 'menu.php';

?>


<div class="container container-formulario">


    <!-- CABEÇALHO -->

    <div class="cabecalho-formulario">

        <div>

            <span class="subtitulo-dashboard">
                SERVIÇOS
            </span>

            <h1>Novo serviço</h1>

            <p>
                Cadastre um serviço oferecido pela barbearia,
                informando duração e valor.
            </p>

        </div>

    </div>


    <!-- MENSAGEM DE ERRO -->

    <?php if ($mensagem !== ''): ?>

        <div class="mensagem erro">

            <?= htmlspecialchars($mensagem) ?>

        </div>

    <?php endif; ?>


    <!-- FORMULÁRIO -->

    <div class="card-formulario">

        <form method="POST">


            <div class="grid-formulario">


                <!-- NOME -->

                <div
                    class="
                        campo-formulario
                        campo-formulario-grande
                    "
                >

                    <label for="nome">
                        Nome do serviço
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Ex.: Corte masculino"
                        value="<?= htmlspecialchars(
                            $_POST['nome'] ?? ''
                        ) ?>"
                        required
                    >

                </div>


                <!-- DESCRIÇÃO -->

                <div
                    class="
                        campo-formulario
                        campo-formulario-grande
                    "
                >

                    <label for="descricao">
                        Descrição
                    </label>

                    <textarea
                        id="descricao"
                        name="descricao"
                        class="textarea-servico"
                        placeholder="Descreva brevemente o serviço..."
                    ><?= htmlspecialchars(
                        $_POST['descricao'] ?? ''
                    ) ?></textarea>

                    <small class="ajuda-campo">
                        Campo opcional.
                    </small>

                </div>


                <!-- DURAÇÃO -->

                <div class="campo-formulario">

                    <label for="duracao">
                        Duração
                    </label>

                    <input
                        type="number"
                        id="duracao"
                        name="duracao"
                        min="1"
                        placeholder="Ex.: 45"
                        value="<?= htmlspecialchars(
                            $_POST['duracao'] ?? ''
                        ) ?>"
                        required
                    >

                    <small class="ajuda-campo">
                        Informe o tempo em minutos.
                    </small>

                </div>


                <!-- PREÇO -->

                <div class="campo-formulario">

                    <label for="preco">
                        Preço
                    </label>

                    <input
                        type="number"
                        id="preco"
                        name="preco"
                        min="0"
                        step="0.01"
                        placeholder="0,00"
                        value="<?= htmlspecialchars(
                            $_POST['preco'] ?? ''
                        ) ?>"
                        required
                    >

                    <small class="ajuda-campo">
                        Valor cobrado pelo serviço.
                    </small>

                </div>


            </div>


            <!-- AÇÕES -->

            <div class="acoes-formulario">

                <a
                    href="servicos.php"
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
                    Cadastrar serviço
                </button>

            </div>


        </form>

    </div>


</div>

</body>

</html>