<?php
// ==========================================================
// 1. CONFIGURAÇÃO DO BANCO DE DADOS
// ==========================================================
$host = 'localhost';
$db   = 'srv_odonto'; 
$user = 'root';              
$pass = 'qwe123!@#';                  
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// ==========================================================
// 2. LÓGICA DE DADOS
// ==========================================================

// Pega o ID da URL.
$id_paciente = $_GET['id'] ?? null; // Começa nulo para decidirmos depois
$mensagem = "";

// A. SALVAR DADOS (UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Se veio POST, precisamos ter certeza que temos um ID. 
    // Se a URL não tinha ID, usamos o hidden ou o da lógica abaixo.
    // Para simplificar, assumimos que quem salva já carregou a página com um ID.
    $id_para_salvar = $_GET['id'] ?? $_POST['id_hidden'] ?? 0;
    
    try {
        $sql_update = "UPDATE pacientes SET 
                       nome = ?, data_nascimento = ?, cpf = ?, sexo = ?, 
                       telefone1 = ?, telefone2 = ?, rua = ?, 
                       bairro = ?, cidade = ?, obs = ?
                       WHERE id = ?";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            $_POST['nome'],
            $_POST['data_nascimento'],
            $_POST['cpf'],
            $_POST['sexo'] ?? null,
            $_POST['telefone1'],
            $_POST['telefone2'],
            $_POST['rua'],
            $_POST['bairro'],
            $_POST['cidade'],
            $_POST['obs'],
            $id_para_salvar
        ]);

        $mensagem = "Dados atualizados com sucesso!";
        $id_paciente = $id_para_salvar; // Mantém na tela o paciente salvo
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar: " . $e->getMessage();
    }
}

// B. LÓGICA DE PESQUISA (ORDEM ALFABÉTICA AQUI)
$resultados_busca = [];
if (isset($_GET['termo_busca']) && !empty($_GET['termo_busca'])) {
    $termo = "%" . $_GET['termo_busca'] . "%";
    // *** ALTERADO: Adicionado ORDER BY nome ASC ***
    $stmt_busca = $pdo->prepare("SELECT id, nome, cpf FROM pacientes WHERE nome LIKE ? ORDER BY nome ASC LIMIT 10");
    $stmt_busca->execute([$termo]);
    $resultados_busca = $stmt_busca->fetchAll();
}

// C. CARREGAR PACIENTE (LÓGICA INICIAL)

// Se NÃO tem ID na URL, busca o PRIMEIRO por ORDEM ALFABÉTICA
if (!$id_paciente) {
    // *** ALTERADO: Adicionado ORDER BY nome ASC ***
    $stmt_first = $pdo->query("SELECT * FROM pacientes ORDER BY nome ASC LIMIT 1");
    $paciente = $stmt_first->fetch();
    
    if($paciente) { 
        $id_paciente = $paciente['id']; 
    } else {
        die("Nenhum paciente encontrado no banco de dados.");
    }
} else {
    // Se TEM ID, busca esse específico
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$id_paciente]);
    $paciente = $stmt->fetch();
    
    // Fallback: Se o ID da URL não existe, pega o primeiro alfabético
    if (!$paciente) {
        $stmt_first = $pdo->query("SELECT * FROM pacientes ORDER BY nome ASC LIMIT 1");
        $paciente = $stmt_first->fetch();
        if($paciente) $id_paciente = $paciente['id'];
    }
}

// D. NAVEGAÇÃO (SETAS)
// Nota: As setas continuam navegando por ID (sequência de cadastro), 
// pois navegar por nome (A-Z) é complexo quando existem nomes iguais.
$stmt_prev = $pdo->prepare("SELECT id FROM pacientes WHERE id < ? ORDER BY id DESC LIMIT 1");
$stmt_prev->execute([$id_paciente]);
$prev_id = $stmt_prev->fetchColumn();

