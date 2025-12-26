<?php
// ============================================================================
// AJAX_ODONTO.PHP - Handler exclusivo para requisições AJAX do odontograma
// ============================================================================

// Evitar qualquer output antes do JSON
ob_start();

//require_once 'conexao.php';

header('Content-Type: application/json');

// Validar se é requisição AJAX
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ajax_odonto'])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida']);
    exit;
}

try {
    // === AÇÃO: LIMPAR TUDO ===
    if (isset($_POST['acao']) && $_POST['acao'] == 'limpar_tudo') {
        $paciente_id = intval($_POST['paciente_id']);
        
        if ($paciente_id <= 0) {
            throw new Exception('ID do paciente inválido');
        }
        
        $pdo->prepare("DELETE FROM odontograma WHERE paciente_id = ?")->execute([$paciente_id]);
        $pdo->prepare("DELETE FROM odontograma_historico WHERE paciente_id = ?")->execute([$paciente_id]);
        
        ob_end_clean(); // Limpa qualquer buffer
        echo json_encode(['sucesso' => true, 'mensagem' => 'Odontograma limpo!']);
        exit;
    }
    
    // === AÇÃO: SALVAR CLIQUE NO DENTE ===
    $dente = intval($_POST['dente'] ?? 0);
    $face = $_POST['face'] ?? '';
    $status_novo = intval($_POST['status'] ?? 0);
    $paciente_id = intval($_POST['paciente_id'] ?? 0);
    
    // Validações
    if ($paciente_id <= 0) {
        throw new Exception('ID do paciente inválido');
    }
    
    if ($dente <= 0) {
        throw new Exception('Número do dente inválido');
    }
    
    if (!in_array($face, ['oclusal', 'vestibular', 'distal', 'lingual', 'mesial'])) {
        throw new Exception('Face inválida');
    }
    
    if ($status_novo < 0 || $status_novo > 3) {
        throw new Exception('Status inválido');
    }
    
    // Buscar status anterior
    $stmt = $pdo->prepare("SELECT status FROM odontograma WHERE paciente_id = ? AND dente = ? AND face = ?");
    $stmt->execute([$paciente_id, $dente, $face]);
    $row = $stmt->fetch();
    $status_anterior = $row ? intval($row['status']) : 0;
    
    // Atualizar ou inserir
    $stmt = $pdo->prepare("
        INSERT INTO odontograma (paciente_id, dente, face, status) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status)
    ");
    $stmt->execute([$paciente_id, $dente, $face, $status_novo]);
    
    // Registrar no histórico (apenas se mudou)
    if ($status_anterior != $status_novo) {
        $stmt_hist = $pdo->prepare("
            INSERT INTO odontograma_historico (paciente_id, dente, face, status_anterior, status_novo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt_hist->execute([$paciente_id, $dente, $face, $status_anterior, $status_novo]);
    }
    
    ob_end_clean(); // Limpa qualquer buffer
    echo json_encode([
        'sucesso' => true,
        'dente' => $dente,
        'face' => $face,
        'status_anterior' => $status_anterior,
        'status_novo' => $status_novo,
        'mudou' => ($status_anterior != $status_novo)
    ]);
    
} catch (Exception $e) {
    ob_end_clean(); // Limpa qualquer buffer
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}

exit;