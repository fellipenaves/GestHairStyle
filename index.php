<?php

require_once 'conexao.php';

$sql = 'SELECT cli_id, cli_nome, cli_telefone FROM CLIENTE ORDER BY cli_nome';

$consulta = $conexao->query($sql);
$clientes = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GestHairStyle</title>

    <style>
        body {
            margin: 0;
            padding: 40px;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #222;
        }

        .botao-cadastro {
            display: inline-block;
            margin: 10px 0;
            padding: 12px 18px;
            border-radius: 5px;
            background-color: #17202a;
            color: white;
            text-decoration: none;
        }

        .botao-cadastro:hover {
            background-color: #2c3e50;
        }

        .container {
            max-width: 900px;
            margin: auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12);
        }

        h1 {
            color: #17202a;
        }

        table {
            width: 100%;
            margin-top: 25px;
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

        .aviso {
            margin: 15px 0;
            padding: 12px;
            border-radius: 5px;
        }

        .aviso-sucesso {
            color: #155724;
            background-color: #d4edda;
        }

        .aviso-erro {
            color: #721c24;
            background-color: #f8d7da;
        }

    </style>
</head>

<body>

<div class="container">
    <h1>GestHairStyle</h1>

    <?php if (($_GET['status'] ?? '') === 'excluido'): ?>
    <div class="aviso aviso-sucesso">
        Cliente excluído com sucesso!
    </div>
    <?php elseif (($_GET['status'] ?? '') === 'cliente_em_uso'): ?>
    <div class="aviso aviso-erro">
        Este cliente possui agendamentos e não pode ser excluído.
    </div>
    <?php elseif (isset($_GET['status'])): ?>
    <div class="aviso aviso-erro">
        Não foi possível excluir o cliente.
    </div>
    <?php endif; ?>

    <p>Conexão com o banco realizada com sucesso!</p>

    <a class="botao-cadastro" href="cadastrar_cliente.php">
        Cadastrar novo cliente
    </a>

    <h2>Clientes cadastrados</h2>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Telefone</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?= htmlspecialchars($cliente['cli_id']) ?></td>
                    <td><?= htmlspecialchars($cliente['cli_nome']) ?></td>
                    <td><?= htmlspecialchars($cliente['cli_telefone'] ?? '') ?></td>
                    <td>
                        <a href="editar_cliente.php?id=<?= (int) $cliente['cli_id'] ?>">
                            Editar
                        </a>
                        <form
                            action="excluir_cliente.php"
                            method="POST"
                            class="form-excluir"
                            onsubmit="return confirm('Deseja realmente excluir este cliente?');"
                        >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int) $cliente['cli_id'] ?>"
                            >

                            <button type="submit" class="botao-excluir">
                                Excluir
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>