<?php
require_once 'conexao.php';

$turma_id = $_GET['turma_id'] ?? null;

if ($turma_id) {
    try {
        // Ajuste 'turma_id' se o nome da coluna na tabela ALUNOS for diferente
        $stmt = $pdo->prepare("SELECT id, nome FROM alunos WHERE turma_id = ? AND ativo = 1 ORDER BY nome ASC");
        $stmt->execute([$turma_id]);
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($alunos);
    } catch (Exception $e) {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>