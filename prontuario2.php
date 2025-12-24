<?php
// ==========================================================
// 1. LÓGICA DO ODONTOGRAMA (SALVAR JSON VIA AJAX)
// ==========================================================
// Se for uma requisição POST com JSON, é o clique no dente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Pega o ID do paciente ou usa 'temp'
    $id_p = $input['id_paciente'] ?? 'temp';
    $arquivo_dados = "dados_odonto_{$id_p}.json";

    $dadosAtuais = file_exists($arquivo_dados) ? json_decode(file_get_contents($arquivo_dados), true) : [];
    $chave = $input['dente'] . '_' . $input['face'];
    $dadosAtuais[$chave] = $input['status'];
    
    file_put_contents($arquivo_dados, json_encode($dadosAtuais));
    echo json_encode(['sucesso' => true]);
    exit; // Para a execução aqui para não carregar o HTML
}

// ==========================================================
// 2. CONEXÃO E DADOS DO PACIENTE
// ==========================================================
$host = 'localhost';
$db   = 'srv_odonto'; 
$user = 'root';              
$pass = 'qwe123!@#';                  
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

$id_paciente = $_GET['id'] ?? null;
$mensagem = "";

// Salvar Formulário de Dados Pessoais
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'salvar_dados') {
    $id_para_salvar = $_GET['id'] ?? $_POST['id_hidden'] ?? 0;
    try {
        $sql_update = "UPDATE pacientes SET 
                       nome = ?, data_nascimento = ?, cpf = ?, sexo = ?, 
                       telefone1 = ?, telefone2 = ?, 
                       rua = ?, bairro = ?, cidade = ?, estado_id = ?, obs = ?
                       WHERE id = ?";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            $_POST['nome'], $_POST['data_nascimento'], $_POST['cpf'], $_POST['sexo'] ?? null,
            $_POST['telefone1'], $_POST['telefone2'], 
            $_POST['rua'], $_POST['bairro'], $_POST['cidade'], $_POST['estado_id'], $_POST['obs'],
            $id_para_salvar
        ]);
        $mensagem = "Dados salvos com sucesso!";
        $id_paciente = $id_para_salvar; 
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar: " . $e->getMessage();
    }
}

// Carregar Paciente
if (!$id_paciente) {
    $stmt_first = $pdo->query("SELECT * FROM pacientes ORDER BY nome ASC LIMIT 1");
    $paciente = $stmt_first->fetch();
    if($paciente) $id_paciente = $paciente['id'];
} else {
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$id_paciente]);
    $paciente = $stmt->fetch();
}

// Navegação A-Z
$prev_id = null; $next_id = null;
if (!empty($paciente)) {
    $nome_atual = $paciente['nome'];
    $stmt_prev = $pdo->prepare("SELECT id FROM pacientes WHERE nome < ? ORDER BY nome DESC LIMIT 1");
    $stmt_prev->execute([$nome_atual]);
    $prev_id = $stmt_prev->fetchColumn();
    $stmt_next = $pdo->prepare("SELECT id FROM pacientes WHERE nome > ? ORDER BY nome ASC LIMIT 1");
    $stmt_next->execute([$nome_atual]);
    $next_id = $stmt_next->fetchColumn();
}

// ==========================================================
// 3. FUNÇÕES DO ODONTOGRAMA (PHP VISUAL)
// ==========================================================
// Carrega o JSON específico deste paciente
$arquivo_odonto = "dados_odonto_{$id_paciente}.json";
$mapaTratamentos = file_exists($arquivo_odonto) ? json_decode(file_get_contents($arquivo_odonto), true) : [];

// Configurações Geométricas
$centerX = 350; $centerY = 350; $radiusExt = 260; $radiusInt = 160;

function getPositionStyle($centerX, $centerY, $radius, $angleDeg) {
    $rad = deg2rad($angleDeg - 90); 
    $x = $centerX + ($radius * cos($rad));
    $y = $centerY + ($radius * sin($rad));
    return "top: " . ($y - 20) . "px; left: " . ($x - 20) . "px;";
}

