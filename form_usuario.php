<?php
// Conexão com banco de dados
require_once 'conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$mensagem = "";
$tipo_mensagem = "";
$isEdicao = ($id !== null && $id !== false);

// --- MODO: EDIÇÃO - Carregar dados existentes ---
if ($isEdicao) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die("<div class='alert alert-danger m-3'>Usuário não encontrado.</div>");
        }

        // Buscar horários
        $stmtH = $pdo->prepare("SELECT * FROM horarios_acesso WHERE user_id = ?");
        $stmtH->execute([$id]);
        $horariosBanco = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        $horariosAtivos = [];
        foreach ($horariosBanco as $h) {
            $horariosAtivos[$h['dia_semana']] = [
                'inicio' => substr($h['hora_inicio'], 0, 5),
                'fim' => substr($h['hora_fim'], 0, 5)
            ];
        }

    } catch (Exception $e) {
        die("<div class='alert alert-danger m-3'>Erro: " . $e->getMessage() . "</div>");
    }
} else {
    // --- MODO: NOVO USUÁRIO - Valores padrão ---
    $user = [
        'name' => '',
        'username' => '',
        'email' => '',
        'group_id' => '',
        'status' => 1
    ];
    $horariosAtivos = [];
}

// --- PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $nome = trim($_POST['name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email'] ?? '');
        $grupo = (int)$_POST['group_id'];
        $status = isset($_POST['status']) ? 1 : 0;
        
        // Validações básicas
        if (empty($nome)) {
            throw new Exception("O nome é obrigatório!");
        }
        if (empty($username)) {
            throw new Exception("O login (username) é obrigatório!");
        }

        if ($isEdicao) {
            // === EDITAR USUÁRIO ===
            
            // Verifica se o username já existe em outro usuário
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                throw new Exception("Este login (username) já está sendo usado por outro usuário!");
            }
            
            // Se preencheu nova senha, atualiza com senha
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 4) {
                    throw new Exception("A senha deve ter no mínimo 4 caracteres!");
                }
                
                $senhaHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $sql = "UPDATE users SET name=?, username=?, email=?, group_id=?, status=?, password=?, modified=NOW() WHERE id=?";
                $params = [$nome, $username, $email, $grupo, $status, $senhaHash, $id];
                
                $mensagem = "Usuário atualizado com sucesso! Senha alterada.";
            } else {
                // Mantém a senha atual
                $sql = "UPDATE users SET name=?, username=?, email=?, group_id=?, status=?, modified=NOW() WHERE id=?";
                $params = [$nome, $username, $email, $grupo, $status, $id];
                
                $mensagem = "Usuário atualizado com sucesso!";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $user_id = $id;

        } else {
            // === CRIAR NOVO USUÁRIO ===
            
            // Verifica se o username já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception("Este login (username) já está cadastrado!");
            }
            
            if (empty($_POST['password'])) {
                throw new Exception("A senha é obrigatória para novos usuários!");
            }
            
            if (strlen($_POST['password']) < 4) {
                throw new Exception("A senha deve ter no mínimo 4 caracteres!");
            }
            
            $senhaHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (name, username, email, password, group_id, status, created, modified) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $username, $email, $senhaHash, $grupo, $status]);
            
            $user_id = $pdo->lastInsertId();
            $mensagem = "Usuário cadastrado com sucesso! Senha criptografada.";
        }

        // === ATUALIZAR HORÁRIOS ===
        $pdo->prepare("DELETE FROM horarios_acesso WHERE user_id = ?")->execute([$user_id]);

        if (isset($_POST['horarios'])) {
            $stmtH = $pdo->prepare("INSERT INTO horarios_acesso (user_id, dia_semana, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)");
            foreach ($_POST['horarios'] as $dia => $dados) {
                if (isset($dados['ativo'])) {
                    if (empty($dados['inicio']) || empty($dados['fim'])) {
                        throw new Exception("Horário inválido para o dia " . $dia);
                    }
                    if ($dados['inicio'] >= $dados['fim']) {
                        throw new Exception("O horário de início deve ser menor que o horário de fim!");
                    }
                    
                    $stmtH->execute([$user_id, $dia, $dados['inicio'] . ':00', $dados['fim'] . ':00']);
                }
            }
        }

        $pdo->commit();
        $tipo_mensagem = "success";
        
        echo "<script>
            setTimeout(function() {
                window.parent.location.reload();
            }, 1500);
        </script>";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar grupos disponíveis
