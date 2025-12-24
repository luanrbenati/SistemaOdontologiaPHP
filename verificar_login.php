<?php
session_start();

// Inclui a conexão global com o banco de dados
require_once 'conexao.php';

// Verifica se a conexão PDO foi estabelecida
if (!isset($pdo)) {
    die("Erro: Falha na conexão com banco de dados.");
}

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // --- CORREÇÃO AQUI ---
    // Antes verificava (empty($username) || empty($password))
    // Agora verifica APENAS o usuário. A senha pode vir vazia.
    if (empty($username)) {
        $_SESSION['erro_login'] = "O campo Usuário é obrigatório.";
        header("Location: index.php");
        exit;
    }
    
    try {
        // Busca o usuário no banco
        $sql = "SELECT u.id, u.name, u.username, u.password, u.status, u.group_id, g.name as nome_grupo
                FROM users u
                LEFT JOIN groups g ON u.group_id = g.id
                WHERE u.username = :username
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se usuário existe
        if (!$usuario) {
            $_SESSION['erro_login'] = "Usuário não encontrado.";
            header("Location: index.php");
            exit;
        }

        // === LÓGICA DE SENHA (JÁ ESTAVA CORRETA, AGORA VAI FUNCIONAR) ===
        $senha_digitada = $_POST['password'] ?? '';
        $senha_banco = $usuario['password'];

        $senha_valida = false;

        if (empty($senha_digitada)) {
            // CENÁRIO 1: Usuário não digitou senha -> LIBERA O ACESSO
            $senha_valida = true; 
        } else {
            // CENÁRIO 2: Usuário digitou senha -> VERIFICA SE BATE
            if (password_verify($senha_digitada, $senha_banco)) {
                $senha_valida = true;
            }
        }

        if (!$senha_valida) {
            $_SESSION['erro_login'] = "Senha incorreta.";
            header("Location: index.php");
            exit;
        }

        // Verifica se o usuário está ativo (ADICIONEI POIS TINHA SUMIDO)
        if ($usuario['status'] != 1) {
            $_SESSION['erro_login'] = "Usuário inativo.";
            header("Location: index.php");
            exit;
        }
        
        // === VERIFICAÇÃO DE DIA E HORÁRIO ===
        
        $dia_atual = date('N'); // 1 (segunda) a 7 (domingo)
        $hora_atual = date('H:i:s');
        
        $sql_horarios = "SELECT dia_semana, hora_inicio, hora_fim 
                         FROM horarios_acesso 
                         WHERE user_id = :user_id";
        
        $stmt_horarios = $pdo->prepare($sql_horarios);
        $stmt_horarios->bindParam(':user_id', $usuario['id'], PDO::PARAM_INT);
        $stmt_horarios->execute();
        
        $horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($horarios)) {
            $acesso_permitido = false;
            $horarios_disponiveis = [];
            
            foreach ($horarios as $horario) {
                $dia_nome = ['', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
                // Correção de segurança para índice indefinido
                $nome_dia = $dia_nome[$horario['dia_semana']] ?? 'Dia';
                
                $horarios_disponiveis[] = $nome_dia . ": " . 
                                          substr($horario['hora_inicio'], 0, 5) . " às " . 
                                          substr($horario['hora_fim'], 0, 5);
                
                if ($horario['dia_semana'] == $dia_atual) {
                    if ($hora_atual >= $horario['hora_inicio'] && $hora_atual <= $horario['hora_fim']) {
                        $acesso_permitido = true;
                        break;
                    }
                }
            }
            
            if (!$acesso_permitido) {
                $_SESSION['erro_login'] = "Acesso negado. Horários permitidos:<br>" . 
                                          implode("<br>", $horarios_disponiveis);
                header("Location: index.php");
                exit;
            }
        }
        
        // === LOGIN APROVADO ===
        
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['name'];
        $_SESSION['user_username'] = $usuario['username'];
        $_SESSION['usuario_logado'] = true; // IMPORTANTE PARA O DASHBOARD
        $_SESSION['logado'] = true;         // MANTENDO COMPATIBILIDADE
        $_SESSION['user_group_id'] = $usuario['group_id'];
        $_SESSION['user_group_name'] = $usuario['nome_grupo'];
        $_SESSION['login_time'] = time();
        
        // Log de acesso
        $sql_log = "INSERT INTO logs_acesso (user_id, data_hora, ip_address) 
                    VALUES (:user_id, NOW(), :ip)";
        
        $stmt_log = $pdo->prepare($sql_log);
        $stmt_log->bindParam(':user_id', $usuario['id'], PDO::PARAM_INT);
        $ip = $_SERVER['REMOTE_ADDR']; // Variável auxiliar para bind
        $stmt_log->bindParam(':ip', $ip, PDO::PARAM_STR);
        $stmt_log->execute();
        
        header("Location: dashboard.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['erro_login'] = "Erro no sistema.";
        error_log("Erro no login: " . $e->getMessage());
        header("Location: index.php");
        exit;
    }
    
} else {
    header("Location: index.php");
    exit;
}
?>