function getCircularSVG($dente, $dados) {
    $statusMap = [
        0 => ['fill' => 'white', 'stroke' => '#333'],
        1 => ['fill' => '#ffc107', 'stroke' => '#d39e00'], // Amarelo
        2 => ['fill' => '#198754', 'stroke' => '#146c43'], // Verde
        3 => ['fill' => '#dc3545', 'stroke' => '#b02a37'], // Vermelho
    ];
    $paths = [
        'oclusal'    => 'circle', 
        'vestibular' => 'M15,15 L10,10 A28,28 0 0,1 40,10 L35,15 Q25,20 15,15 Z', 
        'distal'     => 'M35,15 L40,10 A28,28 0 0,1 40,40 L35,35 Q30,25 35,15 Z', 
        'lingual'    => 'M35,35 L40,40 A28,28 0 0,1 10,40 L15,35 Q25,30 35,35 Z', 
        'mesial'     => 'M15,35 L10,40 A28,28 0 0,1 10,10 L15,15 Q20,25 15,35 Z', 
    ];
    $svg = '<svg width="40" height="40" viewBox="0 0 50 50" class="tooth-icon">';
    foreach ($paths as $face => $path) {
        if ($face === 'oclusal') continue;
        $st = $dados[$dente.'_'.$face] ?? 0;
        $style = $statusMap[$st];
        $svg .= sprintf('<path d="%s" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClick(%d, \'%s\', this)" data-status="%d" />', $path, $style['fill'], $style['stroke'], $dente, $face, $st);
    }
    $stCenter = $dados[$dente.'_oclusal'] ?? 0;
    $styleCenter = $statusMap[$stCenter];
    $svg .= sprintf('<circle cx="25" cy="25" r="8" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClick(%d, \'oclusal\', this)" data-status="%d" />', $styleCenter['fill'], $styleCenter['stroke'], $dente, $stCenter);
    $svg .= '</svg>';
    return $svg;
}

