<?php

require_once 'conexao.php';

$sql = 'SELECT cli_id, cli_nome, cli_telefone FROM CLIENTE ORDER BY cli_nome';
$consulta = $conexao->query($sql);
$clientes = $consulta->fetchAll(PDO::FETCH_ASSOC);

$totalClientes = $conexao->query('SELECT COUNT(*) FROM CLIENTE')->fetchColumn();
$totalAgendamentos = $conexao->query('SELECT COUNT(*) FROM AGENDAMENTO')->fetchColumn();
$totalBarbeiros = $conexao->query('SELECT COUNT(*) FROM BARBEIRO')->fetchColumn();
$totalServicos = $conexao->query('SELECT COUNT(*) FROM SERVICO')->fetchColumn();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestHairStyle</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <div class="topo-dashboard">
        <div class="topo-texto">
            <span class="tag-painel">Painel de Gestão</span>
            <h1>GestHairStyle</h1>
            <p>
                Gerencie clientes, barbeiros, serviços e agendamentos
                de forma simples, rápida e organizada.
            </p>
        </div>

        <div class="acoes-rapidas">
            <a class="botao-cadastro" href="cadastrar_cliente.php">+ Novo cliente</a>
            <a class="botao-cadastro" href="agendamentos.php">Agendamentos</a>
            <a class="botao-cadastro" href="servicos.php">Serviços</a>
            <a class="botao-cadastro" href="barbeiros.php">Barbeiros</a>
        </div>
    </div>

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

    <div class="grid-resumo">
        <div class="card-resumo">
            <div class="icone-card">👥</div>
            <div class="info-card">
                <span>Clientes</span>
                <strong><?= (int) $totalClientes ?></strong>
                <small>Total de clientes cadastrados</small>
            </div>
        </div>

        <div class="card-resumo">
            <div class="icone-card">📅</div>
            <div class="info-card">
                <span>Agendamentos</span>
                <strong><?= (int) $totalAgendamentos ?></strong>
                <small>Total de agendamentos registrados</small>
            </div>
        </div>

        <div class="card-resumo">
            <div class="icone-card">✂️</div>
            <div class="info-card">
                <span>Serviços</span>
                <strong><?= (int) $totalServicos ?></strong>
                <small>Serviços disponíveis no sistema</small>
            </div>
        </div>

        <div class="card-resumo">
            <div class="icone-card">🧔</div>
            <div class="info-card">
                <span>Barbeiros</span>
                <strong><?= (int) $totalBarbeiros ?></strong>
                <small>Profissionais cadastrados</small>
            </div>
        </div>
    </div>

    <div class="bloco-tabela">
        <div class="cabecalho-bloco">
            <div>
                <h2>Clientes cadastrados</h2>
                <p>Visualize, edite ou exclua os clientes cadastrados no sistema.</p>
            </div>
        </div>

        <div class="tabela-responsiva">
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
    </div>

</div>

</body>
</html>