// resources/js/general-affair.js

document.addEventListener('alpine:init', () => {
    const config = window.gaConfig || {};

    Alpine.data('gaData', () => ({
        // --- 1. MODAL STATES ---
        showDetailModal: false,
        showCreateModal: false,
        showConfirmModal: false,
        showEditModal: false,
        showAcceptModal: false,
        showRejectModal: false,
        show: false,

        acceptId: '',
        rejectId: '',

        // Data Selection
        selected: JSON.parse(localStorage.getItem('ga_selected_ids') || '[]').map(String),
        pageIds: (config.pageIds || []).map(String),

        // Current User Data (untuk reset ke saya)
        currentUser: {
            nik: window.gaConfig?.userNik || '',
            name: window.gaConfig?.userName || '',
            department: window.gaConfig?.userDept || ''
        },

        // --- MAPPING LOKASI KE DEPARTMENT ---
        locationMap: {
            'Plant A': 'Low Voltage',
            'Plant B': 'Medium Voltage',
            'Plant C': 'Low Voltage',
            'Plant D': 'Medium Voltage',
            'Autowire': 'Low Voltage',
            'MC Cable': 'Low Voltage',
            'QC LAB': 'QR',
            'QC LV': 'QR',
            'QC MV': 'QR',
            'QC FO': 'QR',
            'RM 1': 'SC',
            'RM 2': 'SC',
            'RM 3': 'SC',
            'RM 5': 'SC',
            'RM Office': 'SC',
            'Workshop Electric': 'MT',
            'Konstruksi': 'FH',
            'Plant E': 'FO',
            'Plant Tools': 'PE',
            'Gudang Jadi': 'SS',
            'GA': 'GA',
            'FA': 'FA',
            'IT': 'IT',
            'HC': 'HC',
            'Sales': 'Sales',
            'Marketing': 'Marketing',
        },

        // Form Data
        formData: {
            nik: '',
            manual_requester_name: '',
            department: '',
            plant_id: '',
            category: 'RINGAN',
            description: '',
            parameter_permintaan: '',
            status_permintaan: 'OPEN'
        },

        editForm: {
            id: '',
            ticket_num: '',
            status: '',
            photo_path: '',
            target_date: '',
            actual_date: ''
        },

        // --- DATA HOLDER ---
        ticket: null,
        isChecking: false,

        // --- METHODS ---
        init() {
            setTimeout(() => this.show = true, 100);
            this.resetToMe();

            // Auto Listen to event dari tabel (Dispatch method)
            window.addEventListener('buka-detail', (e) => {
                this.openDetail(e.detail);
            });
        },

        // FUNGSI DETAIL (PENTING: Gunakan Base64 untuk handle karakter enter)
        openDetail(encodedData) {
            if (!encodedData) return;
            try {
                // Decode base64 dan parse ke JSON
                const ticketData = JSON.parse(atob(encodedData));
                this.ticket = ticketData;
                this.showDetailModal = true;
                console.log('Ticket detail loaded:', this.ticket);
            } catch (error) {
                console.error('Error parsing ticket detail:', error);
                Swal.fire('Error', 'Gagal memuat detail data.', 'error');
            }
        },

        resetToMe() {
            this.formData.nik = this.currentUser.nik;
            this.formData.manual_requester_name = this.currentUser.name;
            this.formData.department = this.currentUser.department;
        },

        async checkNik() {
            if (!this.formData.nik) { this.resetToMe(); return; }
            if (this.formData.nik === this.currentUser.nik) { this.resetToMe(); return; }

            this.isChecking = true;
            try {
                const response = await fetch(`/ga/check-employee?nik=${this.formData.nik}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.formData.manual_requester_name = result.data.name;
                    this.formData.department = result.data.department;
                    
                    Swal.fire({
                        toast: true, position: 'top-end', icon: 'success',
                        title: 'Data Ditemukan', showConfirmButton: false, timer: 2000
                    });
                } else {
                    this.formData.manual_requester_name = '';
                    this.formData.department = '';
                    Swal.fire({
                        icon: 'error',
                        title: 'NIK Tidak Ditemukan!',
                        html: `<div class="text-slate-600">Maaf, NIK <b>${this.formData.nik}</b> tidak terdaftar.</div>`,
                        confirmButtonColor: '#0f172a'
                    });
                }
            } catch (e) { console.error(e); } 
            finally { this.isChecking = false; }
        },

        updateDepartment() {
            let select = document.getElementById('plantSelect');
            if (select) {
                let selectedOption = select.options[select.selectedIndex];
                let selectedText = selectedOption.text.trim();
                if (this.locationMap[selectedText]) {
                    this.formData.department = this.locationMap[selectedText];
                }
            }
        },

        openAcceptModal(id) { this.acceptId = id; this.showAcceptModal = true; },
        openRejectModal(id) { this.rejectId = id; this.showRejectModal = true; },

        toggleSelectAll() {
            const allSelected = this.pageIds.every(id => this.selected.includes(id));
            if (allSelected) {
                this.selected = this.selected.filter(id => !this.pageIds.includes(id));
            } else {
                this.pageIds.forEach(id => {
                    if (!this.selected.includes(id)) this.selected.push(id);
                });
            }
            localStorage.setItem('ga_selected_ids', JSON.stringify(this.selected));
        }
    }));
});

// Flatpickr Range Init
document.addEventListener('DOMContentLoaded', function() {
    const pickerInput = document.getElementById("date_range_picker");
    const config = window.gaConfig || {};

    if (pickerInput && typeof flatpickr !== 'undefined') {
        flatpickr(pickerInput, {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "j F Y",
            defaultDate: [config.startDate, config.endDate],
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    const start = selectedDates[0].toISOString().split('T')[0];
                    const end = selectedDates[1].toISOString().split('T')[0];
                    document.getElementById('start_date').value = start;
                    document.getElementById('end_date').value = end;
                }
            }
        });
    }
});