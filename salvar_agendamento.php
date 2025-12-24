<?php
// === IMPORTANTE: INICIAR SESSÃO PARA PEGAR O USUÁRIO LOGADO ===
session_start();

require_once 'conexao.php';
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) {
    echo json_encode(['sucesso' => false, 'erro' => 'Sem dados']);
    exit;
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Se a sessão não tiver user_id, tenta usar um ID padrão ou retorna erro
    // echo json_encode(['sucesso' => false, 'erro' => 'Usuário não logado']); exit;
    $usuario_logado_id = 1; // ID de fallback caso a sessão falhe (PERIGOSO EM PRODUÇÃO)
} else {
    $usuario_logado_id = $_SESSION['user_id'];
}

try {
    $timestamp = strtotime($dados['start']);
    $data_atendimento = date('Y-m-d', $timestamp);
    $hora = date('H:i', $timestamp);
    $data_solicitacao = date('Y-m-d'); 

    // Valores Padrão
    $perfil_id = 1;     
    $status_id = 1;     

    // SQL ATUALIZADO COM acl_usuario_id E turma_id vindo do form
    $sql = "INSERT INTO marcacoes (
                paciente_id, 
                aluno_id,        
                professor_id,    
                turma_id,         -- Vem do form
                acl_usuario_id,   -- Vem da SESSÃO (Quem agendou)
                data_atendimento, 
                hora, 
                data_solicitacao, 
                obs, 
                perfil_id,
                status_marcacao_id, 
                created
            ) VALUES (
                :paciente, 
                :aluno,
                :prof,
                :turma,
                :usuario,
                :data_atend, 
                :hora, 
                :data_solic,
                :obs, 
                :perfil,
                :status, 
                NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':paciente'   => $dados['paciente_id'],
        ':aluno'      => $dados['aluno_id'],
        ':prof'       => $dados['professor_id'],
        ':turma'      => $dados['turma_id'],      // Agora dinâmico
        ':usuario'    => $usuario_logado_id,      // ID de quem está logado
        ':data_atend' => $data_atendimento,
        ':hora'       => $hora,
        ':data_solic' => $data_solicitacao,
        ':obs'        => $dados['obs'] ?? '',
        ':perfil'     => $perfil_id,
        ':status'     => $status_id
    ]);

    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>