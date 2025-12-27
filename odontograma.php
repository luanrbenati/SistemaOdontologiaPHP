<?php
// ==================================================================
// ODONTOGRAMA - VERSÃO RESPONSIVA FINAL (CORREÇÃO DE ALINHAMENTO)
// ==================================================================

global $pdo, $id_paciente;

// === CARREGAR DADOS ===
$dados_odonto = [];
if (!empty($id_paciente)) {
    try {
        $stmt = $pdo->prepare("SELECT dente, face, status FROM odontograma WHERE paciente_id = ?");
        $stmt->execute([$id_paciente]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['dente'] . '_' . $row['face'];
            $dados_odonto[$key] = intval($row['status']);
        }
    } catch (PDOException $e) { error_log($e->getMessage()); }
}

// === CARREGAR HISTÓRICO ===
$historico = [];
if (!empty($id_paciente)) {
    try {
        $stmt = $pdo->prepare("SELECT dente, face, status_anterior, status_novo, data_registro FROM odontograma_historico WHERE paciente_id = ? ORDER BY data_registro DESC LIMIT 50");
        $stmt->execute([$id_paciente]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log($e->getMessage()); }
}

// === CONFIGURAÇÕES VISUAIS ===
$width = 800;
$height = 750;
$centerX = $width / 2; 
$centerY = $height / 2;
$radiusExt = 230; 
$radiusInt = 165;
$iconSize = 34;

// === FUNÇÕES AUXILIARES ===
function getPos($cx, $cy, $radius, $angleDeg) {
    global $iconSize;
    $rad = deg2rad($angleDeg - 90); 
    $offset = $iconSize / 2;
    return "top:" . ($cy + ($radius * sin($rad)) - $offset) . "px; left:" . ($cx + ($radius * cos($rad)) - $offset) . "px;";
}

function getLblPos($cx, $cy, $radius, $angleDeg) {
    $rad = deg2rad($angleDeg - 90); 
    $rLabel = $radius + (($radius > 200) ? 35 : -35);
    return "top:" . ($cy + ($rLabel * sin($rad)) - 10) . "px; left:" . ($cx + ($rLabel * cos($rad)) - 10) . "px;";
}

function getToothSVG($dente, $dados_odonto) {
    global $iconSize;
    $paths = [
        'vestibular' => 'M 14,14 L 6,6 A 20,20 0 0 1 34,6 L 26,14 A 8.5,8.5 0 0 0 14,14 Z',
        'distal'     => 'M 26,14 L 34,6 A 20,20 0 0 1 34,34 L 26,26 A 8.5,8.5 0 0 0 26,14 Z',
        'lingual'    => 'M 26,26 L 34,34 A 20,20 0 0 1 6,34 L 14,26 A 8.5,8.5 0 0 0 26,26 Z',
        'mesial'     => 'M 14,26 L 6,34 A 20,20 0 0 1 6,6 L 14,14 A 8.5,8.5 0 0 0 14,26 Z'
    ];
    $colors = [0=>['#fff','#bbb'], 1=>['#fbbf24','#d97706'], 2=>['#10b981','#059669'], 3=>['#ef4444','#b91c1c']];

    $svg = '<svg width="'.$iconSize.'" height="'.$iconSize.'" viewBox="0 0 40 40" class="tooth-svg">';
    foreach ($paths as $face => $path) {
        $st = $dados_odonto["{$dente}_{$face}"] ?? 0;
        $c = $colors[$st];
        $svg .= sprintf('<path d="%s" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClickOdonto(%d,\'%s\',this)" data-status="%d"><title>Dente %d - %s</title></path>', $path, $c[0], $c[1], $dente, $face, $st, $dente, ucfirst($face));
    }
    $st = $dados_odonto["{$dente}_oclusal"] ?? 0;
    $c = $colors[$st];
    $svg .= sprintf('<circle cx="20" cy="20" r="7" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClickOdonto(%d,\'oclusal\',this)" data-status="%d"><title>Dente %d - Oclusal</title></circle>', $c[0], $c[1], $dente, $st, $dente);
    return $svg . '</svg>';
}
?>

<style>
    .odonto-wrapper-reset { width: 100%; display: block; }
    .odonto-container { display: flex; height: 750px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: relative; }
    
    /* Área do Canvas */
    .canvas-panel-odonto { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; background-color: #fff; }
    
    /* Toolbar */
    .toolbar-odonto { display: flex; gap: 8px; padding: 10px 15px; background: #f8f9fa; border-radius: 50px; border: 1px solid #e9ecef; margin-top: 15px; z-index: 100; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .tool-btn { padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; display: flex; align-items: center; gap: 6px; opacity: 0.6; user-select: none; }
    .tool-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .tool-btn.active { opacity: 1; transform: scale(1.05); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    
    .btn-eraser { background: #f1f3f5; color: #495057; border-color: #ced4da; }
    .btn-eraser.active { background: #e9ecef; border-color: #adb5bd; color: black; }
    .btn-todo { background: #fff3cd; color: #856404; border-color: #ffeeba; }
    .btn-todo.active { background: #ffecb5; border-color: #fbbf24; }
    .btn-done { background: #d1e7dd; color: #0f5132; border-color: #badbcc; }
    .btn-done.active { background: #c3e6cb; border-color: #10b981; }
    .btn-extra { background: #f8d7da; color: #842029; border-color: #f5c2c7; }
    .btn-extra.active { background: #f5c6cb; border-color: #ef4444; }

    /* Visual do Odontograma */
    .odontograma-visual { position: relative; width: <?php echo $width; ?>px; height: <?php echo $height; ?>px; margin-top: -20px; transition: transform 0.2s ease; transform-origin: top center; }
    
    .tooth-container { position: absolute; display: flex; justify-content: center; align-items: center; z-index: 20; }
    .tooth-svg { transition: transform 0.2s; filter: drop-shadow(1px 2px 2px rgba(0,0,0,0.1)); }
    .tooth-svg:hover { transform: scale(1.15); z-index: 50; }
    .face-part { transition: fill 0.2s, stroke 0.2s; }
    .face-part:hover { opacity: 0.8; }
    .cursor-paint .face-part { cursor: crosshair; }
    .cursor-pointer .face-part { cursor: pointer; }
    .tooth-number { position: absolute; width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; font-size: 11px; font-weight: 700; color: #adb5bd; z-index: 5; pointer-events: none; }
    
    .axis-line { position: absolute; background-color: #e9ecef; }
    .axis-v { top: 80px; bottom: 80px; left: 50%; width: 1px; }
    .axis-h { left: 80px; right: 80px; top: 50%; height: 1px; }
    .axis-lbl { position: absolute; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #ced4da; background: white; padding: 0 5px; }

    /* Sidebar Histórico */
    .sidebar-panel-odonto { width: 300px; background-color: #f8f9fa; border-left: 1px solid #dee2e6; display: flex; flex-direction: column; }
    .sidebar-header { padding: 15px; border-bottom: 1px solid #dee2e6; background: white; }
    .history-list { flex: 1; overflow-y: auto; padding: 10px; }
    .history-card { background: white; padding: 10px; border-radius: 6px; margin-bottom: 8px; border: 1px solid #e9ecef; border-left-width: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
    .card-meta { display: flex; justify-content: space-between; font-size: 0.75rem; color: #6c757d; margin-bottom: 3px; }
    .card-detail { font-size: 0.85rem; color: #343a40; line-height: 1.3; }

    /* ===== RESPONSIVIDADE MOBILE (CORREÇÃO DE ALINHAMENTO) ===== */
    @media (max-width: 992px) {
        .odonto-container { flex-direction: column; height: auto !important; }
        .canvas-panel-odonto { width: 100%; overflow: hidden; min-height: 350px; display: block; }
        
        /* AQUI ESTÁ A CORREÇÃO PRINCIPAL: top left */
        .odontograma-visual { 
            transform-origin: top left !important; 
            margin: 10px 0 0 0 !important; 
            left: 0 !important;
        }

        .sidebar-panel-odonto { width: 100%; border-left: none; border-top: 1px solid #dee2e6; height: auto; max-height: 400px; }
        .toolbar-odonto { flex-wrap: wrap; justify-content: center; width: 95%; margin: 10px auto; }
        
        /* Ajuste de toque */
        .tooth-svg { transform: scale(1.1); }
        .tooth-number { font-size: 10px; }
    }
</style>

<div class="odonto-wrapper-reset">
<div class="odonto-container">
    <div class="canvas-panel-odonto" id="canvasOdonto">
        <div class="toolbar-odonto">
            <div class="tool-btn btn-eraser" onclick="setTool(0, this)"><i class="fa-solid fa-eraser"></i> Normal</div>
            <div class="tool-btn btn-todo active" onclick="setTool(1, this)"><i class="fa-solid fa-triangle-exclamation"></i> A Fazer</div>
            <div class="tool-btn btn-done" onclick="setTool(2, this)"><i class="fa-solid fa-check"></i> Feito</div>
            <div class="tool-btn btn-extra" onclick="setTool(3, this)"><i class="fa-solid fa-xmark"></i> Extraído</div>
            <div class="vr mx-2 bg-secondary opacity-25"></div>
            <button class="btn btn-sm text-danger border-0" onclick="resetOdonto()"><i class="fa-solid fa-trash-can"></i></button>
        </div>

        <div class="odontograma-visual cursor-paint">
            <div class="axis-line axis-v"></div>
            <div class="axis-line axis-h"></div>
            <div class="axis-lbl" style="top: 40px; left: 50%; transform: translateX(-50%);">Sup</div>
            <div class="axis-lbl" style="bottom: 40px; left: 50%; transform: translateX(-50%);">Inf</div>
            <div class="axis-lbl" style="left: 40px; top: 50%; transform: translateY(-50%);">Dir</div>
            <div class="axis-lbl" style="right: 40px; top: 50%; transform: translateY(-50%);">Esq</div>

            <?php
            $quadrantes = [
                ['d' => [18,17,16,15,14,13,12,11], 'st' => -10, 'sp' => -10.8, 'r' => $radiusExt],
                ['d' => [55,54,53,52,51],          'st' => -12, 'sp' => -14,   'r' => $radiusInt],
                ['d' => [21,22,23,24,25,26,27,28], 'st' => 10,  'sp' => 10.8,  'r' => $radiusExt],
                ['d' => [61,62,63,64,65],          'st' => 12,  'sp' => 14,    'r' => $radiusInt],
                ['d' => [38,37,36,35,34,33,32,31], 'st' => 170, 'sp' => -10.8, 'r' => $radiusExt],
                ['d' => [75,74,73,72,71],          'st' => 168, 'sp' => -14,   'r' => $radiusInt],
                ['d' => [41,42,43,44,45,46,47,48], 'st' => -170,'sp' => 10.8,  'r' => $radiusExt],
                ['d' => [81,82,83,84,85],          'st' => -168,'sp' => 14,    'r' => $radiusInt],
            ];
            foreach ($quadrantes as $q) {
                $angle = $q['st'];
                foreach ($q['d'] as $dente) {
                    echo '<div class="tooth-container" style="' . getPos($centerX, $centerY, $q['r'], $angle) . '">' . getToothSVG($dente, $dados_odonto) . '</div>';
                    echo '<div class="tooth-number" style="' . getLblPos($centerX, $centerY, $q['r'], $angle) . '">' . $dente . '</div>';
                    $angle += $q['sp'];
                }
            }
            ?>
        </div>
    </div>

    <div class="sidebar-panel-odonto">
        <div class="sidebar-header"><h6 class="fw-bold mb-0 text-secondary small text-uppercase"><i class="fa-solid fa-clock-rotate-left me-1"></i> Histórico</h6></div>
        <div class="history-list" id="historyListOdonto">
            <?php if (empty($historico)): ?>
                <div class="text-center text-muted mt-5 small opacity-50"><i class="fa-regular fa-folder-open fa-2x mb-2"></i><br>Nenhum registro</div>
            <?php else: ?>
                <?php 
                $lbs = [0=>['t'=>'Normal','c'=>'#adb5bd'], 1=>['t'=>'A Fazer','c'=>'#fbbf24'], 2=>['t'=>'Feito','c'=>'#10b981'], 3=>['t'=>'Extraído','c'=>'#ef4444']];
                foreach ($historico as $item): 
                    $data = date('d/m/Y H:i', strtotime($item['data_registro']));
                    $cor = $lbs[$item['status_novo']]['c'] ?? '#ccc';
                ?>
                <div class="history-card" style="border-left-color: <?php echo $cor; ?>">
                    <div class="card-meta"><strong>Dente <?php echo $item['dente']; ?></strong> <span><?php echo $data; ?></span></div>
                    <div class="card-detail"><span class="text-muted"><?php echo ucfirst($item['face']); ?>:</span> <?php echo $lbs[$item['status_anterior']]['t']; ?> <i class="fa-solid fa-arrow-right small mx-1"></i> <strong><?php echo $lbs[$item['status_novo']]['t']; ?></strong></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
(function() {
    let currentTool = 1;
    const VISUAL = { 0: {f:'white',s:'#bbb'}, 1: {f:'#fbbf24',s:'#d97706'}, 2: {f:'#10b981',s:'#059669'}, 3: {f:'#ef4444',s:'#b91c1c'} };

    window.setTool = function(statusId, btn) {
        currentTool = statusId;
        document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
        if(btn) btn.classList.add('active');
        const c = document.querySelector('.odontograma-visual');
        if (statusId === 0) { c.classList.remove('cursor-paint'); c.classList.add('cursor-pointer'); }
        else { c.classList.add('cursor-paint'); c.classList.remove('cursor-pointer'); }
    };

    window.handleClickOdonto = function(dente, face, el) {
        const oldStatus = parseInt(el.getAttribute('data-status'));
        let newStatus = currentTool;
        if (oldStatus === newStatus) return;

        updateVisual(el, newStatus);

        const fd = new FormData();
        fd.append('ajax_odonto', '1'); fd.append('dente', dente); fd.append('face', face); fd.append('status', newStatus); fd.append('paciente_id', <?php echo intval($id_paciente); ?>);

        fetch('ajax_odonto.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => { if (data.sucesso && data.mudou) addHistory(dente, face, oldStatus, newStatus); else if(!data.sucesso) { updateVisual(el, oldStatus); alert("Erro ao salvar!"); } })
        .catch(err => { updateVisual(el, oldStatus); });
    };

    function updateVisual(el, st) {
        el.setAttribute('fill', VISUAL[st].f); el.setAttribute('stroke', VISUAL[st].s); el.setAttribute('data-status', st);
    }

    window.resetOdonto = function() {
        if(!confirm('Limpar tudo?')) return;
        const fd = new FormData(); fd.append('ajax_odonto', '1'); fd.append('acao', 'limpar_tudo'); fd.append('paciente_id', <?php echo intval($id_paciente); ?>);
        fetch('ajax_odonto.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => { if (data.sucesso) location.reload(); });
    };

    function addHistory(d, f, sa, sn) {
        const lbs = {0:{t:'Normal',c:'#adb5bd'},1:{t:'A Fazer',c:'#fbbf24'},2:{t:'Feito',c:'#10b981'},3:{t:'Extraído',c:'#ef4444'}};
        const l = document.getElementById('historyListOdonto');
        if(l.querySelector('.text-center')) l.querySelector('.text-center').remove();
        const now = new Date().toLocaleString('pt-BR', {hour:'2-digit', minute:'2-digit', day:'2-digit', month:'2-digit'});
        const c = document.createElement('div');
        c.className = 'history-card'; c.style.borderLeftColor = lbs[sn].c;
        c.innerHTML = `<div class="card-meta"><strong>Dente ${d}</strong> <span>${now}</span></div><div class="card-detail"><span class="text-muted">${f}:</span> ${lbs[sa].t} <i class="fa-solid fa-arrow-right small mx-1"></i> <strong>${lbs[sn].t}</strong></div>`;
        l.insertBefore(c, l.firstChild);
    }

    // === CORREÇÃO DO ZOOM PARA MOBILE ===
    function resizeOdontograma() {
        const visual = document.querySelector('.odontograma-visual');
        const container = document.querySelector('.canvas-panel-odonto');
        const origW = 800; const origH = 750;
        const contW = container.offsetWidth;

        if (contW < origW && contW > 0) {
            const scale = contW / origW;
            visual.style.transform = `scale(${scale})`;
            // Ajuste de altura e margem para centralizar
            const newHeight = (origH * scale) + 120; 
            container.style.height = `${newHeight}px`;
        } else {
            visual.style.transform = 'scale(1)';
            container.style.height = 'auto';
        }
    }
    window.addEventListener('load', resizeOdontograma);
    window.addEventListener('resize', resizeOdontograma);
})();
</script>