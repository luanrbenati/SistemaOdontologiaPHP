<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'conexao.php'; 

$pacientes = []; $professores = []; $turmas = []; $perfis = [];
try {
    $pacientes = $pdo->query("SELECT id, nome FROM pacientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $professores = $pdo->query("SELECT id, nome FROM professores WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $turmas = $pdo->query("SELECT id, nome FROM turmas ORDER BY nome DESC")->fetchAll(PDO::FETCH_ASSOC);
    $perfis = $pdo->query("SELECT id, nome FROM perfis ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { echo "Erro dados: ".$e->getMessage(); }
?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/pt-BR.js"></script>
<style>
    .fc-event-main { cursor: pointer; color: #fff; font-size: 0.85rem; }
    .select2-container { z-index: 9999; }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-dark">Agenda de Consultas</h4>
    </div>
    <div class="card shadow-sm border-0"><div class="card-body p-3"><div id='calendar'></div></div></div>
</div>

<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6" id="modalTitle">Novo Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAgendamento">
                    <input type="hidden" name="id" id="agendamento_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Data</label>
                            <input type="date" class="form-control form-control-sm" name="data_atendimento" id="data_atendimento" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Horário</label>
                            <input type="time" class="form-control form-control-sm" name="hora" id="input_hora" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Perfil / Clínica</label>
                        <select class="form-select select2-modal" name="perfil_id" id="select_perfil" style="width: 100%;">
                            <option value="">Selecione...</option>
                            <?php foreach($perfis as $p): ?><option value="<?= $p['id'] ?>"><?= $p['nome'] ?></option><?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Turma</label>
                        <select class="form-select select2-modal" name="turma_id" id="select_turma" style="width: 100%;" onchange="carregarAlunos(this.value)">
                            <option value="">Selecione a turma...</option>
                            <?php foreach($turmas as $t): ?><option value="<?= $t['id'] ?>"><?= $t['nome'] ?></option><?php endforeach; ?>
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
                                <?php foreach($pacientes as $p): ?><option value="<?= $p['id'] ?>"><?= $p['nome'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Professor</label>
                            <select class="form-select select2-modal" name="professor_id" id="select_professor" style="width: 100%;">
                                <option value="">Selecione...</option>
                                <?php foreach($professores as $p): ?><option value="<?= $p['id'] ?>"><?= $p['nome'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Observações</label>
                        <textarea class="form-control form-control-sm" name="obs" id="input_obs" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-1 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-danger" id="btnExcluir" style="display:none;" onclick="excluirEvento()">
                    <i class="fa-solid fa-trash"></i> Excluir
                </button>
                
                <div>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="salvarEvento()">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var calendar, modal; 

    document.addEventListener('DOMContentLoaded', function() {
        modal = new bootstrap.Modal(document.getElementById('modalAgendamento'));
        iniciarCalendario();
        $('.select2-modal').select2({ theme: 'bootstrap-5', dropdownParent: $('#modalAgendamento') });
    });

    // Função para carregar alunos ao trocar turma (Com suporte a pré-seleção)
    function carregarAlunos(turmaId, alunoPreSelecionado = null) {
        var sel = $('#select_aluno');
        sel.empty().append('<option>Carregando...</option>').prop('disabled', true);
        if(!turmaId) return;

        fetch('buscar_alunos.php?turma_id=' + turmaId)
            .then(r => r.json())
            .then(data => {
                sel.empty().append('<option value="">Selecione...</option>');
                data.forEach(a => sel.append(new Option(a.nome, a.id)));
                sel.prop('disabled', false);
                // Se estamos editando, seleciona o aluno correto
                if(alunoPreSelecionado) sel.val(alunoPreSelecionado).trigger('change');
            });
    }

    function iniciarCalendario() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', locale: 'pt-br', themeSystem: 'bootstrap5',
            events: 'api_eventos.php',

            // === 1. CLIQUE NO BRANCO (CRIAR NOVO) ===
            select: function(info) {
                resetModal("Novo Agendamento");
                
                // Preenche Data e Hora padrão
                let dataIso = info.startStr.split('T')[0];
                let horaIso = info.startStr.includes('T') ? info.startStr.split('T')[1].substring(0,5) : "08:00";
                
                $('#data_atendimento').val(dataIso);
                $('#input_hora').val(horaIso);
                
                modal.show();
            },

            // === 2. CLIQUE NO EVENTO (EDITAR) ===
            eventClick: function(info) {
                resetModal("Editar Agendamento");
                
                let props = info.event.extendedProps; // Dados extras do backend
                
                // Preenche campos simples
                $('#agendamento_id').val(info.event.id); // ID IMPORTANTE!
                $('#data_atendimento').val(props.data_atendimento);
                $('#input_hora').val(props.hora);
                $('#input_obs').val(props.obs);

                // Preenche Select2 (Trigger change é essencial)
                $('#select_perfil').val(props.perfil_id).trigger('change');
                $('#select_turma').val(props.turma_id).trigger('change');
                $('#select_paciente').val(props.paciente_id).trigger('change');
                $('#select_professor').val(props.professor_id).trigger('change');

                // Carrega alunos daquela turma e seleciona o certo
                carregarAlunos(props.turma_id, props.aluno_id);

                // Mostra botão de excluir
                $('#btnExcluir').show();
                modal.show();
            }
        });
        calendar.render();
    }

    function resetModal(titulo) {
        $('#formAgendamento')[0].reset();
        $('#modalTitle').text(titulo);
        $('#agendamento_id').val(''); // Limpa ID
        $('.select2-modal').val(null).trigger('change');
        $('#btnExcluir').hide(); // Esconde botão excluir
    }

    function salvarEvento() {
        const dados = {
            id:               $('#agendamento_id').val(), // Se tiver ID, é Update
            data_atendimento: $('#data_atendimento').val(),
            hora:             $('#input_hora').val(),
            perfil_id:        $('#select_perfil').val(),
            turma_id:         $('#select_turma').val(),
            paciente_id:      $('#select_paciente').val(),
            aluno_id:         $('#select_aluno').val(),
            professor_id:     $('#select_professor').val(),
            obs:              $('#input_obs').val()
        };

        // Validação simples
        if(!dados.data_atendimento || !dados.hora || !dados.paciente_id) {
            alert("Preencha os campos obrigatórios."); return;
        }

        enviarDados(dados, 'editar_agendamento.php');
    }

    function excluirEvento() {
        if(!confirm("Tem certeza que deseja excluir este agendamento?")) return;
        
        const dados = {
            id: $('#agendamento_id').val(),
            acao: 'excluir' // Flag para o PHP saber que é delete
        };
        enviarDados(dados, 'editar_agendamento.php');
    }

    function enviarDados(dados, url) {
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dados)
        })
        .then(r => r.json())
        .then(data => {
            if(data.sucesso) {
                modal.hide();
                calendar.refetchEvents();
            } else {
                alert('Erro: ' + data.erro);
            }
        });
    }
</script>