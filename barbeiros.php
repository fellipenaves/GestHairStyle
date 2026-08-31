<?php

require_once 'conexao.php';

$consulta = $conexao->query(
    'SELECT barb_id, barb_nome, barb_telefone
     FROM BARBEIRO
     ORDER BY barb_nome'
);

$barbeiros = $consulta->fetchAll(PDO::FETCH_ASSOC);

$totalBarbeiros = count($barbeiros);

$barbeirosComTelefone = 0;

foreach ($barbeiros as $barbeiro) {
    if (!empty(trim($barbeiro['barb_telefone'] ?? ''))) {
        $barbeirosComTelefone++;
    }
}

$barbeirosComAgendamentos = $conexao
    ->query(
        'SELECT COUNT(DISTINCT barb_id)
         FROM AGENDAMENTO'
    )
    ->fetchColumn();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Barbeiros | GestHairStyle</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php
$paginaAtual = 'barbeiros';
require 'menu.php';
?>

<div class="container">
    <div class="cabecalho-pagina">

    <div>
        <span class="subtitulo-dashboard">
            EQUIPE
        </span>

        <h1>Barbeiros</h1>

        <p>
            Gerencie os profissionais responsáveis
            pelos atendimentos da barbearia.
        </p>
    </div>

    <a
        href="cadastrar_barbeiro.php"
        class="botao-destaque"
    >
        + Novo barbeiro
    </a>

</div>


<div class="grid-resumo grid-resumo-barbeiros">

    <div class="card-resumo">

        <div class="icone-card">
            🧔
        </div>

        <div class="info-card">

            <span>
                Profissionais
            </span>

            <strong>
                <?= $totalBarbeiros ?>
            </strong>

            <small>
                Total de barbeiros cadastrados
            </small>

        </div>

    </div>


    <div class="card-resumo">

        <div class="icone-card">
            📱
        </div>

        <div class="info-card">

            <span>
                Com telefone
            </span>

            <strong>
                <?= $barbeirosComTelefone ?>
            </strong>

            <small>
                Profissionais com contato cadastrado
            </small>

        </div>

    </div>


    <div class="card-resumo">

        <div class="icone-card">
            📅
        </div>

        <div class="info-card">

            <span>
                Com agendamentos
            </span>

            <strong>
                <?= (int) $barbeirosComAgendamentos ?>
            </strong>

            <small>
                Profissionais vinculados a atendimentos
            </small>

        </div>

    </div>

</div>

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

    <div class="tabela-padrao">
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
</div>

</body>
</html>