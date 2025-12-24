<?php
require_once 'conexao.php';

// Inicializa variáveis
$id = $_GET['id'] ?? null;
$mensagem = "";
$tipo_alerta = "";

// Dados iniciais (vazios para novo, ou preenchidos para edição)
$procedimento = [
    'nome' => '', 
    'codigo' => '', 
    'codigo_sus' => '',
    'qtd_necessario' => 1
];

// Se for edição, busca os dados atuais
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM procedimentos WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dados) {
        $procedimento = $dados;
    }
}

// Processamento do Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome           = $_POST['nome'];
    $codigo         = $_POST['codigo'];
    $codigo_sus     = $_POST['codigo_sus'];
    $qtd_necessario = $_POST['qtd_necessario'];

    try {
        if ($id) {
            // SQL de Atualização
            $sql = "UPDATE procedimentos SET nome=?, codigo=?, codigo_sus=?, qtd_necessario=?, modified=NOW() WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $codigo, $codigo_sus, $qtd_necessario, $id]);
        } else {
            // SQL de Inserção
            $sql = "INSERT INTO procedimentos (nome, codigo, codigo_sus, qtd_necessario, created) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $codigo, $codigo_sus, $qtd_necessario]);
        }
        
        header("Location: listar_procedimentos.php?status=sucesso");
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
                    <div class="col-md-12">
                        <label class="form-label"><i class="fa-solid fa-tooth me-1"></i> Nome do Procedimento</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($procedimento['nome']) ?>" required placeholder="Ex: Ação coletiva de escovação dental supervisionada">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-barcode me-1"></i> Código</label>
                        <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($procedimento['codigo']) ?>" required placeholder="0101020015">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-hashtag me-1"></i> Código SUS</label>
                        <input type="text" name="codigo_sus" class="form-control" value="<?= htmlspecialchars($procedimento['codigo_sus']) ?>" required placeholder="0101020015">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-sort-numeric-up me-1"></i> Quantidade Necessária</label>
                        <input type="number" name="qtd_necessario" class="form-control" value="<?= htmlspecialchars($procedimento['qtd_necessario']) ?>" required min="1" placeholder="1">
                        <small class="text-muted">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Número de vezes que o procedimento deve ser realizado
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