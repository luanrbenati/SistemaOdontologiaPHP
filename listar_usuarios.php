<?php
// Tenta usar a conexão do index.php
try {
    if (!isset($pdo)) throw new Exception("Erro de conexão: Variável \$pdo não definida.");

    // === SQL ATUALIZADO ===
    // Adicionei o LEFT JOIN groups g ON u.group_id = g.id
    // E busquei g.name como 'nome_grupo'
    $sql = "
        SELECT 
            u.id, 
            u.name, 
            u.username, 
            u.group_id, 
            u.status,
            g.name as nome_grupo,
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
        LEFT JOIN groups g ON u.group_id = g.id  -- Ligação com a tabela da imagem
        LEFT JOIN horarios_acesso h ON u.id = h.user_id
        GROUP BY u.id
        ORDER BY u.name ASC
    ";

    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<div class='alert alert-danger m-3'>Erro: " . $e->getMessage() . "</div>";
    $usuarios = []; 
}
?>

<style>
    .horario-cell { font-size: 0.8rem; color: #666; line-height: 1.2; }
    #iframeEditor { width: 100%; height: 500px; border: none; }
    
    /* Cabeçalho Azul */
    .thead-blue th {
        background-color: #3498db !important;
        color: white !important;
        border: none;
    }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-dark">Gerenciamento de Usuários</h4>
        
        <button onclick="abrirModal('form_usuario.php')" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i> Novo Usuário
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="thead-blue">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Status</th>
                            <th>Nome</th>
                            <th>Login</th>
                            <th>Grupo</th>
                            <th>Dias e Horários</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Nenhum usuário encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $user): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-secondary">#<?= $user['id'] ?></td>
                                <td>
                                    <?= ($user['status'] == 1) 
                                        ? '<span class="badge bg-success bg-opacity-75">Ativo</span>' 
                                        : '<span class="badge bg-danger bg-opacity-75">Inativo</span>'; ?>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($user['nome_grupo'] ?? 'Sem Grupo') ?>
                                    </span>
                                </td>
                                
                                <td class="horario-cell">
                                    <?= $user['resumo_horarios'] ?: "<span class='text-muted fst-italic'>Sem restrições</span>" ?>
                                </td>
                                <td class="text-end pe-3">
                                    <button onclick="abrirModal('form_usuario.php?id=<?= $user['id'] ?>')" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditor" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered"> 
    <div class="modal-content">
      <div class="modal-header bg-light py-2">
        <h5 class="modal-title fs-6 fw-bold text-secondary"><i class="fa-solid fa-pen-to-square me-2"></i>Edição Rápida</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="fecharModal()"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="iframeEditor" src=""></iframe>
      </div>
    </div>
  </div>
</div>

<script>
    function abrirModal(url) {
        var modalEl = document.getElementById('modalEditor');
        var iframe = document.getElementById('iframeEditor');
        iframe.src = url;
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function fecharModal() {
        document.getElementById('iframeEditor').src = '';
        window.location.reload();
    }

    document.getElementById('iframeEditor').onload = function() {
        try {
            if (this.contentWindow.location.href.indexOf('listar') !== -1) {
                var modalEl = document.getElementById('modalEditor');
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                window.location.reload(); 
            }
        } catch (e) { }
    };
</script>