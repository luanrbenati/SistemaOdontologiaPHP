<?php
// Tenta usar a conexão do index.php
try {
    if (!isset($pdo)) throw new Exception("Erro de conexão: Variável \$pdo não definida.");

    // SQL com JOIN para buscar disciplina e professor
    $sql = "
        SELECT 
            t.id, 
            t.nome,
            t.ano,
            t.semestre,
            t.disciplina_id,
            t.ativo,
            t.aluno_id,
            d.nome as disciplina_nome,
            d.periodo as disciplina_periodo,
            a.nome as aluno_nome,
            DATE_FORMAT(t.created, '%d/%m/%Y %H:%i') as criado_em,
            DATE_FORMAT(t.modified, '%d/%m/%Y %H:%i') as modificado_em
        FROM turmas t
        LEFT JOIN disciplinas d ON t.disciplina_id = d.id
        LEFT JOIN alunos a ON t.aluno_id = a.id
        ORDER BY t.ano DESC, t.semestre DESC, t.nome ASC
    ";

    $stmt = $pdo->query($sql);
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<div class='alert alert-danger m-3'>Erro: " . $e->getMessage() . "</div>";
    $turmas = []; 
}
?>

<style>
    .info-secundaria { font-size: 0.8rem; color: #666; }
    #iframeEditor { width: 100%; height: 550px; border: none; }
    
    .thead-blue th {
        background-color: #3498db !important;
        color: white !important;
        border: none;
        white-space: nowrap;
    }
    .table-responsive { border-radius: 8px; }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-dark">Gerenciamento de Turmas</h4>
            <p class="text-muted small mb-0">Listagem de turmas por disciplina e período</p>
        </div>
        
        <button onclick="abrirModal('form_turma.php')" class="btn btn-primary btn-sm shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Nova Turma
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
                            <th>Nome da Turma</th>
                            <th>Ano/Semestre</th>
                            <th>Disciplina</th>
                            <th>Monitor (Aluno)</th>
                            <th class="text-end pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($turmas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Nenhum registro encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($turmas as $turma): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-secondary">#<?= $turma['id'] ?></td>
                                <td>
                                    <?= ($turma['ativo'] == 1) 
                                        ? '<span class="badge bg-success bg-opacity-75">Ativa</span>' 
                                        : '<span class="badge bg-secondary bg-opacity-75">Inativa</span>'; ?>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($turma['nome']) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        <?= $turma['ano'] ?>/<?= $turma['semestre'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($turma['disciplina_nome']): ?>
                                        <i class="fa-solid fa-book me-1 text-primary"></i>
                                        <span class="text-dark"><?= htmlspecialchars($turma['disciplina_nome']) ?></span>
                                        <?php if($turma['disciplina_periodo']): ?>
                                            <br><small class="text-muted"><?= $turma['disciplina_periodo'] ?>º Período</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Sem disciplina</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($turma['aluno_nome']): ?>
                                        <i class="fa-solid fa-user-graduate me-1 text-success"></i>
                                        <span class="text-dark"><?= htmlspecialchars($turma['aluno_nome']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Sem monitor</span>
                                    <?php endif; ?>
                                </td>
                               <td class="text-end pe-3 text-nowrap">
                                    <button onclick="visualizarRegistro('turma', <?= $turma['id'] ?>)" class="btn btn-sm btn-outline-info me-1" title="Visualizar">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button onclick="abrirModal('form_turma.php?id=<?= $turma['id'] ?>')" class="btn btn-sm btn-outline-primary" title="Editar">
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

<!-- Modal de Visualização Genérico -->
<div class="modal fade" id="modalVisualizar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"> 
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light py-2">
        <h5 class="modal-title fs-6 fw-bold text-secondary"><i class="fa-solid fa-info-circle me-2"></i>Detalhes da Turma</h5>
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
        <h5 class="modal-title fs-6 fw-bold text-secondary"><i class="fa-solid fa-users me-2"></i>Cadastro de Turma</h5>
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

    function visualizarRegistro(tipo, id) {
        var modal = new bootstrap.Modal(document.getElementById('modalVisualizar'));
        modal.show();
        
        fetch('visualizar.php?tipo=' + tipo + '&id=' + id)
            .then(response => response.text())
            .then(data => {
                document.getElementById('conteudoVisualizar').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('conteudoVisualizar').innerHTML = 
                    '<div class="alert alert-danger">Erro ao carregar dados.</div>';
            });
    }

    document.getElementById('iframeEditor').onload = function() {
        try {
            if (this.contentWindow.location.href.indexOf('listar') !== -1) {
                fecharModal();
            }
        } catch (e) { }
    };
</script>