<?php
// ==================================================================
// ODONTOGRAMA - VERSÃO OTIMIZADA COM BARRA DE FERRAMENTAS
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

// === CONFIGURAÇÕES VISUAIS ===
$width = 800;
$height = 750;
$centerX = $width / 2; 
$centerY = $height / 2;
$radiusExt = 230; // Ajustado levemente
$radiusInt = 165;
$iconSize = 34;

// === FUNÇÕES AUXILIARES DE POSICIONAMENTO ===
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
    
    // Definição dos caminhos SVG
    $paths = [
        'vestibular' => 'M 14,14 L 6,6 A 20,20 0 0 1 34,6 L 26,14 A 8.5,8.5 0 0 0 14,14 Z',
        'distal'     => 'M 26,14 L 34,6 A 20,20 0 0 1 34,34 L 26,26 A 8.5,8.5 0 0 0 26,14 Z',
        'lingual'    => 'M 26,26 L 34,34 A 20,20 0 0 1 6,34 L 14,26 A 8.5,8.5 0 0 0 26,26 Z',
        'mesial'     => 'M 14,26 L 6,34 A 20,20 0 0 1 6,6 L 14,14 A 8.5,8.5 0 0 0 14,26 Z'
    ];

    $status_colors = [
        0 => ['fill' => 'white',   'stroke' => '#bbb'],
        1 => ['fill' => '#fbbf24', 'stroke' => '#d97706'], // Amarelo (A Fazer)
        2 => ['fill' => '#10b981', 'stroke' => '#059669'], // Verde (Feito)
        3 => ['fill' => '#ef4444', 'stroke' => '#b91c1c']  // Vermelho (Extraído)
    ];

    $svg = '<svg width="'.$iconSize.'" height="'.$iconSize.'" viewBox="0 0 40 40" class="tooth-svg">';
    
    // Renderiza faces externas
    foreach ($paths as $face => $path) {
        $id = "face_{$dente}_{$face}";
        $key = "{$dente}_{$face}";
        $status = $dados_odonto[$key] ?? 0;
        $color = $status_colors[$status];
        $nomeFace = ucfirst($face);
        
        $svg .= sprintf(
            '<path id="%s" d="%s" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClickOdonto(%d, \'%s\', this)" data-status="%d" title="Dente %d - %s"><title>Dente %d - %s</title></path>', 
            $id, $path, $color['fill'], $color['stroke'], 
            $dente, $face, $status, $dente, $nomeFace, $dente, $nomeFace
        );
    }
    
    // Renderiza face central (Oclusal)
    $idCentro = "face_{$dente}_oclusal";
    $key = "{$dente}_oclusal";
    $status = $dados_odonto[$key] ?? 0;
    $color = $status_colors[$status];
    
    $svg .= sprintf(
        '<circle id="%s" cx="20" cy="20" r="7" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClickOdonto(%d, \'oclusal\', this)" data-status="%d" title="Dente %d - Oclusal"><title>Dente %d - Oclusal</title></circle>', 
        $idCentro, $color['fill'], $color['stroke'], 
        $dente, $status, $dente, $dente
    );
    
    $svg .= '</svg>';
    return $svg;
}
?>

