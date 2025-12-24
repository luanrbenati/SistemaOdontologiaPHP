<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sem dados']);
    exit;
}

// Verifica usuário logado
$usuario_logado_id = $_SESSION['user_id'] ?? 1; // Fallback para 1 se não tiver sessão

try {
    $timestamp = strtotime($dados['start']);
    $data_atendimento = date('Y-m-d', $timestamp);
    $hora = date('H:i', $timestamp);
    $data_solicitacao = date('Y-m-d'); 
    
    // Status Padrão (Aguardando)
    $status_id = 1;     

    $sql = "INSERT INTO marcacoes (
                paciente_id, 
                aluno_id,        
                professor_id,    
                turma_id,
                perfil_id,        -- Campo Importante
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
                :perfil,          -- Bind do Perfil
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
        ':prof'       => $dados['professor_id'],
        ':turma'      => $dados['turma_id'],
        ':perfil'     => $dados['perfil_id'],     // Agora vem do formulário
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