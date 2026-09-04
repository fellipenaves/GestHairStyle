<?php

require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');


$barbeiroId = filter_input(
    INPUT_GET,
    'barbeiro_id',
    FILTER_VALIDATE_INT
);

$servicoId = filter_input(
    INPUT_GET,
    'servico_id',
    FILTER_VALIDATE_INT
);

$data = trim(
    $_GET['data'] ?? ''
);


/* =========================================
   VALIDAÇÃO
   ========================================= */

if (
    !$barbeiroId ||
    !$servicoId ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)
) {

    echo json_encode([
        'sucesso' => false,
        'horarios' => []
    ]);

    exit;
}


/* =========================================
   DOMINGO FECHADO
   ========================================= */

$dataObjeto = DateTime::createFromFormat(
    '!Y-m-d',
    $data
);

if (!$dataObjeto) {

    echo json_encode([
        'sucesso' => false,
        'horarios' => []
    ]);

    exit;
}


$diaSemana = (int) $dataObjeto->format('N');

if ($diaSemana === 7) {

    echo json_encode([
        'sucesso' => true,
        'horarios' => [],
        'mensagem' => 'A barbearia não funciona aos domingos.'
    ]);

    exit;
}

/* =========================================
   IMPEDE DATAS PASSADAS
   ========================================= */

$hoje = new DateTime('today');

if ($dataObjeto < $hoje) {

    echo json_encode([
        'sucesso' => true,
        'horarios' => [],
        'mensagem' =>
            'Não é possível agendar em uma data passada.'
    ]);

    exit;
}


/* =========================================
   DURAÇÃO DO SERVIÇO
   ========================================= */

$consultaServico = $conexao->prepare(
    'SELECT serv_duracao_min
     FROM SERVICO
     WHERE serv_id = :id'
);

$consultaServico->execute([
    ':id' => $servicoId
]);

$duracao = $consultaServico->fetchColumn();


if (!$duracao) {

    echo json_encode([
        'sucesso' => false,
        'horarios' => []
    ]);

    exit;
}


$duracao = (int) $duracao;


/* =========================================
   AGENDAMENTOS DO BARBEIRO
   ========================================= */

$consultaAgenda = $conexao->prepare(
    "SELECT
        agend_data_hora,
        agend_tempo_final

     FROM AGENDAMENTO

     WHERE barb_id = :barbeiro_id

       AND DATE(agend_data_hora) = :data

       AND agend_status <> 'cancelado'

     ORDER BY agend_data_hora"
);

$consultaAgenda->execute([

    ':barbeiro_id' => $barbeiroId,

    ':data' => $data

]);

$ocupados = $consultaAgenda->fetchAll(
    PDO::FETCH_ASSOC
);


/* =========================================
   GERA HORÁRIOS
   08:00 ÀS 21:00
   Intervalos de 30 minutos
   ========================================= */

$abertura = new DateTime(
    $data . ' 08:00:00'
);

$fechamento = new DateTime(
    $data . ' 21:00:00'
);

$horariosDisponiveis = [];

$agora = new DateTime();

$horarioAtual = clone $abertura;


while ($horarioAtual < $fechamento) {

    $inicio = clone $horarioAtual;

    $fim = clone $inicio;

    $fim->modify(
        '+' . $duracao . ' minutes'
    );


    /* Serviço precisa terminar até 21h */

    if ($fim > $fechamento) {
        break;
    }

    /* Não mostra horários que já passaram hoje */

if (
    $data === $agora->format('Y-m-d') &&
    $inicio <= $agora
) {

    $horarioAtual->modify(
        '+30 minutes'
    );

    continue;
}


    $conflito = false;


    foreach ($ocupados as $ocupado) {

        $inicioOcupado =
            new DateTime(
                $ocupado['agend_data_hora']
            );

        $fimOcupado =
            new DateTime(
                $ocupado['agend_tempo_final']
            );


        /*
         * Existe conflito quando:
         *
         * novo início < fim existente
         * e
         * novo fim > início existente
         */

        if (
            $inicio < $fimOcupado &&
            $fim > $inicioOcupado
        ) {

            $conflito = true;

            break;
        }
    }


    if (!$conflito) {

        $horariosDisponiveis[] = [

            'hora' =>
                $inicio->format('H:i'),

            'fim' =>
                $fim->format('H:i')

        ];
    }


    /* próximo horário a cada 30 minutos */

    $horarioAtual->modify(
        '+30 minutes'
    );
}


/* =========================================
   RESPOSTA
   ========================================= */

echo json_encode([

    'sucesso' => true,

    'duracao' => $duracao,

    'horarios' =>
        $horariosDisponiveis

]);