<style>
    /* ===== LAYOUT GERAL ===== */
    .odonto-wrapper-reset { width: 100%; display: block; }
    .odonto-container { display: flex; height: 750px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: relative; }
    
    /* ===== ÁREA ESQUERDA (CANVAS) ===== */
    .canvas-panel-odonto { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; background-color: #fff; }
    
    /* ===== BARRA DE FERRAMENTAS ===== */
    .toolbar-odonto { display: flex; gap: 8px; padding: 10px 15px; background: #f8f9fa; border-radius: 50px; border: 1px solid #e9ecef; margin-top: 15px; z-index: 100; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    
    .tool-btn { 
        padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; 
        transition: all 0.2s; border: 1px solid transparent; display: flex; align-items: center; gap: 6px; 
        opacity: 0.6; user-select: none;
    }
    .tool-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .tool-btn.active { opacity: 1; transform: scale(1.05); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    
    /* Cores dos botões */
    .btn-eraser { background: #f1f3f5; color: #495057; border-color: #ced4da; }
    .btn-eraser.active { background: #e9ecef; border-color: #adb5bd; color: black; }
    
    .btn-todo { background: #fff3cd; color: #856404; border-color: #ffeeba; }
    .btn-todo.active { background: #ffecb5; border-color: #fbbf24; }

    .btn-done { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }
    .btn-done.active { background: #c3e6cb; border-color: #10b981; }

    .btn-extra { background: #f8d7da; color: #842029; border-color: #f5c2c7; }
    .btn-extra.active { background: #f5c6cb; border-color: #ef4444; }

    /* ===== CANVAS DESENHO ===== */
    .odontograma-visual { position: relative; width: <?php echo $width; ?>px; height: <?php echo $height; ?>px; margin-top: -20px; }
    
    .tooth-container { position: absolute; display: flex; justify-content: center; align-items: center; z-index: 20; }
    .tooth-svg { transition: transform 0.2s; filter: drop-shadow(1px 2px 2px rgba(0,0,0,0.1)); }
    .tooth-svg:hover { transform: scale(1.15); z-index: 50; }
    .face-part { transition: fill 0.2s, stroke 0.2s; }
    .face-part:hover { opacity: 0.8; }
    
    /* CURSORES */
    .cursor-paint .face-part { cursor: crosshair; }
    .cursor-pointer .face-part { cursor: pointer; }

    .tooth-number { position: absolute; width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; font-size: 11px; font-weight: 700; color: #adb5bd; z-index: 5; pointer-events: none; }

    /* EIXOS */
    .axis-line { position: absolute; background-color: #e9ecef; }
    .axis-v { top: 80px; bottom: 80px; left: 50%; width: 1px; }
    .axis-h { left: 80px; right: 80px; top: 50%; height: 1px; }
    .axis-lbl { position: absolute; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #ced4da; background: white; padding: 0 5px; }

    /* ===== SIDEBAR HISTÓRICO ===== */
    .sidebar-panel-odonto { width: 300px; background-color: #f8f9fa; border-left: 1px solid #dee2e6; display: flex; flex-direction: column; }
    .sidebar-header { padding: 15px; border-bottom: 1px solid #dee2e6; background: white; }
    .history-list { flex: 1; overflow-y: auto; padding: 10px; }
    
    .history-card { background: white; padding: 10px; border-radius: 6px; margin-bottom: 8px; border: 1px solid #e9ecef; border-left-width: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); animation: slideIn 0.3s ease-out; }
    @keyframes slideIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
    .card-meta { display: flex; justify-content: space-between; font-size: 0.75rem; color: #6c757d; margin-bottom: 3px; }
    .card-detail { font-size: 0.85rem; color: #343a40; line-height: 1.3; }
</style>

<div class="odonto-wrapper-reset">
<div class="odonto-container">
    
    <div class="canvas-panel-odonto" id="canvasOdonto">
        
        <div class="toolbar-odonto">
            <div class="tool-btn btn-eraser" onclick="setTool(0, this)" title="Limpar / Normal">
                <i class="fa-solid fa-eraser"></i> Limpar
            </div>
            <div class="tool-btn btn-todo active" onclick="setTool(1, this)" title="Marcar procedimento pendente">
                <i class="fa-solid fa-triangle-exclamation"></i> A Fazer
            </div>
            <div class="tool-btn btn-done" onclick="setTool(2, this)" title="Marcar procedimento concluído">
                <i class="fa-solid fa-check"></i> Feito
            </div>
            <div class="tool-btn btn-extra" onclick="setTool(3, this)" title="Marcar para extração">
                <i class="fa-solid fa-xmark"></i> Extraído
            </div>
            
            <div class="vr mx-2 bg-secondary opacity-25"></div>
            
            <button class="btn btn-sm text-danger border-0" onclick="resetOdonto()" title="Limpar Odontograma Inteiro">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </div>

        <div class="odontograma-visual cursor-paint">
            <div class="axis-line axis-v"></div>
            <div class="axis-line axis-h"></div>
            <div class="axis-lbl" style="top: 40px; left: 50%; transform: translateX(-50%);">Superior</div>
            <div class="axis-lbl" style="bottom: 40px; left: 50%; transform: translateX(-50%);">Inferior</div>
            <div class="axis-lbl" style="left: 40px; top: 50%; transform: translateY(-50%);">Dir</div>
            <div class="axis-lbl" style="right: 40px; top: 50%; transform: translateY(-50%);">Esq</div>

            <?php
            // GERAÇÃO DOS DENTES
            $quadrantes = [
                ['dentes' => [18,17,16,15,14,13,12,11], 'start' => -10, 'step' => -10.8, 'radius' => $radiusExt],
                ['dentes' => [55,54,53,52,51],          'start' => -12, 'step' => -14,   'radius' => $radiusInt],
                ['dentes' => [21,22,23,24,25,26,27,28], 'start' => 10,  'step' => 10.8,  'radius' => $radiusExt],
                ['dentes' => [61,62,63,64,65],          'start' => 12,  'step' => 14,    'radius' => $radiusInt],
                ['dentes' => [38,37,36,35,34,33,32,31], 'start' => 170, 'step' => -10.8, 'radius' => $radiusExt],
                ['dentes' => [75,74,73,72,71],          'start' => 168, 'step' => -14,   'radius' => $radiusInt],
                ['dentes' => [41,42,43,44,45,46,47,48], 'start' => -170,'step' => 10.8,  'radius' => $radiusExt],
                ['dentes' => [81,82,83,84,85],          'start' => -168,'step' => 14,    'radius' => $radiusInt],
            ];

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

    <div class="sidebar-panel-odonto">
        <div class="sidebar-header">
            <h6 class="fw-bold mb-0 text-secondary small text-uppercase">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Histórico de Alterações
            </h6>
        </div>
        <div class="history-list" id="historyListOdonto">
            <?php if (empty($historico)): ?>
                <div class="text-center text-muted mt-5 small opacity-50">
                    <i class="fa-regular fa-folder-open fa-2x mb-2"></i><br>Nenhum registro
                </div>
            <?php else: ?>
                <?php 
                $labels = [
                    0 => ['txt' => 'Normal', 'cor' => '#adb5bd'],
                    1 => ['txt' => 'A Fazer', 'cor' => '#fbbf24'],
                    2 => ['txt' => 'Feito', 'cor' => '#10b981'],
                    3 => ['txt' => 'Extraído', 'cor' => '#ef4444']
                ];
                foreach ($historico as $item): 
                    $data = date('d/m/Y H:i', strtotime($item['data_registro']));
                    $cor = $labels[$item['status_novo']]['cor'] ?? '#ccc';
                    $txtAnt = $labels[$item['status_anterior']]['txt'];
                    $txtNovo = $labels[$item['status_novo']]['txt'];
                ?>
                <div class="history-card" style="border-left-color: <?php echo $cor; ?>">
                    <div class="card-meta">
                        <strong>Dente <?php echo $item['dente']; ?></strong>
                        <span><?php echo $data; ?></span>
                    </div>
                    <div class="card-detail">
                        <span class="text-muted"><?php echo ucfirst($item['face']); ?>:</span> 
                        <?php echo $txtAnt; ?> <i class="fa-solid fa-arrow-right small mx-1"></i> <strong><?php echo $txtNovo; ?></strong>
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
    // ESTADO DA FERRAMENTA (0=Normal, 1=A Fazer, 2=Feito, 3=Extraído)
    // Inicia com "A Fazer" (1)
    let currentTool = 1;

    // Definição visual dos status
    const VISUAL = {
        0: { fill: 'white',   stroke: '#bbb' },
        1: { fill: '#fbbf24', stroke: '#d97706' },
        2: { fill: '#10b981', stroke: '#059669' },
        3: { fill: '#ef4444', stroke: '#b91c1c' }
    };

    // TROCA DE FERRAMENTA
    window.setTool = function(statusId, btn) {
        currentTool = statusId;
        
        // Atualiza botões
        document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
        if(btn) btn.classList.add('active');

        // Atualiza cursor
        const canvas = document.querySelector('.odontograma-visual');
        if (statusId === 0) {
            canvas.classList.remove('cursor-paint');
            canvas.classList.add('cursor-pointer');
        } else {
            canvas.classList.add('cursor-paint');
            canvas.classList.remove('cursor-pointer');
        }
    };

    // CLIQUE NO DENTE
    window.handleClickOdonto = function(dente, face, el) {
        const oldStatus = parseInt(el.getAttribute('data-status'));
        let newStatus = currentTool;

        // Se clicar com a mesma ferramenta, não faz nada (ou poderia desmarcar)
        if (oldStatus === newStatus) return;

        // Otimismo: Atualiza a tela antes do AJAX
        updateVisual(el, newStatus);

        // Envia para o servidor
        const formData = new FormData();
        formData.append('ajax_odonto', '1');
        formData.append('dente', dente);
        formData.append('face', face);
        formData.append('status', newStatus);
        formData.append('paciente_id', <?php echo intval($id_paciente); ?>);

        fetch('ajax_odonto.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) {
                if(data.mudou) addHistory(dente, face, oldStatus, newStatus);
            } else {
                // Erro: Reverte visual
                console.error(data.erro);
                updateVisual(el, oldStatus);
                alert("Erro ao salvar!");
            }
        })
        .catch(err => {
            console.error(err);
            updateVisual(el, oldStatus);
        });
    };

    function updateVisual(el, status) {
        const style = VISUAL[status];
        el.setAttribute('fill', style.fill);
        el.setAttribute('stroke', style.stroke);
        el.setAttribute('data-status', status);
    }

    // LIMPAR TUDO
    window.resetOdonto = function() {
        if(!confirm('Deseja realmente LIMPAR todo o odontograma deste paciente?')) return;
        
        const formData = new FormData();
        formData.append('ajax_odonto', '1');
        formData.append('acao', 'limpar_tudo');
        formData.append('paciente_id', <?php echo intval($id_paciente); ?>);
        
        fetch('ajax_odonto.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) location.reload();
        });
    };

    // ADICIONAR AO HISTÓRICO VISUAL
    function addHistory(dente, face, stAnt, stNovo) {
        const labels = {
            0: {t: 'Normal', c: '#adb5bd'},
            1: {t: 'A Fazer', c: '#fbbf24'},
            2: {t: 'Feito', c: '#10b981'},
            3: {t: 'Extraído', c: '#ef4444'}
        };
        
        const list = document.getElementById('historyListOdonto');
        const empty = list.querySelector('.text-center');
        if(empty) empty.remove();

        const now = new Date().toLocaleString('pt-BR', {hour:'2-digit', minute:'2-digit', day:'2-digit', month:'2-digit'});
        
        const card = document.createElement('div');
        card.className = 'history-card';
        card.style.borderLeftColor = labels[stNovo].c;
        card.innerHTML = `
            <div class="card-meta"><strong>Dente ${dente}</strong> <span>${now}</span></div>
            <div class="card-detail">
                <span class="text-muted">${face.charAt(0).toUpperCase() + face.slice(1)}:</span> 
                ${labels[stAnt].t} <i class="fa-solid fa-arrow-right small mx-1"></i> <strong>${labels[stNovo].t}</strong>
            </div>
        `;
        list.insertBefore(card, list.firstChild);
    }
})();
</script>