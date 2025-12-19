document.addEventListener('DOMContentLoaded', function () {
    // Ambil data konfigurasi dari Blade
    const config = window.gaDashboardData || {};

    // Cek apakah Chart.js sudah di-load via CDN
    if (typeof Chart === 'undefined') return;

    // --- 1. PERFORMANCE CHART (Doughnut) ---
    const ctxPerf = document.getElementById('performanceChart');
    if (ctxPerf && config.performance) {
        new Chart(ctxPerf, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Belum Selesai'],
                datasets: [{
                    data: [config.performance.percentage, (100 - config.performance.percentage)],
                    backgroundColor: ['#FACC15', '#E2E8F0'], // Kuning, Abu-abu
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
                    legend: { display: false }, // Sembunyikan legend default
                    datalabels: { // Konfigurasi Plugin Datalabels
                        color: '#fff',
                        font: { weight: 'bold' },
                        formatter: (val) => val > 0 ? val : ''
                    }
                },
                ...options
            }
        });
    };

    // --- 2. CHART LOKASI, DEPT, PARAMETER ---
    if (config.loc) createStandardChart('locChart', 'bar', config.loc.labels, config.loc.values, '#3b82f6');
    if (config.dept) createStandardChart('deptChart', 'bar', config.dept.labels, config.dept.values, '#8b5cf6');
    
    // --- 3. CHART PARAMETER (Doughnut Khusus) ---
    const ctxParam = document.getElementById('paramChart');
    if (ctxParam && config.param) {
        new Chart(ctxParam.getContext('2d'), {
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

    // --- 4. CHART BOBOT (Pie) ---
    const ctxBobot = document.getElementById('bobotChart');
    if (ctxBobot && config.bobot) {
        new Chart(ctxBobot.getContext('2d'), {
            type: 'pie',
            data: {
                labels: config.bobot.labels,
                datasets: [{
                    data: config.bobot.values,
                    backgroundColor: ['#ef4444', '#f59e0b', '#22c55e'], // Merah, Kuning, Hijau
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

    // --- 5. GANTT CHART ---
    const ctxGantt = document.getElementById('ganttChart');
    if (ctxGantt && config.gantt) {
        const rawData = config.gantt.raw;
        const chartData = config.gantt.data; // Data tanggal [start, end]

        // --- LOGIKA WARNA DINAMIS (UPDATED: SAFE MODE) ---
        const dynamicColors = rawData.map((item, index) => {
            // 1. SAFETY CHECK: Cek apakah data tanggal tersedia di index ini?
            // Jika chartData[index] undefined, kembalikan warna default (abu-abu) & hentikan proses
            if (!chartData || !chartData[index]) {
                console.warn('Data tanggal tidak ditemukan untuk index:', index);
                return '#e2e8f0'; 
            }

            const status = item.status;
            
            // Ambil tanggal akhir (end_date ada di index 1)
            const endDate = new Date(chartData[index][1]);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // 1. HIJAU: Selesai
            if (status === 'completed') {
                return '#10b981'; // emerald-500
            }

            // 2. MERAH: Overdue
            if (status !== 'completed' && endDate < today) {
                return '#ef4444'; // red-500
            }

            // 3. BIRU: On Progress
            if (status === 'in_progress') {
                return '#3b82f6'; // blue-500
            }

            // 4. KUNING: Default
            return '#f59e0b'; // amber-500
        });

        new Chart(ctxGantt.getContext('2d'), {
            type: 'bar',
            data: {
                labels: config.gantt.labels,
                datasets: [{
                    label: 'Durasi Pengerjaan',
                    data: config.gantt.data,
                    
                    // GUNAKAN WARNA DINAMIS DI SINI
                    backgroundColor: dynamicColors,
                    borderColor: dynamicColors,
                    
                    borderWidth: 1,
                    barPercentage: 0.6,
                    // Custom Data mapping
                    departments: rawData.map(item => item.dept),
                    locations: rawData.map(item => item.loc),
                    statuses: rawData.map(item => item.status) 
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day', displayFormats: { day: 'd MMM' }, tooltipFormat: 'd MMM yyyy' },
                        min: new Date(new Date().setDate(new Date().getDate() - 7)),
                        grid: { color: '#f1f5f9' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { weight: 'bold', size: 11 } }
                    }
                },
                plugins: {
                    legend: { display: false },
                    annotation: {
                        annotations: {
                            todayLine: {
                                type: 'line',
                                xMin: new Date(),
                                xMax: new Date(),
                                borderColor: 'rgba(239, 68, 68, 0.8)', // Merah transparan
                                borderWidth: 2,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: 'Hari Ini',
                                    position: 'start',
                                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                                    color: 'white',
                                    font: { size: 10, weight: 'bold' },
                                    yAdjust: -10
                                }
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        callbacks: {
                            title: (context) => context[0].label,
                            label: (context) => {
                                const raw = context.raw;
                                const ds = context.dataset;
                                const idx = context.dataIndex;
                                const deptName = ds.departments[idx] || '-';
                                const locName = ds.locations[idx] || '-';
                                const status = ds.statuses[idx];
                                const itemColor = context.dataset.backgroundColor[idx]; // Ambil warna item saat ini

                                let statusText = '';
                                
                                // Tambahkan teks status berdasarkan warna/kondisi
                                if (status === 'completed') {
                                    statusText = ' (✅ SELESAI)';
                                } else if (itemColor === '#ef4444') {
                                    statusText = ' (🔥 OVERDUE)';
                                } else if (status === 'in_progress') {
                                    statusText = ' (⚡ ON PROGRESS)';
                                } else {
                                    statusText = ' (⏳ PENDING)';
                                }
                                
                                const start = new Date(raw[0]).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                                const end = new Date(raw[1]).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

                                return [
                                    `📍 Lokasi: ${locName}`,
                                    `🏢 Dept: ${deptName}${statusText}`,
                                    `📅 Jadwal: ${start} - ${end}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }
});

// --- EXPORT PDF FUNCTION (Global) ---
window.exportToPDF = function () {
    // Validasi Library
    if (typeof Swal === 'undefined' || typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        alert('Library pendukung (SweetAlert, Html2Canvas, JsPDF) gagal dimuat. Periksa koneksi internet Anda.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const element = document.getElementById('dashboard-content');
    const filterStart = document.getElementById('start_date').value;
    const filterEnd = document.getElementById('end_date').value;
    const config = window.gaDashboardData || {};

    // Tentukan Nama File
    let startDateVal = filterStart || config.meta.defaultStartDateFilename;
    let endDateFilename, endDateHeader;
    let dateObj = filterEnd ? new Date(filterEnd) : new Date();

    // Format Tanggal Akhir
    const year = dateObj.getFullYear();
    const day = String(dateObj.getDate()).padStart(2, '0');
    const monthName = dateObj.toLocaleString('id-ID', { month: 'long' });
    
    endDateFilename = `${day}-${monthName}-${year}`;
    endDateHeader = `${day} ${monthName} ${year}`;

    // Format Tanggal Awal untuk Header
    let startDateHeader = config.meta.defaultStartDateHeader;
    if (filterStart) {
        const sDate = new Date(filterStart);
        startDateHeader = `${String(sDate.getDate()).padStart(2, '0')} ${sDate.toLocaleString('id-ID', { month: 'long' })} ${sDate.getFullYear()}`;
    }

    const fileName = `Laporan-GA-${startDateVal}_sd_${endDateFilename}.pdf`;
    const headerText = `Periode Data: ${startDateHeader} s/d ${endDateHeader}`;

    // Loading Alert
    Swal.fire({
        title: 'Memproses PDF...',
        text: `Menyiapkan rentang: ${startDateVal} s/d ${endDateFilename}`,
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    // Proses Render
    html2canvas(element, {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: '#f8fafc',
        ignoreElements: (el) => el.tagName === 'BUTTON' // Abaikan tombol
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgWidth = 210;
        const pageHeight = 297;
        const imgHeight = canvas.height * imgWidth / canvas.width;
        let heightLeft = imgHeight;
        let position = 15;

        // Header PDF
        pdf.setFontSize(10);
        pdf.text("Laporan Dashboard General Affair", 10, 8);
        pdf.setFontSize(9);
        pdf.setTextColor(100);
        pdf.text(headerText, 10, 13);

        // Gambar Halaman 1
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        // Halaman Selanjutnya (jika panjang)
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
            text: 'File: ' + fileName,
            timer: 3000,
            showConfirmButton: false
        });

    }).catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Gagal Export', text: 'Lihat console browser untuk detail.' });
    });
};