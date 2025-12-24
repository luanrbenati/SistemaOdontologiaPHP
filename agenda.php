<?php require_once 'conexao.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Agenda de Consultas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

    <style>
        /* Ajuste para o link do evento ficar branco e legível */
        .fc-event-main { cursor: pointer; color: #fff; }
        .fc-toolbar-title { font-size: 1.5rem !important; }
        .fc-button { font-size: 0.8rem !important; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid p-4">
    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div id='calendar' class="p-3"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAgendamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa-solid fa-calendar-plus me-2"></i>Novo Agendamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAgendamento">
                    <input type="hidden" id="start_iso"> 
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Data Selecionada</label>
                        <input type="text" class="form-control bg-light" id="view_data" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ID do Paciente</label>
                        <input type="number" class="form-control" name="paciente_id" required placeholder="Digite o ID do paciente">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Observações</label>
                        <textarea class="form-control" name="obs" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarEvento()">Salvar Agendamento</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var calendar; // Variável global
    var modalEl = document.getElementById('modalAgendamento');
    var modal = new bootstrap.Modal(modalEl);

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');

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
            editable: false, // Deixei false para evitar erros de edição complexa por enquanto
            dayMaxEvents: true,
            
            // CARREGA OS DADOS DO ARQUIVO PHP
            events: 'api_eventos.php',

            // AO CLICAR NO ESPAÇO EM BRANCO (CRIAR)
            select: function(info) {
                // info.startStr vem como YYYY-MM-DD ou YYYY-MM-DDTHH:mm:ss
                document.getElementById('start_iso').value = info.startStr;
                
                // Formata data bonita para o usuário ver
                let dataFormatada = new Date(info.startStr).toLocaleString('pt-BR');
                document.getElementById('view_data').value = dataFormatada;
                
                modal.show();
            },
            
            // AO CLICAR NO EVENTO (VISUALIZAR)
            eventClick: function(info) {
                alert('Paciente: ' + info.event.title + '\nObs: ' + (info.event.extendedProps.obs || 'Nenhuma'));
            }
        });

        calendar.render();
    });

    function salvarEvento() {
        const form = document.getElementById('formAgendamento');
        const formData = new FormData(form);
        
        // Dados manuais
        const dados = {
            start: document.getElementById('start_iso').value,
            paciente_id: form.querySelector('[name="paciente_id"]').value,
            obs: form.querySelector('[name="obs"]').value
        };

        if(!dados.paciente_id) { alert('Preencha o Paciente'); return; }

        fetch('salvar_agendamento.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dados)
        })
        .then(response => response.json())
        .then(data => {
            if(data.sucesso) {
                alert('Agendamento realizado!');
                modal.hide();
                form.reset();
                calendar.refetchEvents(); // Recarrega os dados sem atualizar a página
            } else {
                alert('Erro: ' + data.erro);
            }
        })
        .catch(err => console.error(err));
    }
</script>

</body>
</html>