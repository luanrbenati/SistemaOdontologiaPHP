<?php
require_once 'conexao.php';
header('Content-Type: application/json');

// Recebe o JSON
$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sem dados recebidos']);
    exit;
}

try {
    // 1. Prepara as datas
    $timestamp = strtotime($dados['start']);
    $data_atendimento = date('Y-m-d', $timestamp);
    $hora = date('H:i', $timestamp);
    $data_solicitacao = date('Y-m-d'); // Preenche com a data de hoje (obrigatório no seu banco)

    // 2. Valores Padrão para campos que existem na sua tabela (Print 3)
    // Ajuste esses IDs conforme a necessidade ou pegue da sessão do usuário
    $turma_id = 1;      // ID de uma turma padrão
    $perfil_id = 1;     // ID de um perfil padrão
    $professor_id = 1;  // ID de um professor padrão (ou null se permitir)
    $status_id = 1;     // 1 = Aguardando

    // 3. SQL Ajustado para SUAS colunas (Print 2)
    $sql = "INSERT INTO marcacoes (
                paciente_id, 
                data_atendimento, 
                hora, 
                data_solicitacao, 
                obs, 
                turma_id, 
                professor_id,
                perfil_id,
                status_marcacao_id, 
                created
            ) VALUES (
                :paciente, 
                :data_atend, 
                :hora, 
                :data_solic,
                :obs, 
                :turma, 
                :prof,
                :perfil,
                :status, 
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':paciente'   => $dados['paciente_id'],
        ':data_atend' => $data_atendimento,
        ':hora'       => $hora,
        ':data_solic' => $data_solicitacao,
        ':obs'        => $dados['obs'] ?? '',
        ':turma'      => $turma_id,
        ':prof'       => $professor_id,
        ':perfil'     => $perfil_id,
        ':status'     => $status_id
    ]);

    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>