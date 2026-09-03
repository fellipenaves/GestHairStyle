<?php

require_once 'conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Cliente inválido.');
}

$consulta = $conexao->prepare(
    'SELECT * FROM CLIENTE WHERE cli_id = :id'
);

$consulta->execute([':id' => $id]);
$cliente = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die('Cliente não encontrado.');
}

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');

    $cpf = preg_replace(
        '/\D/',
        '',
        $_POST['cpf'] ?? ''
    );

    $telefone = trim(
        $_POST['telefone'] ?? ''
    );

    $dataNascimento =
        $_POST['data_nascimento'] ?? '';

    if (
        $nome === '' ||
        strlen($cpf) !== 11
    ) {

        $mensagem =
            'Preencha o nome e informe um CPF com 11 números.';

        $tipoMensagem = 'erro';

    } else {

        try {

            $sql = '
                UPDATE CLIENTE

                SET
                    cli_nome = :nome,
                    cli_cpf = :cpf,
                    cli_telefone = :telefone,
                    cli_data_nasc = :data_nascimento

                WHERE cli_id = :id
            ';

            $comando = $conexao->prepare($sql);

            $comando->execute([

                ':nome' => $nome,

                ':cpf' => $cpf,

                ':telefone' =>
                    $telefone !== ''
                        ? $telefone
                        : null,

                ':data_nascimento' =>
                    $dataNascimento !== ''
                        ? $dataNascimento
                        : null,

                ':id' => $id

            ]);

            $mensagem =
                'Cliente atualizado com sucesso!';

            $tipoMensagem = 'sucesso';


            /* Recarrega os dados atualizados */

            $consulta->execute([
                ':id' => $id
            ]);

            $cliente =
                $consulta->fetch(
                    PDO::FETCH_ASSOC
                );

        } catch (PDOException $erro) {

            if ($erro->getCode() === '23000') {

                $mensagem =
                    'Este CPF já pertence a outro cliente.';

            } else {

                $mensagem =
                    'Não foi possível atualizar o cliente.';

            }

            $tipoMensagem = 'erro';
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
        Editar cliente | GestHairStyle
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>

<?php

$paginaAtual = 'clientes';

require 'menu.php';

?>


<div class="container container-formulario">


    <!-- CABEÇALHO -->

    <div class="cabecalho-formulario">

        <div>

            <span class="subtitulo-dashboard">
                CLIENTES
            </span>

            <h1>Editar cliente</h1>

            <p>
                Atualize as informações cadastrais
                do cliente.
            </p>

        </div>

    </div>


    <!-- MENSAGEM -->

    <?php if ($mensagem !== ''): ?>

        <div class="mensagem <?= $tipoMensagem ?>">

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
                        Nome completo
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite o nome completo"
                        value="<?= htmlspecialchars(
                            $_POST['nome']
                            ?? $cliente['cli_nome']
                        ) ?>"
                        required
                    >

                </div>


                <!-- CPF -->

                <div class="campo-formulario">

                    <label for="cpf">
                        CPF
                    </label>

                    <input
                        type="text"
                        id="cpf"
                        name="cpf"
                        maxlength="14"
                        placeholder="000.000.000-00"
                        value="<?= htmlspecialchars(
                            $_POST['cpf']
                            ?? $cliente['cli_cpf']
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
                        placeholder="(19) 99999-9999"
                        value="<?= htmlspecialchars(
                            $_POST['telefone']
                            ?? $cliente['cli_telefone']
                            ?? ''
                        ) ?>"
                    >

                </div>


                <!-- DATA DE NASCIMENTO -->

                <div class="campo-formulario">

                    <label for="data_nascimento">
                        Data de nascimento
                    </label>

                    <input
                        type="date"
                        id="data_nascimento"
                        name="data_nascimento"
                        value="<?= htmlspecialchars(
                            $_POST['data_nascimento']
                            ?? $cliente['cli_data_nasc']
                            ?? ''
                        ) ?>"
                    >

                </div>


            </div>


            <!-- AÇÕES -->

            <div class="acoes-formulario">

                <a
                    href="clientes.php"
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