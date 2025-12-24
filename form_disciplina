<?php
require_once 'conexao.php';

// Inicializa variáveis
$id = $_GET['id'] ?? null;
$mensagem = "";
$tipo_alerta = "";

// Dados iniciais (vazios para novo, ou preenchidos para edição)
$disciplina = [
    'nome' => '', 
    'periodo' => '', 
    'professor_id' => ''
];

// Se for edição, busca os dados atuais
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM disciplinas WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dados) {
        $disciplina = $dados;
    }
}

// Buscar lista de professores para o select
$stmt_prof = $pdo->query("SELECT id, nome FROM professores WHERE ativo = 1 ORDER BY nome ASC");
$professores = $stmt_prof->fetchAll(PDO::FETCH_ASSOC);

// Processamento do Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome         = $_POST['nome'];
    $periodo      = $_POST['periodo'];
    $professor_id = $_POST['professor_id'] ?: null; // Se vazio, grava NULL

    try {
        if ($id) {
            // SQL de Atualização
            $sql = "UPDATE disciplinas SET nome=?, periodo=?, professor_id=?, modified=NOW() WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $periodo, $professor_id, $id]);
        } else {
            // SQL de Inserção
            $sql = "INSERT INTO disciplinas (nome, periodo, professor_id, created) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $periodo, $professor_id]);
        }
        
        header("Location: listar_disciplinas.php?status=sucesso");
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
    </style>
</head>
<body>

<div class="container-fluid">
    <?php if($mensagem): ?>
        <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
            <?= $mensagem ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label"><i class="fa-solid fa-book me-1"></i> Nome da Disciplina</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($disciplina['nome']) ?>" required placeholder="Ex: Estágio Supervisionado Clínica do Adulto I">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-calendar-days me-1"></i> Período</label>
                        <select name="periodo" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php for($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>" <?= ($disciplina['periodo'] == $i) ? 'selected' : '' ?>>
                                    <?= $i ?>º Período
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><i class="fa-solid fa-chalkboard-user me-1"></i> Professor Responsável</label>
                        <select name="professor_id" class="form-select">
                            <option value="">Sem professor atribuído</option>
                            <?php foreach($professores as $prof): ?>
                                <option value="<?= $prof['id'] ?>" <?= ($disciplina['professor_id'] == $prof['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prof['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Apenas professores ativos aparecem nesta lista
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
</body>
</html>