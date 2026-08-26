<?php

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php?status=invalido');
    exit;
}

try {
    $comando = $conexao->prepare(
        'DELETE FROM CLIENTE WHERE cli_id = :id'
    );

    $comando->execute([':id' => $id]);

    if ($comando->rowCount() === 0) {
        header('Location: index.php?status=nao_encontrado');
    } else {
        header('Location: index.php?status=excluido');
    }

} catch (PDOException $erro) {
    if ($erro->getCode() === '23000') {
        header('Location: index.php?status=cliente_em_uso');
    } else {
        header('Location: index.php?status=erro');
    }
}

exit;