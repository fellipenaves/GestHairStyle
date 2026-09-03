<?php

require_once 'conexao.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die('Barbeiro inválido.');
}


/* =========================================
   BUSCA O BARBEIRO
   ========================================= */

$consulta = $conexao->prepare(
    'SELECT
        barb_id,
        barb_nome,
        barb_telefone
     FROM BARBEIRO
     WHERE barb_id = :id'
);

$consulta->execute([
    ':id' => $id
]);

$barbeiro = $consulta->fetch(
    PDO::FETCH_ASSOC
);

if (!$barbeiro) {
    die('Barbeiro não encontrado.');
}


$mensagem = '';


/* =========================================
   ATUALIZAÇÃO
   ========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim(
        $_POST['nome'] ?? ''
    );

    $telefone = trim(
        $_POST['telefone'] ?? ''
    );


    if ($nome === '') {

        $mensagem =
            'Informe o nome do barbeiro.';

    } else {

        try {

            $comando = $conexao->prepare(
                'UPDATE BARBEIRO

                 SET
                    barb_nome = :nome,
                    barb_telefone = :telefone

                 WHERE barb_id = :id'
            );

            $comando->execute([

                ':nome' => $nome,

                ':telefone' =>
                    $telefone !== ''
                        ? $telefone
                        : null,

                ':id' => $id

            ]);


            header(
                'Location: barbeiros.php?status=atualizado'
            );

            exit;

        } catch (PDOException $erro) {

            $mensagem =
                'Não foi possível atualizar o barbeiro.';
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
        Editar barbeiro | GestHairStyle
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>

<?php

$paginaAtual = 'barbeiros';

require 'menu.php';

?>


<div class="container container-formulario">


    <!-- CABEÇALHO -->

    <div class="cabecalho-formulario">

        <div>

            <span class="subtitulo-dashboard">
                EQUIPE
            </span>

            <h1>Editar barbeiro</h1>

            <p>
                Atualize as informações
                do profissional cadastrado.
            </p>

        </div>

    </div>


    <!-- MENSAGEM -->

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
                        Nome do barbeiro
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        placeholder="Digite o nome do profissional"
                        value="<?= htmlspecialchars(
                            $_POST['nome']
                            ?? $barbeiro['barb_nome']
                        ) ?>"
                        required
                    >

                </div>


                <!-- TELEFONE -->

                <div class="campo-formulario">

                    <label for="telefone">
                        Telefone
                    </label>

                    <input
                        type="text"
                        id="telefone"
                        name="telefone"
                        maxlength="20"
                        placeholder="(11) 99999-9999"
                        value="<?= htmlspecialchars(
                            $_POST['telefone']
                            ?? $barbeiro['barb_telefone']
                            ?? ''
                        ) ?>"
                    >

                    <small class="ajuda-campo">
                        Campo opcional.
                    </small>

                </div>


            </div>


            <!-- AÇÕES -->

            <div class="acoes-formulario">

                <a
                    href="barbeiros.php"
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