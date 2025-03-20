// Script principal
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#dataTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json'
            },
            responsive: true
        });
    }
    
    // Inicializar gráficos se existirem
    initCharts();
});

// Inicializar gráficos
function initCharts() {
    // Verificar se Chart.js está disponível
    if (typeof Chart === 'undefined') return;
    
    // Verificar se os elementos existem
    const abastecimentoChart = document.getElementById('abastecimentoChart');
    const manutencaoChart = document.getElementById('manutencaoChart');
    
    if (abastecimentoChart && typeof abastecimentoData !== 'undefined') {
        // Gráfico de abastecimentos por mês
        new Chart(abastecimentoChart, {
            type: 'bar',
            data: {
                labels: abastecimentoData.labels,
                datasets: [{
                    label: 'Valor (R$)',
                    data: abastecimentoData.values,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    if (manutencaoChart && typeof manutencaoData !== 'undefined') {
        // Gráfico de manutenções por tipo
        new Chart(manutencaoChart, {
            type: 'pie',
            data: {
                labels: manutencaoData.labels,
                datasets: [{
                    label: 'Valor (R$)',
                    data: manutencaoData.values,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            }
        });
    }
}

// Máscara para valores monetários
function formatMoney(input) {
    let value = input.value.replace(/\D/g, '');
    value = (parseInt(value) / 100).toFixed(2) + '';
    value = value.replace(".", ",");
    value = value.replace(/(\d)(\d{3})(\,)/g, "$1.$2$3");
    value = value.replace(/(\d)(\d{3})(\d{3})(\,)/g, "$1.$2.$3$4");
    input.value = 'R$ ' + value;
}

// Máscara para placas de veículos
function formatPlaca(input) {
    let value = input.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
    if (value.length > 3) {
        value = value.substring(0, 3) + '-' + value.substring(3);
    }
    input.value = value;
}

// Confirmação de exclusão
function confirmDelete(message) {
    return confirm(message || 'Tem certeza que deseja excluir este item?');
}

// Validação de formulário
function validateForm(form) {
    const required = form.querySelectorAll('[required]');
    let valid = true;
    
    required.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            valid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return valid;
}
