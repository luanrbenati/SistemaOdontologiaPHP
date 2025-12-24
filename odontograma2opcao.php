<?php
// odontograma.php - APENAS A PARTE VISUAL E FUNÇÕES

// Carrega o JSON específico deste paciente (A variável $id_paciente vem do arquivo pai)
$arquivo_odonto = "dados_odonto_{$id_paciente}.json";
$mapaTratamentos = file_exists($arquivo_odonto) ? json_decode(file_get_contents($arquivo_odonto), true) : [];

// Configurações Geométricas
$centerX = 350; $centerY = 350; $radiusExt = 260; $radiusInt = 160;

// Funções de Renderização (Se não existirem ainda)
if (!function_exists('getPositionStyle')) {
    function getPositionStyle($centerX, $centerY, $radius, $angleDeg) {
        $rad = deg2rad($angleDeg - 90); 
        $x = $centerX + ($radius * cos($rad));
        $y = $centerY + ($radius * sin($rad));
        return "top: " . ($y - 20) . "px; left: " . ($x - 20) . "px;";
    }
}

if (!function_exists('getCircularSVG')) {
    function getCircularSVG($dente, $dados) {
        $statusMap = [
            0 => ['fill' => 'white', 'stroke' => '#333'],
            1 => ['fill' => '#ffc107', 'stroke' => '#d39e00'], // Amarelo
            2 => ['fill' => '#198754', 'stroke' => '#146c43'], // Verde
            3 => ['fill' => '#dc3545', 'stroke' => '#b02a37'], // Vermelho
        ];
        $paths = [
            'oclusal'    => 'circle', 
            'vestibular' => 'M15,15 L10,10 A28,28 0 0,1 40,10 L35,15 Q25,20 15,15 Z', 
            'distal'     => 'M35,15 L40,10 A28,28 0 0,1 40,40 L35,35 Q30,25 35,15 Z', 
            'lingual'    => 'M35,35 L40,40 A28,28 0 0,1 10,40 L15,35 Q25,30 35,35 Z', 
            'mesial'     => 'M15,35 L10,40 A28,28 0 0,1 10,10 L15,15 Q20,25 15,35 Z', 
        ];
        $svg = '<svg width="40" height="40" viewBox="0 0 50 50" class="tooth-icon">';
        foreach ($paths as $face => $path) {
            if ($face === 'oclusal') continue;
            $st = $dados[$dente.'_'.$face] ?? 0;
            $style = $statusMap[$st];
            $svg .= sprintf('<path d="%s" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClick(%d, \'%s\', this)" data-status="%d" />', $path, $style['fill'], $style['stroke'], $dente, $face, $st);
        }
        $stCenter = $dados[$dente.'_oclusal'] ?? 0;
        $styleCenter = $statusMap[$stCenter];
        $svg .= sprintf('<circle cx="25" cy="25" r="8" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClick(%d, \'oclusal\', this)" data-status="%d" />', $styleCenter['fill'], $styleCenter['stroke'], $dente, $stCenter);
        $svg .= '</svg>';
        return $svg;
    }
}

if (!function_exists('renderToothAbsolute')) {
    function renderToothAbsolute($isoCode, $label, $angle, $radius, $cx, $cy, $dados) {
        $style = getPositionStyle($cx, $cy, $radius, $angle);
        $svg = getCircularSVG($isoCode, $dados);
        $labelRadius = ($radius > 200) ? $radius + 30 : $radius - 30;
        $styleLabel = getPositionStyle($cx, $cy, $labelRadius, $angle);
        echo "<div class='tooth-wrapper' style='$style'>$svg</div>";
        echo "<div class='tooth-label' style='$styleLabel'>$label</div>";
    }
}
?>

