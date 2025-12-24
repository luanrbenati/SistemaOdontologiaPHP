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
                d.id, 
                d.nome, 
                d.periodo, 
                d.professor_id,
                p.nome as professor_nome,
                p.siape as professor_siape,
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
                id, 
                nome, 
                siape, 
                ativo, 
                telefone1, 
                telefone2,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM professores
            WHERE id = ?
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
                id, 
                nome, 
                matricula, 
                ativo, 
                telefone1, 
                telefone2,
                email,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM alunos
            WHERE id = ?
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
            ['label' => 'Quantidade Necessária', 'campo' => 'qtd_necessario', 'icone' => 'fa-sort-numeric-up', 'tipo' => 'badge'],
            ['label' => 'Data de Criação', 'campo' => 'created', 'icone' => 'fa-calendar-plus', 'tipo' => 'data'],
            ['label' => 'Última Modificação', 'campo' => 'modified', 'icone' => 'fa-calendar-check', 'tipo' => 'data']
        ],
        'sql' => "
            SELECT 
                id, 
                nome, 
                codigo, 
                codigo_sus,
                qtd_necessario,
                DATE_FORMAT(created, '%d/%m/%Y às %H:%i') as created,
                DATE_FORMAT(modified, '%d/%m/%Y às %H:%i') as modified
            FROM procedimentos
            WHERE id = ?
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
    
    // Se o campo estiver vazio, não exibe a linha
    if (empty($valor) && $campo['tipo'] !== 'status') {
        return null;
    }
    
    $html = '<div class="info-row">';
    $html .= '<div class="info-label">';
    $html .= '<i class="fa-solid ' . $campo['icone'] . ' me-1"></i> ' . $campo['label'];
    $html .= '</div>';
    $html .= '<div class="info-value' . (isset($campo['negrito']) && $campo['negrito'] ? ' fw-bold' : '') . '">';
    
    switch ($campo['tipo']) {
        case 'code':
            $html .= '<code class="text-secondary fw-bold">#' . htmlspecialchars($valor) . '</code>';
            break;
            
        case 'badge':
            $sufixo = $campo['sufixo'] ?? '';
            $html .= '<span class="badge bg-info text-dark">' . htmlspecialchars($valor) . ' ' . $sufixo . '</span>';
            break;
            
        case 'status':
            if ($valor == 1) {
                $html .= '<span class="badge bg-success">Ativo</span>';
            } else {
                $html .= '<span class="badge bg-danger">Inativo</span>';
            }
            break;
            
        case 'data':
            $html .= '<span class="text-muted">' . htmlspecialchars($valor) . '</span>';
            break;
            
        default: // text
            $html .= htmlspecialchars($valor);
            
            // Campo extra (como SIAPE do professor)
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
        color: #555; 
        font-size: 0.85rem;
        margin-bottom: 4px;
    }
    .info-value { 
        color: #333; 
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