<?php
// ============================================================================
// AJAX_ODONTO.PHP - Handler AJAX para odontograma com segurança aprimorada
// ============================================================================

session_start();
ob_start();

require_once 'conexao.php';

header('Content-Type: application/json');

// === VALIDAÇÕES DE SEGURANÇA ===

// 1. Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
    exit;
}

// 2. Validar requisição AJAX
if (!isset($_POST['ajax_odonto'])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida']);
    exit;
}

// 3. Validar token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'Token de segurança inválido']);
    exit;
}

try {
    // === AÇÃO: LIMPAR TUDO ===
    if (isset($_POST['acao']) && $_POST['acao'] == 'limpar_tudo') {
        $paciente_id = filter_var($_POST['paciente_id'] ?? 0, FILTER_VALIDATE_INT);
        
        if ($paciente_id <= 0) {
            throw new Exception('ID do paciente inválido');
        }
        
        // Verificar se o paciente existe
        $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE id = ?");
        $stmt->execute([$paciente_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Paciente não encontrado');
        }
        
        $pdo->beginTransaction();
        
        $pdo->prepare("DELETE FROM odontograma WHERE paciente_id = ?")->execute([$paciente_id]);
        $pdo->prepare("DELETE FROM odontograma_historico WHERE paciente_id = ?")->execute([$paciente_id]);
        
        $pdo->commit();
        
        ob_end_clean();
        echo json_encode(['sucesso' => true, 'mensagem' => 'Odontograma limpo com sucesso!']);
        exit;
    }
    
    // === AÇÃO: CARREGAR HISTÓRICO PAGINADO ===
    if (isset($_GET['acao']) && $_GET['acao'] == 'historico') {
        $paciente_id = filter_var($_GET['paciente_id'] ?? 0, FILTER_VALIDATE_INT);
        $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT);
        $limit = 10;
        
        if ($paciente_id <= 0) {
            throw new Exception('ID do paciente inválido');
        }
        
        $stmt = $pdo->prepare("
            SELECT dente, face, status_anterior, status_novo, data_registro 
            FROM odontograma_historico 
            WHERE paciente_id = ? 
            ORDER BY data_registro DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$paciente_id, $limit, $offset]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ob_end_clean();
        echo json_encode(['sucesso' => true, 'dados' => $historico]);
        exit;
    }
    
    // === AÇÃO: SALVAR CLIQUE NO DENTE ===
    
    // Validar e sanitizar inputs
    $dente = filter_var($_POST['dente'] ?? 0, FILTER_VALIDATE_INT);
    $face = filter_var($_POST['face'] ?? '', FILTER_SANITIZE_STRING);
    $status_novo = filter_var($_POST['status'] ?? 0, FILTER_VALIDATE_INT);
    $paciente_id = filter_var($_POST['paciente_id'] ?? 0, FILTER_VALIDATE_INT);
    
    // Validações de dados
    if ($paciente_id <= 0) {
        throw new Exception('ID do paciente inválido');
    }
    
    // Validar número do dente (permanentes + decíduos)
    $dentes_validos = array_merge(
        range(11, 18), range(21, 28), range(31, 38), range(41, 48), // Permanentes
        range(51, 55), range(61, 65), range(71, 75), range(81, 85)  // Decíduos
    );
    
    if (!in_array($dente, $dentes_validos, true)) {
        throw new Exception('Número do dente inválido: ' . $dente);
    }
    
    // Validar face
    $faces_validas = ['oclusal', 'vestibular', 'distal', 'lingual', 'mesial'];
    if (!in_array($face, $faces_validas, true)) {
        throw new Exception('Face inválida: ' . $face);
    }
    
    // Validar status
    if ($status_novo < 0 || $status_novo > 3) {
        throw new Exception('Status inválido: ' . $status_novo);
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Buscar status anterior
    $stmt = $pdo->prepare("
        SELECT status FROM odontograma 
        WHERE paciente_id = ? AND dente = ? AND face = ?
    ");
    $stmt->execute([$paciente_id, $dente, $face]);
    $row = $stmt->fetch();
    $status_anterior = $row ? intval($row['status']) : 0;
    
    // Atualizar ou inserir status
    $stmt = $pdo->prepare("
        INSERT INTO odontograma (paciente_id, dente, face, status) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            status = VALUES(status),
            data_atualizacao = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$paciente_id, $dente, $face, $status_novo]);
    
    // Registrar no histórico apenas se houve mudança
    $mudou = ($status_anterior != $status_novo);
    if ($mudou) {
        $stmt_hist = $pdo->prepare("
            INSERT INTO odontograma_historico 
            (paciente_id, dente, face, status_anterior, status_novo) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt_hist->execute([$paciente_id, $dente, $face, $status_anterior, $status_novo]);
    }
    
    // Commit da transação
    $pdo->commit();
    
    ob_end_clean();
    echo json_encode([
        'sucesso' => true,
        'dente' => $dente,
        'face' => $face,
        'status_anterior' => $status_anterior,
        'status_novo' => $status_novo,
        'mudou' => $mudou,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro PDO em ajax_odonto.php: " . $e->getMessage());
    
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro no banco de dados. Por favor, tente novamente.'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erro em ajax_odonto.php: " . $e->getMessage());
    
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ]);
}

exit;