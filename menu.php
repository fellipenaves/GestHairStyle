<?php

$paginaAtual = $paginaAtual ?? '';

?>

<nav class="menu-principal">

    <div class="menu-marca">
        <div class="menu-logo">✂</div>

        <div>
            <strong>GestHairStyle</strong>
            <span>Gestão de Barbearia</span>
        </div>
    </div>


    <div class="menu-links">

        <a
            href="index.php"
            class="<?= $paginaAtual === 'dashboard' ? 'ativo' : '' ?>"
        >
            <span class="menu-icone">⌂</span>
            Dashboard
        </a>


        <a
            href="clientes.php"
            class="<?= $paginaAtual === 'clientes' ? 'ativo' : '' ?>"
        >
            <span class="menu-icone">👥</span>
            Clientes
        </a>


        <a
            href="agendamentos.php"
            class="<?= $paginaAtual === 'agendamentos' ? 'ativo' : '' ?>"
        >
            <span class="menu-icone">📅</span>
            Agendamentos
        </a>


        <a
            href="servicos.php"
            class="<?= $paginaAtual === 'servicos' ? 'ativo' : '' ?>"
        >
            <span class="menu-icone">✂️</span>
            Serviços
        </a>


        <a
            href="barbeiros.php"
            class="<?= $paginaAtual === 'barbeiros' ? 'ativo' : '' ?>"
        >
            <span class="menu-icone">🧔</span>
            Barbeiros
        </a>

    </div>

</nav>