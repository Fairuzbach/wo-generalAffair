document.addEventListener('DOMContentLoaded', function () {
    // Ambil data konfigurasi dari Blade (Global Variable)
    const config = window.gaDashboardData || {};

    // Cek apakah Chart.js sudah di-load
    if (typeof Chart === 'undefined') return;

    // Helper: Hancurkan chart lama jika ada (Mencegah error Canvas reused)
    const destroyChartIfExists = (id) => {
        const chartInstance = Chart.getChart(id);
        if (chartInstance) chartInstance.destroy();
    };

    // --- 1. PERFORMANCE CHART (Doughnut) ---
    const ctxPerfId = 'performanceChart';
    const ctxPerf = document.getElementById(ctxPerfId);
    if (ctxPerf && config.performance) {
        destroyChartIfExists(ctxPerfId); // Destroy chart lama
        new Chart(ctxPerf, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Belum Selesai'],
                datasets: [{
                    data: [config.performance.percentage, (100 - config.performance.percentage)],
                    backgroundColor: ['#FACC15', '#E2E8F0'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // --- HELPER UNTUK CHART STANDAR ---
    const createStandardChart = (id, type, labels, data, color, options = {}) => {
        const ctx = document.getElementById(id);
        if (!ctx) return;
        
        destroyChartIfExists(id); // Destroy chart lama

        new Chart(ctx.getContext('2d'), {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total',
                    data: data,
                    backgroundColor: color,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold' },
                        formatter: (val) => val > 0 ? val : ''
                    }
                },
                ...options
            }
        });
    };

    // --- 2. CHART LOKASI & DEPT ---
    if (config.loc) createStandardChart('locChart', 'bar', config.loc.labels, config.loc.values, '#3b82f6');
    if (config.dept) createStandardChart('deptChart', 'bar', config.dept.labels, config.dept.values, '#8b5cf6');

    // --- 3. CHART PARAMETER ---
    const paramId = 'paramChart';
    if (document.getElementById(paramId) && config.param) {
        destroyChartIfExists(paramId);
        new Chart(document.getElementById(paramId).getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: config.param.labels,
                datasets: [{
                    data: config.param.values,
                    backgroundColor: ['#36a2eb', '#ff6384', '#4bc0c0', '#ff9f40', '#9966ff'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value, ctx) => {
                            let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            let percentage = (value * 100 / sum).toFixed(0) + "%";
                            return value > 0 ? percentage : '';
                        }
                    }
                }
            }
        });
    }

    // --- 4. CHART BOBOT ---
    const bobotId = 'bobotChart';
    if (document.getElementById(bobotId) && config.bobot) {
        destroyChartIfExists(bobotId);
        new Chart(document.getElementById(bobotId).getContext('2d'), {
            type: 'pie',
            data: {
                labels: config.bobot.labels,
                datasets: [{
                    data: config.bobot.values,
                    backgroundColor: ['#ef4444', '#f59e0b', '#22c55e'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 14 },
                        formatter: (value, ctx) => {
                            let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            let percentage = (value * 100 / sum).toFixed(0) + "%";
                            return value > 0 ? percentage : '';
                        }
                    }
                }
            }
        });
    }

    // ============================================================
    // BAGIAN GANTT CHART DIHAPUS DARI SINI
    // KARENA SUDAH DITANGANI OLEH gantt-chart.blade.php
    // ============================================================
});


// --- EXPORT PDF FUNCTION (Global) ---
// Kita pakai versi yang di JS ini karena lebih lengkap (ada SweetAlert & Pagination)
window.exportToPDF = function () {
    if (typeof Swal === 'undefined' || typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        alert('Library pendukung gagal dimuat.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const element = document.getElementById('dashboard-content');
    
    // Ambil tanggal dari input filter jika ada, atau default
    const filterStart = document.getElementById('start_date') ? document.getElementById('start_date').value : null;
    const filterEnd = document.getElementById('end_date') ? document.getElementById('end_date').value : null;
    const config = window.gaDashboardData || {};

    let startDateVal = filterStart || (config.meta ? config.meta.defaultStartDateFilename : 'start');
    let dateObj = filterEnd ? new Date(filterEnd) : new Date();
    
    const endDateFilename = dateObj.toISOString().split('T')[0];
    const fileName = `Laporan-GA-${startDateVal}_sd_${endDateFilename}.pdf`;

    Swal.fire({
        title: 'Memproses PDF...',
        text: 'Mohon tunggu sebentar...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    html2canvas(element, {
        scale: 2,
        useCORS: true,
        backgroundColor: '#f8fafc',
        ignoreElements: (el) => el.tagName === 'BUTTON' 
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgWidth = 210; 
        const pageHeight = 297; 
        const imgHeight = canvas.height * imgWidth / canvas.width;
        let heightLeft = imgHeight;
        let position = 0; // Mulai dari atas

        // Halaman 1
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        // Halaman berikutnya jika konten panjang
        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        pdf.save(fileName);

        Swal.fire({
            icon: 'success',
            title: 'Selesai!',
            timer: 2000,
            showConfirmButton: false
        });
    }).catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Gagal Export' });
    });
};