function renderToothAbsolute($isoCode, $label, $angle, $radius, $cx, $cy, $dados) {
    $style = getPositionStyle($cx, $cy, $radius, $angle);
    $svg = getCircularSVG($isoCode, $dados);
    $labelRadius = ($radius > 200) ? $radius + 30 : $radius - 30;
    $styleLabel = getPositionStyle($cx, $cy, $labelRadius, $angle);
    echo "<div class='tooth-wrapper' style='$style'>$svg</div>";
    echo "<div class='tooth-label' style='$styleLabel'>$label</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prontuário - <?php echo htmlspecialchars($paciente['nome'] ?? 'Novo'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; color: #ecf0f1; }
        .sidebar .nav-link { color: #bdc3c7; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #34495e; color: #fff; border-left: 4px solid #3498db; }
        .logo-area { padding: 20px; background-color: #253342; font-weight: bold; font-size: 1.2rem; }
        
        /* Banner e Topo */
        .top-header { background-color: #fff; padding: 10px 20px; border-bottom: 1px solid #dee2e6; }
        .patient-banner { background-color: #3498db; color: white; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .avatar-circle { width: 50px; height: 50px; background-color: rgba(0,0,0,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 15px; border: 2px solid white; }
        .btn-nav-arrow { color: white; opacity: 0.8; transition: 0.3s; }
        .btn-nav-arrow:hover { color: white; opacity: 1; transform: scale(1.1); }

        /* Abas */
        .nav-tabs { border-bottom: 1px solid #dee2e6; }
        .nav-tabs .nav-link { 
            color: #7f8c8d; border: none; border-bottom: 3px solid transparent;
            font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 10px 15px;
            cursor: pointer; /* Importante para o clique */
        }
        .nav-tabs .nav-link:hover { color: #3498db; }
        .nav-tabs .nav-link.active { color: #3498db; border-bottom: 3px solid #3498db; background: transparent; }
        
        /* Formulários */
        .card-custom { border: none; box-shadow: 0 0 10px rgba(0,0,0,0.05); margin-top: 20px; }
        .card-header-custom { background-color: white; border-bottom: 1px solid #eee; font-weight: bold; padding: 15px; }
        .form-label { font-size: 0.85rem; font-weight: bold; color: #333; margin-bottom: 2px;}
        .form-control, .form-select { font-size: 0.9rem; background-color: #fff; }

        /* ODONTOGRAMA CSS */
        .odontograma-container { overflow-x: auto; text-align: center; padding-bottom: 20px; }
        .odontograma-wrapper { position: relative; width: 700px; height: 700px; margin: 20px auto; border-radius: 50%; }
        .cross-v { position: absolute; top: 0; bottom: 0; left: 50%; width: 1px; background: #ccc; z-index: 0; }
        .cross-h { position: absolute; left: 0; right: 0; top: 50%; height: 1px; background: #ccc; z-index: 0; }
        .label-axis { position: absolute; font-weight: bold; font-size: 14px; background: white; padding: 2px 5px; z-index: 10; color: #555; }
        .lbl-top { top: 10px; left: 50%; transform: translateX(-50%); }
        .lbl-bottom { bottom: 10px; left: 50%; transform: translateX(-50%); }
        .lbl-left { top: 50%; left: 0px; transform: translateY(-50%); }
        .lbl-right { top: 50%; right: 0px; transform: translateY(-50%); }
        .tooth-wrapper { position: absolute; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; z-index: 5; }
        .tooth-icon { cursor: pointer; filter: drop-shadow(1px 1px 1px rgba(0,0,0,0.1)); transition: transform 0.2s; }
        .tooth-icon:hover { transform: scale(1.1); }
        .face-part:hover { opacity: 0.8; }
        .tooth-label { position: absolute; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 14px; color: #333; z-index: 4; }
        .legend-box { text-align: center; margin-bottom: 10px; margin-top: 10px; }
        .dot { width: 12px; height: 12px; display: inline-block; border-radius: 50%; border: 1px solid #ccc; margin-right: 5px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-0 d-none d-md-block">
            <div class="logo-area"><i class="fa-solid fa-tooth"></i> Dental SIGO</div>
            <div class="p-3">
                <nav class="nav flex-column mt-2">
                    <a class="nav-link active" href="#"><i class="fa-solid fa-users me-2"></i> Pacientes</a>
                </nav>
            </div>
        </div>

        <div class="col-md-10 bg-light p-0">
            <div class="top-header d-flex justify-content-between align-items-center">
                <h5 class="m-0 text-secondary">D-SIGO</h5>
                <span class="text-muted small">Usuário Logado <i class="fa-solid fa-user-circle ms-1"></i></span>
            </div>

            <div class="p-3">
                <h4 class="mb-0 text-dark">Pacientes <small class="text-muted fs-6">Prontuário do paciente</small></h4>
            </div>

            <div class="patient-banner mx-3 rounded-top">
                <div class="d-flex align-items-center">
                    <?php if($prev_id): ?>
                        <a href="?id=<?php echo $prev_id; ?>" class="btn-nav-arrow me-3" title="Anterior"><i class="fa-solid fa-chevron-left fa-2x"></i></a>
                    <?php else: ?>
                        <span class="me-3 opacity-25"><i class="fa-solid fa-chevron-left fa-2x"></i></span>
                    <?php endif; ?>
                    <div class="avatar-circle"><i class="fa-regular fa-user"></i></div>
                    <h2 class="m-0 fw-light"><?php echo htmlspecialchars($paciente['nome'] ?? 'Sem dados'); ?></h2>
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-light text-primary btn-sm me-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalBusca">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Pesquisar
                    </button>
                    <?php if($next_id): ?>
                        <a href="?id=<?php echo $next_id; ?>" class="btn-nav-arrow" title="Próximo"><i class="fa-solid fa-chevron-right fa-2x"></i></a>
                    <?php else: ?>
                        <span class="opacity-25"><i class="fa-solid fa-chevron-right fa-2x"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white mx-3 border-bottom px-3 pt-2">
                <ul class="nav nav-tabs" id="prontuarioTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-informacoes" type="button"><i class="fa-solid fa-circle-info"></i> INFORMAÇÕES</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-financeiro" type="button"><i class="fa-solid fa-money-bill-wave"></i> FINANCEIRO</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tratamentos" type="button"><i class="fa-solid fa-briefcase-medical"></i> TRATAMENTOS</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-odontograma" type="button"><i class="fa-solid fa-teeth"></i> ODONTOGRAMA</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-anamnese" type="button"><i class="fa-solid fa-clipboard-list"></i> ANAMNESE</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-imagens" type="button"><i class="fa-regular fa-image"></i> IMAGENS</button>
                    </li>
                </ul>
            </div>

            <div class="tab-content container-fluid px-4 pb-5" id="prontuarioTabsContent">
                
                <?php if($mensagem): ?>
                    <div class="alert alert-info mt-3 alert-dismissible fade show">
                        <?php echo $mensagem; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="tab-pane fade show active" id="tab-informacoes" role="tabpanel">
                    <?php if (!empty($paciente)): ?>
                    <form action="?id=<?php echo $id_paciente; ?>" method="POST" class="mt-3">
                        <input type="hidden" name="acao" value="salvar_dados">
                        <input type="hidden" name="id_hidden" value="<?php echo $id_paciente; ?>">

                        <div class="row">
                            <div class="col-lg-7">
                                <div class="card card-custom bg-white">
                                    <div class="card-header-custom text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Dados Pessoais</div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Nome</label>
                                                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($paciente['nome']); ?>">
                                            </div>
                                            <div class="col-md-6"><label class="form-label">e-mail</label><input type="email" class="form-control" placeholder=""></div>
                                            
                                            <div class="col-md-4"><label class="form-label">Celular:</label><div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-mobile-screen"></i></span><input type="text" name="telefone1" class="form-control border-start-0" value="<?php echo htmlspecialchars($paciente['telefone1'] ?? ''); ?>"></div></div>
                                            <div class="col-md-4"><label class="form-label">Fixo:</label><div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-phone"></i></span><input type="text" name="telefone2" class="form-control border-start-0" value="<?php echo htmlspecialchars($paciente['telefone2'] ?? ''); ?>"></div></div>
                                            
                                            <div class="col-md-4">
                                                <label class="form-label">Gênero</label>
                                                <div class="mt-2">
                                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="sexo" value="1" <?php echo ($paciente['sexo'] == 1) ? 'checked' : ''; ?>><label class="form-check-label">Fem.</label></div>
                                                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="sexo" value="2" <?php echo ($paciente['sexo'] == 2) ? 'checked' : ''; ?>><label class="form-check-label">Masc.</label></div>
                                                </div>
                                            </div>

                                            <div class="col-md-6"><label class="form-label">Aniversário</label><input type="date" name="data_nascimento" class="form-control" value="<?php echo $paciente['data_nascimento']; ?>"></div>
                                            <div class="col-md-6"><label class="form-label">CPF</label><input type="text" name="cpf" class="form-control" value="<?php echo htmlspecialchars($paciente['cpf'] ?? ''); ?>"></div>

                                            <div class="col-md-6">
                                                <label class="form-label">Estado</label>
                                                <select name="estado_id" class="form-select">
                                                    <option value="">Selecione</option>
                                                    <option value="1" <?php echo ($paciente['estado_id'] == 1) ? 'selected' : ''; ?>>SP</option>
                                                    <option value="2" <?php echo ($paciente['estado_id'] == 2) ? 'selected' : ''; ?>>RJ</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6"><label class="form-label">Cidade</label><input type="text" name="cidade" class="form-control" value="<?php echo htmlspecialchars($paciente['cidade'] ?? ''); ?>"></div>
                                            <div class="col-md-6"><label class="form-label">Endereço</label><input type="text" name="rua" class="form-control" value="<?php echo htmlspecialchars($paciente['rua'] ?? ''); ?>"></div>
                                            <div class="col-md-3"><label class="form-label">Nº</label><input type="text" name="numero" class="form-control" value="<?php echo htmlspecialchars($paciente['numero'] ?? ''); ?>"></div>
                                            <div class="col-md-3"><label class="form-label">Tipo</label><select name="tipo_residencia" class="form-select"><option value="Res." selected>Res.</option><option value="Com.">Com.</option></select></div>
                                            <div class="col-md-4"><label class="form-label">Bairro</label><input type="text" name="bairro" class="form-control" value="<?php echo htmlspecialchars($paciente['bairro'] ?? ''); ?>"></div>
                                            <div class="col-md-4"><label class="form-label">CEP</label><input type="text" name="cep" class="form-control" value="<?php echo htmlspecialchars($paciente['cep'] ?? ''); ?>"></div>
                                            <div class="col-md-4"><label class="form-label">Complemento</label><input type="text" name="complemento" class="form-control" value="<?php echo htmlspecialchars($paciente['complemento'] ?? ''); ?>"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="card card-custom bg-white">
                                    <div class="card-header-custom text-dark"><i class="fa-solid fa-circle-info me-1"></i> Informações Gerais</div>
                                    <div class="card-body">
                                        <div class="mb-3"><label class="form-label">Observações</label><textarea name="obs" class="form-control" rows="6"><?php echo htmlspecialchars($paciente['obs'] ?? ''); ?></textarea></div>
                                    </div>
                                </div>
                                <div class="text-end mt-3"><button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-check me-2"></i> Alterar Informações</button></div>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-odontograma" role="tabpanel">
                    <div class="card card-custom bg-white mt-3">
                        <div class="card-body">
                            <div class="legend-box">
                                <span class="me-3"><span class="dot" style="background:white"></span>Normal</span>
                                <span class="me-3"><span class="dot" style="background:#ffc107"></span>A Realizar</span>
                                <span class="me-3"><span class="dot" style="background:#198754"></span>Realizado</span>
                                <span class="me-3"><span class="dot" style="background:#dc3545"></span>Extraído</span>
                            </div>

                            <div class="odontograma-container">
                                <div class="odontograma-wrapper">
                                    <div class="cross-v"></div>
                                    <div class="cross-h"></div>
                                    <div class="label-axis lbl-top">SUPERIOR</div>
                                    <div class="label-axis lbl-bottom">INFERIOR</div>
                                    <div class="label-axis lbl-left">DIREITA</div>
                                    <div class="label-axis lbl-right">ESQUERDA</div>

                                    <?php
                                    // GERAÇÃO DOS DENTES
                                    $perm_Q1 = [11=>'1', 12=>'2', 13=>'3', 14=>'4', 15=>'5', 16=>'6', 17=>'7', 18=>'8'];
                                    $dec_Q1 = [51=>'A', 52=>'B', 53=>'C', 54=>'D', 55=>'E'];
                                    $perm_Q2 = [21=>'1', 22=>'2', 23=>'3', 24=>'4', 25=>'5', 26=>'6', 27=>'7', 28=>'8'];
                                    $dec_Q2 = [61=>'A', 62=>'B', 63=>'C', 64=>'D', 65=>'E'];
                                    $perm_Q3 = [31=>'1', 32=>'2', 33=>'3', 34=>'4', 35=>'5', 36=>'6', 37=>'7', 38=>'8'];
                                    $dec_Q3 = [71=>'A', 72=>'B', 73=>'C', 74=>'D', 75=>'E'];
                                    $perm_Q4 = [41=>'1', 42=>'2', 43=>'3', 44=>'4', 45=>'5', 46=>'6', 47=>'7', 48=>'8'];
                                    $dec_Q4 = [81=>'A', 82=>'B', 83=>'C', 84=>'D', 85=>'E'];

                                    $i=0; foreach($perm_Q1 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, -10-($i*10.5), $radiusExt, $centerX, $centerY, $mapaTratamentos); $i++; }
                                    $i=0; foreach($dec_Q1 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, -12-($i*14), $radiusInt, $centerX, $centerY, $mapaTratamentos); $i++; }
                                    $i=0; foreach($perm_Q2 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, 10+($i*10.5), $radiusExt, $centerX, $centerY, $mapaTratamentos); $i++; }
                                    $i=0; foreach($dec_Q2 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, 12+($i*14), $radiusInt, $centerX, $centerY, $mapaTratamentos); $i++; }
                                    $i=0; foreach($perm_Q3 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, 170-($i*10.5), $radiusExt, $centerX, $centerY, $mapaTratamentos); $i++; }
                                    $i=0; foreach($dec_Q3 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, 168-($i*14), $radiusInt, $centerX, $centerY, $mapaTratamentos); $i++; }
                                    $i=0; foreach($perm_Q4 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, -170+($i*10.5), $radiusExt, $centerX, $centerY, $mapaTratamentos); $i++; }
                                    $i=0; foreach($dec_Q4 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, -168+($i*14), $radiusInt, $centerX, $centerY, $mapaTratamentos); $i++; }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-financeiro" role="tabpanel">
                    <div class="card card-custom bg-white mt-3 p-5 text-center text-muted">
                        <i class="fa-solid fa-money-bill-wave fa-3x mb-3"></i>
                        <h4>Módulo Financeiro</h4>
                        <p>Aqui ficará o histórico de pagamentos e orçamentos.</p>
                    </div>
                </div>

            </div> </div>
    </div>
</div>

<div class="modal fade" id="modalBusca" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Buscar Paciente (A-Z)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form action="" method="GET" class="input-group mb-3">
            <input type="text" name="termo_busca" class="form-control" placeholder="Digite o nome..." required>
            <button class="btn btn-primary" type="submit">Buscar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Script do Odontograma
    const ID_PACIENTE = "<?php echo $id_paciente; ?>";

    function handleClick(dente, face, el) {
        let status = parseInt(el.getAttribute('data-status'));
        let novoStatus = (status + 1) > 3 ? 0 : status + 1;

        const colors = {
            0: { fill: 'white', stroke: '#333' },
            1: { fill: '#ffc107', stroke: '#d39e00' }, 
            2: { fill: '#198754', stroke: '#146c43' },
            3: { fill: '#dc3545', stroke: '#b02a37' }
        };

        const style = colors[novoStatus];
        el.setAttribute('fill', style.fill);
        el.setAttribute('stroke', style.stroke);
        el.setAttribute('data-status', novoStatus);

        // Salva via AJAX
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ dente, face, status: novoStatus, id_paciente: ID_PACIENTE })
        }).then(res => res.json()).then(data => console.log("Salvo:", data));
    }
</script>
</body>
</html>