<?php

require_once 'conexao.php';

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {

    header(
        'Location: despesas.php?status=erro'
    );

    exit;
}


try {

    $excluir =
        $conexao->prepare(
            'DELETE
             FROM DESPESA
             WHERE desp_id = :id'
        );

    $excluir->execute([
        ':id' => $id
    ]);


    header(
        'Location: despesas.php?status=excluido'
    );

    exit;


} catch (Throwable $erro) {

    header(
        'Location: despesas.php?status=erro'
    );

    exit;
}