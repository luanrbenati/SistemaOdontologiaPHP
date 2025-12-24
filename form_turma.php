<?php
require_once 'conexao.php';

$id = $_GET['id'] ?? null;
$mensagem = "";
$tipo_alerta = "";

$turma = [
    'nome' => '',
    'ano' => date('Y'),
    'semestre' => 1,
    'disciplina_id' => '',
    'ativo' => 1,
    'aluno_id' => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM turmas WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dados) {
        $turma = $dados;
    }
}

// Buscar disciplinas
$stmt_disc = $pdo->query("SELECT id, nome, periodo FROM disciplinas ORDER BY nome ASC");
$disciplinas = $stmt_disc->fetchAll(PDO::FETCH_ASSOC);

// Buscar alunos ativos
$stmt_aluno = $pdo->query("SELECT id, nome FROM alunos WHERE ativo = 1 ORDER BY nome ASC");
$alunos = $stmt_aluno->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $ano = $_POST['ano'];
    $semestre = $_POST['semestre'];
    $disciplina_id = $_POST['disciplina_id'] ?: null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $aluno_id = $_POST['aluno_id'] ?: null;

    try {
        if ($id) {
            $sql = "UPDATE turmas SET nome=?, ano=?, semestre=?, disciplina_id=?, ativo=?, aluno_id=?, modified=NOW() WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $ano, $semestre, $disciplina_id, $ativo, $aluno_id, $id]);
        } else {
            $sql = "INSERT INTO turmas (nome, ano, semestre, disciplina_id, ativo, aluno_id, created) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $ano, $semestre, $disciplina_id, $ativo, $aluno_id]);
        }
        
        header("Location: listar_turmas.php?status=sucesso");
        exit;
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .card { border: none; border-radius: 10px; }
        .form-label { font-weight: 600; color: #555; font-size: 0.85rem; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 6px; border: 1px solid #ced4da; padding: 0.5rem 0.75rem; }
        .form-control:focus, .form-select:focus { border-color: #3498db; box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15); }
        .btn-salvar { background-color: #3498db; border: none; padding: 8px 25px; font-weight: 600; }
        .btn-salvar:hover { background-color: #2980b9; }
        .form-check-input:checked { background-color: #3498db; border-color: #3498db; }
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if($mensagem): ?>
        <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
            <?= $mensagem ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label"><i class="fa-solid fa-users me-1"></i> Nome da Turma</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($turma['nome']) ?>" required placeholder="Ex: Estágio Supervisionado Clínica do Adulto I - TURMA...">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label d-block"><i class="fa-solid fa-circle-check me-1"></i> Status da Turma</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="ativo" value="1" 
                                   id="statusSwitch" <?= $turma['ativo'] == 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="statusSwitch" id="statusLabel">
                                <?= $turma['ativo'] == 1 ? 'Turma Ativa' : 'Turma Inativa' ?>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label"><i class="fa-solid fa-calendar me-1"></i> Ano</label>
                        <input type="number" name="ano" class="form-control" value="<?= htmlspecialchars($turma['ano']) ?>" required min="2000" max="2100" placeholder="2024">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label"><i class="fa-solid fa-calendar-days me-1"></i> Semestre</label>
                        <select name="semestre" class="form-select" required>
                            <option value="1" <?= $turma['semestre'] == 1 ? 'selected' : '' ?>>1º Semestre</option>
                            <option value="2" <?= $turma['semestre'] == 2 ? 'selected' : '' ?>>2º Semestre</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="fa-solid fa-book me-1"></i> Disciplina</label>
                        <select name="disciplina_id" class="form-select" required>
                            <option value="">Selecione uma disciplina...</option>
                            <?php foreach($disciplinas as $disc): ?>
                                <option value="<?= $disc['id'] ?>" <?= ($turma['disciplina_id'] == $disc['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($disc['nome']) ?>
                                    <?php if($disc['periodo']): ?>
                                        (<?= $disc['periodo'] ?>º Período)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><i class="fa-solid fa-user-graduate me-1"></i> Monitor (Aluno Responsável)</label>
                        <select name="aluno_id" class="form-select">
                            <option value="">Sem monitor atribuído</option>
                            <?php foreach($alunos as $aluno): ?>
                                <option value="<?= $aluno['id'] ?>" <?= ($turma['aluno_id'] == $aluno['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Aluno responsável por auxiliar na turma (opcional)
                        </small>
                    </div>

                    <div class="col-12 text-end mt-4 pt-3 border-top">
                        <button type="button" onclick="parent.fecharModal()" class="btn btn-light btn-sm me-2 text-secondary fw-bold">
                            CANCELAR
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm btn-salvar shadow-sm">
                            <i class="fa-solid fa-check me-1"></i> SALVAR DADOS
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Atualizar texto do Switch dinamicamente
    document.getElementById('statusSwitch').addEventListener('change', function() {
        if(this.checked) {
            document.getElementById('statusLabel').textContent = 'Turma Ativa';
        } else {
            document.getElementById('statusLabel').textContent = 'Turma Inativa';
        }
    });
</script>
</body>
</html>