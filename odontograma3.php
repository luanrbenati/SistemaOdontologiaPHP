<?php
// ==================================================================
// CONFIGURAÇÕES DO ODONTOGRAMA (AJUSTE FINO NAS LATERAIS)
// ==================================================================

$width = 800;
$height = 650;
$centerX = $width / 2; 
$centerY = $height / 2;

$radiusExt = 240; // Permanentes
$radiusInt = 170; // Decíduos
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

// SVG Modern UI (Mantido igual)
function getToothSVG($dente) {
    global $iconSize;
    $paths = [
        'oclusal'    => 'circle',
        'vestibular' => 'M 14,14 L 6,6 A 20,20 0 0 1 34,6 L 26,14 A 8.5,8.5 0 0 0 14,14 Z',
        'distal'     => 'M 26,14 L 34,6 A 20,20 0 0 1 34,34 L 26,26 A 8.5,8.5 0 0 0 26,14 Z',
        'lingual'    => 'M 26,26 L 34,34 A 20,20 0 0 1 6,34 L 14,26 A 8.5,8.5 0 0 0 26,26 Z',
        'mesial'     => 'M 14,26 L 6,34 A 20,20 0 0 1 6,6 L 14,14 A 8.5,8.5 0 0 0 14,26 Z'
    ];

    $svg = '<svg width="'.$iconSize.'" height="'.$iconSize.'" viewBox="0 0 40 40" class="tooth-svg">';
    foreach ($paths as $face => $path) {
        if ($face === 'oclusal') continue;
        $id = "face_{$dente}_{$face}";
        $svg .= sprintf('<path id="%s" d="%s" fill="white" stroke="#777" stroke-width="0.8" class="face-part" onclick="handleClick(%d, \'%s\', this)" data-status="0" />', $id, $path, $dente, $face);
    }
    $idCentro = "face_{$dente}_oclusal";
    $svg .= sprintf('<circle id="%s" cx="20" cy="20" r="7" fill="white" stroke="#777" stroke-width="0.8" class="face-part" onclick="handleClick(%d, \'oclusal\', this)" data-status="0" />', $idCentro, $dente);
    $svg .= '</svg>';
    return $svg;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odontograma Final</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; height: 100vh; overflow: hidden; }
        .app-container { display: flex; height: 100vh; }
        .canvas-panel { flex: 1; background: white; position: relative; display: flex; justify-content: center; align-items: center; box-shadow: 2px 0 10px rgba(0,0,0,0.05); z-index: 10; }
        .odontograma-wrapper { position: relative; width: <?php echo $width; ?>px; height: <?php echo $height; ?>px; }
        .sidebar-panel { width: 350px; background-color: #f9fafb; border-left: 1px solid #e5e7eb; display: flex; flex-direction: column; }
        
        /* Dentes */
        .tooth-container { position: absolute; display: flex; justify-content: center; align-items: center; z-index: 20; }
        .tooth-svg { cursor: pointer; filter: drop-shadow(2px 3px 4px rgba(0,0,0,0.1)); transition: all 0.2s ease-out; }
        .tooth-svg:hover { transform: scale(1.15); filter: drop-shadow(4px 6px 8px rgba(0,0,0,0.15)); z-index: 50; }
        .face-part:hover { opacity: 0.8; }
        .tooth-number { position: absolute; width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; font-size: 12px; font-weight: 700; color: #6b7280; z-index: 5; }

        /* Fundo */
        .axis-line-v { position: absolute; top: 60px; bottom: 60px; left: 50%; width: 1px; background-color: #e5e7eb; }
        .axis-line-h { position: absolute; left: 60px; right: 60px; top: 50%; height: 1px; background-color: #e5e7eb; }
        .axis-label { position: absolute; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; color: #9ca3af; background: white; padding: 0 10px; }
        .ax-top { top: 30px; left: 50%; transform: translateX(-50%); }
        .ax-btm { bottom: 30px; left: 50%; transform: translateX(-50%); }
        .ax-left { left: 30px; top: 50%; transform: translateY(-50%); }
        .ax-right { right: 30px; top: 50%; transform: translateY(-50%); }

        /* Legenda */
        .floating-legend { position: absolute; top: 20px; left: 20px; background: rgba(255,255,255,0.9); padding: 15px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #f3f4f6; backdrop-filter: blur(5px); }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #374151; margin-bottom: 6px; }
        .color-dot { width: 14px; height: 14px; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .bg-normal { background: white; border: 1px solid #d1d5db; }
        .bg-todo { background: #fbbf24; border: 1px solid #d97706; }
        .bg-done { background: #10b981; border: 1px solid #059669; }
        .bg-extracted { background: #ef4444; border: 1px solid #b91c1c; }

        /* Sidebar */
        .sidebar-header { padding: 20px; border-bottom: 1px solid #e5e7eb; background: white; }
        .history-list { flex: 1; overflow-y: auto; padding: 15px; }
        .history-card { background: white; padding: 12px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.03); border-left: 4px solid #ccc; }
        .card-date { font-size: 10px; text-transform: uppercase; color: #9ca3af; margin-bottom: 2px;}
        .card-title { font-size: 13px; font-weight: 700; color: #1f2937; }
        .card-desc { font-size: 12px; color: #4b5563; margin-top: 2px; }
    </style>
</head>
<body>

<div class="app-container">
    <div class="canvas-panel">
        <div class="floating-legend">
            <h5 class="fw-bold mb-3" style="font-size: 16px;">Odontograma</h5>
            <div class="legend-item"><div class="color-dot bg-normal"></div> Normal</div>
            <div class="legend-item"><div class="color-dot bg-todo"></div> A Realizar</div>
            <div class="legend-item"><div class="color-dot bg-done"></div> Realizado</div>
            <div class="legend-item"><div class="color-dot bg-extracted"></div> Extraído</div>
            <button class="btn btn-sm btn-light border w-100 mt-3" onclick="resetAll()"><i class="bi bi-arrow-counterclockwise"></i> Resetar</button>
        </div>

        <div class="odontograma-wrapper">
            <div class="axis-line-v"></div><div class="axis-line-h"></div>
            <div class="axis-label ax-top">Superior</div><div class="axis-label ax-btm">Inferior</div>
            <div class="axis-label ax-left">Direita</div><div class="axis-label ax-right">Esquerda</div>

            <?php
            // --- CORREÇÃO DO ESPAÇAMENTO LATERAL ---
            // Ajustei os ângulos 'start' e 'step' para criar um "gap" maior nas laterais (90 e 270 graus)
            // Os arcos superiores terminam antes, e os inferiores começam depois.
            $quadrantes = [
                // Q1 (Sup. Dir) - Termina em -86
                ['dentes' => [11, 12, 13, 14, 15, 16, 17, 18], 'start' => -10, 'step' => -10.8, 'radius' => $radiusExt],
                ['dentes' => [51, 52, 53, 54, 55],             'start' => -12, 'step' => -14,   'radius' => $radiusInt],
                // Q2 (Sup. Esq) - Termina em +86
                ['dentes' => [21, 22, 23, 24, 25, 26, 27, 28], 'start' => 10,  'step' => 10.8,  'radius' => $radiusExt],
                ['dentes' => [61, 62, 63, 64, 65],             'start' => 12,  'step' => 14,    'radius' => $radiusInt],
                // Q3 (Inf. Esq) - Começa em +94 (mais para baixo)
                ['dentes' => [31, 32, 33, 34, 35, 36, 37, 38], 'start' => 170, 'step' => -10.8, 'radius' => $radiusExt],
                ['dentes' => [71, 72, 73, 74, 75],             'start' => 168, 'step' => -14,   'radius' => $radiusInt],
                // Q4 (Inf. Dir) - Começa em -94 (mais para baixo)
                ['dentes' => [41, 42, 43, 44, 45, 46, 47, 48], 'start' => -170,'step' => 10.8,  'radius' => $radiusExt],
                ['dentes' => [81, 82, 83, 84, 85],             'start' => -168,'step' => 14,    'radius' => $radiusInt],
            ];

            foreach ($quadrantes as $q) {
                $angle = $q['start'];
                foreach ($q['dentes'] as $dente) {
                    echo '<div class="tooth-container" style="' . getPos($centerX, $centerY, $q['radius'], $angle) . '">';
                    echo getToothSVG($dente);
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

    <div class="sidebar-panel">
        <div class="sidebar-header"><h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2"></i> Histórico Clínico</h6></div>
        <div class="history-list" id="historyList"></div>
    </div>
</div>

<script>
    const DB_KEY = 'odonto_final_fixed_v2';
    const STATUS = {
        0: { label: 'Normal',     fill: 'white',   stroke: '#777',    color: '#9ca3af' },
        1: { label: 'A Realizar', fill: '#fbbf24', stroke: '#d97706', color: '#fbbf24' },
        2: { label: 'Realizado',  fill: '#10b981', stroke: '#059669', color: '#10b981' },
        3: { label: 'Extraído',   fill: '#ef4444', stroke: '#b91c1c', color: '#ef4444' }
    };
    function getDB() { const data = localStorage.getItem(DB_KEY); return data ? JSON.parse(data) : { map: {}, history: [] }; }
    function saveDB(db) { localStorage.setItem(DB_KEY, JSON.stringify(db)); }
    document.addEventListener('DOMContentLoaded', () => { loadVisualState(); renderHistory(); });

    function handleClick(dente, face, el) {
        let currentStatus = parseInt(el.getAttribute('data-status'));
        let nextStatus = (currentStatus + 1) > 3 ? 0 : currentStatus + 1;
        updateElementVisual(el, nextStatus);
        const db = getDB(); const key = `${dente}_${face}`;
        if (currentStatus !== nextStatus) {
            db.history.unshift({ id: Date.now(), dente: dente, face: capitalize(face), from: STATUS[currentStatus].label, to: STATUS[nextStatus].label, date: new Date().toLocaleString('pt-BR'), colorCode: STATUS[nextStatus].color });
        }
        db.map[key] = nextStatus; saveDB(db); renderHistory();
    }

    function updateElementVisual(el, statusIdx) {
        const style = STATUS[statusIdx]; el.setAttribute('fill', style.fill); el.setAttribute('stroke', style.stroke); el.setAttribute('data-status', statusIdx);
    }
    function loadVisualState() {
        const db = getDB(); for (const [key, status] of Object.entries(db.map)) { const el = document.getElementById(`face_${key}`); if (el) updateElementVisual(el, status); }
    }
    function renderHistory() {
        const db = getDB(); const list = document.getElementById('historyList'); list.innerHTML = '';
        if (db.history.length === 0) { list.innerHTML = '<div class="text-center text-muted mt-5 small">Nenhum registro encontrado.</div>'; return; }
        db.history.forEach(item => {
            list.innerHTML += `<div class="history-card" style="border-left-color: ${item.colorCode}"><div class="d-flex justify-content-between align-items-center"><div class="card-title">Dente ${item.dente}</div><div class="card-date">${item.date}</div></div><div class="small fw-bold text-secondary">${item.face}</div><div class="card-desc">${item.from} <i class="bi bi-arrow-right-short"></i> <strong>${item.to}</strong></div></div>`;
        });
    }
    function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
    function resetAll() { if(confirm('Tem certeza que deseja apagar todo o histórico e começar do zero?')) { localStorage.removeItem(DB_KEY); location.reload(); } }
</script>
</body>
</html>