<?php

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: barbeiros.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: barbeiros.php?status=invalido');
    exit;
}

try {
    $comando = $conexao->prepare(
        'DELETE FROM BARBEIRO WHERE barb_id = :id'
    );

    $comando->execute([':id' => $id]);

    if ($comando->rowCount() === 0) {
        header('Location: barbeiros.php?status=nao_encontrado');
    } else {
        header('Location: barbeiros.php?status=excluido');
    }

} catch (PDOException $erro) {
    if ($erro->getCode() === '23000') {
        header('Location: barbeiros.php?status=barbeiro_em_uso');
    } else {
        header('Location: barbeiros.php?status=erro');
    }
}

exit;