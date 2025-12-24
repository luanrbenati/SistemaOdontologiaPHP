<?php
// 1. SEGURANÇA E SESSÃO
session_start();

// Se não estiver logado, manda pro login
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

require_once 'conexao.php'; 

$nome_usuario = $_SESSION['user_name'] ?? 'Usuário';
$grupo_usuario = $_SESSION['user_group_name'] ?? 'Nível de Acesso';
$pagina = $_GET['page'] ?? 'home'; 

// === FUNÇÃO PARA GERAR O MENU (COM SUBMENUS) ===
function renderizarMenu($pagina_atual) {
    // Agrupamento de páginas para controle de estado (aberto/fechado)
    $paginas_manutencao = ['usuarios', 'grupos', 'criar_usuario', 'editar_usuario'];
    $paginas_cadastros = ['professores', 'alunos', 'disciplinas']; 
    
    $is_manutencao_active = in_array($pagina_atual, $paginas_manutencao);
    $is_cadastros_active = in_array($pagina_atual, $paginas_cadastros);
    
    // Controle de classes 'active'
    $active_home = ($pagina_atual == 'home') ? 'active' : '';
    $active_pacientes = ($pagina_atual == 'pacientes') ? 'active' : '';
    
    // === NOVO: Lógica para ativar o botão Agenda ===
    $active_agenda = ($pagina_atual == 'agenda') ? 'active' : '';

    echo '
    <nav class="nav flex-column mt-2">
        <a class="nav-link ' . $active_home . '" href="?page=home">
            <i class="fa-solid fa-house me-2" style="width:20px"></i> Início
        </a>

        <a class="nav-link ' . $active_agenda . '" href="?page=agenda">
            <i class="fa-solid fa-calendar-days me-2" style="width:20px"></i> Agenda
        </a>

        <a class="nav-link ' . $active_pacientes . '" href="?page=pacientes">
            <i class="fa-solid fa-users me-2" style="width:20px"></i> Pacientes
        </a>

        <a class="nav-link d-flex justify-content-between align-items-center ' . ($is_cadastros_active ? 'active' : '') . '" 
           data-bs-toggle="collapse" href="#submenuCadastros" role="button" 
           aria-expanded="' . ($is_cadastros_active ? 'true' : 'false') . '">
            <span>
                <i class="fa-solid fa-address-book me-2" style="width:20px"></i> Cadastros
            </span>
            <i class="fa-solid fa-chevron-right menu-arrow"></i>
        </a>
        <div class="collapse ' . ($is_cadastros_active ? 'show' : '') . '" id="submenuCadastros">
            <div class="submenu nav flex-column">
                <a class="nav-link ' . ($pagina_atual == 'professores' ? 'active' : '') . '" href="?page=professores">
                    <i class="fa-solid fa-chalkboard-user me-2"></i> Professores
                </a>
                <a class="nav-link ' . ($pagina_atual == 'alunos' ? 'active' : '') . '" href="?page=alunos">
                    <i class="fa-solid fa-user-graduate me-2"></i> Alunos
                </a>
                <a class="nav-link ' . ($pagina_atual == 'disciplinas' ? 'active' : '') . '" href="?page=disciplinas">
                    <i class="fa-solid fa-book me-2"></i> Disciplinas
                </a>
            </div>
        </div>

        <a class="nav-link d-flex justify-content-between align-items-center ' . ($is_manutencao_active ? 'active' : '') . '" 
           data-bs-toggle="collapse" href="#submenuManutencao" role="button" 
           aria-expanded="' . ($is_manutencao_active ? 'true' : 'false') . '">
            <span>
                <i class="fa-solid fa-screwdriver-wrench me-2" style="width:20px"></i> Manutenção
            </span>
            <i class="fa-solid fa-chevron-right menu-arrow"></i>
        </a>
        <div class="collapse ' . ($is_manutencao_active ? 'show' : '') . '" id="submenuManutencao">
            <div class="submenu nav flex-column">
                <a class="nav-link ' . ($pagina_atual == 'usuarios' ? 'active' : '') . '" href="?page=usuarios">
                    <i class="fa-solid fa-user-gear me-2"></i> Usuários
                </a>
                <a class="nav-link ' . ($pagina_atual == 'grupos' ? 'active' : '') . '" href="?page=grupos">
                    <i class="fa-solid fa-users-gear me-2"></i> Grupos
                </a>
            </div>
        </div>     
    </nav>';
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
    body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 0.9rem; overflow-x: hidden; }
    .sidebar { background-color: #2c3e50; min-height: 100vh; color: #ecf0f1; }
    .nav-link { color: #bdc3c7; margin-bottom: 2px; padding: 10px 15px; cursor: pointer; text-decoration: none; display: block; transition: all 0.2s ease; }
    .nav-link:hover { background-color: #34495e; color: #fff; padding-left: 20px; }
    .nav-link.active { background-color: #34495e; color: #fff; border-left: 4px solid #3498db; }
    .menu-arrow { transition: transform 0.3s ease; font-size: 0.8rem; }
    .nav-link[aria-expanded="true"] .menu-arrow { transform: rotate(90deg); }
    .submenu .nav-link { padding-left: 48px; font-size: 0.85rem; background-color: rgba(0, 0, 0, 0.15); border-left: none; }
    .submenu .nav-link:hover { background-color: rgba(255, 255, 255, 0.05); padding-left: 52px; }
    .submenu .nav-link.active { background-color: rgba(52, 152, 219, 0.25); color: #3498db; font-weight: bold; border-left: 4px solid #3498db; }
    .logo-area { padding: 15px; background-color: #253342; font-weight: bold; font-size: 1.2rem; color: white; }
    .main-content { min-height: 100vh; background-color: #f4f6f9; display: flex; flex-direction: column; }
    .top-header { background-color: #fff; padding: 5px 20px; border-bottom: 1px solid #dee2e6; height: 60px; flex-shrink: 0; }
    .user-dropdown { cursor: pointer; border-radius: 5px; transition: 0.2s; }
    .user-dropdown:hover { background-color: #f8f9fa; }
    .page-body { flex-grow: 1; overflow-y: auto; padding: 0; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row flex-nowrap">
        <div class="col-md-3 col-lg-2 sidebar p-0 d-none d-md-block">
            <div class="logo-area text-center">
                <i class="fa-solid fa-tooth me-2"></i> Dental SIGO
            </div>
            <div class="p-3">
                <?php renderizarMenu($pagina); ?>
            </div>
        </div>

        <div class="col-12 col-md-9 col-lg-10 p-0 main-content">
            <div class="top-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary d-md-none me-3 border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuMobile">
                        <i class="fa-solid fa-bars fa-lg"></i>
                    </button>
                    <h5 class="m-0 text-secondary fw-bold">D-SIGO</h5>
                </div>
                
                <div class="dropdown">
                    <div class="d-flex align-items-center user-dropdown px-2 py-1" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-end me-2 lh-1 d-none d-sm-block"> 
                            <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= htmlspecialchars($nome_usuario) ?></div>
                            <div class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($grupo_usuario) ?></div>
                        </div>
                        <i class="fa-solid fa-circle-user fa-2x text-secondary"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user-pen me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>

            <div class="page-body">
                <?php
                switch ($pagina) {
                    case 'home':
                        echo '
                        <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted p-5 text-center">
                            <i class="fa-solid fa-tooth fa-4x mb-3 text-secondary" style="opacity: 0.1;"></i>
                            <h4 style="opacity: 0.6;">Bem-vindo ao D-SIGO</h4>
                            <p class="small" style="opacity: 0.6;">Selecione uma opção no menu lateral.</p>
                        </div>';
                        break;

                    // === NOVO CASE PARA A AGENDA ===
                    case 'agenda':
                        if (file_exists('agenda.php')) include 'agenda.php';
                        else echo "<div class='alert alert-warning m-3'>Arquivo agenda.php não encontrado. Verifique se ele está na mesma pasta.</div>";
                        break;

                    case 'pacientes':
                        if (file_exists('prontuario.php')) include 'prontuario.php';
                        else echo "<div class='alert alert-danger m-3'>Erro: prontuario.php não encontrado.</div>";
                        break;

                    case 'professores':
                        if (file_exists('listar_professores.php')) include 'listar_professores.php';
                        else echo "<div class='alert alert-warning m-3'>Arquivo listar_professores.php não encontrado.</div>";
                        break;

                    case 'alunos':
                        if (file_exists('listar_alunos.php')) include 'listar_alunos.php';
                        else echo "<div class='alert alert-warning m-3'>Arquivo listar_alunos.php não encontrado.</div>";
                        break;

                    case 'disciplinas':
                        if (file_exists('listar_disciplinas.php')) include 'listar_disciplinas.php';
                        else echo "<div class='alert alert-warning m-3'>Arquivo listar_disciplinas.php não encontrado.</div>";
                        break;

                    case 'usuarios':
                        if (file_exists('listar_usuarios.php')) include 'listar_usuarios.php';
                        else echo "<div class='alert alert-warning m-3'>Página de usuários não encontrada.</div>";
                        break;
                    
                    case 'grupos':
                        echo "<div class='p-5 text-center text-muted'><h3>Gestão de Grupos</h3><p>Em desenvolvimento...</p></div>";
                        break;

                    default:
                        echo "<div class='p-5 text-center'><h2>Página não encontrada</h2></div>";
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start offcanvas-custom bg-dark text-white" tabindex="-1" id="menuMobile" style="width: 280px;">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title"><i class="fa-solid fa-tooth me-2"></i> Dental SIGO</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="p-3">
            <?php renderizarMenu($pagina); ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>