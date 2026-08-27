<?php

require_once 'conexao.php';

$consulta = $conexao->query(
    'SELECT
        serv_id,
        serv_nome,
        serv_descricao,
        serv_duracao_min,
        serv_preco
     FROM SERVICO
     ORDER BY serv_nome'
);

$servicos = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Serviços | GestHairStyle</title>

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
            max-width: 1000px;
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

        @media (max-width: 700px) {
            .tabela-container {
                overflow-x: auto;
            }
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
    <h1>Serviços</h1>

    <?php if (($_GET['status'] ?? '') === 'criado'): ?>
        <div class="mensagem-sucesso">
            Serviço cadastrado com sucesso!
        </div>

    <?php elseif (($_GET['status'] ?? '') === 'atualizado'): ?>
        <div class="mensagem-sucesso">
            Serviço atualizado com sucesso!
        </div>

    <?php elseif (($_GET['status'] ?? '') === 'excluido'): ?>
        <div class="mensagem-sucesso">
            Serviço excluído com sucesso!
        </div>

    <?php elseif (($_GET['status'] ?? '') === 'servico_em_uso'): ?>
        <div class="mensagem-erro">
            Este serviço está ligado a um agendamento e não pode ser excluído.
        </div>

    <?php elseif (isset($_GET['status'])): ?>
        <div class="mensagem-erro">
            Não foi possível excluir o serviço.
        </div>
    <?php endif; ?>

    <a class="botao" href="index.php">
        Voltar aos clientes
    </a>

    <a class="botao" href="cadastrar_servico.php">
        Novo serviço
    </a>

    <div class="tabela-container">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Duração</th>
                    <th>Preço</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($servicos as $servico): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($servico['serv_nome']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $servico['serv_descricao'] ?? ''
                            ) ?>
                        </td>

                        <td>
                            <?= (int) $servico['serv_duracao_min'] ?> minutos
                        </td>

                        <td>
                            R$ <?= number_format(
                                $servico['serv_preco'],
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            <a href="editar_servico.php?id=<?= (int) $servico['serv_id'] ?>">
                                Editar
                            </a>

                            <form
                                action="excluir_servico.php"
                                method="POST"
                                class="form-excluir"
                                onsubmit="return confirm('Deseja realmente excluir este serviço?');"
                            >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $servico['serv_id'] ?>"
                                >

                                <button type="submit" class="botao-excluir">
                                    Excluir
                                </button>
                            </form>
                        </td>

                    </tr>
                <?php endforeach; ?>

                <?php if (count($servicos) === 0): ?>
                    <tr>
                        <td colspan="5">
                            Nenhum serviço cadastrado.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>