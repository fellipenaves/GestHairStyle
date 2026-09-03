<?php

require_once 'conexao.php';

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $dataNascimento = $_POST['data_nascimento'] ?? '';

    if ($nome === '' || strlen($cpf) !== 11) {
        $mensagem = 'Preencha o nome e informe um CPF com 11 números.';
        $tipoMensagem = 'erro';
    } else {
        try {
            $sql = 'INSERT INTO CLIENTE
                    (cli_nome, cli_cpf, cli_telefone, cli_data_nasc)
                    VALUES
                    (:nome, :cpf, :telefone, :data_nascimento)';

            $comando = $conexao->prepare($sql);

            $comando->execute([
                ':nome' => $nome,
                ':cpf' => $cpf,
                ':telefone' => $telefone !== '' ? $telefone : null,
                ':data_nascimento' => $dataNascimento !== ''
                    ? $dataNascimento
                    : null
            ]);

            $mensagem = 'Cliente cadastrado com sucesso!';
            $tipoMensagem = 'sucesso';

        } catch (PDOException $erro) {
            if ($erro->getCode() === '23000') {
                $mensagem = 'Este CPF já está cadastrado.';
            } else {
                $mensagem = 'Não foi possível cadastrar o cliente.';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar cliente | GestHairStyle</title>

    <link rel="stylesheet" href="style.css">
</head>

<?php
$paginaAtual = 'clientes';
require 'menu.php';
?>

<div class="container container-formulario">
    <div class="cabecalho-formulario">

    <div>

        <span class="subtitulo-dashboard">
            CLIENTES
        </span>

        <h1>Novo cliente</h1>

        <p>
            Cadastre as informações básicas do cliente
            para utilizá-lo nos agendamentos.
        </p>

    </div>

</div>


<?php if ($mensagem !== ''): ?>

    <div class="mensagem <?= $tipoMensagem ?>">

        <?= htmlspecialchars($mensagem) ?>

    </div>

<?php endif; ?>


<div class="card-formulario">

    <form method="POST">

        <div class="grid-formulario">

            <div class="campo-formulario campo-formulario-grande">

                <label for="nome">
                    Nome completo
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    placeholder="Digite o nome completo"
                    value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                    required
                >

            </div>


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
                    value="<?= htmlspecialchars($_POST['cpf'] ?? '') ?>"
                    required
                >

            </div>


            <div class="campo-formulario">

                <label for="telefone">
                    Telefone
                </label>

                <input
                    type="text"
                    id="telefone"
                    name="telefone"
                    placeholder="(19) 99999-9999"
                    value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>"
                >

            </div>


            <div class="campo-formulario">

                <label for="data_nascimento">
                    Data de nascimento
                </label>

                <input
                    type="date"
                    id="data_nascimento"
                    name="data_nascimento"
                    value="<?= htmlspecialchars(
                        $_POST['data_nascimento'] ?? ''
                    ) ?>"
                >

            </div>

        </div>


        <div class="acoes-formulario">

            <a
                href="clientes.php"
                class="botao-secundario"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="botao-destaque botao-salvar"
            >
                Cadastrar cliente
            </button>

        </div>

    </form>

</div>

</body>
</html>