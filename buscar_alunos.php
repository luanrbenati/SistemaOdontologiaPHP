<?php
require_once 'conexao.php';

// Recebe o ID da turma via GET
$turma_id = $_GET['turma_id'] ?? null;

// Array para retorno
$alunos = [];

if ($turma_id) {
    try {
        // === SQL CORRIGIDO ===
        // Faz a ligação: Tabela Alunos (a) -> Tabela de Ligação (at) -> ID da Turma
        $sql = "
            SELECT a.id, a.nome 
            FROM alunos a
            INNER JOIN alunos_turmas at ON a.id = at.aluno_id
            WHERE at.turma_id = ? 
              AND a.ativo = 1 
            ORDER BY a.nome ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$turma_id]);
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        // Em caso de erro, retorna array vazio
        // Se quiser debugar, pode descomentar a linha abaixo:
        // echo json_encode(['erro' => $e->getMessage()]); exit;
    }
}

// Retorna JSON para o JavaScript
header('Content-Type: application/json');
echo json_encode($alunos);
?>