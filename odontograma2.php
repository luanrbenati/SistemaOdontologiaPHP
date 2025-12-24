<?php
// ==================================================================
// BACKEND (Salva e Carrega JSON)
// ==================================================================
$arquivo_dados = 'dados.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $dadosAtuais = file_exists($arquivo_dados) ? json_decode(file_get_contents($arquivo_dados), true) : [];
    $chave = $input['dente'] . '_' . $input['face'];
    $dadosAtuais[$chave] = $input['status'];
    file_put_contents($arquivo_dados, json_encode($dadosAtuais));
    echo json_encode(['sucesso' => true]);
    exit;
}

$mapaTratamentos = file_exists($arquivo_dados) ? json_decode(file_get_contents($arquivo_dados), true) : [];

// ==================================================================
// CONFIGURAÇÕES DE LAYOUT (MATEMÁTICA)
// ==================================================================

// Configurações do Círculo
$centerX = 350; // Centro X do container
$centerY = 350; // Centro Y do container
$radiusExt = 260; // Raio dos Permanentes
$radiusInt = 160; // Raio dos Decíduos

// Função para calcular posição CSS (Top/Left) baseado no ângulo
function getPositionStyle($centerX, $centerY, $radius, $angleDeg) {
    // Converte para radianos
    // Ajuste de -90 graus porque 0 graus na web é "Direita" (3 horas), mas queremos começar do Topo
    $rad = deg2rad($angleDeg - 90); 
    $x = $centerX + ($radius * cos($rad));
    $y = $centerY + ($radius * sin($rad));
    // Subtrai metade do tamanho do ícone (40px) para centralizar
    return "top: " . ($y - 20) . "px; left: " . ($x - 20) . "px;";
}

// SVG DO DENTE CIRCULAR (Igual imagem: Centro + 4 Fatias)
function getCircularSVG($dente, $dados) {
    $statusMap = [
        0 => ['fill' => 'white', 'stroke' => '#333'],
        1 => ['fill' => '#ffc107', 'stroke' => '#d39e00'], // Amarelo
        2 => ['fill' => '#198754', 'stroke' => '#146c43'], // Verde
        3 => ['fill' => '#dc3545', 'stroke' => '#b02a37'], // Vermelho (Extraído)
    ];

    // Desenho vetorial das 5 partes (Centro, Cima, Dir, Baixo, Esq)
    // Coordenadas baseadas em viewbox 0 0 50 50
    $paths = [
        'oclusal'    => 'circle', // Tratamento especial abaixo
        'vestibular' => 'M15,15 L10,10 A28,28 0 0,1 40,10 L35,15 Q25,20 15,15 Z', // Topo
        'distal'     => 'M35,15 L40,10 A28,28 0 0,1 40,40 L35,35 Q30,25 35,15 Z', // Direita
        'lingual'    => 'M35,35 L40,40 A28,28 0 0,1 10,40 L15,35 Q25,30 35,35 Z', // Baixo
        'mesial'     => 'M15,35 L10,40 A28,28 0 0,1 10,10 L15,15 Q20,25 15,35 Z', // Esquerda
    ];

    $svg = '<svg width="40" height="40" viewBox="0 0 50 50" class="tooth-icon">';
    
    // 1. Renderiza as fatias externas
    foreach ($paths as $face => $path) {
        if ($face === 'oclusal') continue;
        
        $st = $dados[$dente.'_'.$face] ?? 0;
        $style = $statusMap[$st];
        
        $svg .= sprintf(
            '<path d="%s" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClick(%d, \'%s\', this)" data-status="%d" />',
            $path, $style['fill'], $style['stroke'], $dente, $face, $st
        );
    }

    // 2. Renderiza o Centro (Oclusal) por cima
    $stCenter = $dados[$dente.'_oclusal'] ?? 0;
    $styleCenter = $statusMap[$stCenter];
    $svg .= sprintf(
        '<circle cx="25" cy="25" r="8" fill="%s" stroke="%s" stroke-width="1" class="face-part" onclick="handleClick(%d, \'oclusal\', this)" data-status="%d" />',
        $styleCenter['fill'], $styleCenter['stroke'], $dente, $stCenter
    );

    $svg .= '</svg>';
    return $svg;
}

