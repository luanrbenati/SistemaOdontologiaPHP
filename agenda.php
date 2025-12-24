<?php 
// 1. ATIVAR VISUALIZAÇÃO DE ERROS (Isso vai nos mostrar o problema)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se o arquivo de conexão existe antes de incluir
if (!file_exists('conexao.php')) {
    die("<div class='alert alert-danger'>Erro Fatal: O arquivo <strong>conexao.php</strong> não foi encontrado na pasta.</div>");
}
require_once 'conexao.php'; 

// Variáveis iniciais
$pacientes = [];
$professores = [];
$turmas = [];

try {
    // 1. Buscar Pacientes
    $stmt = $pdo->query("SELECT id, nome FROM pacientes ORDER BY nome ASC");
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Buscar Professores
    $stmt = $pdo->query("SELECT id, nome FROM professores WHERE ativo = 1 ORDER BY nome ASC");
    $professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Buscar Turmas
    // Sua tabela está correta (tem id e nome), então isso DEVE funcionar.
    $stmt = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome DESC");
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Se der erro no SQL, mostra aqui
    echo "<div class='alert alert-danger m-3'>
            <strong>Erro no Banco de Dados:</strong><br> " . $e->getMessage() . "
          </div>";
} catch (Exception $e) {
    // Outros erros
    echo "<div class='alert alert-danger m-3'>
            <strong>Erro Geral:</strong><br> " . $e->getMessage() . "
          </div>";
}
?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/pt-BR.js"></script>

<style>
    .fc-event-main { cursor: pointer; color: #fff; font-size: 0.85rem; }
    .fc-toolbar-title { font-size: 1.25rem !important; }
    .select2-container { z-index: 9999; }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 text-dark">Agenda de Consultas</h4>
            <p class="text-muted small mb-0">Gerenciamento de horários por turma</p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6"><i class="fa-solid fa-calendar-plus me-2"></i>Novo Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAgendamento">
                    <input type="hidden" id="start_iso"> 
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Data/Hora</label>
                        <input type="text" class="form-control form-control-sm bg-light" id="view_data" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Turma</label>
                        <select class="form-select select2-modal" name="turma_id" id="select_turma" style="width: 100%;" onchange="carregarAlunos(this.value)">
                            <option value="">Selecione a turma...</option>
                            <?php if (!empty($turmas)): ?>
                                <?php foreach($turmas as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Nenhuma turma encontrada</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Aluno Responsável</label>
                        <select class="form-select select2-modal" name="aluno_id" id="select_aluno" style="width: 100%;" disabled>
                            <option value="">Selecione primeiro a turma...</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Paciente</label>
                            <select class="form-select select2-modal" name="paciente_id" id="select_paciente" style="width: 100%;">
                                <option value="">Pesquisar...</option>
                                <?php foreach($pacientes as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Professor</label>
                            <select class="form-select select2-modal" name="professor_id" id="select_professor" style="width: 100%;">
                                <option value="">Selecione...</option>
                                <?php foreach($professores as $prof): ?>
                                    <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Observações</label>
                        <textarea class="form-control form-control-sm" name="obs" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="salvarEvento()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
    var calendar; 
    var modalEl = document.getElementById('modalAgendamento');
    var modal = new bootstrap.Modal(modalEl);

    document.addEventListener('DOMContentLoaded', function() {
        iniciarCalendario();
        inicializarSelect2();
    });

    function inicializarSelect2() {
        $('.select2-modal').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalAgendamento'),
            language: "pt-BR",
            placeholder: "Selecione..."
        });
    }

    function carregarAlunos(turmaId) {
        var alunoSelect = $('#select_aluno');
        alunoSelect.empty().append('<option value="">Carregando...</option>').prop('disabled', true);

        if(turmaId) {
            // Busca os alunos via AJAX
            fetch('buscar_alunos.php?turma_id=' + turmaId)
                .then(response => {
                    if (!response.ok) { throw new Error('Erro na rede'); }
                    return response.json();
                })
                .then(data => {
                    alunoSelect.empty().append('<option value="">Selecione o aluno...</option>');
                    if (data.length > 0) {
                        data.forEach(aluno => {
                            alunoSelect.append(new Option(aluno.nome, aluno.id));
                        });
                    } else {
                        alunoSelect.append('<option value="">Nenhum aluno nesta turma</option>');
                    }
                    alunoSelect.prop('disabled', false);
                })
                .catch(err => {
                    console.error("Erro:", err);
                    alunoSelect.empty().append('<option value="">Erro ao buscar alunos</option>');
                });
        } else {
            alunoSelect.empty().append('<option value="">Selecione primeiro a turma...</option>');
        }
    }

    function iniciarCalendario() {
        var calendarEl = document.getElementById('calendar');
        if(!calendarEl) return;

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pt-br',
            themeSystem: 'bootstrap5',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            navLinks: true,
            selectable: true,
            dayMaxEvents: true,
            events: 'api_eventos.php',

            select: function(info) {
                $('#formAgendamento')[0].reset();
                $('.select2-modal').val(null).trigger('change');
                $('#select_aluno').prop('disabled', true).html('<option>Selecione a turma...</option>');
                
                document.getElementById('start_iso').value = info.startStr;
                let dataFormatada = new Date(info.startStr).toLocaleString('pt-BR');
                document.getElementById('view_data').value = dataFormatada;
                modal.show();
            },
            
            eventClick: function(info) {
                alert('Paciente: ' + info.event.title);
            }
        });
        calendar.render();
    }

    function salvarEvento() {
        const dados = {
            start: $('#start_iso').val(),
            paciente_id: $('#select_paciente').val(),
            aluno_id: $('#select_aluno').val(),
            professor_id: $('#select_professor').val(),
            turma_id: $('#select_turma').val(),
            obs: $('textarea[name="obs"]').val()
        };

        if(!dados.turma_id) { alert('Selecione a Turma'); return; }
        if(!dados.paciente_id) { alert('Selecione o Paciente'); return; }
        if(!dados.aluno_id) { alert('Selecione o Aluno'); return; }

        fetch('salvar_agendamento.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dados)
        })
        .then(response => response.json())
        .then(data => {
            if(data.sucesso) {
                modal.hide();
                calendar.refetchEvents(); 
            } else {
                alert('Erro: ' + (data.erro || 'Erro desconhecido'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro de comunicação. Verifique o console (F12) para detalhes.');
        });
    }
</script>