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
    // Lista de páginas que fazem parte do grupo "Manutenção"
    // Adicione aqui qualquer página nova que deva manter esse menu aberto
    $paginas_manutencao = ['usuarios', 'grupos', 'criar_usuario', 'editar_usuario'];
    
    // Verifica se a página atual pertence ao grupo
    $is_manutencao_active = in_array($pagina_atual, $paginas_manutencao);
    
    // Define classes dinâmicas
    $collapse_class = $is_manutencao_active ? 'show' : ''; // 'show' mantém o menu expandido
    $parent_active = $is_manutencao_active ? 'active' : ''; // Destaca o item pai "Manutenção"
    $aria_expanded = $is_manutencao_active ? 'true' : 'false'; // Controla a setinha

    // Classes dos itens individuais
    $active_home = ($pagina_atual == 'home') ? 'active' : '';
    $active_pacientes = ($pagina_atual == 'pacientes') ? 'active' : '';
    
    // Subitens
    $active_usuarios = ($pagina_atual == 'usuarios') ? 'active' : '';
    $active_grupos = ($pagina_atual == 'grupos') ? 'active' : ''; 

    echo '
    <nav class="nav flex-column mt-2">
        <a class="nav-link ' . $active_home . '" href="?page=home">
            <i class="fa-solid fa-house me-2" style="width:20px"></i> Início
        </a>

        <a class="nav-link ' . $active_pacientes . '" href="?page=pacientes">
            <i class="fa-solid fa-users me-2" style="width:20px"></i> Pacientes
        </a>

        <a class="nav-link d-flex justify-content-between align-items-center ' . $parent_active . '" 
           data-bs-toggle="collapse" 
           href="#submenuManutencao" 
           role="button" 
           aria-expanded="' . $aria_expanded . '" 
           aria-controls="submenuManutencao">
            <span>
                <i class="fa-solid fa-screwdriver-wrench me-2" style="width:20px"></i> Manutenção
            </span>
            <i class="fa-solid fa-chevron-right menu-arrow"></i>
        </a>

        <div class="collapse ' . $collapse_class . '" id="submenuManutencao">
            <div class="submenu nav flex-column">
                
                <a class="nav-link ' . $active_usuarios . '" href="?page=usuarios">
                    <i class="fa-solid fa-user-gear me-2"></i> Usuários
                </a>
                
                <a class="nav-link ' . $active_grupos . '" href="?page=grupos">
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
    /* === CSS GERAL === */
    body { 
        background-color: #f4f6f9; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        font-size: 0.9rem; 
        overflow-x: hidden; 
    }
    
    /* SIDEBAR (Desktop) */
    .sidebar { 
        background-color: #2c3e50; 
        min-height: 100vh; 
        color: #ecf0f1; 
    }
    
    /* ESTILOS DE MENU */
    .nav-link { 
        color: #bdc3c7; 
        margin-bottom: 2px; 
        padding: 10px 15px; 
        cursor: pointer; 
        text-decoration: none; 
        display: block;
        transition: all 0.2s ease;
    }
    .nav-link:hover { 
        background-color: #34495e; 
        color: #fff; 
        padding-left: 20px; 
    }
    .nav-link.active { 
        background-color: #34495e; 
        color: #fff; 
        border-left: 4px solid #3498db; 
    }

    /* --- NOVOS ESTILOS PARA O SUBMENU --- */
    
    /* Setinha que gira */
    .menu-arrow {
        transition: transform 0.3s ease;
        font-size: 0.8rem;
    }
    /* Quando o menu está aberto (aria-expanded=true), gira a seta */
    .nav-link[aria-expanded="true"] .menu-arrow {
        transform: rotate(90deg);
    }
    
    /* Estilo dos itens filhos */
    .submenu .nav-link {
        padding-left: 48px; /* Recuo para a direita */
        font-size: 0.85rem; 
        background-color: rgba(0, 0, 0, 0.15); /* Fundo um pouco mais escuro */
        border-left: none; /* Remove a borda azul padrão */
    }
    .submenu .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.05);
        padding-left: 52px;
    }
    /* Destaque específico para o filho ativo */
    .submenu .nav-link.active {
        background-color: rgba(52, 152, 219, 0.25); 
        color: #3498db;
        font-weight: bold;
        border-left: 4px solid #3498db;
    }

    /* ------------------------------------- */
    
    .logo-area { 
        padding: 15px; 
        background-color: #253342; 
        font-weight: bold; 
        font-size: 1.2rem; 
        color: white;
    }
    
    /* OFFCANVAS (Menu Mobile) */
    .offcanvas-custom {
        background-color: #2c3e50;
        color: white;
    }
    .offcanvas-custom .btn-close {
        filter: invert(1); 
    }

    /* CONTEÚDO PRINCIPAL */
    .main-content { 
        min-height: 100vh; 
        background-color: #f4f6f9; 
        display: flex;
        flex-direction: column;
    }
    .top-header { 
        background-color: #fff; 
        padding: 5px 20px; 
        border-bottom: 1px solid #dee2e6; 
        height: 60px;
        flex-shrink: 0;
    }
    
    /* DROPDOWN DE USUÁRIO */
    .user-dropdown { cursor: pointer; border-radius: 5px; transition: 0.2s; }
    .user-dropdown:hover { background-color: #f8f9fa; }
    
    .page-body {
        flex-grow: 1;
        overflow-y: auto;
        padding: 0;
    }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row flex-nowrap">
        
        <div class="col-md-3 col-lg-2 sidebar p-0 d-none d-md-block">
            <div class="logo-area">
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
                            <div class="fw-bold text-dark" style="font-size: 0.85rem;">
                                <?= htmlspecialchars($nome_usuario) ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($grupo_usuario) ?>
                            </div>
                        </div>
                        <i class="fa-solid fa-circle-user fa-2x text-secondary"></i>
                        <i class="fa-solid fa-chevron-down ms-2 text-muted" style="font-size: 0.7rem;"></i>
                    </div>
                    
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><h6 class="dropdown-header">Minha Conta</h6></li>
                        <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user-pen me-2"></i>Editar Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger fw-bold" href="logout.php">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>Sair do Sistema
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="page-body">
                <?php
                switch ($pagina) {
                    case 'home':
                        ?>
                        <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted p-5 text-center">
                            <i class="fa-solid fa-tooth fa-4x mb-3 text-secondary" style="opacity: 0.1;"></i>
                            <h4 style="opacity: 0.6;">Bem-vindo</h4>
                            <p class="small" style="opacity: 0.6;">Selecione uma opção no menu para começar.</p>
                        </div>
                        <?php
                        break;

                    case 'pacientes':
                        if (file_exists('prontuario.php')) include 'prontuario.php';
                        else echo "<div class='alert alert-danger m-3'>Erro: Arquivo prontuario.php não encontrado.</div>";
                        break;

                    case 'usuarios':
                        if (file_exists('listar_usuarios.php')) include 'listar_usuarios.php';
                        elseif (file_exists('criar_usuario.php')) { echo "<div class='m-3'>"; include 'criar_usuario.php'; echo "</div>"; }
                        else echo "<div class='alert alert-warning m-3'>Página de usuários em construção.</div>";
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

<div class="offcanvas offcanvas-start offcanvas-custom" tabindex="-1" id="menuMobile">
    <div class="offcanvas-header logo-area">
        <h5 class="offcanvas-title"><i class="fa-solid fa-tooth me-2"></i> Dental SIGO</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="p-3">
            <?php renderizarMenu($pagina); ?>
        </div>
        
        <div class="p-3 border-top border-secondary mt-3">
            <small class="text-white-50">Logado como:</small><br>
            <strong><?= htmlspecialchars($nome_usuario) ?></strong>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>