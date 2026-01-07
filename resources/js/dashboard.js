import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(ChartDataLabels);

document.addEventListener('DOMContentLoaded', function () {
    // Ambil data konfigurasi dari Blade (Global Variable)
    const config = window.gaDashboardData || {};

    // Cek apakah Chart.js sudah di-load
    if (typeof Chart === 'undefined') {
        console.error('Chart.js tidak ditemukan!');
        return;
    }

    // Helper: Hancurkan chart lama jika ada (Mencegah error Canvas reused)
    const destroyChartIfExists = (id) => {
        const chartInstance = Chart.getChart(id);
        if (chartInstance) chartInstance.destroy();
    };

    // ============================================================
    // 1. PERFORMANCE CHART (Doughnut)
    // ============================================================
    const ctxPerfId = 'performanceChart';
    const ctxPerf = document.getElementById(ctxPerfId);
    if (ctxPerf && config.performance) {
        destroyChartIfExists(ctxPerfId);
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

    // ============================================================
    // HELPER UNTUK CHART STANDAR (Bar Horizontal)
    // ============================================================
    const createStandardChart = (id, type, labels, data, color, options = {}) => {
        const ctx = document.getElementById(id);
        if (!ctx) return;
        
        destroyChartIfExists(id);

        new Chart(ctx.getContext('2d'), {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total',
                    data: data,
                    backgroundColor: color,
                    borderRadius: 4,
                    barPercentage: 0.8
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
                        font: { 
                            weight: 'bold',
                            size: 11
                        },
                        formatter: (val) => val > 0 ? val : '',
                        anchor: 'end',
                        align: 'start',
                        offset: 4
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            precision: 0,
                            font: { size: 10 }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            autoSkip: false,
                            font: { 
                                size: 10,
                                weight: '600'
                            }
                        }
                    }
                },
                ...options
            }
        });
    };

    // ============================================================
    // 2. CHART LOKASI
    // ============================================================
    if (config.loc) {
        createStandardChart(
            'locChart', 
            'bar', 
            config.loc.labels, 
            config.loc.values, 
            '#3b82f6'
        );
    }

    // ============================================================
    // 3. CHART DEPARTMENT
    // ============================================================
    if (config.dept) {
        createStandardChart(
            'deptChart', 
            'bar', 
            config.dept.labels, 
            config.dept.values, 
            '#8b5cf6'
        );
    }

    // ============================================================
    // 4. CHART PARAMETER (Doughnut)
    // ============================================================
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
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: { size: 10 }
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: { 
                            weight: 'bold', 
                            size: 11 
                        },
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
    // 5. CHART BOBOT (Pie)
    // ============================================================
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
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: { size: 10 }
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: { 
                            weight: 'bold', 
                            size: 12 
                        },
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
    // CATATAN: GANTT CHART SUDAH DIPINDAH KE gantt-chart.blade.php
    // Jadi tidak perlu ada kode Gantt Chart di sini lagi
    // ============================================================
});

// ============================================================
// EXPORT PDF FUNCTION (Global)
// ============================================================
window.exportToPDF = function () {
    if (typeof Swal === 'undefined' || typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        alert('Library pendukung gagal dimuat. Pastikan SweetAlert2, html2canvas, dan jsPDF sudah di-load.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const element = document.getElementById('dashboard-content');
    
    if (!element) {
        alert('Element dashboard-content tidak ditemukan!');
        return;
    }
    
    // Ambil tanggal dari input filter jika ada
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
        ignoreElements: (el) => el.tagName === 'BUTTON' || el.classList.contains('no-print')
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgWidth = 210; 
        const pageHeight = 297; 
        const imgHeight = canvas.height * imgWidth / canvas.width;
        let heightLeft = imgHeight;
        let position = 0;

        // Halaman pertama
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
            title: 'PDF Berhasil Di-export!',
            text: `File: ${fileName}`,
            timer: 2000,
            showConfirmButton: false
        });
    }).catch(err => {
        console.error('Error saat export PDF:', err);
        Swal.fire({ 
            icon: 'error', 
            title: 'Gagal Export PDF',
            text: 'Terjadi kesalahan saat membuat PDF. Silakan coba lagi.'
        });
    });
};