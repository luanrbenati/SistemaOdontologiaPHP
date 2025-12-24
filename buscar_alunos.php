<?php
require_once 'conexao.php';

$turma_id = $_GET['turma_id'] ?? null;
$alunos = [];

if ($turma_id) {
    try {
        // Faz a busca na tabela ALUNOS unindo com ALUNOS_TURMAS
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
        // Retorna vazio em caso de erro
    }
}

header('Content-Type: application/json');
echo json_encode($alunos);
?>