// Função Auxiliar para renderizar um dente posicionado
function renderToothAbsolute($isoCode, $label, $angle, $radius, $cx, $cy, $dados) {
    $style = getPositionStyle($cx, $cy, $radius, $angle);
    $svg = getCircularSVG($isoCode, $dados);
    
    // Posiciona o label (número) um pouco fora do dente
    // Se for raio externo, label mais longe. Se interno, label mais perto do centro.
    $labelRadius = ($radius > 200) ? $radius + 30 : $radius - 30;
    $styleLabel = getPositionStyle($cx, $cy, $labelRadius, $angle);
    
    echo "<div class='tooth-wrapper' style='$style'>$svg</div>";
    echo "<div class='tooth-label' style='$styleLabel'>$label</div>";
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Odontograma Circular</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff; font-family: 'Arial', sans-serif; }
        
        /* Container Circular */
        .odontograma-wrapper {
            position: relative;
            width: 700px;
            height: 700px;
            margin: 20px auto;
            border-radius: 50%;
            /* background: #f8f9fa; Opcional: ver o circulo */
        }

        /* Linhas da Cruz */
        .cross-v {
            position: absolute; top: 0; bottom: 0; left: 50%; width: 1px; background: #ccc; z-index: 0;
        }
        .cross-h {
            position: absolute; left: 0; right: 0; top: 50%; height: 1px; background: #ccc; z-index: 0;
        }

        /* Textos de Orientação */
        .label-axis { position: absolute; font-weight: bold; font-size: 14px; background: white; padding: 2px 5px; z-index: 10; color: #555; }
        .lbl-top { top: 10px; left: 50%; transform: translateX(-50%); }
        .lbl-bottom { bottom: 10px; left: 50%; transform: translateX(-50%); }
        .lbl-left { top: 50%; left: 0px; transform: translateY(-50%); }
        .lbl-right { top: 50%; right: 0px; transform: translateY(-50%); }

        /* O Dente */
        .tooth-wrapper {
            position: absolute;
            width: 40px; height: 40px;
            display: flex; justify-content: center; align-items: center;
            z-index: 5;
        }
        .tooth-icon {
            cursor: pointer;
            filter: drop-shadow(1px 1px 1px rgba(0,0,0,0.1));
            transition: transform 0.2s;
        }
        .tooth-icon:hover { transform: scale(1.1); }
        .face-part:hover { opacity: 0.8; }

        /* O Número do dente (1, 2, A, B...) */
        .tooth-label {
            position: absolute;
            width: 40px; height: 40px;
            display: flex; justify-content: center; align-items: center;
            font-weight: bold; font-size: 14px; color: #333;
            z-index: 4;
        }
        
        /* Legenda */
        .legend-box { text-align: center; margin-bottom: 20px; }
        .dot { width: 12px; height: 12px; display: inline-block; border-radius: 50%; border: 1px solid #ccc; margin-right: 5px; }

    </style>
</head>
<body>

    <div class="container">
        <h3 class="text-center mt-4">Odontograma Geométrico</h3>
        
        <div class="legend-box">
            <span class="me-3"><span class="dot" style="background:white"></span>Normal</span>
            <span class="me-3"><span class="dot" style="background:#ffc107"></span>A Realizar</span>
            <span class="me-3"><span class="dot" style="background:#198754"></span>Realizado</span>
            <span class="me-3"><span class="dot" style="background:#dc3545"></span>Extraído</span>
        </div>

        <div class="odontograma-wrapper">
            <div class="cross-v"></div>
            <div class="cross-h"></div>
            
            <div class="label-axis lbl-top">SUPERIOR</div>
            <div class="label-axis lbl-bottom">INFERIOR</div>
            <div class="label-axis lbl-left">DIREITA</div>
            <div class="label-axis lbl-right">ESQUERDA</div>

            <?php
            // LÓGICA DE GERAÇÃO DOS DENTES
            // Precisamos mapear: (Código ISO) => [Rótulo Visual, Ângulo em Graus]
            // Ângulos: 0 = Topo, 90 = Dir, 180 = Baixo, 270 = Esq (no nosso sistema ajustado)
            
            // --- QUADRANTE 1 (Superior Direito - Visual Esquerda da Tela) ---
            // ISO 18 a 11. Ângulos de 275 a 355 (aprox)
            // Obs: Na tela, "Direita do Paciente" fica à ESQUERDA do observador.
            // Vamos seguir a imagem: "DIREITA" está escrito na esquerda da tela.
            
            // Q1: ISO 11-18 (Perm) e 51-55 (Dec). Fica no topo à esquerda do observador (Direita do paciente)
            // Ângulos de -10 a -80 graus
            
            // CORREÇÃO VISUAL:
            // A imagem mostra:
            // Topo (12h): Linha divisória.
            // Direita da Imagem (Lado Esquerdo do Paciente - Q2): Dentes 21..28
            // Esquerda da Imagem (Lado Direito do Paciente - Q1): Dentes 11..18
            
            // Configuração de Ângulos (Graus partindo do Topo 0h)
            // Lado Esquerdo da Imagem (Q1 e Q4 - Direita Paciente) -> Ângulos Negativos
            // Lado Direito da Imagem (Q2 e Q3 - Esquerda Paciente) -> Ângulos Positivos

            // PERMANENTES (Raio Externo)
            $perm_Q1 = [11=>'1', 12=>'2', 13=>'3', 14=>'4', 15=>'5', 16=>'6', 17=>'7', 18=>'8'];
            $perm_Q2 = [21=>'1', 22=>'2', 23=>'3', 24=>'4', 25=>'5', 26=>'6', 27=>'7', 28=>'8'];
            $perm_Q3 = [31=>'1', 32=>'2', 33=>'3', 34=>'4', 35=>'5', 36=>'6', 37=>'7', 38=>'8'];
            $perm_Q4 = [41=>'1', 42=>'2', 43=>'3', 44=>'4', 45=>'5', 46=>'6', 47=>'7', 48=>'8'];

            // DECÍDUOS (Raio Interno) - A, B, C, D, E
            $dec_Q1 = [51=>'A', 52=>'B', 53=>'C', 54=>'D', 55=>'E'];
            $dec_Q2 = [61=>'A', 62=>'B', 63=>'C', 64=>'D', 65=>'E'];
            $dec_Q3 = [71=>'A', 72=>'B', 73=>'C', 74=>'D', 75=>'E'];
            $dec_Q4 = [81=>'A', 82=>'B', 83=>'C', 84=>'D', 85=>'E'];

            // Loop para renderizar
            // Offset é o espaçamento angular. 8 dentes em 90 graus = ~11 graus cada.
            
            // Q1 (Superior Direito Paciente -> Esquerda da Tela): Ângulos -10, -20...
            $i = 0;
            foreach ($perm_Q1 as $iso => $lbl) {
                $angle = -10 - ($i * 10.5); // Começa perto do topo e vai descendo pra esquerda
                renderToothAbsolute($iso, $lbl, $angle, $radiusExt, $centerX, $centerY, $mapaTratamentos);
                $i++;
            }
            $i = 0;
            foreach ($dec_Q1 as $iso => $lbl) {
                $angle = -12 - ($i * 14); // Deciduos são menos, espaçamento maior
                renderToothAbsolute($iso, $lbl, $angle, $radiusInt, $centerX, $centerY, $mapaTratamentos);
                $i++;
            }

            // Q2 (Superior Esquerdo Paciente -> Direita da Tela): Ângulos 10, 20...
            $i = 0;
            foreach ($perm_Q2 as $iso => $lbl) {
                $angle = 10 + ($i * 10.5); 
                renderToothAbsolute($iso, $lbl, $angle, $radiusExt, $centerX, $centerY, $mapaTratamentos);
                $i++;
            }
            $i = 0;
            foreach ($dec_Q2 as $iso => $lbl) {
                $angle = 12 + ($i * 14); 
                renderToothAbsolute($iso, $lbl, $angle, $radiusInt, $centerX, $centerY, $mapaTratamentos);
                $i++;
            }

            // Q3 (Inferior Esquerdo Paciente -> Direita da Tela Baixo): Ângulos 170, 160...
            // Partindo de baixo (180) para a direita
            $i = 0;
            foreach ($perm_Q3 as $iso => $lbl) {
                $angle = 170 - ($i * 10.5); 
                renderToothAbsolute($iso, $lbl, $angle, $radiusExt, $centerX, $centerY, $mapaTratamentos);
                $i++;
            }
            $i = 0;
            foreach ($dec_Q3 as $iso => $lbl) {
                $angle = 168 - ($i * 14); 
                renderToothAbsolute($iso, $lbl, $angle, $radiusInt, $centerX, $centerY, $mapaTratamentos);
                $i++;
            }

            // Q4 (Inferior Direito Paciente -> Esquerda da Tela Baixo): Ângulos -170, -160...
            $i = 0;
            foreach ($perm_Q4 as $iso => $lbl) {
                $angle = -170 + ($i * 10.5); 
                renderToothAbsolute($iso, $lbl, $angle, $radiusExt, $centerX, $centerY, $mapaTratamentos);
                $i++;
            }
            $i = 0;
            foreach ($dec_Q4 as $iso => $lbl) {
                $angle = -168 + ($i * 14); 
                renderToothAbsolute($iso, $lbl, $angle, $radiusInt, $centerX, $centerY, $mapaTratamentos);
                $i++;
            }

            ?>
        </div>
    </div>

    <script>
        function handleClick(dente, face, el) {
            let status = parseInt(el.getAttribute('data-status'));
            // Ciclo: 0 > 1 (Amarelo) > 2 (Verde) > 3 (Vermelho) > 0
            let novoStatus = (status + 1) > 3 ? 0 : status + 1;

            const colors = {
                0: { fill: 'white', stroke: '#333' },
                1: { fill: '#ffc107', stroke: '#d39e00' }, 
                2: { fill: '#198754', stroke: '#146c43' },
                3: { fill: '#dc3545', stroke: '#b02a37' }
            };

            const style = colors[novoStatus];
            el.setAttribute('fill', style.fill);
            el.setAttribute('stroke', style.stroke);
            el.setAttribute('data-status', novoStatus);

            fetch('index.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ dente, face, status: novoStatus })
            });
        }
    </script>
</body>
</html>