$stmt_next = $pdo->prepare("SELECT id FROM pacientes WHERE id > ? ORDER BY id ASC LIMIT 1");
$stmt_next->execute([$id_paciente]);
$next_id = $stmt_next->fetchColumn();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prontuário - <?php echo htmlspecialchars($paciente['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Sidebar */
        .sidebar { background-color: #2c3e50; min-height: 100vh; color: #ecf0f1; }
        .sidebar .nav-link { color: #bdc3c7; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #34495e; color: #fff; border-left: 4px solid #3498db; }
        .logo-area { padding: 20px; background-color: #253342; font-weight: bold; font-size: 1.2rem; }
        
        /* Header e Banner */
        .top-header { background-color: #fff; padding: 10px 20px; border-bottom: 1px solid #dee2e6; }
        .patient-banner { background-color: #3498db; color: white; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .avatar-circle { width: 50px; height: 50px; background-color: rgba(0,0,0,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-right: 15px; border: 2px solid white; }
        
        /* Forms e Cards */
        .nav-tabs .nav-link { color: #555; border: none; font-weight: 600; font-size: 0.9rem; }
        .nav-tabs .nav-link.active { color: #3498db; border-bottom: 3px solid #3498db; background: transparent; }
        .card-custom { border: none; box-shadow: 0 0 10px rgba(0,0,0,0.05); margin-top: 20px; }
        .card-header-custom { background-color: white; border-bottom: 1px solid #eee; font-weight: bold; padding: 15px; }
        .form-label { font-size: 0.85rem; font-weight: bold; color: #333; margin-bottom: 2px;}
        .form-control, .form-select { font-size: 0.9rem; background-color: #fff; }
        
        /* Link da Seta */
        .btn-nav-arrow { color: white; opacity: 0.8; transition: 0.3s; }
        .btn-nav-arrow:hover { color: white; opacity: 1; transform: scale(1.1); }
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
                        <a href="?id=<?php echo $prev_id; ?>" class="btn-nav-arrow me-3" title="Anterior">
                            <i class="fa-solid fa-chevron-left fa-2x"></i>
                        </a>
                    <?php else: ?>
                        <span class="me-3 opacity-25"><i class="fa-solid fa-chevron-left fa-2x"></i></span>
                    <?php endif; ?>

                    <div class="avatar-circle"><i class="fa-regular fa-user"></i></div>
                    <h2 class="m-0 fw-light"><?php echo htmlspecialchars($paciente['nome']); ?></h2>
                </div>

                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-light text-primary btn-sm me-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalBusca">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Pesquisar
                    </button>

                    <?php if($next_id): ?>
                        <a href="?id=<?php echo $next_id; ?>" class="btn-nav-arrow" title="Próximo">
                            <i class="fa-solid fa-chevron-right fa-2x"></i>
                        </a>
                    <?php else: ?>
                        <span class="opacity-25"><i class="fa-solid fa-chevron-right fa-2x"></i></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white mx-3 border-bottom px-3 pt-2">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active" href="#">INFORMAÇÕES</a></li>
                </ul>
            </div>

            <div class="container-fluid px-4 pb-5">
                
                <?php if($mensagem): ?>
                    <div class="alert alert-info mt-3 alert-dismissible fade show">
                        <?php echo $mensagem; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="?id=<?php echo $id_paciente; ?>" method="POST">
                    <input type="hidden" name="id_hidden" value="<?php echo $id_paciente; ?>">

                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card card-custom bg-white">
                                <div class="card-header-custom text-dark">Dados Pessoais</div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nome</label>
                                            <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($paciente['nome']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">e-mail</label>
                                            <input type="email" class="form-control" placeholder="Sem coluna no DB">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Celular:</label>
                                            <input type="text" name="telefone1" class="form-control" value="<?php echo htmlspecialchars($paciente['telefone1']); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Fixo:</label>
                                            <input type="text" name="telefone2" class="form-control" value="<?php echo htmlspecialchars($paciente['telefone2'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Gênero</label>
                                            <div class="mt-2">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="sexo" value="1" <?php echo ($paciente['sexo'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Fem.</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="sexo" value="2" <?php echo ($paciente['sexo'] == 2) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Masc.</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Aniversário</label>
                                            <input type="date" name="data_nascimento" class="form-control" value="<?php echo $paciente['data_nascimento']; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">CPF</label>
                                            <input type="text" name="cpf" class="form-control" value="<?php echo htmlspecialchars($paciente['cpf'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Estado (ID)</label>
                                            <input type="number" name="estado_id" class="form-control" value="<?php echo htmlspecialchars($paciente['estado_id']); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Cidade</label>
                                            <input type="text" name="cidade" class="form-control" value="<?php echo htmlspecialchars($paciente['cidade'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Endereço (Rua)</label>
                                            <input type="text" name="rua" class="form-control" value="<?php echo htmlspecialchars($paciente['rua'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bairro</label>
                                            <input type="text" name="bairro" class="form-control" value="<?php echo htmlspecialchars($paciente['bairro'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card card-custom bg-white">
                                <div class="card-header-custom text-dark">Informações Gerais</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Observações</label>
                                        <textarea name="obs" class="form-control" rows="6"><?php echo htmlspecialchars($paciente['obs'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa-solid fa-check me-2"></i> Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBusca" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Buscar Paciente (A-Z)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="" method="GET" class="input-group mb-3">
            <input type="text" name="termo_busca" class="form-control" placeholder="Digite o nome..." required value="<?php echo $_GET['termo_busca'] ?? ''; ?>">
            <button class="btn btn-primary" type="submit">Buscar</button>
        </form>

        <?php if (!empty($resultados_busca)): ?>
            <div class="list-group">
                <p class="text-muted small mb-1">Resultados (Ordem Alfabética):</p>
                <?php foreach($resultados_busca as $res): ?>
                    <a href="?id=<?php echo $res['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($res['nome']); ?></strong><br>
                            <small class="text-muted">CPF: <?php echo $res['cpf']; ?></small>
                        </div>
                        <i class="fa-solid fa-chevron-right text-muted"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <script>
                document.addEventListener("DOMContentLoaded", function(){
                    var myModal = new bootstrap.Modal(document.getElementById('modalBusca'));
                    myModal.show();
                });
            </script>
        <?php elseif(isset($_GET['termo_busca'])): ?>
            <div class="alert alert-warning">Nenhum paciente encontrado.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>