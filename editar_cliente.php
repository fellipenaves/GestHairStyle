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
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $dataNascimento = $_POST['data_nascimento'] ?? '';

    if ($nome === '' || strlen($cpf) !== 11) {
        $mensagem = 'Preencha o nome e informe um CPF com 11 números.';
        $tipoMensagem = 'erro';
    } else {
        try {
            $sql = 'UPDATE CLIENTE
                    SET cli_nome = :nome,
                        cli_cpf = :cpf,
                        cli_telefone = :telefone,
                        cli_data_nasc = :data_nascimento
                    WHERE cli_id = :id';

            $comando = $conexao->prepare($sql);

            $comando->execute([
                ':nome' => $nome,
                ':cpf' => $cpf,
                ':telefone' => $telefone !== '' ? $telefone : null,
                ':data_nascimento' => $dataNascimento !== ''
                    ? $dataNascimento
                    : null,
                ':id' => $id
            ]);

            $mensagem = 'Cliente atualizado com sucesso!';
            $tipoMensagem = 'sucesso';

            $consulta->execute([':id' => $id]);
            $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $erro) {
            if ($erro->getCode() === '23000') {
                $mensagem = 'Este CPF já pertence a outro cliente.';
            } else {
                $mensagem = 'Não foi possível atualizar o cliente.';
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

    <title>Editar cliente | GestHairStyle</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #222;
        }

        .container {
            max-width: 600px;
            margin: auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12);
        }

        h1 {
            margin-top: 0;
            color: #17202a;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #bbb;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: #17202a;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #2c3e50;
        }

        .mensagem {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
        }

        .sucesso {
            color: #155724;
            background-color: #d4edda;
        }

        .erro {
            color: #721c24;
            background-color: #f8d7da;
        }

        .voltar {
            display: inline-block;
            margin-top: 20px;
            color: #17202a;
        }
    </style>
</head>

<body>

<div class="container">
    <h1>Editar cliente</h1>

    <?php if ($mensagem !== ''): ?>
        <div class="mensagem <?= $tipoMensagem ?>">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="nome">Nome completo</label>
        <input
            type="text"
            id="nome"
            name="nome"
            value="<?= htmlspecialchars($cliente['cli_nome']) ?>"
            required
        >

        <label for="cpf">CPF</label>
        <input
            type="text"
            id="cpf"
            name="cpf"
            maxlength="14"
            value="<?= htmlspecialchars($cliente['cli_cpf']) ?>"
            required
        >

        <label for="telefone">Telefone</label>
        <input
            type="text"
            id="telefone"
            name="telefone"
            value="<?= htmlspecialchars($cliente['cli_telefone'] ?? '') ?>"
        >

        <label for="data_nascimento">Data de nascimento</label>
        <input
            type="date"
            id="data_nascimento"
            name="data_nascimento"
            value="<?= htmlspecialchars($cliente['cli_data_nasc'] ?? '') ?>"
        >

        <button type="submit">Salvar alterações</button>
    </form>

    <a class="voltar" href="index.php">← Voltar para a lista</a>
</div>

</body>
</html>