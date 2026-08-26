<?php

require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: agendamentos.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';

$statusPermitidos = [
    'pendente',
    'confirmado',
    'concluido',
    'cancelado'
];

if (!$id || !in_array($status, $statusPermitidos, true)) {
    header('Location: agendamentos.php?status=atualizacao_invalida');
    exit;
}

try {
    $comando = $conexao->prepare(
        'UPDATE AGENDAMENTO
         SET agend_status = :status
         WHERE agend_id = :id'
    );

    $comando->execute([
        ':status' => $status,
        ':id' => $id
    ]);

    header('Location: agendamentos.php?status=atualizado');

} catch (PDOException $erro) {
    header('Location: agendamentos.php?status=erro_atualizacao');
}

exit;