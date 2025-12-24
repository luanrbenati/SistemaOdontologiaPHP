<?php
// ==================================================================
// ODONTOGRAMA - VERSÃO MYSQL INTEGRADA (ARQUIVO PRINCIPAL)
// ==================================================================
// Este arquivo apenas exibe o odontograma
// O AJAX é processado em ajax_odonto.php separadamente
// ==================================================================

global $pdo, $id_paciente;

// === CARREGAR DADOS DO ODONTOGRAMA ===
$dados_odonto = [];
if (!empty($id_paciente)) {
    try {
        $stmt = $pdo->prepare("SELECT dente, face, status FROM odontograma WHERE paciente_id = ?");
        $stmt->execute([$id_paciente]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['dente'] . '_' . $row['face'];
            $dados_odonto[$key] = intval($row['status']);
        }
    } catch (PDOException $e) {
        error_log("Erro ao carregar odontograma: " . $e->getMessage());
    }
}

// === CARREGAR HISTÓRICO ===
$historico = [];
if (!empty($id_paciente)) {
    try {
        $stmt = $pdo->prepare("
            SELECT dente, face, status_anterior, status_novo, data_registro 
            FROM odontograma_historico 
            WHERE paciente_id = ? 
            ORDER BY data_registro DESC 
            LIMIT 50
        ");
        $stmt->execute([$id_paciente]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao carregar histórico: " . $e->getMessage());
    }
}

// === FUNÇÕES DE LAYOUT ===
$width = 800;
$height = 650;
$centerX = $width / 2; 
$centerY = $height / 2;
$radiusExt = 240;
$radiusInt = 170;
$iconSize = 34;

function getPos($cx, $cy, $radius, $angleDeg) {
    global $iconSize;
    $rad = deg2rad($angleDeg - 90); 
    $offset = $iconSize / 2;
    $x = $cx + ($radius * cos($rad));
    $y = $cy + ($radius * sin($rad));
    return "top: " . ($y - $offset) . "px; left: " . ($x - $offset) . "px;";
}

function getLblPos($cx, $cy, $radius, $angleDeg) {
    $rad = deg2rad($angleDeg - 90); 
    $dist = ($radius > 200) ? 35 : -35;
    $rLabel = $radius + $dist;
    return "top: " . ($cy + ($rLabel * sin($rad)) - 10) . "px; left: " . ($cx + ($rLabel * cos($rad)) - 10) . "px;";
}

function getToothSVG($dente, $dados_odonto) {
    global $iconSize;
    
    // Paths das faces do dente
    $paths = [
        'oclusal'    => 'circle',
        'vestibular' => 'M 14,14 L 6,6 A 20,20 0 0 1 34,6 L 26,14 A 8.5,8.5 0 0 0 14,14 Z',
        'distal'     => 'M 26,14 L 34,6 A 20,20 0 0 1 34,34 L 26,26 A 8.5,8.5 0 0 0 26,14 Z',
        'lingual'    => 'M 26,26 L 34,34 A 20,20 0 0 1 6,34 L 14,26 A 8.5,8.5 0 0 0 26,26 Z',
        'mesial'     => 'M 14,26 L 6,34 A 20,20 0 0 1 6,6 L 14,14 A 8.5,8.5 0 0 0 14,26 Z'
    ];

    // Cores por status
    $status_colors = [
        0 => ['fill' => 'white',   'stroke' => '#777'],
        1 => ['fill' => '#fbbf24', 'stroke' => '#d97706'],
        2 => ['fill' => '#10b981', 'stroke' => '#059669'],
        3 => ['fill' => '#ef4444', 'stroke' => '#b91c1c']
    ];

    $svg = '<svg width="'.$iconSize.'" height="'.$iconSize.'" viewBox="0 0 40 40" class="tooth-svg">';
    
    // Renderizar faces externas
    foreach ($paths as $face => $path) {
        if ($face === 'oclusal') continue;
        
        $id = "face_{$dente}_{$face}";
        $key = "{$dente}_{$face}";
        $status = $dados_odonto[$key] ?? 0;
        $color = $status_colors[$status];
        
        $svg .= sprintf(
            '<path id="%s" d="%s" fill="%s" stroke="%s" stroke-width="0.8" class="face-part" onclick="handleClickOdonto(%d, \'%s\', this)" data-status="%d" />', 
            htmlspecialchars($id), 
            $path, 
            $color['fill'], 
            $color['stroke'], 
            $dente, 
            $face, 
            $status
        );
    }
    
    // Renderizar centro (oclusal)
    $idCentro = "face_{$dente}_oclusal";
    $key = "{$dente}_oclusal";
    $status = $dados_odonto[$key] ?? 0;
    $color = $status_colors[$status];
    
    $svg .= sprintf(
        '<circle id="%s" cx="20" cy="20" r="7" fill="%s" stroke="%s" stroke-width="0.8" class="face-part" onclick="handleClickOdonto(%d, \'oclusal\', this)" data-status="%d" />', 
        htmlspecialchars($idCentro), 
        $color['fill'], 
        $color['stroke'], 
        $dente, 
        $status
    );
    
    $svg .= '</svg>';
    return $svg;
}
?>

<style>
    /* ===== CONTAINER PRINCIPAL ===== */
    .odonto-container { 
        display: flex; 
        height: 600px; 
        background: white; 
        border-radius: 8px; 
        overflow: hidden; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin: 0 !important;
        padding: 0 !important;
        position: relative;
    }
    
    /* Remover margens de containers pais se necessário */
    .odonto-container * {
        box-sizing: border-box;
    }
    
    .canvas-panel-odonto { 
        flex: 1; 
        position: relative; 
        display: flex; 
        justify-content: center; 
        align-items: center;
        min-height: 600px;
    }
    
    .odontograma-wrapper { 
        position: relative; 
        width: <?php echo $width; ?>px; 
        height: <?php echo $height; ?>px;
        margin: 0;
        padding: 0;
    }
    
    .sidebar-panel-odonto { 
        width: 320px; 
        background-color: #f9fafb; 
        border-left: 1px solid #e5e7eb; 
        display: flex; 
        flex-direction: column; 
    }
    
    /* ===== DENTES ===== */
    .tooth-container { 
        position: absolute; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        z-index: 20; 
    }
    
    .tooth-svg { 
        cursor: pointer; 
        filter: drop-shadow(2px 3px 4px rgba(0,0,0,0.1)); 
        transition: all 0.2s ease-out; 
    }
    
    .tooth-svg:hover { 
        transform: scale(1.15); 
        filter: drop-shadow(4px 6px 8px rgba(0,0,0,0.15)); 
        z-index: 50; 
    }
    
    .face-part:hover { 
        opacity: 0.8; 
    }
    
    .tooth-number { 
        position: absolute; 
        width: 20px; 
        height: 20px; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        font-size: 12px; 
        font-weight: 700; 
        color: #6b7280; 
        z-index: 5; 
    }

    /* ===== EIXOS ===== */
    .axis-line-v { 
        position: absolute; 
        top: 60px; 
        bottom: 60px; 
        left: 50%; 
        width: 1px; 
        background-color: #e5e7eb; 
    }
    
    .axis-line-h { 
        position: absolute; 
        left: 60px; 
        right: 60px; 
        top: 50%; 
        height: 1px; 
        background-color: #e5e7eb; 
    }
    
    .axis-label { 
        position: absolute; 
        font-size: 11px; 
        font-weight: 600; 
        text-transform: uppercase; 
        letter-spacing: 1.5px; 
        color: #9ca3af; 
        background: white; 
        padding: 0 10px; 
    }
    
    .ax-top { top: 30px; left: 50%; transform: translateX(-50%); }
    .ax-btm { bottom: 30px; left: 50%; transform: translateX(-50%); }
    .ax-left { left: 30px; top: 50%; transform: translateY(-50%); }
    .ax-right { right: 30px; top: 50%; transform: translateY(-50%); }

    /* ===== LEGENDA ===== */
    .floating-legend { 
        position: absolute; 
        top: 20px; 
        left: 20px; 
        background: rgba(255,255,255,0.95); 
        padding: 15px; 
        border-radius: 12px; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        border: 1px solid #f3f4f6; 
        backdrop-filter: blur(5px); 
        z-index: 100; 
    }
    
    .legend-item { 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        font-size: 13px; 
        color: #374151; 
        margin-bottom: 6px; 
    }
    
    .color-dot { 
        width: 14px; 
        height: 14px; 
        border-radius: 4px; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.1); 
    }
    
    .bg-normal { background: white; border: 1px solid #d1d5db; }
    .bg-todo { background: #fbbf24; border: 1px solid #d97706; }
    .bg-done { background: #10b981; border: 1px solid #059669; }
    .bg-extracted { background: #ef4444; border: 1px solid #b91c1c; }

    /* ===== SIDEBAR HISTÓRICO ===== */
    .sidebar-header-odonto { 
        padding: 20px; 
        border-bottom: 1px solid #e5e7eb; 
        background: white; 
    }
    
    .history-list { 
        flex: 1; 
        overflow-y: auto; 
        padding: 15px; 
    }
    
    .history-card { 
        background: white; 
        padding: 12px; 
        border-radius: 8px; 
        margin-bottom: 10px; 
        border: 1px solid #e5e7eb; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.03); 
        border-left: 4px solid #ccc; 
    }
    
    .card-date { 
        font-size: 10px; 
        text-transform: uppercase; 
        color: #9ca3af; 
        margin-bottom: 2px;
    }
    
    .card-title { 
        font-size: 13px; 
        font-weight: 700; 
        color: #1f2937; 
    }
    
    .card-desc { 
        font-size: 12px; 
        color: #4b5563; 
        margin-top: 2px; 
    }
    
    /* ===== RESET PARA FORÇAR POSICIONAMENTO NO TOPO ===== */
    .odonto-wrapper-reset {
        margin: 0 !important;
        padding: 0 !important;
        clear: both;
        float: none;
        display: block;
        width: 100%;
    }
    
    /* Reset geral da página onde o odonto está inserido */
    body .odonto-wrapper-reset {
        transform: translateY(0);
    }
</style>

<div class="odonto-wrapper-reset">
<div class="odonto-container">
    <div class="canvas-panel-odonto">
        <!-- Legenda Flutuante -->
        <div class="floating-legend">
            <h5 class="fw-bold mb-3" style="font-size: 16px;">Legenda</h5>
            <div class="legend-item"><div class="color-dot bg-normal"></div> Normal</div>
            <div class="legend-item"><div class="color-dot bg-todo"></div> A Realizar</div>
            <div class="legend-item"><div class="color-dot bg-done"></div> Realizado</div>
            <div class="legend-item"><div class="color-dot bg-extracted"></div> Extraído</div>
            <button class="btn btn-sm btn-danger border w-100 mt-3" onclick="resetOdonto()">
                <i class="fa-solid fa-rotate-left me-1"></i> Limpar Tudo
            </button>
        </div>

        <!-- Canvas do Odontograma -->
        <div class="odontograma-wrapper">
            <div class="axis-line-v"></div>
            <div class="axis-line-h"></div>
            <div class="axis-label ax-top">Superior</div>
            <div class="axis-label ax-btm">Inferior</div>
            <div class="axis-label ax-left">Direita</div>
            <div class="axis-label ax-right">Esquerda</div>

            <?php
            // Definição dos quadrantes
            $quadrantes = [
                ['dentes' => [11, 12, 13, 14, 15, 16, 17, 18], 'start' => -10, 'step' => -10.8, 'radius' => $radiusExt],
                ['dentes' => [51, 52, 53, 54, 55],             'start' => -12, 'step' => -14,   'radius' => $radiusInt],
                ['dentes' => [21, 22, 23, 24, 25, 26, 27, 28], 'start' => 10,  'step' => 10.8,  'radius' => $radiusExt],
                ['dentes' => [61, 62, 63, 64, 65],             'start' => 12,  'step' => 14,    'radius' => $radiusInt],
                ['dentes' => [31, 32, 33, 34, 35, 36, 37, 38], 'start' => 170, 'step' => -10.8, 'radius' => $radiusExt],
                ['dentes' => [71, 72, 73, 74, 75],             'start' => 168, 'step' => -14,   'radius' => $radiusInt],
                ['dentes' => [41, 42, 43, 44, 45, 46, 47, 48], 'start' => -170,'step' => 10.8,  'radius' => $radiusExt],
                ['dentes' => [81, 82, 83, 84, 85],             'start' => -168,'step' => 14,    'radius' => $radiusInt],
            ];

            // Renderizar todos os dentes
            foreach ($quadrantes as $q) {
                $angle = $q['start'];
                foreach ($q['dentes'] as $dente) {
                    echo '<div class="tooth-container" style="' . getPos($centerX, $centerY, $q['radius'], $angle) . '">';
                    echo getToothSVG($dente, $dados_odonto);
                    echo '</div>';
                    
                    echo '<div class="tooth-number" style="' . getLblPos($centerX, $centerY, $q['radius'], $angle) . '">';
                    echo $dente;
                    echo '</div>';
                    
                    $angle += $q['step'];
                }
            }
            ?>
        </div>
    </div>

    <!-- Sidebar com Histórico -->
    <div class="sidebar-panel-odonto">
        <div class="sidebar-header-odonto">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-clock-rotate-left me-2"></i> Histórico
            </h6>
        </div>
        <div class="history-list" id="historyListOdonto">
            <?php if (empty($historico)): ?>
                <div class="text-center text-muted mt-5 small">Nenhum registro</div>
            <?php else: ?>
                <?php 
                $status_labels = [
                    0 => ['label' => 'Normal', 'color' => '#9ca3af'],
                    1 => ['label' => 'A Realizar', 'color' => '#fbbf24'],
                    2 => ['label' => 'Realizado', 'color' => '#10b981'],
                    3 => ['label' => 'Extraído', 'color' => '#ef4444']
                ];
                
                $face_pt = [
                    'oclusal' => 'Oclusal',
                    'vestibular' => 'Vestibular',
                    'distal' => 'Distal',
                    'lingual' => 'Lingual',
                    'mesial' => 'Mesial'
                ];
                
                foreach ($historico as $item): 
                    $face_nome = $face_pt[$item['face']] ?? ucfirst($item['face']);
                    $status_novo = $status_labels[$item['status_novo']];
                    $status_ant = $status_labels[$item['status_anterior']];
                    $data = date('d/m/Y H:i', strtotime($item['data_registro']));
                ?>
                <div class="history-card" style="border-left-color: <?php echo htmlspecialchars($status_novo['color']); ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="card-title">Dente <?php echo htmlspecialchars($item['dente']); ?></div>
                        <div class="card-date"><?php echo htmlspecialchars($data); ?></div>
                    </div>
                    <div class="small fw-bold text-secondary"><?php echo htmlspecialchars($face_nome); ?></div>
                    <div class="card-desc">
                        <?php echo htmlspecialchars($status_ant['label']); ?> 
                        <i class="fa-solid fa-arrow-right-long"></i> 
                        <strong><?php echo htmlspecialchars($status_novo['label']); ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
(function() {
    // Configuração dos status
    const STATUS = {
        0: { label: 'Normal',     fill: 'white',   stroke: '#777' },
        1: { label: 'A Realizar', fill: '#fbbf24', stroke: '#d97706' },
        2: { label: 'Realizado',  fill: '#10b981', stroke: '#059669' },
        3: { label: 'Extraído',   fill: '#ef4444', stroke: '#b91c1c' }
    };

    // Handler de clique no dente
    window.handleClickOdonto = function(dente, face, el) {
        let currentStatus = parseInt(el.getAttribute('data-status'));
        let nextStatus = (currentStatus + 1) > 3 ? 0 : currentStatus + 1;
        
        // Atualizar visual imediatamente
        updateElementVisual(el, nextStatus);
        
        // Salvar no banco via AJAX
        const formData = new FormData();
        formData.append('ajax_odonto', '1');
        formData.append('dente', dente);
        formData.append('face', face);
        formData.append('status', nextStatus);
        formData.append('paciente_id', <?php echo intval($id_paciente); ?>);
        
        fetch('ajax_odonto.php', {
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Erro HTTP: ' + res.status);
            }
            return res.json();
        })
        .then(data => {
            if (data.sucesso) {
                console.log('✓ Salvo:', data);
                if (data.mudou) {
                    addHistoryItem(dente, face, currentStatus, nextStatus);
                }
            } else {
                console.error('Erro do servidor:', data.erro);
                alert('Erro ao salvar: ' + (data.erro || 'desconhecido'));
                updateElementVisual(el, currentStatus);
            }
        })
        .catch(err => {
            console.error('Erro AJAX:', err);
            alert('Erro de conexão! Verifique o console.');
            updateElementVisual(el, currentStatus);
        });
    };
    
    // Adicionar item no histórico
    function addHistoryItem(dente, face, statusAnt, statusNovo) {
        const STATUS_LABELS = {
            0: { label: 'Normal', color: '#9ca3af' },
            1: { label: 'A Realizar', color: '#fbbf24' },
            2: { label: 'Realizado', color: '#10b981' },
            3: { label: 'Extraído', color: '#ef4444' }
        };
        
        const FACE_LABELS = {
            'oclusal': 'Oclusal',
            'vestibular': 'Vestibular',
            'distal': 'Distal',
            'lingual': 'Lingual',
            'mesial': 'Mesial'
        };
        
        const historyList = document.getElementById('historyListOdonto');
        const now = new Date().toLocaleString('pt-BR');
        
        // Remover mensagem "Nenhum registro"
        const emptyMsg = historyList.querySelector('.text-center.text-muted');
        if (emptyMsg) emptyMsg.remove();
        
        // Criar novo card
        const card = document.createElement('div');
        card.className = 'history-card';
        card.style.borderLeftColor = STATUS_LABELS[statusNovo].color;
        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="card-title">Dente ${dente}</div>
                <div class="card-date">${now}</div>
            </div>
            <div class="small fw-bold text-secondary">${FACE_LABELS[face]}</div>
            <div class="card-desc">
                ${STATUS_LABELS[statusAnt].label} 
                <i class="fa-solid fa-arrow-right-long"></i> 
                <strong>${STATUS_LABELS[statusNovo].label}</strong>
            </div>
        `;
        
        historyList.insertBefore(card, historyList.firstChild);
    }

    // Atualizar visual do elemento
    function updateElementVisual(el, statusIdx) {
        const style = STATUS[statusIdx];
        el.setAttribute('fill', style.fill);
        el.setAttribute('stroke', style.stroke);
        el.setAttribute('data-status', statusIdx);
    }

    // Resetar odontograma
    window.resetOdonto = function() {
        if(!confirm('⚠️ ATENÇÃO: Isso vai apagar TODO o odontograma deste paciente!\n\nTem certeza?')) {
            return;
        }
        
        if(confirm('Última chance! Realmente deseja LIMPAR TUDO?')) {
            const formData = new FormData();
            formData.append('ajax_odonto', '1');
            formData.append('acao', 'limpar_tudo');
            formData.append('paciente_id', <?php echo intval($id_paciente); ?>);
            
            fetch('ajax_odonto.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    location.reload();
                } else {
                    alert('Erro: ' + data.erro);
                }
            })
            .catch(err => {
                console.error('Erro:', err);
                alert('Erro ao limpar!');
            });
        }
    };
})();
</script>