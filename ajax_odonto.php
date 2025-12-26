<?php
// ajax_odonto.php
session_start();
require_once 'conexao.php'; // Certifique-se que este arquivo conecta ao seu banco $pdo

header('Content-Type: application/json');

// Verifica se é uma requisição POST válida
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
    exit;
}

// Verifica se foi enviado o parâmetro principal
if (!isset($_POST['ajax_odonto'])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Parâmetro ausente']);
    exit;
}

try {
    // === AÇÃO 1: LIMPAR TUDO ===
    if (isset($_POST['acao']) && $_POST['acao'] === 'limpar_tudo') {
        $id_paciente = intval($_POST['paciente_id']);
        
        // Remove registros do odontograma atual
        $stmt = $pdo->prepare("DELETE FROM odontograma WHERE paciente_id = ?");
        $stmt->execute([$id_paciente]);
        
        // Opcional: Registrar no histórico que foi limpo
        $stmt = $pdo->prepare("INSERT INTO odontograma_historico (paciente_id, dente, face, status_anterior, status_novo, data_registro) VALUES (?, 'todos', 'geral', 0, 0, NOW())");
        $stmt->execute([$id_paciente]);

        echo json_encode(['sucesso' => true]);
        exit;
    }

    // === AÇÃO 2: SALVAR DENTE/FACE ===
    $paciente_id = intval($_POST['paciente_id']);
    $dente       = intval($_POST['dente']);
    $face        = $_POST['face'];
    $novo_status = intval($_POST['status']);

    // 1. Descobrir status anterior (para o histórico)
    $stmt = $pdo->prepare("SELECT status FROM odontograma WHERE paciente_id = ? AND dente = ? AND face = ?");
    $stmt->execute([$paciente_id, $dente, $face]);
    $status_ant = $stmt->fetchColumn();

    if ($status_ant === false) {
        $status_ant = 0; // Se não existe, assume normal (0)
    }

    // Se o status não mudou, não faz nada
    if ($status_ant == $novo_status) {
        echo json_encode(['sucesso' => true, 'mudou' => false]);
        exit;
    }

    // 2. Atualizar ou Inserir no Odontograma
    // Primeiro tentamos atualizar
    $stmt = $pdo->prepare("UPDATE odontograma SET status = ? WHERE paciente_id = ? AND dente = ? AND face = ?");
    $stmt->execute([$novo_status, $paciente_id, $dente, $face]);

    // Se não atualizou nenhuma linha (não existia), inserimos
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO odontograma (paciente_id, dente, face, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$paciente_id, $dente, $face, $novo_status]);
    }

    // 3. Gravar Histórico
    $stmt = $pdo->prepare("INSERT INTO odontograma_historico (paciente_id, dente, face, status_anterior, status_novo) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$paciente_id, $dente, $face, $status_ant, $novo_status]);

    echo json_encode(['sucesso' => true, 'mudou' => true]);

} catch (PDOException $e) {
    // Retorna erro amigável
    echo json_encode(['sucesso' => false, 'erro' => 'Erro Banco: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>