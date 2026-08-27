<?php

require_once 'conexao.php';

$consulta = $conexao->query(
    'SELECT barb_id, barb_nome, barb_telefone
     FROM BARBEIRO
     ORDER BY barb_nome'
);

$barbeiros = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Barbeiros | GestHairStyle</title>

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
            max-width: 800px;
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

        .botao {
            display: inline-block;
            margin: 10px 8px 20px 0;
            padding: 12px 18px;
            border-radius: 5px;
            background-color: #17202a;
            color: white;
            text-decoration: none;
        }

        .botao:hover {
            background-color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            color: white;
            background-color: #17202a;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .mensagem-sucesso {
            margin: 15px 0;
            padding: 12px;
            border-radius: 5px;
            color: #155724;
            background-color: #d4edda;
        }

        .form-excluir {
            display: inline;
        }

        .botao-excluir {
            margin-left: 10px;
            padding: 0;
            border: none;
            background: none;
            color: #c0392b;
            font: inherit;
            text-decoration: underline;
            cursor: pointer;
        }

        .mensagem-erro {
            margin: 15px 0;
            padding: 12px;
            border-radius: 5px;
            color: #721c24;
            background-color: #f8d7da;
        }

    </style>
</head>

<body>

<div class="container">
    <h1>Barbeiros</h1>

    <?php if (($_GET['status'] ?? '') === 'criado'): ?>
        <div class="mensagem-sucesso">
            Barbeiro cadastrado com sucesso!
        </div>

    <?php elseif (($_GET['status'] ?? '') === 'atualizado'): ?>
        <div class="mensagem-sucesso">
            Barbeiro atualizado com sucesso!
        </div>

    <?php elseif (($_GET['status'] ?? '') === 'excluido'): ?>
        <div class="mensagem-sucesso">
            Barbeiro excluído com sucesso!
        </div>

    <?php elseif (($_GET['status'] ?? '') === 'barbeiro_em_uso'): ?>
        <div class="mensagem-erro">
            Este barbeiro possui agendamentos e não pode ser excluído.
        </div>

    <?php elseif (isset($_GET['status'])): ?>
        <div class="mensagem-erro">
            Não foi possível excluir o barbeiro.
        </div>
    <?php endif; ?>

    <a class="botao" href="index.php">
        Voltar aos clientes
    </a>

    <a class="botao" href="cadastrar_barbeiro.php">
        Novo barbeiro
    </a>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($barbeiros as $barbeiro): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($barbeiro['barb_nome']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $barbeiro['barb_telefone'] ?? 'Não informado'
                        ) ?>
                    </td>

                    <td>
                        <a href="editar_barbeiro.php?id=<?= (int) $barbeiro['barb_id'] ?>">
                            Editar
                        </a>

                        <form
                            action="excluir_barbeiro.php"
                            method="POST"
                            class="form-excluir"
                            onsubmit="return confirm('Deseja realmente excluir este barbeiro?');"
                        >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $barbeiro['barb_id'] ?>"
                            >

                            <button type="submit" class="botao-excluir">
                                Excluir
                            </button>
                        </form>
                    </td>
                </tr>

            <?php endforeach; ?>

            <?php if (count($barbeiros) === 0): ?>
                <tr>
                    <td colspan="3">
                        Nenhum barbeiro cadastrado.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>