<?php

$config = require __DIR__ . '/config.php';

try {
    $conexao = new PDO(
        "mysql:host={$config['host']};port={$config['porta']};dbname={$config['banco']};charset=utf8mb4",
        $config['usuario'],
        $config['senha']
    );

    $conexao->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $erro) {
    die('Não foi possível conectar ao banco de dados.');
}