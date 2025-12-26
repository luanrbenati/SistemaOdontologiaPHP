<?php
session_start();

require_once 'conexao.php';

// === GERAÇÃO DO TOKEN CSRF ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_paciente = $_GET['id'] ?? null;
$mensagem = "";

// === PROCESSAMENTO DE FORMULÁRIOS ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erro de segurança: Token inválido! Atualize a página e tente novamente.");
    }

    if (isset($_POST['acao'])) {
    
        // UPLOAD DE FOTO
        if ($_POST['acao'] == 'upload_foto' && isset($_FILES['arquivo_foto'])) {
            $id_alvo = $_POST['id_hidden'] ?? $id_paciente;
            
            if ($id_alvo && $_FILES['arquivo_foto']['error'] == 0) {
                try {
                    $arquivo_tmp = $_FILES['arquivo_foto']['tmp_name'];
                    
                    list($largura_orig, $altura_orig, $tipo) = getimagesize($arquivo_tmp);
                    
                    switch ($tipo) {
                        case IMAGETYPE_JPEG: $origem = imagecreatefromjpeg($arquivo_tmp); break;
                        case IMAGETYPE_PNG:  $origem = imagecreatefrompng($arquivo_tmp); break;
                        case IMAGETYPE_GIF:  $origem = imagecreatefromgif($arquivo_tmp); break;
                        default: throw new Exception("Formato de imagem não suportado.");
                    }

                    // Correção de rotação EXIF
                    if ($tipo == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                        $exif = @exif_read_data($arquivo_tmp);
                        if (!empty($exif['Orientation'])) {
                            switch ($exif['Orientation']) {
                                case 3: $origem = imagerotate($origem, 180, 0); break;
                                case 6: $origem = imagerotate($origem, -90, 0); break;
                                case 8: $origem = imagerotate($origem, 90, 0); break;
                            }
                        }
                    }

                    $largura_rotacionada = imagesx($origem);
                    $altura_rotacionada = imagesy($origem);
                    $max_largura = 800;
                    
                    if ($largura_rotacionada > $max_largura) {
                        $ratio = $max_largura / $largura_rotacionada;
                        $nova_largura = $max_largura;
                        $nova_altura = $altura_rotacionada * $ratio;
                    } else {
                        $nova_largura = $largura_rotacionada;
                        $nova_altura = $altura_rotacionada;
                    }

                    $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);

                    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
                        imagealphablending($nova_imagem, false);
                        imagesavealpha($nova_imagem, true);
                        $transparent = imagecolorallocatealpha($nova_imagem, 255, 255, 255, 127);
                        imagefilledrectangle($nova_imagem, 0, 0, $nova_largura, $nova_altura, $transparent);
                    }

                    imagecopyresampled($nova_imagem, $origem, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_rotacionada, $altura_rotacionada);
                    
                    ob_start();
                    imagejpeg($nova_imagem, null, 80);
                    $conteudo_foto = ob_get_clean();
                    
                    imagedestroy($nova_imagem);
                    imagedestroy($origem);

                    $stmt = $pdo->prepare("UPDATE pacientes SET foto = ? WHERE id = ?");
                    $stmt->execute([$conteudo_foto, $id_alvo]);
                    
                    echo "<script>window.location.href='?page=pacientes&id=$id_alvo';</script>";
                    exit;

                } catch (Exception $e) { 
                    $mensagem = "Erro ao enviar foto: " . $e->getMessage(); 
                }
            }
        }

        // REMOVER FOTO
        if ($_POST['acao'] == 'remover_foto') {
            $id_alvo = $_POST['id_hidden'] ?? $id_paciente;
            if ($id_alvo) {
                $stmt = $pdo->prepare("UPDATE pacientes SET foto = NULL WHERE id = ?");
                $stmt->execute([$id_alvo]);
                echo "<script>window.location.href='?page=pacientes&id=$id_alvo';</script>";
                exit;
            }
        }

        // SALVAR DADOS DO FORMULÁRIO
        if ($_POST['acao'] == 'salvar_dados') {
            $id_para_salvar = $_GET['id'] ?? $_POST['id_hidden'] ?? 0;
            try {
                $sql = "UPDATE pacientes SET 
                        nome=?, data_nascimento=?, cpf=?, sexo=?, telefone1=?, telefone2=?, 
                        email=?, rua=?, numero=?, bairro=?, cidade=?, estado_id=?, cep=?, 
                        complemento=?, tipo_residencia=?, obs=?, pediatria=?, responsavel=? 
                        WHERE id=?";
                
                $stmt = $pdo->prepare($sql);
                
                $pediatria = $_POST['pediatria'] ?? 0;
                $responsavel = ($pediatria == 1) ? ($_POST['responsavel'] ?? null) : null;

                $stmt->execute([
                    $_POST['nome'], $_POST['data_nascimento'], $_POST['cpf'], $_POST['sexo'] ?? null,
                    $_POST['telefone1'], $_POST['telefone2'], $_POST['email'] ?? null,
                    $_POST['rua'], $_POST['numero'], $_POST['bairro'], $_POST['cidade'], 
                    $_POST['estado_id'], $_POST['cep'], $_POST['complemento'], 
                    $_POST['tipo_residencia'], $_POST['obs'], 
                    $pediatria, $responsavel,
                    $id_para_salvar
                ]);
                
                $mensagem = "Dados atualizados com sucesso!";
                $id_paciente = $id_para_salvar; 
            } catch (Exception $e) {
                $mensagem = "Erro ao salvar: " . $e->getMessage();
            }
        }
        
        // NOVA ANOTAÇÃO CLÍNICA
        if ($_POST['acao'] == 'nova_anotacao') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO odontograma_anotacoes (paciente_id, dente, anotacao) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['id_hidden'],
                    $_POST['dente'] ?? null,
                    $_POST['anotacao']
                ]);
                $mensagem = "Anotação adicionada com sucesso!";
            } catch (Exception $e) {
                $mensagem = "Erro ao salvar anotação: " . $e->getMessage();
            }
        }
    }
}

