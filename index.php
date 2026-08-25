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
    </style>
</head>

<body>

<div class="container">
    <h1>GestHairStyle</h1>

    <p>Conexão com o banco realizada com sucesso!</p>

    <h2>Clientes cadastrados</h2>

    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Telefone</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><?= htmlspecialchars($cliente['cli_id']) ?></td>
                    <td><?= htmlspecialchars($cliente['cli_nome']) ?></td>
                    <td><?= htmlspecialchars($cliente['cli_telefone'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>