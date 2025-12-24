<?php
session_start();
require_once 'conexao.php';
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) { echo json_encode(['sucesso'=>false, 'erro'=>'Sem dados']); exit; }

$usuario_id = $_SESSION['user_id'] ?? 1;

try {
    // === 1. ROTINA DE EXCLUSÃO ===
    if (isset($dados['acao']) && $dados['acao'] == 'excluir') {
        $stmt = $pdo->prepare("DELETE FROM marcacoes WHERE id = ?");
        $stmt->execute([$dados['id']]);
        echo json_encode(['sucesso' => true]);
        exit;
    }

    // === 2. ROTINA DE SALVAR (INSERT ou UPDATE) ===
    
    // Verifica conflito de horário (Professor não pode estar em 2 lugares)
    // Se for edição (tem ID), excluímos o próprio registro da checagem
    $sqlCheck = "SELECT id FROM marcacoes 
                 WHERE professor_id = :prof 
                 AND data_atendimento = :data 
                 AND hora = :hora
                 AND status_marcacao_id != 3";
    
    if (!empty($dados['id'])) {
        $sqlCheck .= " AND id != :id_atual"; // Ignora a si mesmo na checagem
    }

    $stmtCheck = $pdo->prepare($sqlCheck);
    $paramsCheck = [
        ':prof' => $dados['professor_id'],
        ':data' => $dados['data_atendimento'],
        ':hora' => $dados['hora']
    ];
    if (!empty($dados['id'])) $paramsCheck[':id_atual'] = $dados['id'];

    $stmtCheck->execute($paramsCheck);

    if ($stmtCheck->rowCount() > 0) {
        echo json_encode(['sucesso'=>false, 'erro'=>'Conflito de horário para este professor!']);
        exit;
    }

    // Define se é INSERT ou UPDATE
    if (empty($dados['id'])) {
        // --- INSERIR NOVO ---
        $sql = "INSERT INTO marcacoes (paciente_id, aluno_id, professor_id, turma_id, perfil_id, acl_usuario_id, data_atendimento, hora, obs, data_solicitacao, status_marcacao_id, created) 
                VALUES (:pac, :alu, :prof, :turm, :perf, :usu, :data, :hora, :obs, NOW(), 1, NOW())";
        $params = [
            ':pac'=>$dados['paciente_id'], ':alu'=>$dados['aluno_id'], ':prof'=>$dados['professor_id'], 
            ':turm'=>$dados['turma_id'], ':perf'=>$dados['perfil_id'], ':usu'=>$usuario_id, 
            ':data'=>$dados['data_atendimento'], ':hora'=>$dados['hora'], ':obs'=>$dados['obs']
        ];
    } else {
        // --- ATUALIZAR EXISTENTE ---
        $sql = "UPDATE marcacoes SET 
                    paciente_id=:pac, aluno_id=:alu, professor_id=:prof, turma_id=:turm, 
                    perfil_id=:perf, data_atendimento=:data, hora=:hora, obs=:obs, modified=NOW()
                WHERE id=:id";
        $params = [
            ':pac'=>$dados['paciente_id'], ':alu'=>$dados['aluno_id'], ':prof'=>$dados['professor_id'], 
            ':turm'=>$dados['turma_id'], ':perf'=>$dados['perfil_id'], 
            ':data'=>$dados['data_atendimento'], ':hora'=>$dados['hora'], ':obs'=>$dados['obs'],
            ':id'=>$dados['id']
        ];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>