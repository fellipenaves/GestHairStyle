<?php

require_once 'conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Serviço inválido.');
}

$consulta = $conexao->prepare(
    'SELECT * FROM SERVICO WHERE serv_id = :id'
);

$consulta->execute([':id' => $id]);
$servico = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    die('Serviço não encontrado.');
}

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
        $mensagem = 'Preencha corretamente todos os campos obrigatórios.';
    } else {
        try {
            $comando = $conexao->prepare(
                'UPDATE SERVICO
                 SET serv_nome = :nome,
                     serv_descricao = :descricao,
                     serv_duracao_min = :duracao,
                     serv_preco = :preco
                 WHERE serv_id = :id'
            );

            $comando->execute([
                ':nome' => $nome,
                ':descricao' => $descricao !== '' ? $descricao : null,
                ':duracao' => $duracao,
                ':preco' => $preco,
                ':id' => $id
            ]);

            header('Location: servicos.php?status=atualizado');
            exit;

        } catch (PDOException $erro) {
            $mensagem = 'Não foi possível atualizar o serviço.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar serviço | GestHairStyle</title>

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

        input,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #bbb;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
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
    <h1>Editar serviço</h1>

    <?php if ($mensagem !== ''): ?>
        <div class="erro">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="nome">Nome do serviço</label>
        <input
            type="text"
            id="nome"
            name="nome"
            value="<?= htmlspecialchars($servico['serv_nome']) ?>"
            required
        >

        <label for="descricao">Descrição</label>
        <textarea
            id="descricao"
            name="descricao"
        ><?= htmlspecialchars($servico['serv_descricao'] ?? '') ?></textarea>

        <label for="duracao">Duração em minutos</label>
        <input
            type="number"
            id="duracao"
            name="duracao"
            min="1"
            value="<?= (int) $servico['serv_duracao_min'] ?>"
            required
        >

        <label for="preco">Preço</label>
        <input
            type="number"
            id="preco"
            name="preco"
            min="0"
            step="0.01"
            value="<?= htmlspecialchars($servico['serv_preco']) ?>"
            required
        >

        <button type="submit">Salvar alterações</button>
    </form>

    <a class="voltar" href="servicos.php">
        ← Voltar aos serviços
    </a>
</div>

</body>
</html>