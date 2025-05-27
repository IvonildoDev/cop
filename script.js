// Adicione essa função no início do arquivo para atualizar o horário atual
function atualizarHorarioAtual() {
    const agora = new Date();
    const dataHoraFormatada = agora.toISOString().slice(0, 16); // Formato YYYY-MM-DDThh:mm

    // Localizar todos os campos de data-hora que precisam ser atualizados com o horário atual
    const camposDataHora = document.querySelectorAll('.hora-atual');

    camposDataHora.forEach(campo => {
        campo.value = dataHoraFormatada;
    });

    // Verificar se há o campo específico de início de operação
    const campoInicioOperacao = document.getElementById('inicio_operacao');
    if (campoInicioOperacao) {
        campoInicioOperacao.value = dataHoraFormatada;
    }

    return agora;
}

// Função para formatar a data e hora
function formatarDataHora(data) {
    const pad = (num) => String(num).padStart(2, '0');

    const dia = pad(data.getDate());
    const mes = pad(data.getMonth() + 1);
    const ano = data.getFullYear();
    const hora = pad(data.getHours());
    const minuto = pad(data.getMinutes());

    return `${ano}-${mes}-${dia}T${hora}:${minuto}`;
}

// Evento para botão de Iniciar Operação
document.addEventListener('DOMContentLoaded', function () {
    // Inicializar o horário ao carregar a página
    atualizarHorarioAtual();

    // Configurar timer para atualizar o horário a cada minuto
    setInterval(atualizarHorarioAtual, 60000);

    // Capturar botão de iniciar operação
    const btnIniciarOperacao = document.querySelector('.btn-iniciar-operacao');
    if (btnIniciarOperacao) {
        btnIniciarOperacao.addEventListener('click', function () {
            // Atualizar o horário de início da operação no momento do clique
            const agora = atualizarHorarioAtual();
            console.log("Operação iniciada às:", formatarDataHora(agora));

            // Se houver um campo oculto para armazenar o timestamp
            const timestampField = document.getElementById('timestamp_inicio_operacao');
            if (timestampField) {
                timestampField.value = agora.getTime(); // Armazena o timestamp em milissegundos
            }
        });
    }

    // Capturar formulários que possam ter campos de data/hora
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            // Atualizar horário nos campos marcados como hora-atual antes do envio
            atualizarHorarioAtual();
        });
    });
});

// Modal e controle de aguardo
const modal = document.getElementById("motivoAguardoModal");
const btnInicioAguardo = document.getElementById("inicioAguardo");
const btnFimAguardo = document.getElementById("fimAguardo");
const btnConfirmarAguardo = document.getElementById("confirmarAguardo");
const span = document.getElementsByClassName("close")[0];
let timerAguardo = null;
let segundosAguardo = 0;

// Abrir modal ao clicar em Iniciar Aguardo
btnInicioAguardo.addEventListener("click", function () {
    modal.style.display = "block";
});

// Fechar o modal ao clicar no X
span.addEventListener("click", function () {
    modal.style.display = "none";
});

// Fechar o modal ao clicar fora dele
window.addEventListener("click", function (event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
});

// Iniciar aguardo quando confirmar o motivo
btnConfirmarAguardo.addEventListener("click", function () {
    const motivo = document.getElementById("motivoAguardo").value.trim();

    if (!motivo) {
        alert("Por favor, informe o motivo do aguardo.");
        return;
    }

    // Fechar o modal
    modal.style.display = "none";

    // Registrar início do aguardo
    const now = new Date();
    document.getElementById("inicioAguardoTimestamp").value = now.toISOString();
    document.getElementById("motivoAguardoValor").value = motivo;

    // Atualizar interface
    btnInicioAguardo.disabled = true;
    btnFimAguardo.disabled = false;

    const statusIcon = document.getElementById("statusAguardoIcon");
    const statusText = document.getElementById("statusAguardo");
    statusIcon.className = "fas fa-pause-circle status-ativo";
    statusText.textContent = "Aguardando: " + motivo;

    // Adicionar classe de aguardo ativo ao contêiner
    document.querySelector(".mobilizacao-container:last-of-type").classList.add("aguardo-ativo");

    // Iniciar o cronômetro
    timerAguardo = setInterval(function () {
        segundosAguardo++;
        document.getElementById("tempoAguardo").textContent = "Tempo: " + formatarTempo(segundosAguardo);
    }, 1000);

    document.getElementById("aguardoStatus").value = "Em andamento";

    // Registrar o aguardo no banco de dados (opcional - pode ser feito via AJAX)
    registrarAguardo("iniciar", motivo);
});

// Finalizar aguardo
document.getElementById("fimAguardo").addEventListener("click", function () {
    // Registrar o fim do aguardo
    const now = new Date();
    document.getElementById("fimAguardoTimestamp").value = now.toISOString();

    // Atualizar interface
    this.disabled = true;
    document.getElementById("inicioAguardo").disabled = false;

    // Parar o cronômetro
    clearInterval(timerAguardo);

    const statusIcon = document.getElementById("statusAguardoIcon");
    const statusText = document.getElementById("statusAguardo");
    statusIcon.className = "fas fa-check-circle status-concluido";

    // Calcular o tempo total de aguardo formatado
    const tempoFormatado = formatarTempo(segundosAguardo);
    statusText.textContent = "Aguardo finalizado. Duração: " + tempoFormatado;

    // Remover classe de aguardo ativo
    document.querySelector(".mobilizacao-container:last-of-type").classList.remove("aguardo-ativo");

    document.getElementById("aguardoStatus").value = "Concluído";

    // Registrar o fim do aguardo no banco de dados (opcional - via AJAX)
    registrarAguardo("finalizar");
});

// Função para registrar aguardo no banco via AJAX
function registrarAguardo(acao, motivo = "") {
    const formData = new FormData();
    formData.append("acao", acao);

    if (acao === "iniciar") {
        formData.append("motivo", motivo);
        formData.append("inicio", document.getElementById("inicioAguardoTimestamp").value);
    } else {
        formData.append("fim", document.getElementById("fimAguardoTimestamp").value);
        formData.append("inicio", document.getElementById("inicioAguardoTimestamp").value);
        formData.append("duracao", segundosAguardo);
    }

    // Enviar para o servidor
    fetch("registrar_aguardo.php", {
        method: "POST",
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log("Aguardo registrado com sucesso:", data.message);
            } else {
                console.error("Erro ao registrar aguardo:", data.message);
            }
        })
        .catch(error => {
            console.error("Erro na requisição:", error);
        });
}

// Manter as outras funções existentes do aguardo...
// (código existente para controle de aguardos)