<?php
require_once 'conexao.php'; // Certifique-se que sua conexão $pdo está aqui

header('Content-Type: application/json');

try {
    // Tenta pegar o nome do paciente fazendo JOIN com tabela pacientes
    // Se a tabela de pacientes tiver outro nome, ajuste aqui
    $sql = "
        SELECT 
            m.id, 
            COALESCE(p.nome, 'Paciente ID: ' || m.paciente_id) as title, 
            
            -- Junta DATA e HORA para formar o formato ISO (YYYY-MM-DD HH:MM:SS)
            CONCAT(m.data_atendimento, ' ', m.hora) as start,
            
            -- Cria uma hora final falsa (adiciona 1 hora) só pro calendário desenhar o bloco
            DATE_ADD(CONCAT(m.data_atendimento, ' ', m.hora), INTERVAL 1 HOUR) as end,
            
            m.obs,
            
            -- Cores baseadas no status (Ajuste os IDs conforme sua regra de negócio)
            CASE m.status_marcacao_id
                WHEN 1 THEN '#f39c12'  -- Aguardando (Laranja)
                WHEN 2 THEN '#198754'  -- Atendido/Confirmado (Verde)
                WHEN 3 THEN '#dc3545'  -- Faltou (Vermelho)
                ELSE '#0d6efd'         -- Padrão (Azul)
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
    // Retorna array vazio em caso de erro para não quebrar o JS
    echo json_encode([]);
}