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
    // --- CADASTROS BÁSICOS ---
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

    'procedimento' => [
        'tabela' => 'procedimentos',
        'titulo' => 'Procedimento',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome do Procedimento', 'campo' => 'nome', 'icone' => 'fa-tooth', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'Código', 'campo' => 'codigo', 'icone' => 'fa-barcode', 'tipo' => 'code'],
            ['label' => 'Código SUS', 'campo' => 'codigo_sus', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Qtd. Necessária', 'campo' => 'qtd_necessario', 'icone' => 'fa-sort-numeric-up', 'tipo' => 'badge'],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                id, nome, codigo, codigo_sus, qtd_necessario,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM procedimentos WHERE id = ?
        "
    ],

    // --- NOVOS TIPOS ADICIONADOS ---

    'perfil' => [
        'tabela' => 'perfis',
        'titulo' => 'Perfil',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome do Perfil', 'campo' => 'nome', 'icone' => 'fa-id-badge', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                id, nome,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM perfis WHERE id = ?
        "
    ],

  'turma' => [
        'tabela' => 'turmas',
        'titulo' => 'Turma',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome da Turma', 'campo' => 'nome', 'icone' => 'fa-users', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'Ano/Semestre', 'campo' => 'periodo_completo', 'icone' => 'fa-calendar', 'tipo' => 'badge'],
            ['label' => 'Status', 'campo' => 'ativo', 'icone' => 'fa-circle-check', 'tipo' => 'status'],
            ['label' => 'Disciplina', 'campo' => 'disciplina_nome', 'icone' => 'fa-book', 'tipo' => 'text', 'campo_extra' => 'disciplina_periodo_texto', 'label_extra' => 'Período'],
            ['label' => 'Monitor (Aluno)', 'campo' => 'aluno_nome', 'icone' => 'fa-user-graduate', 'tipo' => 'text'],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                t.id,
                t.nome,
                t.ano,
                t.semestre,
                t.ativo,
                CONCAT(t.ano, '/', t.semestre, 'º Semestre') as periodo_completo,
                d.nome as disciplina_nome,
                d.periodo as disciplina_periodo,
                CONCAT(d.periodo, 'º Período') as disciplina_periodo_texto,
                a.nome as aluno_nome,
                DATE_FORMAT(t.created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(t.modified, '%d/%m/%Y às %H:%i') as modified
            FROM turmas t
            LEFT JOIN disciplinas d ON t.disciplina_id = d.id
            LEFT JOIN alunos a ON t.aluno_id = a.id
            WHERE t.id = ?
        "
    ],

    'grupo' => [ // Tabela 'groups' geralmente
        'tabela' => 'groups',
        'titulo' => 'Grupo de Acesso',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome do Grupo', 'campo' => 'name', 'icone' => 'fa-shield-halved', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'Descrição', 'campo' => 'Alias', 'icone' => 'fa-align-left', 'tipo' => 'text'],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                id, name, Alias,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM groups WHERE id = ?
        "
    ],
    
    'usuario' => [
        'tabela' => 'users',
        'titulo' => 'Usuário',
        'campos' => [
            ['label' => 'ID', 'campo' => 'id', 'icone' => 'fa-hashtag', 'tipo' => 'code'],
            ['label' => 'Nome', 'campo' => 'name', 'icone' => 'fa-user', 'tipo' => 'text', 'negrito' => true],
            ['label' => 'Login', 'campo' => 'username', 'icone' => 'fa-key', 'tipo' => 'code'],
            ['label' => 'Status', 'campo' => 'status', 'icone' => 'fa-circle-check', 'tipo' => 'status'],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                id, name, username, status,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM users WHERE id = ?
        "
    ]
];

// Verifica se o tipo existe
if (!isset($config[$tipo])) {
    echo '<div class="alert alert-danger">Tipo inválido: <strong>' . htmlspecialchars($tipo) . '</strong> não configurado.</div>';
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
    echo '<div class="alert alert-danger">Erro SQL: ' . $e->getMessage() . '</div>';
    exit;
}

// Função para renderizar o valor baseado no tipo
function renderizarCampo($campo, $dados) {
    $valor = $dados[$campo['campo']] ?? null;
    
    // Se o campo estiver vazio e não for status (que pode ser 0) e nem html (que pode ser vazio), retorna null
    if ((empty($valor) && $valor !== '0' && $valor !== 0) && $campo['tipo'] !== 'status') {
         if ($campo['tipo'] === 'html' && $campo['campo'] === 'resumo_horarios') {
             $valor = "<em class='text-muted'>Sem restrições</em>";
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
            $html .= '<code class="text-primary fw-bold">#' . htmlspecialchars($valor) . '</code>';
            break;
            
        case 'badge':
            $sufixo = $campo['sufixo'] ?? '';
            $html .= '<span class="badge bg-info text-dark">' . htmlspecialchars($valor) . ' ' . $sufixo . '</span>';
            break;
            
        case 'status':
            if ($valor == 1) {
                $html .= '<span class="badge bg-success bg-opacity-75">Ativo</span>';
            } else {
                $html .= '<span class="badge bg-danger bg-opacity-75">Inativo</span>';
            }
            break;
        
        case 'html':
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