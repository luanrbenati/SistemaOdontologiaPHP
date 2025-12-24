<?php
require_once 'conexao.php';
header('Content-Type: application/json');

try {
    // SQL ajustado para trazer TODOS os IDs necessários para edição
    $sql = "
        SELECT 
            m.id, 
            COALESCE(p.nome, CONCAT('Paciente #', m.paciente_id)) as title, 
            CONCAT(m.data_atendimento, ' ', m.hora) as start,
            -- Duração fake de 30min só pro calendário renderizar
            DATE_ADD(CONCAT(m.data_atendimento, ' ', m.hora), INTERVAL 30 MINUTE) as end,
            
            -- Cores baseadas no status
            CASE m.status_marcacao_id
                WHEN 1 THEN '#f39c12'  -- Aguardando (Laranja)
                WHEN 2 THEN '#198754'  -- Atendido (Verde)
                WHEN 3 THEN '#dc3545'  -- Cancelado (Vermelho)
                ELSE '#0d6efd'         -- Padrão (Azul)
            END as color,

            -- DADOS EXTRAS (ExtendedProps) PARA EDIÇÃO
            m.paciente_id,
            m.aluno_id,
            m.professor_id,
            m.turma_id,
            m.perfil_id,
            m.obs,
            m.hora,
            m.data_atendimento

        FROM marcacoes m
        LEFT JOIN pacientes p ON m.paciente_id = p.id
        WHERE m.status_marcacao_id != 99 -- Exemplo: não trazer excluídos lógicos se houver
    ";

    $stmt = $pdo->query($sql);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($eventos);

} catch (Exception $e) {
    echo json_encode([]);
}
?>