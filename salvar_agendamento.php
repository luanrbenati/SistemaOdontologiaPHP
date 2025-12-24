<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sem dados recebidos']);
    exit;
}

// Pega usuário da sessão (se não tiver, usa 1 como fallback)
$usuario_logado_id = $_SESSION['user_id'] ?? 1;

try {
    // Dados recebidos do JSON
    $data_atendimento = $dados['data_atendimento'];
    $hora = $dados['hora'];
    $professor_id = $dados['professor_id'];

    // === TRAVA DE SEGURANÇA: Verificar conflito de horário ===
    // Procura se esse professor JÁ TEM agendamento nesse dia e hora
    // Ignorando agendamentos com status cancelado (ex: status_marcacao_id = 3)
    $sqlCheck = "SELECT id FROM marcacoes 
                 WHERE professor_id = :prof 
                 AND data_atendimento = :data 
                 AND hora = :hora
                 AND status_marcacao_id != 3"; 
    
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':prof' => $professor_id,
        ':data' => $data_atendimento,
        ':hora' => $hora
    ]);

    if ($stmtCheck->rowCount() > 0) {
        echo json_encode([
            'sucesso' => false, 
            'erro' => 'Conflito! O Professor selecionado já possui um agendamento nesta data e horário.'
        ]);
        exit;
    }
    // ========================================================

    $data_solicitacao = date('Y-m-d'); 
    $status_id = 1; // 1 = Aguardando

    // Insert Completo
    $sql = "INSERT INTO marcacoes (
                paciente_id, 
                aluno_id,        
                professor_id,    
                turma_id,
                perfil_id,
                acl_usuario_id,
                data_atendimento, 
                hora, 
                data_solicitacao, 
                obs, 
                status_marcacao_id, 
                created
            ) VALUES (
                :paciente, 
                :aluno,
                :prof,
                :turma,
                :perfil,
                :usuario,
                :data_atend, 
                :hora, 
                :data_solic,
                :obs, 
                :status, 
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':paciente'   => $dados['paciente_id'],
        ':aluno'      => $dados['aluno_id'],
        ':prof'       => $professor_id,
        ':turma'      => $dados['turma_id'],
        ':perfil'     => $dados['perfil_id'],
        ':usuario'    => $usuario_logado_id,
        ':data_atend' => $data_atendimento,
        ':hora'       => $hora,
        ':data_solic' => $data_solicitacao,
        ':obs'        => $dados['obs'] ?? '',
        ':status'     => $status_id
    ]);

    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>