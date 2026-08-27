<?php

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: servicos.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: servicos.php?status=invalido');
    exit;
}

try {
    $comando = $conexao->prepare(
        'DELETE FROM SERVICO WHERE serv_id = :id'
    );

    $comando->execute([':id' => $id]);

    if ($comando->rowCount() === 0) {
        header('Location: servicos.php?status=nao_encontrado');
    } else {
        header('Location: servicos.php?status=excluido');
    }

} catch (PDOException $erro) {
    if ($erro->getCode() === '23000') {
        header('Location: servicos.php?status=servico_em_uso');
    } else {
        header('Location: servicos.php?status=erro');
    }
}

exit;