// === CARREGAR DADOS DO PACIENTE ===
if (!$id_paciente) {
    $stmt = $pdo->query("SELECT * FROM pacientes ORDER BY nome ASC LIMIT 1");
    $paciente = $stmt->fetch();
    if($paciente) $id_paciente = $paciente['id'];
} else {
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$id_paciente]);
    $paciente = $stmt->fetch();
    
    if (!$paciente) {
        $stmt = $pdo->query("SELECT * FROM pacientes ORDER BY nome ASC LIMIT 1");
        $paciente = $stmt->fetch();
        if($paciente) $id_paciente = $paciente['id'];
    }
}

// === NAVEGAÇÃO ===
$prev_id = null; 
$next_id = null;
if (!empty($paciente)) {
    $nome = $paciente['nome'];
    $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE nome < ? ORDER BY nome DESC LIMIT 1");
    $stmt->execute([$nome]);
    $prev_id = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE nome > ? ORDER BY nome ASC LIMIT 1");
    $stmt->execute([$nome]);
    $next_id = $stmt->fetchColumn();
}

// === CARREGAR PLANOS DE TRATAMENTO ===
$planos_tratamento = [];
if (!empty($id_paciente)) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM plano_tratamento 
            WHERE paciente_id = ? 
            ORDER BY 
                FIELD(prioridade, 'urgente', 'alta', 'media', 'baixa'),
                data_planejada ASC
        ");
        $stmt->execute([$id_paciente]);
        $planos_tratamento = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao carregar planos: " . $e->getMessage());
    }
}

// === CALCULAR RESUMO FINANCEIRO ===
$resumo_financeiro = ['total_aberto' => 0, 'total_pago' => 0, 'total_geral' => 0];
if (!empty($id_paciente)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN status IN ('planejado', 'em_andamento') THEN custo ELSE 0 END) as aberto,
                SUM(CASE WHEN status = 'concluido' THEN custo ELSE 0 END) as pago,
                SUM(custo) as total
            FROM plano_tratamento 
            WHERE paciente_id = ? AND status != 'cancelado'
        ");
        $stmt->execute([$id_paciente]);
        $row = $stmt->fetch();
        if ($row) {
            $resumo_financeiro = [
                'total_aberto' => floatval($row['aberto']),
                'total_pago' => floatval($row['pago']),
                'total_geral' => floatval($row['total'])
            ];
        }
    } catch (PDOException $e) {
        error_log("Erro ao calcular resumo: " . $e->getMessage());
    }
}
?>

