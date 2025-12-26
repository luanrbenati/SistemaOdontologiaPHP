<?php
require_once 'conexao.php';

$id = $_GET['id'] ?? null;
$mensagem = "";
$tipo_alerta = "";

// Definição dos campos padrão para um novo professor
$professor = [
    'nome' => '',
    'siape' => '',
    'telefone1' => '',
    'telefone2' => '',
    'ativo' => 1
];

// Se houver ID, busca os dados do professor para edição
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM professores WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dados) {
        $professor = $dados;
    }
}

// Processamento do Formulário (Salvar/Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $siape = $_POST['siape'];
    $telefone1 = $_POST['telefone1'];
    $telefone2 = $_POST['telefone2'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    try {
        if ($id) {
            // Atualizar existente
            $sql = "UPDATE professores SET nome=?, siape=?, telefone1=?, telefone2=?, ativo=?, modified=NOW() WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $siape, $telefone1, $telefone2, $ativo, $id]);
        } else {
            // Criar novo
            $sql = "INSERT INTO professores (nome, siape, telefone1, telefone2, ativo, created) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $siape, $telefone1, $telefone2, $ativo]);
        }
        
        // Redireciona para a listagem (o script da listagem fecha o modal se detectar redirecionamento)
        header("Location: listar_professores.php?status=sucesso");
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
        /* Mesmos estilos do formulário de turmas */
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
                        <label class="form-label"><i class="fa-solid fa-user-tie me-1"></i> Nome do Professor</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($professor['nome']) ?>" required placeholder="Digite o nome completo...">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label d-block"><i class="fa-solid fa-circle-check me-1"></i> Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="ativo" value="1" 
                                   id="statusSwitch" <?= $professor['ativo'] == 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="statusSwitch" id="statusLabel">
                                <?= $professor['ativo'] == 1 ? 'Professor Ativo' : 'Professor Inativo' ?>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-id-card me-1"></i> SIAPE</label>
                        <input type="text" name="siape" class="form-control" value="<?= htmlspecialchars($professor['siape']) ?>" required placeholder="Código SIAPE">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-phone me-1"></i> Telefone Principal</label>
                        <input type="text" name="telefone1" class="form-control" value="<?= htmlspecialchars($professor['telefone1']) ?>" placeholder="(99) 99999-9999">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-mobile-screen me-1"></i> Telefone Secundário</label>
                        <input type="text" name="telefone2" class="form-control" value="<?= htmlspecialchars($professor['telefone2']) ?>" placeholder="(99) 99999-9999">
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
            document.getElementById('statusLabel').textContent = 'Professor Ativo';
        } else {
            document.getElementById('statusLabel').textContent = 'Professor Inativo';
        }
    });
</script>
</body>
</html>