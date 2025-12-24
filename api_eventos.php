<?php
require_once 'conexao.php';
header('Content-Type: application/json');

try {
    // SQL ajustado para suas colunas
    $sql = "
        SELECT 
            m.id, 
            -- Tenta pegar o nome do paciente, se não tiver, mostra 'Paciente X'
            COALESCE(p.nome, CONCAT('Paciente #', m.paciente_id)) as title, 
            
            -- Junta DATA e HORA para o calendário entender
            CONCAT(m.data_atendimento, ' ', m.hora) as start,
            
            -- Cria uma duração fictícia de 1h
            DATE_ADD(CONCAT(m.data_atendimento, ' ', m.hora), INTERVAL 1 HOUR) as end,
            
            m.obs,
            
            -- Cores baseadas no status
            CASE m.status_marcacao_id
                WHEN 1 THEN '#f39c12'  -- Laranja
                WHEN 2 THEN '#198754'  -- Verde
                ELSE '#0d6efd'         -- Azul
            END as color

        FROM marcacoes m
        LEFT JOIN pacientes p ON m.paciente_id = p.id
        WHERE m.data_atendimento IS NOT NULL 
          AND m.hora IS NOT NULL 
          AND m.hora != ''
    ";

    $stmt = $pdo->query($sql);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($eventos);

} catch (Exception $e) {
    echo json_encode([]);
}
?>