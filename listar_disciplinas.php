<?php
// Tenta usar a conexão do index.php
try {
    if (!isset($pdo)) throw new Exception("Erro de conexão: Variável \$pdo não definida.");

    // SQL com JOIN para buscar o nome do professor
    $sql = "
        SELECT 
            d.id, 
            d.nome, 
            d.periodo, 
            d.professor_id,
            p.nome as professor_nome,
            DATE_FORMAT(d.created, '%d/%m/%Y %H:%i') as criado_em,
            DATE_FORMAT(d.modified, '%d/%m/%Y %H:%i') as modificado_em
        FROM disciplinas d
        LEFT JOIN professores p ON d.professor_id = p.id
        ORDER BY d.nome ASC
    ";

    $stmt = $pdo->query($sql);
    $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<div class='alert alert-danger m-3'>Erro: " . $e->getMessage() . "</div>";
    $disciplinas = []; 
}
?>

<style>
    .info-secundaria { font-size: 0.8rem; color: #666; }
    #iframeEditor { width: 100%; height: 550px; border: none; }
    
    /* Design do Cabeçalho Azul conforme o modelo */
    .thead-blue th {
        background-color: #3498db !important;
        color: white !important;
        border: none;
        white-space: nowrap;
    }
    .table-responsive { border-radius: 8px; }
    .badge-periodo {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
        font-weight: 600;
    }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-dark">Gerenciamento de Disciplinas</h4>
            <p class="text-muted small mb-0">Listagem de disciplinas e períodos</p>
        </div>
        
        <button onclick="abrirModal('form_disciplina.php')" class="btn btn-primary btn-sm shadow-sm">
            <i class="fa-solid fa-book-medical me-1"></i> Nova Disciplina
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="thead-blue">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Nome da Disciplina</th>
                            <th>Período</th>
                            <th>Professor Responsável</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($disciplinas)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Nenhum registro encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($disciplinas as $disc): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-secondary">#<?= $disc['id'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($disc['nome']) ?></td>
                                <td>
                                    <span class="badge badge-periodo bg-info text-dark">
                                        <?= $disc['periodo'] ?>º Período
                                    </span>
                                </td>
                                <td>
                                    <?php if($disc['professor_nome']): ?>
                                        <i class="fa-solid fa-chalkboard-user me-1 text-primary"></i>
                                        <span class="text-dark"><?= htmlspecialchars($disc['professor_nome']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">
                                            <i class="fa-solid fa-user-slash me-1"></i>
                                            Sem professor
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <button onclick="visualizarDisciplina(<?= $disc['id'] ?>)" class="btn btn-sm btn-outline-info me-1" title="Visualizar">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button onclick="abrirModal('form_disciplina.php?id=<?= $disc['id'] ?>')" class="btn btn-sm btn-outline-primary" title="Editar">
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

<!-- Modal de Visualização -->
<div class="modal fade" id="modalVisualizar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"> 
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light py-2">
        <h5 class="modal-title fs-6 fw-bold text-secondary"><i class="fa-solid fa-info-circle me-2"></i>Detalhes da Disciplina</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="conteudoVisualizar">
          <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Carregando...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditor" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered"> 
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light py-2">
        <h5 class="modal-title fs-6 fw-bold text-secondary"><i class="fa-solid fa-book me-2"></i>Cadastro de Disciplina</h5>
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

    function visualizarDisciplina(id) {
        // Abre o modal
        var modal = new bootstrap.Modal(document.getElementById('modalVisualizar'));
        modal.show();
        
        // Faz requisição AJAX para buscar os detalhes usando o visualizador genérico
        fetch('visualizar.php?tipo=disciplina&id=' + id)
            .then(response => response.text())
            .then(data => {
                document.getElementById('conteudoVisualizar').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('conteudoVisualizar').innerHTML = 
                    '<div class="alert alert-danger">Erro ao carregar dados.</div>';
            });
    }

    // Fecha o modal automaticamente se o iframe redirecionar para uma página de sucesso (ex: listar)
    document.getElementById('iframeEditor').onload = function() {
        try {
            if (this.contentWindow.location.href.indexOf('listar') !== -1) {
                fecharModal();
            }
        } catch (e) { }
    };
</script>