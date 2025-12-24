<?php
require_once 'conexao.php';

$tipo = $_GET['tipo'] ?? null;
$id = $_GET['id'] ?? null;

if (!$tipo || !$id) {
    echo '<div class="alert alert-danger">Parâmetros inválidos.</div>';
    exit;
}

// Configuração das tabelas e campos
$config = [
    'disciplina' => [
        'tabela' => 'disciplinas',
        'titulo' => 'Disciplina',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome da Disciplina', 'campo' => 'nome', 'icone' => 'fa-book', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'Período', 'campo' => 'periodo', 'icone' => 'fa-calendar-days', 'tipo' => 'badge', 'sufixo' => 'º Período'],
            ['label' => 'Professor Responsável', 'campo' => 'professor_nome', 'icone' => 'fa-chalkboard-user', 'tipo' => 'text', 'campo_extra' => 'professor_siape', 'label_extra' => 'SIAPE'],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                d.id, d.nome, d.periodo, d.professor_id,
                p.nome as professor_nome, p.siape as professor_siape,
                DATE_FORMAT(d.created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(d.modified, '%d/%m/%Y às %H:%i') as modified
            FROM disciplinas d
            LEFT JOIN professores p ON d.professor_id = p.id
            WHERE d.id = ?
        "
    ],
    
    'professor' => [
        'tabela' => 'professores',
        'titulo' => 'Professor',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome Completo', 'campo' => 'nome', 'icone' => 'fa-user', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'SIAPE', 'campo' => 'siape', 'icone' => 'fa-id-card', 'tipo' => 'code'],
            ['label' => 'Status', 'campo' => 'ativo', 'icone' => 'fa-circle-check', 'tipo' => 'status'],
            ['label' => 'Telefone Principal', 'campo' => 'telefone1', 'icone' => 'fa-phone', 'tipo' => 'text'],
            ['label' => 'Telefone Secundário', 'campo' => 'telefone2', 'icone' => 'fa-phone', 'tipo' => 'text'],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                id, nome, siape, ativo, telefone1, telefone2,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM professores WHERE id = ?
        "
    ],
    
    'aluno' => [
        'tabela' => 'alunos',
        'titulo' => 'Aluno',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome Completo', 'campo' => 'nome', 'icone' => 'fa-user', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'Matrícula', 'campo' => 'matricula', 'icone' => 'fa-id-card', 'tipo' => 'code'],
            ['label' => 'Status', 'campo' => 'ativo', 'icone' => 'fa-circle-check', 'tipo' => 'status'],
            ['label' => 'E-mail', 'campo' => 'email', 'icone' => 'fa-envelope', 'tipo' => 'text'],
            ['label' => 'Telefone Principal', 'campo' => 'telefone1', 'icone' => 'fa-phone', 'tipo' => 'text'],
            ['label' => 'Telefone Secundário', 'campo' => 'telefone2', 'icone' => 'fa-phone', 'tipo' => 'text'],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                id, nome, matricula, ativo, telefone1, telefone2, email,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM alunos WHERE id = ?
        "
    ],

    // === NOVO BLOCO ADICIONADO: USUÁRIO ===
    'usuario' => [
        'tabela' => 'users',
        'titulo' => 'Usuário',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome', 'campo' => 'name', 'icone' => 'fa-user', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'Login (Username)', 'campo' => 'username', 'icone' => 'fa-key', 'tipo' => 'code'],
            ['label' => 'Grupo de Acesso', 'campo' => 'nome_grupo', 'icone' => 'fa-users', 'tipo' => 'badge'],
            ['label' => 'Status', 'campo' => 'status', 'icone' => 'fa-circle-check', 'tipo' => 'status'],
            ['label' => 'Horários Permitidos', 'campo' => 'resumo_horarios', 'icone' => 'fa-clock', 'tipo' => 'html'], // Tipo 'html' novo
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                u.id, 
                u.name, 
                u.username, 
                u.status,
                u.created,
                u.modified,
                g.name as nome_grupo,
                DATE_FORMAT(u.created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(u.modified, '%d/%m/%Y às %H:%i') as modified,
                GROUP_CONCAT(
                    CONCAT(
                        CASE h.dia_semana
                            WHEN 1 THEN 'Seg' WHEN 2 THEN 'Ter' WHEN 3 THEN 'Qua'
                            WHEN 4 THEN 'Qui' WHEN 5 THEN 'Sex' WHEN 6 THEN 'Sáb' WHEN 7 THEN 'Dom'
                        END,
                        ': ', DATE_FORMAT(h.hora_inicio, '%H:%i'), ' às ', DATE_FORMAT(h.hora_fim, '%H:%i')
                    ) ORDER BY h.dia_semana ASC SEPARATOR '<br>'
                ) as resumo_horarios
            FROM users u
            LEFT JOIN groups g ON u.group_id = g.id
            LEFT JOIN horarios_acesso h ON u.id = h.user_id
            WHERE u.id = ?
            GROUP BY u.id
        "
    ]
];

// Verifica se o tipo existe
if (!isset($config[$tipo])) {
    echo '<div class="alert alert-danger">Tipo inválido.</div>';
    exit;
}

$cfg = $config[$tipo];

try {
    $stmt = $pdo->prepare($cfg['sql']);
    $stmt->execute([$id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dados) {
        echo '<div class="alert alert-warning">' . $cfg['titulo'] . ' não encontrado(a).</div>';
        exit;
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
    exit;
}

// Função para renderizar o valor baseado no tipo
function renderizarCampo($campo, $dados) {
    $valor = $dados[$campo['campo']] ?? null;
    
    // Se o campo estiver vazio e não for status (que pode ser 0) e nem html (que pode ser vazio), retorna null
    // Nota: Adicionei verificação se é zero (0) para não esconder campos numéricos válidos
    if ((empty($valor) && $valor !== '0' && $valor !== 0) && $campo['tipo'] !== 'status') {
         // Para horários vazios, mostra mensagem
         if ($campo['tipo'] === 'html' && $campo['campo'] === 'resumo_horarios') {
             $valor = "<em class='text-muted'>Sem restrições de horário</em>";
         } else {
             return null;
         }
    }
    
    $html = '<div class="info-row">';
    $html .= '<div class="info-label">';
    $html .= '<i class="fa-solid ' . $campo['icone'] . ' me-1"></i> ' . $campo['label'];
    $html .= '</div>';
    $html .= '<div class="info-value' . (isset($campo['negrito']) && $campo['negrito'] ? ' fw-bold' : '') . '">';
    
    switch ($campo['tipo']) {
        case 'code':
            $html .= '<code class="text-primary fw-bold">' . htmlspecialchars($valor) . '</code>';
            break;
            
        case 'badge':
            $sufixo = $campo['sufixo'] ?? '';
            // Se for nome_grupo (usuário), usa cor secundária, senão usa info
            $cor = ($campo['campo'] == 'nome_grupo') ? 'bg-secondary' : 'bg-info text-dark';
            $html .= '<span class="badge ' . $cor . '">' . htmlspecialchars($valor) . ' ' . $sufixo . '</span>';
            break;
            
        case 'status':
            if ($valor == 1) {
                $html .= '<span class="badge bg-success bg-opacity-75">Ativo</span>';
            } else {
                $html .= '<span class="badge bg-danger bg-opacity-75">Inativo</span>';
            }
            break;
        
        // NOVO TIPO ADICIONADO PARA O USUÁRIO
        case 'html':
            // Não usa htmlspecialchars para permitir as tags <br>
            $html .= '<span style="line-height: 1.6;">' . $valor . '</span>';
            break;
            
        case 'data':
            $html .= '<span class="text-muted small">' . htmlspecialchars($valor) . '</span>';
            break;
            
        default: // text
            $html .= htmlspecialchars($valor);
            
            if (isset($campo['campo_extra']) && !empty($dados[$campo['campo_extra']])) {
                $html .= '<br><small class="text-muted">';
                $html .= $campo['label_extra'] . ': <code>' . htmlspecialchars($dados[$campo['campo_extra']]) . '</code>';
                $html .= '</small>';
            }
            break;
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}
?>

<style>
    .info-row { 
        padding: 12px 0; 
        border-bottom: 1px solid #f0f0f0; 
    }
    .info-row:last-child { 
        border-bottom: none; 
    }
    .info-label { 
        font-weight: 600; 
        color: #6c757d; 
        font-size: 0.8rem;
        text-transform: uppercase;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }
    .info-value { 
        color: #2c3e50; 
        font-size: 0.95rem;
    }
</style>

<div class="container-fluid p-0">
    <?php foreach ($cfg['campos'] as $campo): ?>
        <?php 
        $html = renderizarCampo($campo, $dados);
        if ($html) echo $html;
        ?>
    <?php endforeach; ?>
</div>