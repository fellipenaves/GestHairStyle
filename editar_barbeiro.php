<?php

require_once 'conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Barbeiro inválido.');
}

$consulta = $conexao->prepare(
    'SELECT barb_id, barb_nome, barb_telefone
     FROM BARBEIRO
     WHERE barb_id = :id'
);

$consulta->execute([':id' => $id]);
$barbeiro = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$barbeiro) {
    die('Barbeiro não encontrado.');
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if ($nome === '') {
        $mensagem = 'Informe o nome do barbeiro.';
    } else {
        try {
            $comando = $conexao->prepare(
                'UPDATE BARBEIRO
                 SET barb_nome = :nome,
                     barb_telefone = :telefone
                 WHERE barb_id = :id'
            );

            $comando->execute([
                ':nome' => $nome,
                ':telefone' => $telefone !== '' ? $telefone : null,
                ':id' => $id
            ]);

            header('Location: barbeiros.php?status=atualizado');
            exit;

        } catch (PDOException $erro) {
            $mensagem = 'Não foi possível atualizar o barbeiro.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar barbeiro | GestHairStyle</title>

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

        .erro {
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 5px;
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
    <h1>Editar barbeiro</h1>

    <?php if ($mensagem !== ''): ?>
        <div class="erro">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="nome">Nome do barbeiro</label>
        <input
            type="text"
            id="nome"
            name="nome"
            maxlength="100"
            value="<?= htmlspecialchars($barbeiro['barb_nome']) ?>"
            required
        >

        <label for="telefone">Telefone</label>
        <input
            type="text"
            id="telefone"
            name="telefone"
            maxlength="20"
            value="<?= htmlspecialchars($barbeiro['barb_telefone'] ?? '') ?>"
        >

        <button type="submit">
            Salvar alterações
        </button>
    </form>

    <a class="voltar" href="barbeiros.php">
        ← Voltar aos barbeiros
    </a>
</div>

</body>
</html>