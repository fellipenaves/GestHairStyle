<?php

require_once 'conexao.php';

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    die('Serviço inválido.');
}


/* =========================================
   BUSCA O SERVIÇO
   ========================================= */

$consulta = $conexao->prepare(
    'SELECT *
     FROM SERVICO
     WHERE serv_id = :id'
);

$consulta->execute([
    ':id' => $id
]);

$servico = $consulta->fetch(
    PDO::FETCH_ASSOC
);

if (!$servico) {
    die('Serviço não encontrado.');
}


$mensagem = '';


/* =========================================
   ATUALIZAÇÃO
   ========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim(
        $_POST['nome'] ?? ''
    );

    $descricao = trim(
        $_POST['descricao'] ?? ''
    );

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
                'UPDATE SERVICO

                 SET
                    serv_nome = :nome,
                    serv_descricao = :descricao,
                    serv_duracao_min = :duracao,
                    serv_preco = :preco

                 WHERE serv_id = :id'
            );

            $comando->execute([

                ':nome' => $nome,

                ':descricao' =>
                    $descricao !== ''
                        ? $descricao
                        : null,

                ':duracao' => $duracao,

                ':preco' => $preco,

                ':id' => $id

            ]);


            header(
                'Location: servicos.php?status=atualizado'
            );

            exit;

        } catch (PDOException $erro) {

            $mensagem =
                'Não foi possível atualizar o serviço.';
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
        Editar serviço | GestHairStyle
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

            <h1>Editar serviço</h1>

            <p>
                Atualize as informações,
                duração ou valor deste serviço.
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
                        Nome do serviço
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Ex.: Corte masculino"
                        value="<?= htmlspecialchars(
                            $_POST['nome']
                            ?? $servico['serv_nome']
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
                        $_POST['descricao']
                        ?? $servico['serv_descricao']
                        ?? ''
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
                            $_POST['duracao']
                            ?? $servico['serv_duracao_min']
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
                            $_POST['preco']
                            ?? $servico['serv_preco']
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
                    Salvar alterações
                </button>

            </div>


        </form>

    </div>


</div>

</body>

</html>