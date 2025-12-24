<?php
require_once 'conexao.php';

try {
    if (!isset($pdo)) throw new Exception("Erro de conexão.");

    // SQL que agrupa os horários em uma única célula
    $sql = "
        SELECT 
            u.id, u.name, u.username, u.group_id, u.status,
            GROUP_CONCAT(
                CONCAT(
                    CASE h.dia_semana
                        WHEN 1 THEN 'Seg' WHEN 2 THEN 'Ter' WHEN 3 THEN 'Qua'
                        WHEN 4 THEN 'Qui' WHEN 5 THEN 'Sex' WHEN 6 THEN 'Sáb' WHEN 7 THEN 'Dom'
                    END,
                    ': ', DATE_FORMAT(h.hora_inicio, '%H:%i'), ' às ', DATE_FORMAT(h.hora_fim, '%H:%i')
                ) ORDER BY h.dia_semana ASC SEPARATOR '<br>'
            ) as resumo_horarios
        FROM users u
        LEFT JOIN horarios_acesso h ON u.id = h.user_id
        GROUP BY u.id
        ORDER BY u.name ASC
    ";

    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}

function getGrupoNome($id) {
    $grupos = [1 => 'Admin', 2 => 'Operador', 3 => 'Visualizador'];
    return $grupos[$id] ?? 'Outro';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lista de Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>.horario-cell { font-size: 0.85rem; color: #555; }</style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Gerenciamento de Usuários</h3>
        <a href="cadastro_usuario.php" class="btn btn-primary">+ Novo Usuário</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Nome</th>
                        <th>Login</th>
                        <th>Grupo</th>
                        <th>Dias e Horários</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user): ?>
                    <tr>
                        <td>#<?= $user['id'] ?></td>
                        <td>
                            <?= ($user['status'] == 1) 
                                ? '<span class="badge bg-success">Ativo</span>' 
                                : '<span class="badge bg-danger">Inativo</span>'; ?>
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><span class="badge bg-secondary"><?= getGrupoNome($user['group_id']) ?></span></td>
                        <td class="horario-cell">
                            <?= $user['resumo_horarios'] ?: "<span class='text-muted small'>Sem restrições</span>" ?>
                        </td>
                        <td class="text-end">
                            <a href="editar_usuario.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>