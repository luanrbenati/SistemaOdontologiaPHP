<?php
require_once 'conexao.php';

// Inicializa variáveis
$id = $_GET['id'] ?? null;
$mensagem = "";
$tipo_alerta = "";

// Dados iniciais (vazios para novo, ou preenchidos para edição)
$aluno = [
    'nome' => '', 
    'matricula' => '', 
    'ativo' => 1, 
    'telefone1' => '', 
    'telefone2' => '',
    'email' => ''
];

// Se for edição, busca os dados atuais
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dados) {
        $aluno = $dados;
    }
}

// Processamento do Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = $_POST['nome'];
    $matricula = $_POST['matricula'];
    $ativo     = isset($_POST['ativo']) ? 1 : 0; 
    $telefone1 = $_POST['telefone1'];
    $telefone2 = $_POST['telefone2'];
    $email     = $_POST['email'];

    try {
        if ($id) {
            // SQL de Atualização
            $sql = "UPDATE alunos SET nome=?, matricula=?, ativo=?, telefone1=?, telefone2=?, email=?, modified=NOW() WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $matricula, $ativo, $telefone1, $telefone2, $email, $id]);
        } else {
            // SQL de Inserção
            $sql = "INSERT INTO alunos (nome, matricula, ativo, telefone1, telefone2, email, created) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $matricula, $ativo, $telefone1, $telefone2, $email]);
        }
        
        header("Location: listar_alunos.php?status=sucesso");
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
        .form-control:focus { border-color: #3498db; box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15); }
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
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label"><i class="fa-solid fa-user me-1"></i> Nome Completo</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($aluno['nome']) ?>" required placeholder="Ex: João Silva">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-id-card me-1"></i> Matrícula</label>
                        <input type="text" name="matricula" class="form-control" value="<?= htmlspecialchars($aluno['matricula']) ?>" required placeholder="000000000">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label d-block"><i class="fa-solid fa-circle-check me-1"></i> Status do Aluno</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="ativo" value="1" 
                                   id="statusSwitch" <?= $aluno['ativo'] == 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="statusSwitch" id="statusLabel">
                                <?= $aluno['ativo'] == 1 ? 'Aluno Ativo' : 'Aluno Inativo' ?>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-phone me-1"></i> Telefone Principal</label>
                        <input type="text" id="tel1" name="telefone1" class="form-control" value="<?= htmlspecialchars($aluno['telefone1']) ?>" placeholder="(00) 00000-0000">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fa-solid fa-phone me-1"></i> Telefone Secundário</label>
                        <input type="text" id="tel2" name="telefone2" class="form-control" value="<?= htmlspecialchars($aluno['telefone2']) ?>" placeholder="(00) 0000-0000">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><i class="fa-solid fa-envelope me-1"></i> E-mail</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($aluno['email']) ?>" placeholder="exemplo@email.com">
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        // Máscara de Telefone Dinâmica
        var SPMaskBehavior = function (val) {
          return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
        },
        spOptions = {
          onKeyPress: function(val, e, field, options) {
              field.mask(SPMaskBehavior.apply({}, arguments), options);
            }
        };
        $('#tel1, #tel2').mask(SPMaskBehavior, spOptions);

        // Atualizar texto do Switch dinamicamente
        $('#statusSwitch').on('change', function() {
            if($(this).is(':checked')) {
                $('#statusLabel').text('Aluno Ativo');
            } else {
                $('#statusLabel').text('Aluno Inativo');
            }
        });
    });
</script>

</body>
</html>