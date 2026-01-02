// resources/js/ga.js

document.addEventListener('alpine:init', () => {
    // Ambil konfigurasi dari window object
    const config = window.gaConfig || {};

    Alpine.data('gaData', () => ({
        // --- 1. MODAL STATES ---
        showDetailModal: false,
        showCreateModal: false,
        showConfirmModal: false,
        showEditModal: false,
        showAcceptModal:false,
        showRejectModal:false,
        show: false,

        acceptId:'',
        rejectId:'',

        // Data Selection
        selected: JSON.parse(localStorage.getItem('ga_selected_ids') || '[]').map(String),
        pageIds: (config.pageIds || []).map(String),

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
        form: { 
            plant: '', 
            plant_name: '', 
            department: '', 
            category: 'RINGAN', 
            description: '', 
            file_name: '', 
            parameter_permintaan: '', 
            status_permintaan: '' 
        },
        editForm: { 
            id: '', 
            ticket_num: '', 
            status: '', 
            photo_path: '', 
            target_date: '', 
            actual_date: '' 
        },

        // --- DATA HOLDER & TIME ---
        ticket: null,
        currentDate: '',
        currentTime: '',

        // Getters
        get selectedTickets() { return this.selected; },

        // Methods
        init() {
            this.updateTime();
            setInterval(() => this.updateTime(), 60000);
            
            // Animasi masuk
            setTimeout(() => this.show = true, 100);

            // Watchers
            this.$watch('showCreateModal', (v) => {
                if (!v) {
                    this.form.plant = '';
                    this.form.plant_name = '';
                    this.form.department = '';
                    this.form.category = 'RINGAN';
                    this.form.description = '';
                    this.form.file_name = '';
                }
            });

            // Initialize Flatpickr for edit modal if needed
            this.$watch('showEditModal', (value) => {
                if (value) {
                    setTimeout(() => {
                        if (typeof flatpickr !== 'undefined') {
                            document.querySelectorAll('.date-picker').forEach(el => {
                                flatpickr(el, { dateFormat: 'Y-m-d', minDate: 'today', allowInput: true });
                            });
                        }
                    }, 100);
                }
            });
        },

        updateTime() {
            const now = new Date();
            this.currentDate = now.toISOString().split('T')[0];
            this.currentTime = now.toTimeString().split(' ')[0].substring(0, 5);
        },

        toggleSelectAll() {
            const allSelected = this.pageIds.every(id => this.selected.includes(id));
            if (allSelected) { 
                this.selected = this.selected.filter(id => !this.pageIds.includes(id)); 
            } else { 
                this.pageIds.forEach(id => { 
                    if (!this.selected.includes(id)) this.selected.push(id); 
                }); 
            }
            // Save to localStorage if needed
            localStorage.setItem('ga_selected_ids', JSON.stringify(this.selected));
        },

        clearSelection() {
            this.selected = [];
            localStorage.removeItem('ga_selected_ids');
        },

        updateDepartment() {
            let select = document.getElementById('plantSelect');
            if (select) {
                let selectedOption = select.options[select.selectedIndex];
                let selectedText = selectedOption.text;
                this.form.plant_name = selectedText;
                if (this.locationMap[selectedText]) { 
                    this.form.department = this.locationMap[selectedText]; 
                }
            }
        },

        handleFile(e) { 
            this.form.file_name = e.target.files[0] ? e.target.files[0].name : ''; 
        },

        submitForm() { 
            // Menggunakan $refs dari elemen HTML terkait
            const form = document.querySelector('form[x-ref="createForm"]'); 
            if(form && form.reportValidity()) {
                form.submit();
            } else {
                this.showConfirmModal = false; 
            }
        },

        openEditModal(data) {
            this.ticket = data;
            this.editForm.id = data.id;
            this.editForm.ticket_num = data.ticket_num;
            this.editForm.status = data.status;
            this.editForm.category = data.category;
            this.editForm.target_date = data.target_completion_date || '';
            this.editForm.photo_path = data.photo_path;
            this.showEditModal = true;
        }
    }));
});

// Flatpickr Range Init
document.addEventListener('DOMContentLoaded', function() {
    const pickerInput = document.getElementById("date_range_picker");
    const config = window.gaConfig || {}; // Ambil config lagi untuk default date

    if (pickerInput && typeof flatpickr !== 'undefined') {
        flatpickr(pickerInput, {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "j F Y",
            defaultDate: [config.startDate, config.endDate], // Dari config
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const offset = selectedDates[0].getTimezoneOffset();
                    const startDate = new Date(selectedDates[0].getTime() - (offset * 60 * 1000)).toISOString().split('T')[0];
                    const endDate = new Date(selectedDates[1].getTime() - (offset * 60 * 1000)).toISOString().split('T')[0];
                    
                    const startInput = document.getElementById('start_date');
                    const endInput = document.getElementById('end_date');
                    
                    if(startInput) startInput.value = startDate;
                    if(endInput) endInput.value = endDate;
                }
            },
            onClose: function(selectedDates) {
                if (selectedDates.length === 0) {
                    const startInput = document.getElementById('start_date');
                    const endInput = document.getElementById('end_date');
                    if(startInput) startInput.value = "";
                    if(endInput) endInput.value = "";
                }
            }
        });
    }
});