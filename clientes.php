<?php

require_once 'conexao.php';

$busca = trim($_GET['busca'] ?? '');


/* =========================================
   INDICADORES
   ========================================= */

$totalClientes = $conexao
    ->query('SELECT COUNT(*) FROM CLIENTE')
    ->fetchColumn();


$clientesComTelefone = $conexao
    ->query("
        SELECT COUNT(*)
        FROM CLIENTE
        WHERE cli_telefone IS NOT NULL
          AND TRIM(cli_telefone) <> ''
    ")
    ->fetchColumn();


$clientesComAgendamentos = $conexao
    ->query("
        SELECT COUNT(DISTINCT cli_id)
        FROM AGENDAMENTO
    ")
    ->fetchColumn();


/* =========================================
   LISTAGEM / BUSCA
   ========================================= */

$sql = "
    SELECT
        cli_id,
        cli_nome,
        cli_telefone
    FROM CLIENTE
";

$parametros = [];

if ($busca !== '') {

    $sql .= "
        WHERE cli_nome LIKE :busca
           OR cli_telefone LIKE :busca
    ";

    $parametros[':busca'] = '%' . $busca . '%';
}

$sql .= " ORDER BY cli_nome";

$consulta = $conexao->prepare($sql);
$consulta->execute($parametros);

$clientes = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Clientes | GestHairStyle</title>

    <link rel="stylesheet" href="style.css">

</head>


<body>

<?php

$paginaAtual = 'clientes';

require 'menu.php';

?>


<div class="container">


    <!-- CABEÇALHO -->

    <div class="cabecalho-pagina">

        <div>

            <span class="subtitulo-dashboard">
                CLIENTES
            </span>

            <h1>Clientes</h1>

            <p>
                Consulte, cadastre e gerencie os clientes
                da barbearia.
            </p>

        </div>


        <a
            href="cadastrar_cliente.php"
            class="botao-destaque"
        >
            + Novo cliente
        </a>

    </div>


    <!-- MENSAGENS -->

    <?php if (($_GET['status'] ?? '') === 'excluido'): ?>

        <div class="mensagem-sucesso">
            Cliente excluído com sucesso!
        </div>

    <?php elseif (($_GET['status'] ?? '') === 'cliente_em_uso'): ?>

        <div class="mensagem-erro">
            Este cliente possui agendamentos
            e não pode ser excluído.
        </div>

    <?php elseif (isset($_GET['status'])): ?>

        <div class="mensagem-erro">
            Não foi possível excluir o cliente.
        </div>

    <?php endif; ?>


    <!-- INDICADORES -->

    <div class="grid-resumo grid-resumo-clientes">


        <div class="card-resumo">

            <div class="icone-card">
                👥
            </div>

            <div class="info-card">

                <span>
                    Clientes cadastrados
                </span>

                <strong>
                    <?= (int) $totalClientes ?>
                </strong>

                <small>
                    Total de clientes no sistema
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
                    <?= (int) $clientesComTelefone ?>
                </strong>

                <small>
                    Clientes com contato cadastrado
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
                    <?= (int) $clientesComAgendamentos ?>
                </strong>

                <small>
                    Clientes que já possuem atendimento
                </small>

            </div>

        </div>


    </div>


    <!-- BUSCA -->

    <form
        method="GET"
        class="barra-busca-clientes"
    >

        <div class="campo-busca-clientes">

            <span>🔎</span>

            <input
                type="text"
                name="busca"
                placeholder="Buscar por nome ou telefone..."
                value="<?= htmlspecialchars($busca) ?>"
            >

        </div>


        <button
            type="submit"
            class="botao-filtrar"
        >
            Buscar
        </button>


        <?php if ($busca !== ''): ?>

            <a
                href="clientes.php"
                class="limpar-filtros"
            >
                Limpar busca
            </a>

        <?php endif; ?>

    </form>


    <!-- TABELA -->

    <div class="tabela-padrao">

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

                        <td>
                            <?= (int) $cliente['cli_id'] ?>
                        </td>


                        <td>
                            <?= htmlspecialchars(
                                $cliente['cli_nome']
                            ) ?>
                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $cliente['cli_telefone']
                                ?? 'Não informado'
                            ) ?>

                        </td>


                        <td>

                            <a
                                href="editar_cliente.php?id=<?= (int)
                                    $cliente['cli_id']
                                ?>"
                            >
                                Editar
                            </a>


                            <form
                                action="excluir_cliente.php"
                                method="POST"
                                class="form-excluir"
                                onsubmit="return confirm(
                                    'Deseja realmente excluir este cliente?'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)
                                        $cliente['cli_id']
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    class="botao-excluir"
                                >
                                    Excluir
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>


                <?php if (count($clientes) === 0): ?>

                    <tr>

                        <td colspan="4">

                            Nenhum cliente encontrado.

                        </td>

                    </tr>

                <?php endif; ?>


            </tbody>

        </table>

    </div>


</div>

</body>

</html>