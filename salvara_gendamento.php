<?php
require_once 'conexao.php';
header('Content-Type: application/json');

// Recebe o JSON enviado pelo Javascript
$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sem dados recebidos']);
    exit;
}

try {
    // 1. Tratamento da Data e Hora
    // O FullCalendar manda algo tipo: "2025-12-24T14:00:00-03:00" ou "2025-12-24"
    $timestamp = strtotime($dados['start']);
    
    // Extrai para o formato do seu banco
    $data_banco = date('Y-m-d', $timestamp);
    $hora_banco = date('H:i', $timestamp); 

    // Se o usuário clicou na visão de "Mês", a hora vem 00:00. 
    // Vamos forçar um horário padrão (ex: 08:00) se vier vazio ou meia noite?
    // Por enquanto deixei como vier.

    // 2. Valores Padrão para os campos obrigatórios do seu banco
    $turma_id = 1; // Você deve pegar isso da sessão futuramente
    $status_inicial = 1; // 1 = Aguardando (conforme seu print)

    // 3. Insert
    $sql = "INSERT INTO marcacoes (
                paciente_id, 
                data_atendimento, 
                hora, 
                obs, 
                turma_id, 
                status_marcacao_id, 
                created
            ) VALUES (
                :paciente, 
                :data, 
                :hora, 
                :obs, 
                :turma, 
                :status, 
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':paciente' => $dados['paciente_id'],
        ':data'     => $data_banco,
        ':hora'     => $hora_banco,
        ':obs'      => $dados['obs'] ?? '',
        ':turma'    => $turma_id,
        ':status'   => $status_inicial
    ]);

    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}