<style>
    body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    
    .patient-banner { background-color: #3498db; color: white; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
    
    .avatar-circle { 
        width: 60px; height: 60px; background-color: #eee; border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; font-size: 24px; 
        margin-right: 15px; border: 3px solid white; overflow: hidden; cursor: pointer; 
        position: relative; transition: transform 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .avatar-circle:hover { transform: scale(1.05); border-color: #e2e6ea; }
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

    .btn-nav-arrow { color: white; opacity: 0.8; transition: 0.3s; font-size: 1.5rem; text-decoration: none; }
    .btn-nav-arrow:hover { color: white; opacity: 1; transform: scale(1.1); }

    .nav-tabs { border-bottom: 1px solid #dee2e6; background: #fff; padding-left: 20px; padding-top: 10px; }
    .nav-tabs .nav-link { 
        color: #7f8c8d; border: none; border-bottom: 3px solid transparent;
        font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 10px 15px;
    }
    .nav-tabs .nav-link:hover { color: #3498db; }
    .nav-tabs .nav-link.active { color: #3498db; border-bottom: 3px solid #3498db; background: transparent; }

    .tab-content { background-color: #f4f6f9; min-height: 500px; }
    .tab-pane { display: none; }
    .tab-pane.active.show { display: block; }
    #tab-informacoes { padding: 1rem 1.5rem; }
    #tab-odontograma { padding: 0 !important; margin: 0 !important; }
    #tab-financeiro { padding: 1rem 1.5rem; }
    #tab-anotacoes { padding: 1rem 1.5rem; }

    .card-custom { border: none; box-shadow: 0 0 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .card-header-custom { background-color: white; border-bottom: 1px solid #eee; font-weight: bold; padding: 12px 15px; font-size: 0.95rem; }
    .form-label { font-size: 0.85rem; font-weight: bold; color: #333; margin-bottom: 2px; }
    .form-control, .form-select { font-size: 0.9rem; background-color: #fff; padding: 0.4rem 0.7rem; }
    .row.g-3 { --bs-gutter-y: 0.8rem; --bs-gutter-x: 0.8rem; }
    
    .badge-prioridade { font-size: 0.7rem; padding: 4px 8px; }
    .prioridade-urgente { background-color: #dc3545; }
    .prioridade-alta { background-color: #fd7e14; }
    .prioridade-media { background-color: #ffc107; }
    .prioridade-baixa { background-color: #6c757d; }
    
    .badge-status { font-size: 0.7rem; padding: 4px 8px; }
    .status-planejado { background-color: #6c757d; }
    .status-em_andamento { background-color: #0dcaf0; }
    .status-concluido { background-color: #198754; }
    .status-cancelado { background-color: #dc3545; }
</style>

<div class="container-fluid p-0">
    <div class="p-3">
        <h4 class="mb-0 text-dark">Pacientes <small class="text-muted fs-6">Prontuário Eletrônico</small></h4>
    </div>
    
    <div class="patient-banner">
        <div class="d-flex align-items-center">
            <?php if($prev_id): ?>
                <a href="?page=pacientes&id=<?php echo $prev_id; ?>" class="btn-nav-arrow me-3"><i class="fa-solid fa-chevron-left"></i></a>
            <?php else: ?>
                <span class="me-3 opacity-25 btn-nav-arrow"><i class="fa-solid fa-chevron-left"></i></span>
            <?php endif; ?>
            
            <div class="avatar-circle" onclick="gerenciarFoto(<?php echo !empty($paciente['foto']) ? 'true' : 'false'; ?>)">
                <?php if (!empty($paciente['foto'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($paciente['foto']); ?>" alt="Foto">
                <?php else: ?>
                    <i class="fa-regular fa-user text-secondary"></i>
                <?php endif; ?>
            </div>

            <h4 class="m-0 fw-light"><?php echo htmlspecialchars($paciente['nome'] ?? 'Sem dados'); ?></h4>
        </div>

        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-light text-primary btn-sm me-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalBusca">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Pesquisar
            </button>
            <?php if($next_id): ?>
                <a href="?page=pacientes&id=<?php echo $next_id; ?>" class="btn-nav-arrow"><i class="fa-solid fa-chevron-right"></i></a>
            <?php else: ?>
                <span class="opacity-25 btn-nav-arrow"><i class="fa-solid fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
    </div>

    <ul class="nav nav-tabs" id="prontuarioTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-informacoes">INFORMAÇÕES</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-odontograma">ODONTOGRAMA</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-financeiro">FINANCEIRO</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-anotacoes">ANOTAÇÕES</button>
        </li>
    </ul>

    <?php if($mensagem): ?>
        <div class="alert alert-info alert-dismissible fade show m-3 py-2">
            <?php echo htmlspecialchars($mensagem); ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="tab-content">
        
        <!-- TAB INFORMAÇÕES -->
        <div class="tab-pane fade show active" id="tab-informacoes">
            <?php if (!empty($paciente)): ?>
            <form action="?page=pacientes&id=<?php echo $id_paciente; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="acao" value="salvar_dados">
                <input type="hidden" name="id_hidden" value="<?php echo $id_paciente; ?>">

                <div class="row">
                    <div class="col-lg-7">
                        <div class="card card-custom bg-white">
                            <div class="card-header-custom text-dark">
                                <i class="fa-solid fa-user-pen me-1 text-secondary"></i> Dados Pessoais
                            </div>
                            <div class="card-body">
                               <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Nome Completo</label>
                                        <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($paciente['nome']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Celular</label>
                                        <input type="text" name="telefone1" class="form-control" value="<?php echo htmlspecialchars($paciente['telefone1'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Fixo</label>
                                        <input type="text" name="telefone2" class="form-control" value="<?php echo htmlspecialchars($paciente['telefone2'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Aniversário</label>
                                        <input type="date" name="data_nascimento" class="form-control" value="<?php echo $paciente['data_nascimento']; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">CPF</label>
                                        <input type="text" name="cpf" class="form-control" value="<?php echo htmlspecialchars($paciente['cpf'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Gênero</label>
                                        <select name="sexo" class="form-select">
                                            <option value="">Selecione...</option>
                                            <option value="1" <?php echo ($paciente['sexo'] == 1) ? 'selected' : ''; ?>>Feminino</option>
                                            <option value="2" <?php echo ($paciente['sexo'] == 2) ? 'selected' : ''; ?>>Masculino</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">E-mail</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($paciente['email'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-12 mt-4 mb-2">
                                        <div class="d-flex align-items-center text-secondary">
                                            <i class="fa-solid fa-map-location-dot me-2 fs-5"></i>
                                            <h6 class="m-0 fw-bold text-uppercase" style="letter-spacing: 0.5px;">Endereço</h6>
                                            <hr class="flex-grow-1 ms-3 opacity-25">
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">CEP</label>
                                        <input type="text" name="cep" class="form-control" value="<?php echo htmlspecialchars($paciente['cep'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tipo</label>
                                        <select name="tipo_residencia" class="form-select">
                                            <option value="Res." <?php echo (($paciente['tipo_residencia'] ?? '') == 'Res.') ? 'selected' : ''; ?>>Residencial</option>
                                            <option value="Com." <?php echo (($paciente['tipo_residencia'] ?? '') == 'Com.') ? 'selected' : ''; ?>>Comercial</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6"></div>
                                    <div class="col-md-10">
                                        <label class="form-label">Logradouro</label>
                                        <input type="text" name="rua" class="form-control" value="<?php echo htmlspecialchars($paciente['rua'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Nº</label>
                                        <input type="text" name="numero" class="form-control" value="<?php echo htmlspecialchars($paciente['numero'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Bairro</label>
                                        <input type="text" name="bairro" class="form-control" value="<?php echo htmlspecialchars($paciente['bairro'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cidade</label>
                                        <input type="text" name="cidade" class="form-control" value="<?php echo htmlspecialchars($paciente['cidade'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">UF</label>
                                        <select name="estado_id" class="form-select">
                                            <option value="">UF</option>
                                            <option value="1" <?php echo ($paciente['estado_id'] == 1) ? 'selected' : ''; ?>>SP</option>
                                            <option value="2" <?php echo ($paciente['estado_id'] == 2) ? 'selected' : ''; ?>>RJ</option>
                                            <option value="3" <?php echo ($paciente['estado_id'] == 3) ? 'selected' : ''; ?>>MG</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Complemento</label>
                                        <input type="text" name="complemento" class="form-control" value="<?php echo htmlspecialchars($paciente['complemento'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card card-custom bg-white">
                            <div class="card-header-custom text-dark">
                                <i class="fa-solid fa-file-medical me-1 text-secondary"></i> Informações Clínicas
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Anamnese / Histórico Médico</label>
                                    <textarea name="obs" class="form-control" rows="6"><?php echo htmlspecialchars($paciente['obs'] ?? ''); ?></textarea>
                                    <small class="text-muted">Alergias, medicamentos em uso, condições pré-existentes, etc.</small>
                                </div>

                                <div class="row g-2 mb-3 bg-light p-3 rounded border">
                                    <div class="col-md-4">
                                        <label class="form-label">É Pediatria?</label>
                                        <select name="pediatria" id="comboPediatria" class="form-select" onchange="verificarPediatria()">
                                            <option value="0" <?php echo (($paciente['pediatria'] ?? 0) == 0) ? 'selected' : ''; ?>>Não</option>
                                            <option value="1" <?php echo (($paciente['pediatria'] ?? 0) == 1) ? 'selected' : ''; ?>>Sim</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nome do Responsável</label>
                                        <input type="text" name="responsavel" id="campoResponsavel" class="form-control" 
                                               value="<?php echo htmlspecialchars($paciente['responsavel'] ?? ''); ?>" disabled>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                    <i class="fa-solid fa-check me-2"></i> Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <!-- TAB ODONTOGRAMA -->
        <div class="tab-pane fade" id="tab-odontograma">
            <?php if(file_exists('odontograma.php')) include 'odontograma.php'; else echo "<div class='p-3'>Arquivo odontograma.php não encontrado</div>"; ?>
        </div>

        <!-- TAB FINANCEIRO -->
        <div class="tab-pane fade" id="tab-financeiro">
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-custom">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-file-invoice-dollar me-2"></i> Plano de Tratamento</span>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoPlano">
                                <i class="fa-solid fa-plus me-1"></i> Novo Procedimento
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($planos_tratamento)): ?>
                                <div class="text-center text-muted p-5">
                                    <i class="fa-solid fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                                    <p>Nenhum procedimento planejado ainda.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 100px;">Data</th>
                                                <th>Procedimento</th>
                                                <th style="width: 80px;">Dente</th>
                                                <th style="width: 100px;">Valor</th>
                                                <th style="width: 100px;">Prioridade</th>
                                                <th style="width: 120px;">Status</th>
                                                <th style="width: 80px;">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($planos_tratamento as $plano): ?>
                                                <tr>
                                                    <td class="small"><?php echo date('d/m/Y', strtotime($plano['data_planejada'])); ?></td>
                                                    <td><?php echo htmlspecialchars($plano['procedimento']); ?></td>
                                                    <td class="text-center"><?php echo $plano['dente'] ?: '-'; ?></td>
                                                    <td class="fw-bold">R$ <?php echo number_format($plano['custo'], 2, ',', '.'); ?></td>
                                                    <td>
                                                        <span class="badge badge-prioridade prioridade-<?php echo $plano['prioridade']; ?>">
                                                            <?php echo ucfirst($plano['prioridade']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-status status-<?php echo $plano['status']; ?>">
                                                            <?php echo str_replace('_', ' ', ucfirst($plano['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editarPlano(<?php echo $plano['id']; ?>)">
                                                            <i class="fa-solid fa-pen"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        </table>
                                </div>
                            <?php endif; ?> </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-custom">
                        <div class="card-header-custom">
                            <i class="fa-solid fa-coins me-2 text-secondary"></i> Resumo Financeiro
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Tratamentos em Aberto
                                    <span class="badge bg-warning text-dark">R$ <?php echo number_format($resumo_financeiro['total_aberto'], 2, ',', '.'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Total Pago
                                    <span class="badge bg-success">R$ <?php echo number_format($resumo_financeiro['total_pago'], 2, ',', '.'); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 fw-bold">
                                    Total Geral
                                    <span>R$ <?php echo number_format($resumo_financeiro['total_geral'], 2, ',', '.'); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-anotacoes">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <i class="fa-solid fa-notes-medical me-2"></i> Histórico e Anotações
                </div>
                <div class="card-body">
                    <form method="post" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="acao" value="nova_anotacao">
                        <input type="hidden" name="id_hidden" value="<?php echo $id_paciente; ?>">
                        
                        <div class="row g-2">
                            <div class="col-md-2">
                                <input type="number" name="dente" class="form-control form-control-sm" placeholder="Dente">
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="anotacao" class="form-control form-control-sm" placeholder="Escreva a anotação aqui..." required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Adicionar</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="text-muted small text-center p-3">
                        Histórico de anotações será listado aqui.
                    </div>
                </div>
            </div>
        </div>

    </div> </div> <script>
    function verificarPediatria() {
        var combo = document.getElementById('comboPediatria');
        var campoResp = document.getElementById('campoResponsavel');
        
        if (combo && campoResp) {
            if (combo.value == '1') {
                campoResp.removeAttribute('disabled');
                campoResp.setAttribute('required', 'required');
            } else {
                campoResp.setAttribute('disabled', 'disabled');
                campoResp.removeAttribute('required');
                campoResp.value = '';
            }
        }
    }

    function gerenciarFoto(temFoto) {
        // Lógica simples para upload de foto via JS se necessário, 
        // ou você pode criar um modal Bootstrap com id "modalFoto"
        alert("Funcionalidade de foto: Use o formulário ou implemente um modal aqui.");
    }

    function editarPlano(id) {
        // Lógica para abrir modal de edição
        console.log("Editar plano: " + id);
    }

    // Inicializa scripts ao carregar
    document.addEventListener('DOMContentLoaded', function() {
        verificarPediatria();
        
        // Ativa as tabs do Bootstrap (caso não esteja usando o data-bs-toggle automático corretamente)
        var triggerTabList = [].slice.call(document.querySelectorAll('#prontuarioTabs button'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
            })
        })
    });
</script>