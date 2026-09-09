<?php

require_once 'conexao.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: agendamentos.php'
    );

    exit;
}


/* =========================================
   DADOS DA ATUALIZAÇÃO
   ========================================= */

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

$status =
    $_POST['status'] ?? '';


$statusPermitidos = [
    'pendente',
    'confirmado',
    'concluido',
    'cancelado'
];


/* =========================================
   FILTROS PARA RETORNO À AGENDA
   ========================================= */

$filtroData =
    trim(
        $_POST['filtro_data'] ?? ''
    );

$filtroStatus =
    trim(
        $_POST['filtro_status'] ?? ''
    );

$filtroBarbeiro =
    filter_input(
        INPUT_POST,
        'filtro_barbeiro',
        FILTER_VALIDATE_INT
    );

$filtroCliente =
    trim(
        $_POST['filtro_cliente'] ?? ''
    );


/* Monta os parâmetros que serão preservados */

$parametrosRetorno = [];


if (
    $filtroData !== '' &&
    preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $filtroData
    )
) {

    $parametrosRetorno[
        'filtro_data'
    ] = $filtroData;
}


if (
    $filtroStatus !== '' &&
    in_array(
        $filtroStatus,
        $statusPermitidos,
        true
    )
) {

    $parametrosRetorno[
        'filtro_status'
    ] = $filtroStatus;
}


if ($filtroBarbeiro) {

    $parametrosRetorno[
        'filtro_barbeiro'
    ] = $filtroBarbeiro;
}


if ($filtroCliente !== '') {

    $parametrosRetorno[
        'filtro_cliente'
    ] = $filtroCliente;
}


/* =========================================
   VALIDAÇÃO
   ========================================= */

if (
    !$id ||
    !in_array(
        $status,
        $statusPermitidos,
        true
    )
) {

    $parametrosRetorno['status'] =
        'atualizacao_invalida';


    header(
        'Location: agendamentos.php?'
        . http_build_query(
            $parametrosRetorno
        )
    );

    exit;
}


/* =========================================
   ATUALIZAÇÃO
   ========================================= */

try {

    $comando =
        $conexao->prepare(
            'UPDATE AGENDAMENTO
             SET agend_status = :status
             WHERE agend_id = :id'
        );


    $comando->execute([

        ':status' => $status,

        ':id' => $id

    ]);


    $parametrosRetorno['status'] =
        'atualizado';


    header(
        'Location: agendamentos.php?'
        . http_build_query(
            $parametrosRetorno
        )
    );


} catch (PDOException $erro) {

    $parametrosRetorno['status'] =
        'erro_atualizacao';


    header(
        'Location: agendamentos.php?'
        . http_build_query(
            $parametrosRetorno
        )
    );
}


exit;