$grupos = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name ASC");
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback se não existir tabela groups
    $grupos = [];
}

$diasSemana = [
    1 => 'Segunda-feira', 
    2 => 'Terça-feira', 
    3 => 'Quarta-feira', 
    4 => 'Quinta-feira', 
    5 => 'Sexta-feira', 
    6 => 'Sábado', 
    7 => 'Domingo'
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdicao ? 'Editar' : 'Novo' ?> Usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .password-toggle {
            position: relative;
        }
        .password-toggle .toggle-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .password-toggle .toggle-icon:hover {
            color: #495057;
        }
        .senha-info {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    
    <?php if($mensagem): ?>
        <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show">
            <i class="fa-solid fa-<?= $tipo_mensagem == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $mensagem ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" id="formUsuario">
        <div class="row g-3">
            
            <div class="col-12">
                <h6 class="fw-bold text-secondary mb-3">
                    <i class="fa-solid fa-user-<?= $isEdicao ? 'pen' : 'plus' ?> me-2"></i>
                    <?= $isEdicao ? 'Editar Usuário: ' . htmlspecialchars($user['username']) : 'Novo Usuário' ?>
                </h6>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Nome Completo <span class="text-danger">*</span>
                </label>
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($user['name']) ?>" 
                       required minlength="3">
                <small class="text-muted">Nome completo do usuário</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Login (Username) <span class="text-danger">*</span>
                </label>
                <input type="text" name="username" class="form-control" 
                       value="<?= htmlspecialchars($user['username']) ?>" 
                       required minlength="3" pattern="[a-zA-Z0-9_]+"
                       title="Apenas letras, números e underscore">
                <small class="text-muted">Login único para acesso ao sistema</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">E-mail</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                       placeholder="exemplo@email.com">
                <small class="text-muted">E-mail para recuperação de senha</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">
                    Senha <?= $isEdicao ? '' : '<span class="text-danger">*</span>' ?>
                </label>
                <div class="password-toggle">
                    <input type="password" 
                           name="password" 
                           id="passwordInput"
                           class="form-control" 
                           placeholder="<?= $isEdicao ? 'Deixe em branco para manter a atual' : 'Digite a senha (mín. 4 caracteres)' ?>"
                           <?= $isEdicao ? '' : 'required' ?>
                           minlength="4">
                    <i class="fa-solid fa-eye toggle-icon" 
                       id="togglePassword"
                       onclick="togglePasswordVisibility()"></i>
                </div>
                
                <?php if($isEdicao): ?>
                    <small class="text-muted">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        Preencha apenas se quiser alterar a senha atual
                    </small>
                <?php else: ?>
                    <div class="senha-info mt-2">
                        <small>
                            <i class="fa-solid fa-shield-halved me-1"></i>
                            <strong>Senha Segura:</strong> A senha será automaticamente criptografada com algoritmo BCrypt.
                        </small>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Grupo</label>
                <select name="group_id" class="form-select">
                    <?php if(empty($grupos)): ?>
                        <option value="">Sem Grupo</option>
                        <option value="1" <?= $user['group_id'] == 1 ? 'selected' : '' ?>>Admin</option>
                        <option value="2" <?= $user['group_id'] == 2 ? 'selected' : '' ?>>Operador</option>
                        <option value="3" <?= $user['group_id'] == 3 ? 'selected' : '' ?>>Visualizador</option>
                    <?php else: ?>
                        <option value="">Selecione...</option>
                        <?php foreach($grupos as $grupo): ?>
                            <option value="<?= $grupo['id'] ?>" <?= ($user['group_id'] == $grupo['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($grupo['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="text-muted">Grupo de permissões do usuário</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Status</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="status" value="1" 
                           id="statusSwitch"
                           <?= $user['status'] == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="statusSwitch">
                        <span id="statusLabel">
                            <?= $user['status'] == 1 ? 'Usuário Ativo' : 'Usuário Inativo' ?>
                        </span>
                    </label>
                </div>
                <small class="text-muted">Apenas usuários ativos podem fazer login</small>
            </div>

            <div class="col-12 mt-4">
                <hr>
                <h6 class="fw-bold text-secondary mb-3">
                    <i class="fa-solid fa-clock me-2"></i>Horários de Acesso (Opcional)
                </h6>
                <div class="alert alert-info py-2">
                    <small>
                        <i class="fa-solid fa-lightbulb me-1"></i>
                        <strong>Dica:</strong> Deixe desmarcado para permitir acesso 24/7. 
                        Marque apenas os dias/horários específicos que o usuário pode acessar o sistema.
                    </small>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">
                                    <input type="checkbox" id="selectAll" 
                                           onclick="toggleAllDays(this)"
                                           title="Marcar/Desmarcar todos">
                                </th>
                                <th>Dia da Semana</th>
                                <th style="width: 150px;">Horário Início</th>
                                <th style="width: 150px;">Horário Fim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diasSemana as $num => $nomeDia): 
                                $temHorario = isset($horariosAtivos[$num]);
                                $inicio = $temHorario ? $horariosAtivos[$num]['inicio'] : '08:00';
                                $fim    = $temHorario ? $horariosAtivos[$num]['fim'] : '18:00';
                            ?>
                            <tr>
                                <td class="text-center">
                                    <input class="form-check-input dia-checkbox" type="checkbox" 
                                           name="horarios[<?= $num ?>][ativo]" value="1" 
                                           <?= $temHorario ? 'checked' : '' ?>>
                                </td>
                                <td><strong><?= $nomeDia ?></strong></td>
                                <td>
                                    <input type="time" class="form-control form-control-sm" 
                                           name="horarios[<?= $num ?>][inicio]" value="<?= $inicio ?>">
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm" 
                                           name="horarios[<?= $num ?>][fim]" value="<?= $fim ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check me-2"></i>
                    <?= $isEdicao ? 'Salvar Alterações' : 'Cadastrar Usuário' ?>
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.parent.fecharModal()">
                    <i class="fa-solid fa-times me-2"></i>Cancelar
                </button>
            </div>

        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle de visualização de senha
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('passwordInput');
        const toggleIcon = document.getElementById('togglePassword');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    
    // Toggle de status com feedback visual
    document.getElementById('statusSwitch')?.addEventListener('change', function() {
        const label = document.getElementById('statusLabel');
        label.textContent = this.checked ? 'Usuário Ativo' : 'Usuário Inativo';
    });
    
    // Marcar/Desmarcar todos os dias
    function toggleAllDays(checkbox) {
        const checkboxes = document.querySelectorAll('.dia-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }
    
    // Validação de horário antes de enviar (CORRIGIDO)
    document.getElementById('formUsuario').addEventListener('submit', function(e) {
        // Pega apenas os dias que estão marcados
        const checkboxes = document.querySelectorAll('.dia-checkbox:checked');
        let temErro = false;

        checkboxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            
            // Pega todos os inputs de tempo daquela linha (índice 0 = inicio, índice 1 = fim)
            const inputs = row.querySelectorAll('input[type="time"]');
            
            // Verifica se os inputs existem antes de acessar o valor
            if (inputs.length >= 2) {
                const inicio = inputs[0].value;
                const fim = inputs[1].value;
                
                // Só valida se ambos estiverem preenchidos
                if (inicio && fim) {
                    if (inicio >= fim) {
                        e.preventDefault(); // Bloqueia o envio
                        temErro = true;
                        
                        // Efeito visual de erro
                        row.classList.add('table-danger');
                        setTimeout(() => row.classList.remove('table-danger'), 3000);
                    }
                }
            }
        });

        if (temErro) {
            alert('Erro: O horário de início deve ser menor que o horário de fim!');
        }
    });
</script>
</body>
</html>