<style>
    /* CSS ESPECÍFICO DO ODONTOGRAMA */
    .odontograma-container { overflow-x: auto; text-align: center; padding-bottom: 20px; }
    .odontograma-wrapper { position: relative; width: 700px; height: 700px; margin: 20px auto; border-radius: 50%; }
    .cross-v { position: absolute; top: 0; bottom: 0; left: 50%; width: 1px; background: #ccc; z-index: 0; }
    .cross-h { position: absolute; left: 0; right: 0; top: 50%; height: 1px; background: #ccc; z-index: 0; }
    .label-axis { position: absolute; font-weight: bold; font-size: 14px; background: white; padding: 2px 5px; z-index: 10; color: #555; }
    .lbl-top { top: 10px; left: 50%; transform: translateX(-50%); }
    .lbl-bottom { bottom: 10px; left: 50%; transform: translateX(-50%); }
    .lbl-left { top: 50%; left: 0px; transform: translateY(-50%); }
    .lbl-right { top: 50%; right: 0px; transform: translateY(-50%); }
    .tooth-wrapper { position: absolute; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; z-index: 5; }
    .tooth-icon { cursor: pointer; filter: drop-shadow(1px 1px 1px rgba(0,0,0,0.1)); transition: transform 0.2s; }
    .tooth-icon:hover { transform: scale(1.1); }
    .face-part:hover { opacity: 0.8; }
    .tooth-label { position: absolute; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 14px; color: #333; z-index: 4; }
    .legend-box { text-align: center; margin-bottom: 10px; margin-top: 10px; }
    .dot { width: 12px; height: 12px; display: inline-block; border-radius: 50%; border: 1px solid #ccc; margin-right: 5px; }
</style>

<div class="card card-custom bg-white mt-3">
    <div class="card-body">
        <div class="legend-box">
            <span class="me-3"><span class="dot" style="background:white"></span>Normal</span>
            <span class="me-3"><span class="dot" style="background:#ffc107"></span>A Realizar</span>
            <span class="me-3"><span class="dot" style="background:#198754"></span>Realizado</span>
            <span class="me-3"><span class="dot" style="background:#dc3545"></span>Extraído</span>
        </div>

        <div class="odontograma-container">
            <div class="odontograma-wrapper">
                <div class="cross-v"></div>
                <div class="cross-h"></div>
                <div class="label-axis lbl-top">SUPERIOR</div>
                <div class="label-axis lbl-bottom">INFERIOR</div>
                <div class="label-axis lbl-left">DIREITA</div>
                <div class="label-axis lbl-right">ESQUERDA</div>

                <?php
                $perm_Q1 = [11=>'1', 12=>'2', 13=>'3', 14=>'4', 15=>'5', 16=>'6', 17=>'7', 18=>'8'];
                $dec_Q1 = [51=>'A', 52=>'B', 53=>'C', 54=>'D', 55=>'E'];
                $perm_Q2 = [21=>'1', 22=>'2', 23=>'3', 24=>'4', 25=>'5', 26=>'6', 27=>'7', 28=>'8'];
                $dec_Q2 = [61=>'A', 62=>'B', 63=>'C', 64=>'D', 65=>'E'];
                $perm_Q3 = [31=>'1', 32=>'2', 33=>'3', 34=>'4', 35=>'5', 36=>'6', 37=>'7', 38=>'8'];
                $dec_Q3 = [71=>'A', 72=>'B', 73=>'C', 74=>'D', 75=>'E'];
                $perm_Q4 = [41=>'1', 42=>'2', 43=>'3', 44=>'4', 45=>'5', 46=>'6', 47=>'7', 48=>'8'];
                $dec_Q4 = [81=>'A', 82=>'B', 83=>'C', 84=>'D', 85=>'E'];

                $i=0; foreach($perm_Q1 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, -10-($i*10.5), $radiusExt, $centerX, $centerY, $mapaTratamentos); $i++; }
                $i=0; foreach($dec_Q1 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, -12-($i*14), $radiusInt, $centerX, $centerY, $mapaTratamentos); $i++; }
                $i=0; foreach($perm_Q2 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, 10+($i*10.5), $radiusExt, $centerX, $centerY, $mapaTratamentos); $i++; }
                $i=0; foreach($dec_Q2 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, 12+($i*14), $radiusInt, $centerX, $centerY, $mapaTratamentos); $i++; }
                $i=0; foreach($perm_Q3 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, 170-($i*10.5), $radiusExt, $centerX, $centerY, $mapaTratamentos); $i++; }
                $i=0; foreach($dec_Q3 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, 168-($i*14), $radiusInt, $centerX, $centerY, $mapaTratamentos); $i++; }
                $i=0; foreach($perm_Q4 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, -170+($i*10.5), $radiusExt, $centerX, $centerY, $mapaTratamentos); $i++; }
                $i=0; foreach($dec_Q4 as $iso=>$lbl) { renderToothAbsolute($iso, $lbl, -168+($i*14), $radiusInt, $centerX, $centerY, $mapaTratamentos); $i++; }
                ?>
            </div>
        </div>
    </div>
</div>