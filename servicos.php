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

$totalServicos = count($servicos);

$precoMedio = 0;
$duracaoMedia = 0;

if ($totalServicos > 0) {

    $somaPrecos = array_sum(
        array_column($servicos, 'serv_preco')
    );

    $somaDuracoes = array_sum(
        array_column($servicos, 'serv_duracao_min')
    );

    $precoMedio = $somaPrecos / $totalServicos;
    $duracaoMedia = $somaDuracoes / $totalServicos;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Serviços | GestHairStyle</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php
$paginaAtual = 'servicos';
require 'menu.php';
?>

<div class="container">
    <div class="cabecalho-pagina">

    <div>
        <span class="subtitulo-dashboard">
            CATÁLOGO
        </span>

        <h1>Serviços</h1>

        <p>
            Gerencie os serviços oferecidos,
            valores e duração dos atendimentos.
        </p>
    </div>

    <a
        href="cadastrar_servico.php"
        class="botao-destaque"
    >
        + Novo serviço
    </a>

</div>


<div class="grid-resumo grid-resumo-servicos">

    <div class="card-resumo">
        <div class="icone-card">✂️</div>

        <div class="info-card">
            <span>Serviços cadastrados</span>

            <strong>
                <?= $totalServicos ?>
            </strong>

            <small>
                Total disponível no catálogo
            </small>
        </div>
    </div>


    <div class="card-resumo">
        <div class="icone-card">💰</div>

        <div class="info-card">
            <span>Preço médio</span>

            <strong>
                R$ <?= number_format(
                    $precoMedio,
                    2,
                    ',',
                    '.'
                ) ?>
            </strong>

            <small>
                Média dos serviços cadastrados
            </small>
        </div>
    </div>


    <div class="card-resumo">
        <div class="icone-card">⏱️</div>

        <div class="info-card">
            <span>Duração média</span>

            <strong>
                <?= round($duracaoMedia) ?> min
            </strong>

            <small>
                Tempo médio de atendimento
            </small>
        </div>
    </div>

</div>

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