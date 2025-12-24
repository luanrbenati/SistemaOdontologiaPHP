<?php
// 1. Conectar
require_once 'conexao.php'; 

// === ROTEAMENTO ===
$pagina = $_GET['page'] ?? 'pacientes'; // Define a página padrão
$id_paciente = $_GET['id'] ?? null;
$mensagem = "";

// =================================================================================
// LÓGICA: PACIENTES (Só roda se estiver na aba pacientes)
// =================================================================================
if ($pagina == 'pacientes') {

    // Processar JSON (Odontograma)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $id_p = $input['id_paciente'] ?? 'temp';
        $arquivo_dados = "dados_odonto_{$id_p}.json";
        $dadosAtuais = file_exists($arquivo_dados) ? json_decode(file_get_contents($arquivo_dados), true) : [];
        $chave = $input['dente'] . '_' . $input['face'];
        $dadosAtuais[$chave] = $input['status'];
        file_put_contents($arquivo_dados, json_encode($dadosAtuais));
        echo json_encode(['sucesso' => true]);
        exit; 
    }

    // Salvar Dados Pessoais
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

    // Lógica de Pesquisa
    $resultados_busca = [];
    if (isset($_GET['termo_busca']) && !empty($_GET['termo_busca'])) {
        $termo = "%" . $_GET['termo_busca'] . "%";
        $stmt_busca = $pdo->prepare("SELECT id, nome, cpf FROM pacientes WHERE nome LIKE ? ORDER BY nome ASC LIMIT 10");
        $stmt_busca->execute([$termo]);
        $resultados_busca = $stmt_busca->fetchAll();
    }

    // Carregar Dados do Paciente Atual
    if (!$id_paciente) {
        $stmt_first = $pdo->query("SELECT * FROM pacientes ORDER BY nome ASC LIMIT 1");
        $paciente = $stmt_first->fetch();
        if($paciente) $id_paciente = $paciente['id'];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
        $stmt->execute([$id_paciente]);
        $paciente = $stmt->fetch();
        if (!$paciente) {
            $stmt_first = $pdo->query("SELECT * FROM pacientes ORDER BY nome ASC LIMIT 1");
            $paciente = $stmt_first->fetch();
            if($paciente) $id_paciente = $paciente['id'];
        }
    }

    // Navegação Setas (A-Z)
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
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>D-SIGO - Sistema Odontológico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* === CONFIGURAÇÃO GERAL === */
    body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow: hidden; font-size: 0.9rem; }
    .container-fluid, .row { height: 100vh; }
    
    /* SIDEBAR */
    .sidebar { background-color: #2c3e50; height: 100vh; color: #ecf0f1; overflow-y: auto; }
    .sidebar .nav-link { color: #bdc3c7; margin-bottom: 2px; padding: 8px 15px; cursor: pointer; text-decoration: none; display: block;}
    .sidebar .nav-link:hover { background-color: #34495e; color: #fff; }
    .sidebar .nav-link.active { background-color: #34495e; color: #fff; border-left: 4px solid #3498db; }
    .logo-area { padding: 15px; background-color: #253342; font-weight: bold; font-size: 1.2rem; }
    
    /* CONTEÚDO PRINCIPAL */
    .main-content { height: 100vh; overflow-y: auto; padding: 0; background-color: #f4f6f9; }
    .top-header { background-color: #fff; padding: 5px 20px; border-bottom: 1px solid #dee2e6; height: 40px; }
    
    /* BANNER PACIENTE */
    .patient-banner { background-color: #3498db; color: white; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; }
    .avatar-circle { width: 45px; height: 45px; background-color: rgba(0,0,0,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-right: 15px; border: 2px solid white; }
    .btn-nav-arrow { color: white; opacity: 0.8; transition: 0.3s; transform: scale(0.9); }
    .btn-nav-arrow:hover { color: white; opacity: 1; transform: scale(1.1); }
    
    /* ABAS (TABS) */
    .nav-tabs { border-bottom: 1px solid #dee2e6; }
    .nav-tabs .nav-link { color: #7f8c8d; border: none; border-bottom: 3px solid transparent; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding: 8px 15px; cursor: pointer; }
    .nav-tabs .nav-link:hover { color: #3498db; }
    .nav-tabs .nav-link.active { color: #3498db; border-bottom: 3px solid #3498db; background: transparent; }
    
    /* CSS DO FORMULÁRIO COMPACTO */
    .card-custom { border: none; box-shadow: 0 0 5px rgba(0,0,0,0.05); margin-top: 10px; }
    .card-header-custom { background-color: white; border-bottom: 1px solid #eee; font-weight: bold; padding: 8px 15px; font-size: 0.9rem; }
    .card-body { padding: 10px 15px; }
    
    .form-label { font-size: 11px; font-weight: 700; color: #666; margin: 0 !important; display: block; }
    .form-control, .form-select, .input-group-text { font-size: 12px !important; height: 26px !important; padding: 1px 6px; border-radius: 3px; }
    .btn { font-size: 12px !important; height: 26px; padding: 0 10px; display: inline-flex; align-items: center; }
    textarea.form-control { height: auto !important; line-height: 1.3; }
    
    .card-body .row { --bs-gutter-y: 0px !important; --bs-gutter-x: 2px !important; }
    .card-body .row > div[class^="col-"] { margin-bottom: 0px !important; padding-top: 2px !important; }
    .mb-3 { margin-bottom: 5px !important; }
    .p-3 { padding: 10px 15px !important; }
    .card-body .col, .card-body [class*="col-"] { padding-bottom: 0 !important; }
    .card-body .form-group, .card-body .mb-3 { margin: 0 !important; padding: 0 !important; }
    .form-check { margin-bottom: 0; min-height: auto; margin-top: 3px; }
    .form-check-input { margin-top: 0.1em; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-0 d-none d-md-block">
            <div class="logo-area"><i class="fa-solid fa-tooth"></i> Dental SIGO</div>
            <div class="p-3">
                <nav class="nav flex-column mt-2">
                    <a class="nav-link <?php echo ($pagina == 'pacientes') ? 'active' : ''; ?>" href="?page=pacientes">
                        <i class="fa-solid fa-users me-2"></i> Pacientes
                    </a>
                    <a class="nav-link <?php echo ($pagina == 'usuarios') ? 'active' : ''; ?>" href="?page=usuarios">
                        <i class="fa-solid fa-user-gear me-2"></i> Usuários
                    </a>
                </nav>
            </div>
        </div>

        <div class="col-md-10 bg-light p-0 main-content">
            <div class="top-header d-flex justify-content-between align-items-center">
                <h5 class="m-0 text-secondary">D-SIGO</h5>
                <span class="text-muted small">Usuário Logado <i class="fa-solid fa-user-circle ms-1"></i></span>
            </div>

            <?php if ($pagina == 'pacientes'): ?>
                <div class="p-3">
                    <h4 class="mb-0 text-dark">Pacientes <small class="text-muted fs-6">Prontuário do paciente</small></h4>
                </div>

                <div class="patient-banner mx-3 rounded-top">
                    <div class="d-flex align-items-center">
                        <?php if($prev_id): ?>
                            <a href="?page=pacientes&id=<?php echo $prev_id; ?>" class="btn-nav-arrow me-3"><i class="fa-solid fa-chevron-left fa-2x"></i></a>
                        <?php else: ?>
                            <span class="me-3 opacity-25"><i class="fa-solid fa-chevron-left fa-2x"></i></span>
                        <?php endif; ?>
                        <div class="avatar-circle"><i class="fa-regular fa-user"></i></div>
                        <h2 class="m-0 fw-light" style="font-size: 1.4rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 600px;">
                            <?php echo htmlspecialchars($paciente['nome'] ?? 'Sem dados'); ?>
                        </h2>
                    </div>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-light text-primary btn-sm me-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalBusca">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Pesquisar
                        </button>
                        <?php if($next_id): ?>
                            <a href="?page=pacientes&id=<?php echo $next_id; ?>" class="btn-nav-arrow"><i class="fa-solid fa-chevron-right fa-2x"></i></a>
                        <?php else: ?>
                            <span class="opacity-25"><i class="fa-solid fa-chevron-right fa-2x"></i></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white mx-3 border-bottom px-3 pt-2">
                    <ul class="nav nav-tabs" id="prontuarioTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-informacoes"><i class="fa-solid fa-circle-info"></i> INFORMAÇÕES</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-financeiro"><i class="fa-solid fa-money-bill-wave"></i> FINANCEIRO</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-odontograma"><i class="fa-solid fa-teeth"></i> ODONTOGRAMA</button></li>
                    </ul>
                </div>

                <div class="tab-content container-fluid px-4 pb-5" id="prontuarioTabsContent">
                    
                    <?php if($mensagem): ?>
                        <div class="alert alert-info mt-3 alert-dismissible fade show">
                            <?php echo $mensagem; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="tab-pane fade show active" id="tab-informacoes">
                        <?php if (!empty($paciente)): ?>
                        <form action="?page=pacientes&id=<?php echo $id_paciente; ?>" method="POST" class="mt-3 h-100">
                            <input type="hidden" name="acao" value="salvar_dados">
                            <input type="hidden" name="id_hidden" value="<?php echo $id_paciente; ?>">

                            <div class="row h-100">
                                <div class="col-lg-7 mb-3">
                                    <div class="card card-custom bg-white h-100">
                                        <div class="card-header-custom text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Dados Pessoais</div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($paciente['nome']); ?>"></div>
                                                <div class="col-md-6"><label class="form-label">e-mail</label><input type="email" class="form-control" placeholder="Sem coluna no DB"></div>
                                                <div class="col-md-4"><label class="form-label">Celular:</label><div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-mobile-screen"></i></span><input type="text" name="telefone1" class="form-control border-start-0" value="<?php echo htmlspecialchars($paciente['telefone1'] ?? ''); ?>"></div></div>
                                                <div class="col-md-4"><label class="form-label">Fixo:</label><div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-phone"></i></span><input type="text" name="telefone2" class="form-control border-start-0" value="<?php echo htmlspecialchars($paciente['telefone2'] ?? ''); ?>"></div></div>
                                                <div class="col-md-4"><label class="form-label">Gênero</label>
                                                    <div class="mt-1">
                                                        <div class="form-check form-check-inline mb-0"><input class="form-check-input" type="radio" name="sexo" value="1" <?php echo ($paciente['sexo'] == 1) ? 'checked' : ''; ?>><label class="form-check-label" style="font-size: 11px;">Fem.</label></div>
                                                        <div class="form-check form-check-inline mb-0"><input class="form-check-input" type="radio" name="sexo" value="2" <?php echo ($paciente['sexo'] == 2) ? 'checked' : ''; ?>><label class="form-check-label" style="font-size: 11px;">Masc.</label></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6"><label class="form-label">Aniversário</label><div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-calendar"></i></span><input type="date" name="data_nascimento" class="form-control border-start-0" value="<?php echo $paciente['data_nascimento']; ?>"></div></div>
                                                <div class="col-md-6"><label class="form-label">CPF</label><input type="text" name="cpf" class="form-control" value="<?php echo htmlspecialchars($paciente['cpf'] ?? ''); ?>"></div>
                                                <div class="col-md-6"><label class="form-label">Estado</label><select name="estado_id" class="form-select"><option value="">Selecione</option><option value="1" <?php echo ($paciente['estado_id'] == 1) ? 'selected' : ''; ?>>SP</option><option value="2" <?php echo ($paciente['estado_id'] == 2) ? 'selected' : ''; ?>>RJ</option></select></div>
                                                <div class="col-md-6"><label class="form-label">Cidade</label><input type="text" name="cidade" class="form-control" value="<?php echo htmlspecialchars($paciente['cidade'] ?? ''); ?>"></div>
                                                <div class="col-md-6"><label class="form-label">Endereço</label><input type="text" name="rua" class="form-control" value="<?php echo htmlspecialchars($paciente['rua'] ?? ''); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Nº</label><input type="text" name="numero" class="form-control" value="<?php echo htmlspecialchars($paciente['numero'] ?? ''); ?>"></div>
                                                <div class="col-md-3"><label class="form-label">Tipo</label><select name="tipo_residencia" class="form-select"><option value="Res." <?php echo (($paciente['tipo_residencia'] ?? '') == 'Res.') ? 'selected' : ''; ?>>Res.</option><option value="Com." <?php echo (($paciente['tipo_residencia'] ?? '') == 'Com.') ? 'selected' : ''; ?>>Com.</option></select></div>
                                                <div class="col-md-4"><label class="form-label">Bairro</label><input type="text" name="bairro" class="form-control" value="<?php echo htmlspecialchars($paciente['bairro'] ?? ''); ?>"></div>
                                                <div class="col-md-4"><label class="form-label">Código postal</label><input type="text" name="cep" class="form-control" value="<?php echo htmlspecialchars($paciente['cep'] ?? ''); ?>"></div>
                                                <div class="col-md-4"><label class="form-label">Complemento</label><input type="text" name="complemento" class="form-control" value="<?php echo htmlspecialchars($paciente['complemento'] ?? ''); ?>"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-5 mb-3">
                                    <div class="card card-custom bg-white h-100 d-flex flex-column">
                                        <div class="card-header-custom text-dark"><i class="fa-solid fa-circle-info me-1"></i> Informações Gerais</div>
                                        <div class="card-body d-flex flex-column">
                                            <div class="mb-3"><label class="form-label">Plano</label><div class="input-group"><select class="form-select"><option>Selecione</option></select><button class="btn btn-primary" type="button"><i class="fa-solid fa-truck-medical"></i></button></div></div>
                                            <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Responsável</label><input type="text" class="form-control"></div><div class="col-6"><label class="form-label">CPF do responsável</label><input type="text" class="form-control"></div></div>
                                            <div class="mb-3 flex-grow-1 d-flex flex-column"><label class="form-label">Observações</label><textarea name="obs" class="form-control h-100" style="resize: none;"><?php echo htmlspecialchars($paciente['obs'] ?? ''); ?></textarea></div>
                                        </div>
                                        <div class="card-footer bg-white border-top-0 pb-3 text-end"><button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-check me-2"></i> Salvar Alterações</button></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="tab-odontograma"><?php include 'odontograma.php'; ?></div>
                    <div class="tab-pane fade" id="tab-financeiro">
                        <div class="card card-custom bg-white mt-3 p-5 text-center text-muted"><i class="fa-solid fa-money-bill-wave fa-3x mb-3"></i><h4>Financeiro</h4></div>
                    </div>
                </div> 

            <?php elseif ($pagina == 'usuarios'): ?>
                
                <div class="p-3">
                    <h4 class="mb-0 text-dark">Usuários do Sistema</h4>
                </div>
                
                <div class="container-fluid px-3">
                    <?php 
                        // INCLUI O SEU ARQUIVO EXISTENTE AQUI
                        if (file_exists('listar_usuarios.php')) {
                            include 'listar_usuarios.php'; 
                        } else {
                            echo '<div class="alert alert-danger">Arquivo listar_usuarios.php não encontrado!</div>';
                        }
                    ?>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<div class="modal fade" id="modalBusca" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Buscar Paciente (A-Z)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form action="" method="GET" class="input-group mb-3">
            <input type="hidden" name="page" value="pacientes">
            <input type="text" name="termo_busca" class="form-control" placeholder="Digite o nome..." required value="<?php echo $_GET['termo_busca'] ?? ''; ?>">
            <button class="btn btn-primary" type="submit">Buscar</button>
        </form>
        <?php if (!empty($resultados_busca)): ?>
            <div class="list-group">
                <p class="text-muted small mb-1">Resultados encontrados:</p>
                <?php foreach($resultados_busca as $res): ?>
                    <a href="?page=pacientes&id=<?php echo $res['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div><strong><?php echo htmlspecialchars($res['nome']); ?></strong><br><small class="text-muted">CPF: <?php echo $res['cpf']; ?></small></div>
                        <i class="fa-solid fa-chevron-right text-muted"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <script>document.addEventListener("DOMContentLoaded", function(){ var myModal = new bootstrap.Modal(document.getElementById('modalBusca')); myModal.show(); });</script>
        <?php elseif(isset($_GET['termo_busca'])): ?>
            <div class="alert alert-warning">Nenhum paciente encontrado.</div>
            <script>document.addEventListener("DOMContentLoaded", function(){ var myModal = new bootstrap.Modal(document.getElementById('modalBusca')); myModal.show(); });</script>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ID_PACIENTE = "<?php echo $id_paciente; ?>";
    function handleClick(dente, face, el) {
        let status = parseInt(el.getAttribute('data-status'));
        let novoStatus = (status + 1) > 3 ? 0 : status + 1;
        const colors = { 0: { fill: 'white', stroke: '#333' }, 1: { fill: '#ffc107', stroke: '#d39e00' }, 2: { fill: '#198754', stroke: '#146c43' }, 3: { fill: '#dc3545', stroke: '#b02a37' } };
        const style = colors[novoStatus];
        el.setAttribute('fill', style.fill); el.setAttribute('stroke', style.stroke); el.setAttribute('data-status', novoStatus);
        fetch(window.location.href, {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ dente, face, status: novoStatus, id_paciente: ID_PACIENTE })
        }).then(res => res.json()).then(data => console.log("Salvo:", data));
    }
</script>
</body>
</html>