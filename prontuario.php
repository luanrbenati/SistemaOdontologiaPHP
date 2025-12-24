<?php
global $pdo;

// === 1. CONEXÃO COM BANCO ===
if (!isset($pdo)) {
    $host = 'localhost'; $db = 'srv_odonto'; $user = 'root'; $pass = 'qwe123!@#';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (\PDOException $e) { die("Erro de Conexão: " . $e->getMessage()); }
}

$id_paciente = $_GET['id'] ?? null;
$mensagem = "";

// === 2. LÓGICA DE PROCESSAMENTO (POST) ===
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao'])) {
    
    // A) UPLOAD DE FOTO (COM CORREÇÃO DE ROTAÇÃO E COMPRESSÃO)
    if ($_POST['acao'] == 'upload_foto' && isset($_FILES['arquivo_foto'])) {
        $id_alvo = $_POST['id_hidden'] ?? $id_paciente;
        
        if ($id_alvo && $_FILES['arquivo_foto']['error'] == 0) {
            try {
                $arquivo_tmp = $_FILES['arquivo_foto']['tmp_name'];
                
                // 1. Pega informações básicas
                list($largura_orig, $altura_orig, $tipo) = getimagesize($arquivo_tmp);
                
                // 2. Carrega a imagem para a memória do PHP
                switch ($tipo) {
                    case IMAGETYPE_JPEG: $origem = imagecreatefromjpeg($arquivo_tmp); break;
                    case IMAGETYPE_PNG:  $origem = imagecreatefrompng($arquivo_tmp); break;
                    case IMAGETYPE_GIF:  $origem = imagecreatefromgif($arquivo_tmp); break;
                    default: throw new Exception("Formato de imagem não suportado.");
                }

                // --- CORREÇÃO DE ROTAÇÃO (O Segredo!) ---
                // Se for JPEG, tenta ler os dados EXIF de orientação
                if ($tipo == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($arquivo_tmp);
                    if (!empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3:
                                $origem = imagerotate($origem, 180, 0); break;
                            case 6:
                                $origem = imagerotate($origem, -90, 0); break; // Gira 90º sentido horário
                            case 8:
                                $origem = imagerotate($origem, 90, 0); break;  // Gira 90º anti-horário
                        }
                    }
                }
                // -----------------------------------------

                // 3. Recalcula dimensões após possível rotação (pois a largura pode ter virado altura)
                $largura_rotacionada = imagesx($origem);
                $altura_rotacionada = imagesy($origem);

                // 4. Define nova largura máxima (ex: 800px) para compressão
                $max_largura = 800;
                
                if ($largura_rotacionada > $max_largura) {
                    $ratio = $max_largura / $largura_rotacionada;
                    $nova_largura = $max_largura;
                    $nova_altura = $altura_rotacionada * $ratio;
                } else {
                    $nova_largura = $largura_rotacionada;
                    $nova_altura = $altura_rotacionada;
                }

                // 5. Cria a nova imagem redimensionada
                $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);

                // Mantém transparência se for PNG/GIF (boa prática)
                if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
                    imagealphablending($nova_imagem, false);
                    imagesavealpha($nova_imagem, true);
                    $transparent = imagecolorallocatealpha($nova_imagem, 255, 255, 255, 127);
                    imagefilledrectangle($nova_imagem, 0, 0, $nova_largura, $nova_altura, $transparent);
                }

                // Copia e redimensiona
                imagecopyresampled($nova_imagem, $origem, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_rotacionada, $altura_rotacionada);
                
                // 6. Gera o arquivo final comprimido
                ob_start(); // Liga o buffer
                imagejpeg($nova_imagem, null, 80); // Qualidade 80
                $conteudo_foto = ob_get_clean(); // Pega o conteúdo
                
                // Limpa memória
                imagedestroy($nova_imagem);
                imagedestroy($origem);

                // Salva no Banco
                $stmt = $pdo->prepare("UPDATE pacientes SET foto = ? WHERE id = ?");
                $stmt->execute([$conteudo_foto, $id_alvo]);
                
                echo "<script>window.location.href='?page=pacientes&id=$id_alvo';</script>";
                exit;

            } catch (Exception $e) { $mensagem = "Erro ao enviar foto: " . $e->getMessage(); }
        }
    }

    // B) REMOVER FOTO (CORRIGIDO COM JAVASCRIPT)
    if ($_POST['acao'] == 'remover_foto') {
        $id_alvo = $_POST['id_hidden'] ?? $id_paciente;
        if ($id_alvo) {
            $stmt = $pdo->prepare("UPDATE pacientes SET foto = NULL WHERE id = ?");
            $stmt->execute([$id_alvo]);
            
            // MUDANÇA AQUI: Usamos JS em vez de header()
            echo "<script>window.location.href='?page=pacientes&id=$id_alvo';</script>";
            exit;
        }
    }

    // C) SALVAR DADOS DO FORMULÁRIO
    if ($_POST['acao'] == 'salvar_dados') {
        $id_para_salvar = $_GET['id'] ?? $_POST['id_hidden'] ?? 0;
        try {
            $sql = "UPDATE pacientes SET 
                    nome=?, data_nascimento=?, cpf=?, sexo=?, telefone1=?, telefone2=?, 
                    rua=?, numero=?, bairro=?, cidade=?, estado_id=?, cep=?, complemento=?, 
                    tipo_residencia=?, obs=?, pediatria=?, responsavel=? 
                    WHERE id=?";
            
            $stmt = $pdo->prepare($sql);
            
            $pediatria = $_POST['pediatria'] ?? 0;
            $responsavel = ($pediatria == 1) ? ($_POST['responsavel'] ?? null) : null;

            $stmt->execute([
                $_POST['nome'], $_POST['data_nascimento'], $_POST['cpf'], $_POST['sexo'] ?? null,
                $_POST['telefone1'], $_POST['telefone2'], 
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
}

// === 3. CARREGAR DADOS DO PACIENTE ===
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

// === 4. NAVEGAÇÃO ===
$prev_id = null; $next_id = null;
if (!empty($paciente)) {
    $nome = $paciente['nome'];
    $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE nome < ? ORDER BY nome DESC LIMIT 1");
    $stmt->execute([$nome]);
    $prev_id = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE nome > ? ORDER BY nome ASC LIMIT 1");
    $stmt->execute([$nome]);
    $next_id = $stmt->fetchColumn();
}
?>

<style>
    /* Estilo Geral */
    body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    
    /* Topo e Banner */
    .patient-banner { background-color: #3498db; color: white; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
    
    /* Avatar / Foto */
    .avatar-circle { 
        width: 60px; height: 60px; 
        background-color: #eee; 
        border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 24px; margin-right: 15px; 
        border: 3px solid white; 
        overflow: hidden; 
        cursor: pointer; 
        position: relative;
        transition: transform 0.2s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .avatar-circle:hover { transform: scale(1.05); border-color: #e2e6ea; }
    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

    .btn-nav-arrow { color: white; opacity: 0.8; transition: 0.3s; font-size: 1.5rem; text-decoration: none; }
    .btn-nav-arrow:hover { color: white; opacity: 1; transform: scale(1.1); }

    /* Tabs */
    .nav-tabs { border-bottom: 1px solid #dee2e6; background: #fff; padding-left: 20px; padding-top: 10px; }
    .nav-tabs .nav-link { 
        color: #7f8c8d; border: none; border-bottom: 3px solid transparent;
        font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 10px 15px;
    }
    .nav-tabs .nav-link:hover { color: #3498db; }
    .nav-tabs .nav-link.active { color: #3498db; border-bottom: 3px solid #3498db; background: transparent; }

    /* Layout */
    .tab-content { background-color: #f4f6f9; min-height: 500px; }
    .tab-pane { display: none; }
    .tab-pane.active.show { display: block; }
    #tab-informacoes { padding: 1rem 1.5rem; }
    #tab-odontograma { padding: 0 !important; margin: 0 !important; }
    #tab-financeiro { padding: 1rem 1.5rem; }

    /* Cards e Forms */
    .card-custom { border: none; box-shadow: 0 0 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .card-header-custom { background-color: white; border-bottom: 1px solid #eee; font-weight: bold; padding: 12px 15px; font-size: 0.95rem; }
    .form-label { font-size: 0.85rem; font-weight: bold; color: #333; margin-bottom: 2px; }
    .form-control, .form-select { font-size: 0.9rem; background-color: #fff; padding: 0.4rem 0.7rem; }
    .row.g-3 { --bs-gutter-y: 0.8rem; --bs-gutter-x: 0.8rem; } 
</style>

<div class="container-fluid p-0">
    <div class="p-3">
        <h4 class="mb-0 text-dark">Pacientes <small class="text-muted fs-6">Prontuário do paciente</small></h4>
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
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-financeiro">FINANCEIRO</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-odontograma">ODONTOGRAMA</button>
        </li>
    </ul>

    <?php if($mensagem): ?>
        <div class="alert alert-info alert-dismissible fade show m-3 py-2">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="tab-content">
        
        <div class="tab-pane fade show active" id="tab-informacoes">
            <?php if (!empty($paciente)): ?>
            <form action="?page=pacientes&id=<?php echo $id_paciente; ?>" method="POST">
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
                                        <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($paciente['nome']); ?>">
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
                                        <input type="email" class="form-control" placeholder="">
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
                                <i class="fa-solid fa-file-medical me-1 text-secondary"></i> Informações Gerais
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Observações Clínicas / Anamnese</label>
                                    <textarea name="obs" class="form-control" rows="6"><?php echo htmlspecialchars($paciente['obs'] ?? ''); ?></textarea>
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

        <div class="tab-pane fade" id="tab-financeiro">
            <div class="p-5 text-center text-muted">
                <i class="fa-solid fa-money-bill-wave fa-3x mb-3 opacity-25"></i>
                <p>Módulo Financeiro em desenvolvimento.</p>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-odontograma">
            <?php if(file_exists('odontograma.php')) include 'odontograma.php'; else echo "<div class='p-3'>Arquivo odontograma.php não encontrado</div>"; ?>
        </div>
    </div>
</div>

<div style="display:none;">
    
    <form id="formCamera" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="upload_foto">
        <input type="hidden" name="id_hidden" value="<?php echo $id_paciente; ?>">
        <input type="file" name="arquivo_foto" id="inputCamera" accept="image/*" capture="environment" onchange="this.form.submit()">
    </form>

    <form id="formGaleria" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="upload_foto">
        <input type="hidden" name="id_hidden" value="<?php echo $id_paciente; ?>">
        <input type="file" name="arquivo_foto" id="inputGaleria" accept="image/*" onchange="this.form.submit()">
    </form>

    <form id="formRemoverFoto" method="POST">
        <input type="hidden" name="acao" value="remover_foto">
        <input type="hidden" name="id_hidden" value="<?php echo $id_paciente; ?>">
    </form>
</div>

<div class="modal fade" id="modalOrigemFoto" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-secondary">Adicionar Foto</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-grid gap-3">
                    <button class="btn btn-primary p-3 shadow-sm" onclick="acionarCamera()">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-camera fa-xl me-3"></i>
                            <span class="fw-bold">Tirar Foto</span>
                        </div>
                    </button>
                    
                    <button class="btn btn-outline-secondary p-3" onclick="acionarGaleria()">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fa-regular fa-images fa-xl me-3"></i>
                            <span class="fw-bold">Abrir Galeria</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalOpcoesFoto" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Foto Atual</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-grid gap-2">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalOrigemFoto">
                    <i class="fa-solid fa-rotate me-2"></i> Trocar Foto
                </button>
                <button class="btn btn-outline-danger" onclick="confirmarRemocao()">
                    <i class="fa-solid fa-trash me-2"></i> Remover Foto
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBusca" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6">Localizar Paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="" method="GET" class="input-group">
                    <input type="hidden" name="page" value="pacientes">
                    <input type="text" name="id" class="form-control" placeholder="Digite o ID para ir direto..." required>
                    <button class="btn btn-primary" type="submit">Ir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// === LÓGICA DE FOTOS ===

function gerenciarFoto(temFoto) {
    if (temFoto) {
        // Se já tem, abre menu de opções (Trocar/Remover)
        var modal = new bootstrap.Modal(document.getElementById('modalOpcoesFoto'));
        modal.show();
    } else {
        // Se não tem, abre menu de escolha (Câmera ou Galeria)
        var modal = new bootstrap.Modal(document.getElementById('modalOrigemFoto'));
        modal.show();
    }
}

function acionarCamera() {
    // Fecha o modal de escolha
    var elModal = document.getElementById('modalOrigemFoto');
    var modal = bootstrap.Modal.getInstance(elModal);
    if (modal) modal.hide();
    
    // Dispara input com capture="environment"
    document.getElementById('inputCamera').click();
}

function acionarGaleria() {
    var elModal = document.getElementById('modalOrigemFoto');
    var modal = bootstrap.Modal.getInstance(elModal);
    if (modal) modal.hide();

    // Dispara input normal
    document.getElementById('inputGaleria').click();
}

function confirmarRemocao() {
    if(confirm("Tem certeza que deseja remover a foto deste paciente?")) {
        document.getElementById('formRemoverFoto').submit();
    }
}

// === OUTRAS LÓGICAS ===

function verificarPediatria() {
    var combo = document.getElementById("comboPediatria");
    var campoResp = document.getElementById("campoResponsavel");
    
    if (combo && campoResp) {
        if (combo.value == "1") {
            campoResp.disabled = false;
        } else {
            campoResp.disabled = true;
            campoResp.value = ""; // Limpa visualmente
        }
    }
}

document.addEventListener("DOMContentLoaded", function() {
    verificarPediatria();
});
</script>