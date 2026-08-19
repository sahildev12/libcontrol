import './bootstrap';
import Alpine from 'alpinejs';
import { initRealtime } from './realtime';

window.Alpine = Alpine;

function validateIndianPhone(value, { required = false } = {}) {
    const cleaned = String(value || '').trim();
    if (! cleaned) {
        return required ? 'Phone number is required.' : null;
    }
    if (!/^[6-9]\d{9}$/.test(cleaned)) {
        return 'Enter a valid 10-digit Indian mobile number.';
    }
    return null;
}

function validateEmail(value, { required = false } = {}) {
    const cleaned = String(value || '').trim();
    if (! cleaned) {
        return required ? 'Email is required.' : null;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleaned)) {
        return 'Enter a valid email address.';
    }
    return null;
}

function firstValidationError(validations) {
    return validations.find(Boolean) || null;
}

function extractAxiosError(error) {
    return error.response?.data?.message
        || Object.values(error.response?.data?.errors || {})[0]?.[0]
        || 'Something went wrong. Please check the form and try again.';
}

function sanitizeDigits(value, maxLength = 10) {
    return String(value || '').replace(/\D/g, '').slice(0, maxLength);
}

function showToast(message, type = 'success', duration = 5000) {
    if (! message) {
        return;
    }

    const now = Date.now();
    if (
        window.__lastToast
        && window.__lastToast.message === message
        && window.__lastToast.type === type
        && now - window.__lastToast.at < 2500
    ) {
        return;
    }

    window.__lastToast = { message, type, at: now };

    window.dispatchEvent(new CustomEvent('show-toast', {
        detail: { message, type, duration },
    }));
}

window.showToast = showToast;
window.sanitizeDigits = sanitizeDigits;

function createDataTableMixin() {
    return {
        search: '',
        page: 1,
        perPage: 10,
        selectedIds: [],
        searchKeys: [],
        exportFileName: 'export',
        exportColumns: [],

        initDataTable() {
            this.$watch('search', () => { this.page = 1; });
            this.$watch('perPage', () => { this.page = 1; });
        },

        filteredRows() {
            const term = this.search.trim().toLowerCase();
            const rows = this.rows || [];
            if (! term) {
                return rows;
            }

            if (this.searchKeys.length === 0) {
                return rows.filter((row) => Object.values(row).join(' ').toLowerCase().includes(term));
            }

            return rows.filter((row) =>
                this.searchKeys.some((key) => String(row[key] ?? '').toLowerCase().includes(term)),
            );
        },

        totalPages() {
            return Math.max(1, Math.ceil(this.filteredRows().length / this.perPage));
        },

        paginatedRows() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredRows().slice(start, start + this.perPage);
        },

        pageStart() {
            if (this.filteredRows().length === 0) {
                return 0;
            }

            return (this.page - 1) * this.perPage + 1;
        },

        pageEnd() {
            return Math.min(this.page * this.perPage, this.filteredRows().length);
        },

        prevPage() {
            if (this.page > 1) {
                this.page--;
            }
        },

        nextPage() {
            if (this.page < this.totalPages()) {
                this.page++;
            }
        },

        allPageSelected() {
            const pageIds = this.paginatedRows().map((row) => row.id);
            return pageIds.length > 0 && pageIds.every((id) => this.selectedIds.includes(id));
        },

        toggleSelectAll(event) {
            const pageIds = this.paginatedRows().map((row) => row.id);
            if (event.target.checked) {
                this.selectedIds = [...new Set([...this.selectedIds, ...pageIds])];
            } else {
                this.selectedIds = this.selectedIds.filter((id) => ! pageIds.includes(id));
            }
        },

        exportRows() {
            const rows = this.filteredRows();
            const columns = this.exportColumns;
            const lines = [columns.map((column) => column.label).join(',')];

            rows.forEach((row) => {
                lines.push(columns.map((column) => {
                    const value = typeof column.value === 'function' ? column.value(row) : (row[column.key] ?? '');
                    return `"${String(value).replaceAll('"', '""')}"`;
                }).join(','));
            });

            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${this.exportFileName}-${Date.now()}.csv`;
            link.click();
            URL.revokeObjectURL(url);
        },
    };
}

function createStudentFormMixin({ storeStudentUrl, inviteStoreUrl, onStudentCreated, qrCanvasSize = 120, defaultStudentType = 'regular' }) {
    return {
        storeStudentUrl,
        inviteStoreUrl,
        qrCanvasSize,
        studentCreateOpen: false,
        studentSaving: false,
        copyingRegistrationLink: false,
        registrationInvite: null,
        studentFormErrors: {},
        studentForm: {
            name: '',
            gender: 'male',
            date_of_birth: '',
            phone: '',
            email: '',
            father_name: '',
            address: '',
            id_proof_type: '',
            id_proof: null,
            photo: null,
            student_type: defaultStudentType,
        },

        sanitizeDigits(value, maxLength = 10) {
            return sanitizeDigits(value, maxLength);
        },

        resetStudentForm() {
            this.studentFormErrors = {};
            this.studentForm = {
                name: '',
                gender: 'male',
                date_of_birth: '',
                phone: '',
                email: '',
                father_name: '',
                address: '',
                id_proof_type: '',
                id_proof: null,
                photo: null,
                student_type: defaultStudentType,
            };
        },

        validateStudentForm() {
            const errors = {};
            const name = String(this.studentForm.name || '').trim();

            if (! name) {
                errors.name = 'Full name is required.';
            } else if (name.length < 2) {
                errors.name = 'Name must be at least 2 characters.';
            } else if (/^\d+$/.test(name)) {
                errors.name = 'Name cannot contain only numbers.';
            }

            if (! ['male', 'female'].includes(this.studentForm.gender)) {
                errors.gender = 'Select Male or Female.';
            }

            if (! ['regular', 'trial'].includes(this.studentForm.student_type)) {
                errors.student_type = 'Select Regular or Trial student.';
            }

            if (! this.studentForm.date_of_birth) {
                errors.date_of_birth = 'Date of birth is required.';
            } else if (this.studentForm.date_of_birth >= new Date().toISOString().slice(0, 10)) {
                errors.date_of_birth = 'Date of birth must be in the past.';
            }

            this.studentForm.phone = sanitizeDigits(this.studentForm.phone);
            const phoneError = validateIndianPhone(this.studentForm.phone, { required: true });
            if (phoneError) {
                errors.phone = phoneError;
            }

            const emailError = validateEmail(this.studentForm.email, { required: false });
            if (emailError) {
                errors.email = emailError;
            }

            if (this.studentForm.id_proof && ! this.studentForm.id_proof_type) {
                errors.id_proof_type = 'Select the ID document type when uploading a file.';
            }

            if (this.studentForm.id_proof_type && ! this.studentForm.id_proof) {
                errors.id_proof = 'Upload the ID document file for the selected type.';
            }

            this.studentFormErrors = errors;

            return Object.keys(errors).length === 0;
        },

        async openStudentCreate() {
            this.resetStudentForm();
            this.studentCreateOpen = true;
            this.registrationInvite = null;

            await this.$nextTick();
            await this.createRegistrationInvite();
        },

        closeStudentCreate() {
            this.studentCreateOpen = false;
            this.studentSaving = false;
            this.registrationInvite = null;
            this.studentFormErrors = {};
        },

        async createRegistrationInvite() {
            if (! this.inviteStoreUrl) {
                return;
            }

            try {
                const response = await window.axios.post(this.inviteStoreUrl);
                this.registrationInvite = response.data.invite;
                await this.renderRegistrationQr(this.registrationInvite.url);
            } catch (e) {
                showToast(e.response?.data?.message || 'Could not create registration link.', 'error');
            }
        },

        async renderRegistrationQr(url) {
            if (! url || ! this.$refs.registrationQr) {
                return;
            }

            try {
                const { default: QRCode } = await import('qrcode');
                await QRCode.toCanvas(this.$refs.registrationQr, url, {
                    width: this.qrCanvasSize,
                    margin: 1,
                });
            } catch (e) {
                // QR rendering is optional; link copy still works.
            }
        },

        async copyRegistrationLink() {
            if (! this.registrationInvite?.url || this.copyingRegistrationLink) {
                return;
            }

            this.copyingRegistrationLink = true;

            try {
                await navigator.clipboard.writeText(this.registrationInvite.url);
                showToast('Registration link copied.');
            } catch (e) {
                showToast('Could not copy link.', 'error');
            } finally {
                window.setTimeout(() => {
                    this.copyingRegistrationLink = false;
                }, 500);
            }
        },

        buildStudentFormData() {
            const data = new FormData();
            ['name', 'gender', 'date_of_birth', 'phone', 'email', 'father_name', 'address', 'id_proof_type', 'student_type'].forEach((key) => {
                if (this.studentForm[key]) {
                    data.append(key, this.studentForm[key]);
                }
            });
            data.append('status', 'active');
            if (this.studentForm.photo) {
                data.append('photo', this.studentForm.photo);
            }
            if (this.studentForm.id_proof) {
                data.append('id_proof', this.studentForm.id_proof);
            }

            return data;
        },

        async submitNewStudent() {
            if (! this.validateStudentForm()) {
                showToast(Object.values(this.studentFormErrors)[0], 'error');
                return;
            }

            this.studentSaving = true;

            try {
                const response = await window.axios.post(this.storeStudentUrl, this.buildStudentFormData(), {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                const student = response.data.student;

                if (typeof onStudentCreated === 'function') {
                    onStudentCreated(student, this);
                }

                showToast(response.data.message);
                this.closeStudentCreate();
            } catch (e) {
                showToast(
                    e.response?.data?.message
                    || Object.values(e.response?.data?.errors || {})[0]?.[0]
                    || 'Could not add student.',
                    'error',
                );
            } finally {
                this.studentSaving = false;
            }
        },
    };
}

function createStudentPickerMixin({ formKey = 'assignForm', idKey = 'student_id', showAddNew = true, studentType = null } = {}) {
    return {
        studentPickerOpen: false,
        studentPickerQuery: '',
        studentPickerShowAddNew: showAddNew,
        studentPickerStyle: {
            top: '0px',
            left: '0px',
            width: '0px',
            maxHeight: '240px',
        },

        getStudentSelectId() {
            return this[formKey]?.[idKey] ?? '';
        },

        setStudentSelectId(id) {
            if (this[formKey]) {
                this[formKey][idKey] = id;
            }
        },

        selectedStudentLabel() {
            const student = (this.students || []).find((item) => String(item.id) === String(this.getStudentSelectId()));

            return student ? `${student.student_code} — ${student.name}` : '';
        },

        filteredStudentsForPicker() {
            const query = this.studentPickerQuery.trim().toLowerCase();
            let list = this.students || [];

            if (studentType) {
                list = list.filter((student) => (student.student_type || 'regular') === studentType);
            }

            if (! query) {
                return list;
            }

            return list.filter((student) =>
                String(student.name || '').toLowerCase().includes(query)
                || String(student.student_code || '').toLowerCase().includes(query)
                || String(student.phone || '').includes(query),
            );
        },

        positionStudentPicker() {
            const trigger = this.$refs.studentPickerTrigger;

            if (! trigger) {
                return;
            }

            const rect = trigger.getBoundingClientRect();
            const viewportPadding = 8;
            const maxPanelHeight = 320;
            const spaceBelow = window.innerHeight - rect.bottom - viewportPadding;
            const spaceAbove = rect.top - viewportPadding;
            const openUp = spaceBelow < 200 && spaceAbove > spaceBelow;
            const availableSpace = openUp ? spaceAbove - 8 : spaceBelow - 8;
            const panelHeight = Math.max(160, Math.min(maxPanelHeight, availableSpace));

            this.studentPickerStyle = {
                top: openUp ? `${rect.top - panelHeight - 4}px` : `${rect.bottom + 4}px`,
                left: `${rect.left}px`,
                width: `${rect.width}px`,
                maxHeight: `${panelHeight}px`,
            };
        },

        bindStudentPickerListeners() {
            this.unbindStudentPickerListeners();
            this._studentPickerReposition = () => this.positionStudentPicker();
            window.addEventListener('scroll', this._studentPickerReposition, true);
            window.addEventListener('resize', this._studentPickerReposition);
        },

        unbindStudentPickerListeners() {
            if (! this._studentPickerReposition) {
                return;
            }

            window.removeEventListener('scroll', this._studentPickerReposition, true);
            window.removeEventListener('resize', this._studentPickerReposition);
            this._studentPickerReposition = null;
        },

        openStudentPicker() {
            this.studentPickerOpen = ! this.studentPickerOpen;

            if (this.studentPickerOpen) {
                this.studentPickerQuery = '';
                this.$nextTick(() => {
                    this.positionStudentPicker();
                    this.bindStudentPickerListeners();
                    this.$refs.studentPickerSearch?.focus();
                });
            } else {
                this.closeStudentPicker();
            }
        },

        closeStudentPicker() {
            this.studentPickerOpen = false;
            this.studentPickerQuery = '';
            this.unbindStudentPickerListeners();
        },

        selectStudentFromPicker(student) {
            this.setStudentSelectId(student.id);
            this.closeStudentPicker();
        },

        selectAddNewFromPicker() {
            this.closeStudentPicker();

            if (typeof this.openStudentCreate === 'function') {
                this.openStudentCreate();
            }
        },
    };
}

Alpine.data('toastHost', () => ({
    toasts: [],

    init() {
        if (window.__toastHostBound) {
            return;
        }

        window.__toastHostBound = true;

        window.addEventListener('show-toast', (event) => {
            this.push(event.detail?.message, event.detail?.type, event.detail?.duration);
        });
    },

    push(message, type = 'success', duration = 5000) {
        if (! message) {
            return;
        }

        if (this.toasts.some((item) => item.message === message && item.type === type && item.visible)) {
            return;
        }

        const id = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
        this.toasts.push({ id, message, type, visible: true });

        window.setTimeout(() => {
            this.dismiss(id);
        }, duration ?? 5000);
    },

    dismiss(id) {
        const toast = this.toasts.find((item) => item.id === id);
        if (! toast) {
            return;
        }

        toast.visible = false;

        window.setTimeout(() => {
            this.toasts = this.toasts.filter((item) => item.id !== id);
        }, 200);
    },
}));

Alpine.data('adminShell', () => ({
    collapsed: false,
    init() {
        const stored = localStorage.getItem('libspace-admin-sidebar-collapsed');
        if (stored === '1') {
            this.collapsed = true;
        }
    },
    toggleCollapsed() {
        this.collapsed = ! this.collapsed;
        localStorage.setItem('libspace-admin-sidebar-collapsed', this.collapsed ? '1' : '0');
    },
}));

function todayYmd() {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function minutesFromHm(value) {
    const raw = String(value || '').slice(0, 5);
    const [hours, minutes] = raw.split(':').map((part) => Number(part));

    if (Number.isNaN(hours) || Number.isNaN(minutes)) {
        return null;
    }

    return (hours * 60) + minutes;
}

function assignmentWindowConflict(seat, form) {
    if (! seat) {
        return null;
    }

    const assignmentDate = form.joining_date || form.trial_start;
    if (assignmentDate && assignmentDate !== todayYmd()) {
        return null;
    }

    let start;
    let end;

    if (form.time_slot === 'custom_hours') {
        start = minutesFromHm(form.custom_start_time);
        end = minutesFromHm(form.custom_end_time);

        if (start === null || end === null) {
            return 'Choose a start and end time.';
        }

        if (end <= start) {
            return 'End time must be after start time.';
        }
    } else {
        const windows = seat.today_windows || [];
        if (! windows.length) {
            return null;
        }

        start = Number(windows[0].start_minutes);
        end = Number(windows[windows.length - 1].end_minutes);
    }

    const overlap = (seat.today_windows || []).find((window) => (
        window.type !== 'free'
        && start < Number(window.end_minutes)
        && end > Number(window.start_minutes)
    ));

    if (overlap) {
        return `That time is already occupied (${overlap.from} – ${overlap.to}). Pick a vacant window.`;
    }

    return null;
}

function currentMinutesNow() {
    const now = new Date();

    return (now.getHours() * 60) + now.getMinutes();
}

function currentWindowForSeat(seat) {
    const windows = seat?.today_windows || [];
    const minutes = currentMinutesNow();

    return windows.find((window) => minutes >= Number(window.start_minutes) && minutes < Number(window.end_minutes)) || null;
}

function displaySeatStatus(seat) {
    if (! seat) {
        return '';
    }

    if (seat.status === 'expired') {
        return 'expired';
    }

    const window = currentWindowForSeat(seat);

    if (! window) {
        return seat.status;
    }

    if (window.type === 'trial') {
        return 'on_trial';
    }

    if (window.type === 'booked') {
        return seat.status === 'expiring_soon' ? 'expiring_soon' : 'occupied';
    }

    if (seat.status === 'expiring_soon') {
        return 'expiring_soon';
    }

    return 'available';
}

function isSeatVacantNow(seat) {
    if (! seat || seat.status === 'expired') {
        return false;
    }

    const window = currentWindowForSeat(seat);

    if (! window) {
        return seat.status === 'available';
    }

    return window.type === 'free';
}

function seatTileClasses(seat) {
    const base = 'relative flex aspect-square min-h-[76px] cursor-pointer flex-col items-center justify-between rounded-xl border-2 p-2 text-center transition-all duration-150 hover:scale-105 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-400';
    const status = displaySeatStatus(seat);

    return {
        available: `${base} border-gray-400 bg-gray-300 text-gray-700 hover:border-gray-500 hover:bg-gray-400`,
        occupied: `${base} border-emerald-600 bg-emerald-500 text-white hover:border-emerald-700 hover:bg-emerald-600`,
        expiring_soon: `${base} border-amber-500 bg-amber-400 text-amber-950 hover:border-amber-600 hover:bg-amber-500`,
        expired: `${base} border-red-500 bg-red-200 text-red-900 ring-2 ring-red-300 ring-dashed hover:bg-red-300`,
        on_trial: `${base} border-cyan-500 bg-cyan-400 text-cyan-950 hover:border-cyan-600 hover:bg-cyan-500`,
        cancelled: `${base} border-gray-400 bg-gray-300 text-gray-600 opacity-70`,
    }[status] || `${base} border-gray-300 bg-white text-gray-700`;
}

function seatStatusLabel(status) {
    return {
        available: 'Vacant',
        occupied: 'Occupied',
        expiring_soon: 'Expiring Soon',
        expired: 'Expired',
        on_trial: 'Trial',
        cancelled: 'Cancelled',
    }[status] || status;
}

Alpine.data('seatMap', (config) => ({
    halls: config.halls || [],
    seats: config.seats || [],
    students: config.students || [],
    timeSlotOptions: config.timeSlotOptions || [],
    selectedHallId: config.selectedHallId || 'all',
    storeUrl: config.storeUrl,
    dataUrl: config.dataUrl || '/seats/data',
    zoom: 100,
    detailOpen: false,
    selectedSeat: null,
    assignMode: false,
    saving: false,
    assignForm: {
        student_id: '',
        hall_id: '',
        seat_id: '',
        time_slot: 'full_day',
        custom_start_time: '09:00',
        custom_end_time: '18:00',
        fee_type: 'monthly',
        fee_amount: 0,
        joining_date: new Date().toISOString().slice(0, 10),
        plan_expiry_date: '',
        membership_mode: 'assigned_seat',
    },
    ...createStudentFormMixin({
        storeStudentUrl: config.storeStudentUrl,
        inviteStoreUrl: config.inviteStoreUrl,
        qrCanvasSize: 60,
        defaultStudentType: 'regular',
        onStudentCreated(student, ctx) {
            ctx.students.unshift(student);
            ctx.assignForm.student_id = student.id;
        },
    }),
    ...createStudentPickerMixin({ formKey: 'assignForm', showAddNew: true, studentType: 'regular' }),

    init() {
        window.addEventListener('libspace:seats-updated', (event) => {
            if (event.detail?.seats) {
                this.seats = event.detail.seats;
            }
            if (event.detail?.time_slot_options) {
                this.timeSlotOptions = event.detail.time_slot_options;
            }
        });

        this._statusTick = window.setInterval(() => {
            this.tick += 1;
        }, 60000);
    },

    tick: 0,

    filteredSeats() {
        if (this.selectedHallId === 'all') {
            return this.seats;
        }

        return this.seats.filter((seat) => String(seat.hall_id) === String(this.selectedHallId));
    },

    setHall(hallId) {
        this.selectedHallId = hallId;
    },

    zoomIn() {
        this.zoom = Math.min(this.zoom + 10, 140);
    },

    zoomOut() {
        this.zoom = Math.max(this.zoom - 10, 70);
    },

    openSeat(seat) {
        this.selectedSeat = seat;
        this.assignMode = isSeatVacantNow(seat);
        this.assignForm = {
            student_id: '',
            hall_id: seat.hall_id,
            seat_id: seat.id,
            time_slot: 'full_day',
            custom_start_time: '09:00',
            custom_end_time: '18:00',
            fee_type: 'monthly',
            fee_amount: 0,
            joining_date: new Date().toISOString().slice(0, 10),
            plan_expiry_date: '',
            membership_mode: 'assigned_seat',
        };
        this.detailOpen = true;
    },

    closeDetail() {
        if (typeof this.closeStudentPicker === 'function') {
            this.closeStudentPicker();
        }
        this.detailOpen = false;
        this.selectedSeat = null;
        this.assignMode = false;
        this.saving = false;
    },

    async refreshSeats() {
        try {
            const response = await window.axios.get(this.dataUrl);
            this.seats = response.data.seats || [];
            if (response.data.halls) {
                this.halls = response.data.halls;
            }
            if (response.data.time_slot_options) {
                this.timeSlotOptions = response.data.time_slot_options;
            }
        } catch (e) {
            // Seat map will refresh on next poll or page reload.
        }
    },

    assignmentTimeError() {
        return assignmentWindowConflict(this.selectedSeat, this.assignForm);
    },

    canCancel() {
        return Boolean(this.selectedSeat?.booking_id);
    },

    async submitAssign() {
        if (! this.selectedSeat || ! isSeatVacantNow(this.selectedSeat)) {
            return;
        }

        const timeError = assignmentWindowConflict(this.selectedSeat, this.assignForm);
        if (timeError) {
            showToast(timeError, 'error');
            return;
        }

        if (! this.assignForm.student_id) {
            showToast('Please choose a student.', 'error');
            return;
        }

        this.saving = true;

        try {
            const payload = { ...this.assignForm };
            if (! payload.plan_expiry_date) {
                delete payload.plan_expiry_date;
            }

            const response = await window.axios.post(this.storeUrl, payload);
            await this.refreshSeats();
            showToast(response.data.message);
            this.closeDetail();
        } catch (e) {
            showToast(
                e.response?.data?.message
                || Object.values(e.response?.data?.errors || {})[0]?.[0]
                || 'Could not assign seat.',
                'error',
            );
        } finally {
            this.saving = false;
        }
    },

    async cancelAssignment() {
        if (! this.selectedSeat?.booking_id) {
            return;
        }

        if (! confirm('Cancel this seat assignment?')) {
            return;
        }

        this.saving = true;

        try {
            const response = await window.axios.post(`/seat-assignments/${this.selectedSeat.booking_id}/cancel`);
            await this.refreshSeats();
            showToast(response.data.message);
            this.closeDetail();
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not cancel assignment.', 'error');
        } finally {
            this.saving = false;
        }
    },

    seatClasses(seat) {
        void this.tick;

        return seatTileClasses(seat);
    },

    displayStatus(seat) {
        void this.tick;

        return displaySeatStatus(seat);
    },

    badgeClasses(seat) {
        if (displaySeatStatus(seat) === 'occupied') {
            return 'rounded bg-emerald-800/80 px-1 py-0.5 text-[9px] normal-case text-white';
        }

        return 'rounded bg-white/80 px-1 py-0.5 text-[9px] normal-case text-gray-700';
    },

    statusLabel(status) {
        return seatStatusLabel(status);
    },
}));

Alpine.data('trialSeatMap', (config) => ({
    halls: config.halls || [],
    seats: config.seats || [],
    students: config.students || [],
    timeSlotOptions: config.timeSlotOptions || [],
    selectedHallId: config.selectedHallId || 'all',
    storeUrl: config.storeUrl,
    dataUrl: config.dataUrl || '/trial-seats/data',
    availableSeatsUrl: config.availableSeatsUrl,
    zoom: 100,
    detailOpen: false,
    selectedSeat: null,
    assignMode: false,
    saving: false,
    tick: 0,
    assignForm: {
        student_id: '',
        hall_id: '',
        seat_id: '',
        time_slot: 'full_day',
        custom_start_time: '09:00',
        custom_end_time: '18:00',
        trial_start: new Date().toISOString().slice(0, 10),
        trial_days: 1,
        fee_amount: 0,
    },
    ...createStudentFormMixin({
        storeStudentUrl: config.storeStudentUrl,
        inviteStoreUrl: config.inviteStoreUrl,
        qrCanvasSize: 60,
        defaultStudentType: 'trial',
        onStudentCreated(student, ctx) {
            ctx.students.unshift(student);
            ctx.assignForm.student_id = student.id;
        },
    }),
    ...createStudentPickerMixin({ formKey: 'assignForm', showAddNew: true, studentType: 'trial' }),

    init() {
        window.addEventListener('libspace:seats-updated', (event) => {
            if (event.detail?.seats) {
                this.seats = event.detail.seats.filter((seat) => ! seat.has_regular_assignment);
            }
            if (event.detail?.time_slot_options) {
                this.timeSlotOptions = event.detail.time_slot_options;
            }
        });

        this._statusTick = window.setInterval(() => {
            this.tick += 1;
        }, 60000);
    },

    filteredSeats() {
        const seats = this.seats.filter((seat) => ! seat.has_regular_assignment);

        if (this.selectedHallId === 'all') {
            return seats;
        }

        return seats.filter((seat) => String(seat.hall_id) === String(this.selectedHallId));
    },

    setHall(hallId) {
        this.selectedHallId = hallId;
    },

    zoomIn() {
        this.zoom = Math.min(this.zoom + 10, 140);
    },

    zoomOut() {
        this.zoom = Math.max(this.zoom - 10, 70);
    },

    openSeat(seat) {
        this.selectedSeat = seat;
        this.assignMode = isSeatVacantNow(seat);
        this.assignForm = {
            student_id: '',
            hall_id: seat.hall_id,
            seat_id: seat.id,
            time_slot: 'full_day',
            custom_start_time: '09:00',
            custom_end_time: '18:00',
            trial_start: new Date().toISOString().slice(0, 10),
            trial_days: 1,
            fee_amount: 0,
        };
        this.detailOpen = true;
    },

    closeDetail() {
        if (typeof this.closeStudentPicker === 'function') {
            this.closeStudentPicker();
        }
        this.detailOpen = false;
        this.selectedSeat = null;
        this.assignMode = false;
        this.saving = false;
    },

    async refreshSeats() {
        try {
            const response = await window.axios.get(this.dataUrl);
            this.seats = (response.data.seats || []).filter((seat) => ! seat.has_regular_assignment);
            if (response.data.halls) {
                this.halls = response.data.halls;
            }
            if (response.data.time_slot_options) {
                this.timeSlotOptions = response.data.time_slot_options;
            }
        } catch (e) {
            // Trial map will refresh on next poll or page reload.
        }
    },

    async submitAssign() {
        if (! this.selectedSeat || ! isSeatVacantNow(this.selectedSeat)) {
            return;
        }

        const timeError = assignmentWindowConflict(this.selectedSeat, this.assignForm);
        if (timeError) {
            showToast(timeError, 'error');
            return;
        }

        if (! this.assignForm.student_id) {
            showToast('Please choose a student.', 'error');
            return;
        }

        this.saving = true;

        try {
            const payload = { ...this.assignForm };
            const response = await window.axios.post(this.storeUrl, payload);
            await this.refreshSeats();
            showToast(response.data.message);
            this.closeDetail();
        } catch (e) {
            showToast(
                e.response?.data?.message
                || Object.values(e.response?.data?.errors || {})[0]?.[0]
                || 'Could not assign trial seat.',
                'error',
            );
        } finally {
            this.saving = false;
        }
    },

    assignmentTimeError() {
        return assignmentWindowConflict(this.selectedSeat, this.assignForm);
    },

    freeHoursLabel(seat) {
        const free = (seat?.today_windows || []).filter((window) => window.type === 'free');

        if (! free.length) {
            return 'No free hours today';
        }

        return free.map((window) => `${window.from} – ${window.to}`).join(', ');
    },

    seatClasses(seat) {
        void this.tick;

        return seatTileClasses(seat);
    },

    displayStatus(seat) {
        void this.tick;

        return displaySeatStatus(seat);
    },

    badgeClasses(seat) {
        if (displaySeatStatus(seat) === 'occupied') {
            return 'rounded bg-emerald-800/80 px-1 py-0.5 text-[9px] normal-case text-white';
        }

        return 'rounded bg-white/80 px-1 py-0.5 text-[9px] normal-case text-gray-700';
    },

    statusLabel(status) {
        return seatStatusLabel(status);
    },
}));

Alpine.data('hallTable', (config) => ({
    rows: config.rows || [],
    flash: '',
    error: '',
    viewOpen: false,
    viewHall: null,
    formOpen: false,
    formMode: 'create',
    saving: false,
    form: { id: null, branch_id: null, name: '', seat_capacity: 10, min_seat_capacity: 1, description: '' },
    branches: config.branches || [],
    defaultBranchId: config.defaultBranchId || null,
    exportUrl: config.exportUrl,
    storeUrl: config.storeUrl,
    bulkDeleteUrl: config.bulkDeleteUrl,
    csrf: config.csrf,
    searchKeys: ['branch_name', 'name', 'description', 'seat_capacity', 'filled_seats_count', 'created_at'],
    exportFileName: 'halls',
    exportColumns: [
        { label: 'Branch', key: 'branch_name' },
        { label: 'Hall', key: 'name' },
        { label: 'Capacity', key: 'seat_capacity' },
        { label: 'Filled', key: 'filled_seats_count' },
        { label: 'Description', key: 'description' },
        { label: 'Created At', key: 'created_at' },
    ],
    ...createDataTableMixin(),

    init() {
        this.initDataTable();
    },

    openCreate() {
        this.formMode = 'create';
        this.form = {
            id: null,
            branch_id: this.defaultBranchId || this.branches[0]?.id || null,
            name: '',
            seat_capacity: 10,
            description: '',
        };
        this.formOpen = true;
        this.error = '';
    },

    openEdit(hall) {
        this.formMode = 'edit';
        this.form = {
            id: hall.id,
            branch_id: hall.branch_id,
            name: hall.name,
            seat_capacity: hall.seat_capacity,
            min_seat_capacity: hall.min_seat_capacity || hall.seat_capacity,
            description: hall.description || '',
        };
        this.formOpen = true;
        this.error = '';
    },

    async openView(hall) {
        try {
            const response = await window.axios.get(`/halls/${hall.id}`);
            this.viewHall = response.data;
            this.viewOpen = true;
        } catch (e) {
            showToast('Could not load hall details.', 'error');
        }
    },

    async submitForm() {
        if (this.formMode === 'edit' && this.form.min_seat_capacity && this.form.seat_capacity < this.form.min_seat_capacity) {
            showToast(`Capacity cannot be reduced below ${this.form.min_seat_capacity} while students are assigned.`, 'error');
            return;
        }

        this.saving = true;
        this.error = '';

        try {
            const payload = {
                branch_id: this.form.branch_id,
                name: this.form.name,
                seat_capacity: this.form.seat_capacity,
                description: this.form.description,
            };

            const response = this.formMode === 'create'
                ? await window.axios.post(this.storeUrl, payload)
                : await window.axios.patch(`/halls/${this.form.id}`, payload);

            const hall = response.data.hall;

            if (this.formMode === 'create') {
                this.rows.unshift(hall);
            } else {
                const index = this.rows.findIndex((row) => row.id === hall.id);
                if (index >= 0) this.rows[index] = hall;
            }

            showToast(response.data.message);
            this.formOpen = false;
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        } finally {
            this.saving = false;
        }
    },

    async deleteOne(hall) {
        if (! confirm(`Delete hall "${hall.name}"?`)) return;

        try {
            const response = await window.axios.delete(`/halls/${hall.id}`);
            this.rows = this.rows.filter((row) => row.id !== hall.id);
            this.selectedIds = this.selectedIds.filter((id) => id !== hall.id);
            showToast(response.data.message);
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        }
    },

    async bulkDelete() {
        if (this.selectedIds.length <= 1) return;
        if (! confirm(`Delete ${this.selectedIds.length} selected hall(s)?`)) return;

        try {
            const response = await window.axios.post(this.bulkDeleteUrl, { ids: this.selectedIds });
            this.rows = this.rows.filter((row) => ! this.selectedIds.includes(row.id));
            this.selectedIds = [];
            showToast(response.data.message);
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        }
    },
}));

Alpine.data('platformBranchesPage', (config) => ({
    branches: config.branches || [],
    storeUrl: config.storeUrl,
    createOpen: false,
    editOpen: false,
    viewOpen: false,
    hallViewOpen: false,
    hallEditOpen: false,
    saving: false,
    flash: '',
    error: '',
    viewBranch: null,
    hallView: null,
    createForm: {
        name: '',
        contact_person: '',
        phone: '',
        email: '',
        login_email: '',
        password: '',
        address: '',
    },
    editForm: {
        id: null,
        name: '',
        contact_person: '',
        phone: '',
        email: '',
        login_email: '',
        password: '',
        address: '',
    },
    hallForm: {
        id: null,
        branch_id: null,
        name: '',
        seat_capacity: 10,
        description: '',
    },

    init() {},

    filteredBranches(search) {
        const term = (search || '').trim().toLowerCase();
        if (! term) {
            return this.branches;
        }

        return this.branches.filter((branch) =>
            [branch.name, branch.contact_person, branch.phone, branch.email]
                .filter(Boolean)
                .join(' ')
                .toLowerCase()
                .includes(term),
        );
    },

    resetCreateForm() {
        this.createForm = {
            name: '',
            contact_person: '',
            phone: '',
            email: '',
            login_email: '',
            password: '',
            address: '',
        };
    },

    validateBranchForm(form, { requireName = true, requirePassword = false } = {}) {
        const errors = [];

        if (requireName && ! String(form.name || '').trim()) {
            errors.push('Branch name is required.');
        }

        const phoneError = validateIndianPhone(form.phone);
        if (phoneError) errors.push(phoneError);

        const emailError = validateEmail(form.email);
        if (emailError) errors.push(emailError);

        const loginEmailError = validateEmail(form.login_email, { required: true });
        if (loginEmailError) errors.push(loginEmailError.replace('Email', 'Login email'));

        if (requirePassword && String(form.password || '').length < 8) {
            errors.push('Password must be at least 8 characters.');
        }

        if (! requirePassword && form.password && String(form.password).length < 8) {
            errors.push('Password must be at least 8 characters.');
        }

        return firstValidationError(errors);
    },

    openCreate() {
        this.resetCreateForm();
        this.createOpen = true;
        this.error = '';
    },

    openEdit(branch) {
        this.editForm = {
            id: branch.id,
            name: branch.name || '',
            contact_person: branch.contact_person || '',
            phone: branch.phone || '',
            email: branch.email || '',
            login_email: branch.login_email || '',
            password: '',
            address: branch.address || '',
        };
        this.editOpen = true;
        this.error = '';
    },

    async openView(branch) {
        try {
            const response = await window.axios.get(`/branch/${branch.id}`);
            this.viewBranch = response.data;
            this.viewOpen = true;
        } catch (e) {
            showToast('Could not load branch details.', 'error');
        }
    },

    async submitCreate() {
        const validationError = this.validateBranchForm(this.createForm, { requirePassword: true });
        if (validationError) {
            showToast(validationError, 'error');
            return;
        }

        this.saving = true;
        try {
            const response = await window.axios.post(this.storeUrl, {
                ...this.createForm,
                phone: String(this.createForm.phone || '').trim() || null,
                email: String(this.createForm.email || '').trim() || null,
            });
            this.branches.unshift(response.data.branch);
            showToast(response.data.message);
            this.createOpen = false;
            this.resetCreateForm();
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        } finally {
            this.saving = false;
        }
    },

    async submitEdit() {
        const validationError = this.validateBranchForm(this.editForm);
        if (validationError) {
            showToast(validationError, 'error');
            return;
        }

        this.saving = true;
        try {
            const payload = {
                ...this.editForm,
                phone: String(this.editForm.phone || '').trim() || null,
                email: String(this.editForm.email || '').trim() || null,
            };
            if (! payload.password) {
                delete payload.password;
            }
            const response = await window.axios.patch(`/branch/${this.editForm.id}`, payload);
            const branch = response.data.branch;
            const index = this.branches.findIndex((row) => row.id === branch.id);
            if (index >= 0) {
                this.branches[index] = branch;
            }
            showToast(response.data.message);
            this.editOpen = false;
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        } finally {
            this.saving = false;
        }
    },

    async resetBranchPassword(branch) {
        if (! branch?.id || ! confirm(`Reset login password for "${branch.name}"?`)) {
            return;
        }

        this.saving = true;
        try {
            const response = await window.axios.post(`/branch/${branch.id}/reset-password`);
            if (this.viewBranch) {
                this.viewBranch.temporary_password = response.data.password;
                this.viewBranch.login_email = response.data.login_email;
            }
            showToast(response.data.message);
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        } finally {
            this.saving = false;
        }
    },

    async copyTemporaryPassword() {
        const password = this.viewBranch?.temporary_password;
        if (! password) {
            return;
        }

        try {
            await navigator.clipboard.writeText(password);
            showToast('Password copied.');
        } catch (e) {
            showToast('Could not copy password.', 'error');
        }
    },

    async deleteBranch(branch) {
        if (! confirm(`Delete branch "${branch.name}"? This removes all related halls, students, and data.`)) {
            return;
        }

        try {
            const response = await window.axios.delete(`/branch/${branch.id}`);
            this.branches = this.branches.filter((row) => row.id !== branch.id);
            showToast(response.data.message);
            if (this.viewBranch?.id === branch.id) {
                this.viewOpen = false;
            }
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not delete branch.', 'error');
        }
    },

    async openHallView(hall) {
        try {
            const response = await window.axios.get(`/halls/${hall.id}`);
            this.hallView = response.data;
            this.hallViewOpen = true;
        } catch (e) {
            showToast('Could not load hall details.', 'error');
        }
    },

    openHallEdit(hall) {
        this.hallForm = {
            id: hall.id,
            branch_id: hall.branch_id,
            name: hall.name,
            seat_capacity: hall.seat_capacity,
            min_seat_capacity: hall.min_seat_capacity || hall.seat_capacity,
            description: hall.description || '',
        };
        this.hallEditOpen = true;
    },

    async submitHallEdit() {
        if (this.hallForm.min_seat_capacity && this.hallForm.seat_capacity < this.hallForm.min_seat_capacity) {
            showToast(`Capacity cannot be reduced below ${this.hallForm.min_seat_capacity} while students are assigned.`, 'error');
            return;
        }

        this.saving = true;
        this.error = '';
        try {
            const response = await window.axios.patch(`/halls/${this.hallForm.id}`, {
                branch_id: this.hallForm.branch_id,
                name: this.hallForm.name,
                seat_capacity: this.hallForm.seat_capacity,
                description: this.hallForm.description,
            });
            const hall = response.data.hall;
            if (this.viewBranch?.halls) {
                const index = this.viewBranch.halls.findIndex((row) => row.id === hall.id);
                if (index >= 0) {
                    this.viewBranch.halls[index] = hall;
                }
            }
            const branch = this.branches.find((row) => row.id === hall.branch_id);
            if (branch) {
                branch.halls_count = this.viewBranch?.halls?.length ?? branch.halls_count;
            }
            showToast(response.data.message);
            this.hallEditOpen = false;
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        } finally {
            this.saving = false;
        }
    },

    async deleteHall(hall) {
        if (! confirm(`Delete hall "${hall.name}"?`)) {
            return;
        }

        try {
            const response = await window.axios.delete(`/halls/${hall.id}`);
            if (this.viewBranch?.halls) {
                this.viewBranch.halls = this.viewBranch.halls.filter((row) => row.id !== hall.id);
            }
            const branch = this.branches.find((row) => row.id === hall.branch_id);
            if (branch && branch.halls_count > 0) {
                branch.halls_count -= 1;
            }
            showToast(response.data.message);
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        }
    },
}));

Alpine.data('studentTable', (config) => ({
    rows: config.rows || [],
    students: config.rows || [],
    flash: '',
    error: '',
    bulkDeleteUrl: config.bulkDeleteUrl,
    viewOpen: false,
    viewLoading: false,
    viewStudent: null,
    editOpen: false,
    editSaving: false,
    editFormErrors: {},
    editForm: {
        id: null,
        student_code: '',
        name: '',
        gender: 'male',
        date_of_birth: '',
        phone: '',
        email: '',
        father_name: '',
        address: '',
        id_proof_type: '',
        status: 'active',
        id_proof: null,
        photo: null,
        student_type: 'regular',
        has_id_proof: false,
        has_photo: false,
    },
    storeUrl: config.storeUrl,
    searchKeys: ['student_code', 'name', 'phone', 'email', 'status', 'student_type', 'student_type_label'],
    exportFileName: 'students',
    exportColumns: [
        { label: 'Code', key: 'student_code' },
        { label: 'Name', key: 'name' },
        { label: 'Phone', key: 'phone' },
        { label: 'Email', key: 'email' },
        { label: 'Status', key: 'status' },
        { label: 'Created At', key: 'created_at' },
    ],
    ...createDataTableMixin(),
    ...createStudentFormMixin({
        storeStudentUrl: config.storeUrl,
        inviteStoreUrl: config.inviteStoreUrl,
        qrCanvasSize: 60,
        onStudentCreated(student, ctx) {
            ctx.rows.unshift(student);
            ctx.students = ctx.rows;
        },
    }),

    init() {
        this.initDataTable();
    },

    openCreate() {
        this.openStudentCreate();
    },

    async openView(row) {
        this.viewOpen = true;
        this.viewLoading = true;
        this.viewStudent = { ...row };

        try {
            const response = await window.axios.get(`/students/${row.id}`);
            this.viewStudent = response.data;
        } catch (e) {
            showToast('Could not load student details.', 'error');
            this.closeView();
        } finally {
            this.viewLoading = false;
        }
    },

    closeView() {
        this.viewOpen = false;
        this.viewLoading = false;
        this.viewStudent = null;
    },

    openEditFromView() {
        const student = this.viewStudent;
        this.closeView();

        if (student) {
            this.openEdit(student);
        }
    },

    openEdit(row) {
        this.editFormErrors = {};
        this.editForm = {
            ...row,
            gender: row.gender || 'male',
            student_type: row.student_type || 'regular',
            date_of_birth: row.date_of_birth || '',
            father_name: row.father_name || '',
            id_proof: null,
            photo: null,
            has_id_proof: Boolean(row.has_id_proof),
            has_photo: Boolean(row.has_photo),
        };
        this.editOpen = true;
        this.error = '';
    },

    closeEdit() {
        this.editOpen = false;
        this.editSaving = false;
        this.editFormErrors = {};
    },

    validateEditForm() {
        const errors = {};
        const name = String(this.editForm.name || '').trim();

        if (! name) {
            errors.name = 'Full name is required.';
        } else if (name.length < 2) {
            errors.name = 'Name must be at least 2 characters.';
        } else if (/^\d+$/.test(name)) {
            errors.name = 'Name cannot contain only numbers.';
        }

        if (! ['male', 'female'].includes(this.editForm.gender)) {
            errors.gender = 'Select Male or Female.';
        }

        if (! ['regular', 'trial'].includes(this.editForm.student_type)) {
            errors.student_type = 'Select Regular or Trial student.';
        }

        if (! this.editForm.date_of_birth) {
            errors.date_of_birth = 'Date of birth is required.';
        } else if (this.editForm.date_of_birth >= new Date().toISOString().slice(0, 10)) {
            errors.date_of_birth = 'Date of birth must be in the past.';
        }

        this.editForm.phone = sanitizeDigits(this.editForm.phone);
        const phoneError = validateIndianPhone(this.editForm.phone, { required: true });
        if (phoneError) {
            errors.phone = phoneError;
        }

        const emailError = validateEmail(this.editForm.email, { required: false });
        if (emailError) {
            errors.email = emailError;
        }

        if (this.editForm.id_proof && ! this.editForm.id_proof_type) {
            errors.id_proof_type = 'Select the ID document type when uploading a file.';
        }

        if (this.editForm.id_proof_type && ! this.editForm.id_proof && ! this.editForm.has_id_proof) {
            errors.id_proof = 'Upload the ID document file for the selected type.';
        }

        this.editFormErrors = errors;

        return Object.keys(errors).length === 0;
    },

    buildEditFormData() {
        const data = new FormData();
        ['name', 'gender', 'student_type', 'date_of_birth', 'phone', 'email', 'father_name', 'address', 'id_proof_type', 'status'].forEach((key) => {
            if (this.editForm[key] !== null && this.editForm[key] !== undefined && this.editForm[key] !== '') {
                data.append(key, this.editForm[key]);
            }
        });
        if (this.editForm.id_proof) {
            data.append('id_proof', this.editForm.id_proof);
        }
        if (this.editForm.photo) {
            data.append('photo', this.editForm.photo);
        }

        return data;
    },

    async submitEditForm() {
        if (! this.validateEditForm()) {
            showToast(Object.values(this.editFormErrors)[0], 'error');
            return;
        }

        this.editSaving = true;
        this.error = '';

        try {
            const response = await window.axios.post(
                `/students/${this.editForm.id}?_method=PATCH`,
                this.buildEditFormData(),
                { headers: { 'Content-Type': 'multipart/form-data' } },
            );

            const student = response.data.student;
            const index = this.rows.findIndex((row) => row.id === student.id);
            if (index >= 0) {
                this.rows[index] = student;
            }
            this.students = this.rows;
            showToast(response.data.message);
            this.closeEdit();
        } catch (e) {
            showToast(e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Could not save student.', 'error');
        } finally {
            this.editSaving = false;
        }
    },

    async deleteOne(row) {
        if (! confirm(`Delete student "${row.name}"?`)) return;
        try {
            const response = await window.axios.delete(`/students/${row.id}`);
            this.rows = this.rows.filter((item) => item.id !== row.id);
            this.students = this.rows;
            this.selectedIds = this.selectedIds.filter((id) => id !== row.id);
            showToast(response.data.message);
        } catch (e) {
            showToast('Could not delete student.', 'error');
        }
    },

    async bulkDelete() {
        if (this.selectedIds.length <= 1) {
            return;
        }

        if (! confirm(`Delete ${this.selectedIds.length} selected student(s)?`)) {
            return;
        }

        try {
            const response = await window.axios.post(this.bulkDeleteUrl, { ids: this.selectedIds });
            this.rows = this.rows.filter((row) => ! this.selectedIds.includes(row.id));
            this.students = this.rows;
            this.selectedIds = [];
            showToast(response.data.message);
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not delete selected students.', 'error');
        }
    },
}));

Alpine.data('enquiryTable', (config) => ({
    rows: config.rows || [],
    flash: '',
    error: '',
    formOpen: false,
    formMode: 'create',
    saving: false,
    form: { id: null, name: '', phone: '', email: '', message: '', status: 'new' },
    storeUrl: config.storeUrl,
    searchKeys: ['name', 'phone', 'email', 'status', 'student_code', 'message'],
    exportFileName: 'enquiries',
    exportColumns: [
        { label: 'Name', key: 'name' },
        { label: 'Phone', key: 'phone' },
        { label: 'Email', key: 'email' },
        { label: 'Status', key: 'status' },
        { label: 'Student Code', key: 'student_code' },
        { label: 'Created At', key: 'created_at' },
    ],
    ...createDataTableMixin(),

    init() {
        this.initDataTable();
    },

    openCreate() {
        this.formMode = 'create';
        this.form = { id: null, name: '', phone: '', email: '', message: '', status: 'new' };
        this.formOpen = true;
        this.error = '';
    },

    openEdit(row) {
        this.formMode = 'edit';
        this.form = { ...row };
        this.formOpen = true;
        this.error = '';
    },

    async submitForm() {
        this.saving = true;
        this.error = '';
        try {
            const response = this.formMode === 'create'
                ? await window.axios.post(this.storeUrl, this.form)
                : await window.axios.patch(`/enquiries/${this.form.id}`, this.form);
            const enquiry = response.data.enquiry;
            if (this.formMode === 'create') this.rows.unshift(enquiry);
            else {
                const index = this.rows.findIndex((row) => row.id === enquiry.id);
                if (index >= 0) this.rows[index] = enquiry;
            }
            showToast(response.data.message);
            this.formOpen = false;
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not save enquiry.', 'error');
        } finally {
            this.saving = false;
        }
    },

    async convert(row) {
        if (! confirm(`Convert enquiry "${row.name}" to student?`)) return;
        try {
            const response = await window.axios.post(`/enquiries/${row.id}/convert`);
            const index = this.rows.findIndex((item) => item.id === row.id);
            if (index >= 0) this.rows[index] = response.data.enquiry;
            showToast(response.data.message);
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not convert enquiry.', 'error');
        }
    },

    async deleteOne(row) {
        if (! confirm(`Delete enquiry "${row.name}"?`)) return;
        try {
            const response = await window.axios.delete(`/enquiries/${row.id}`);
            this.rows = this.rows.filter((item) => item.id !== row.id);
            showToast(response.data.message);
        } catch (e) {
            showToast('Could not delete enquiry.', 'error');
        }
    },
}));

Alpine.data('assignmentPage', (config) => ({
    rows: config.bookings || [],
    students: config.students || [],
    halls: config.halls || [],
    timeSlotOptions: config.timeSlotOptions || [],
    availableSeats: [],
    formOpen: false,
    saving: false,
    flash: '',
    error: '',
    storeUrl: config.storeUrl,
    availableSeatsUrl: config.availableSeatsUrl,
    bulkCancelUrl: config.bulkCancelUrl,
    viewOpen: false,
    viewLoading: false,
    viewAssignment: null,
    searchKeys: ['student_code', 'student_name', 'hall_name', 'seat_number', 'time_slot', 'fee_type', 'plan_expiry_date'],
    exportFileName: 'seat-assignments',
    exportColumns: [
        { label: 'Student Code', key: 'student_code' },
        { label: 'Student Name', key: 'student_name' },
        { label: 'Hall', key: 'hall_name' },
        { label: 'Seat', key: 'seat_number' },
        { label: 'Time Slot', key: 'time_slot' },
        { label: 'Fee Type', key: 'fee_type' },
        { label: 'Fee Amount', key: 'fee_amount' },
        { label: 'Plan Expiry', key: 'plan_expiry_date' },
    ],
    ...createDataTableMixin(),
    ...createStudentPickerMixin({ formKey: 'form', showAddNew: false }),
    form: {
        student_id: '',
        hall_id: '',
        seat_id: '',
        time_slot: 'full_day',
        custom_start_time: '09:00',
        custom_end_time: '18:00',
        fee_type: 'monthly',
        fee_amount: 0,
        joining_date: new Date().toISOString().slice(0, 10),
        plan_expiry_date: '',
        membership_mode: 'assigned_seat',
    },

    init() {
        this.initDataTable();
    },

    async loadSeats() {
        if (! this.form.hall_id || ! this.form.joining_date) {
            this.availableSeats = [];
            return;
        }

        const expiry = this.form.plan_expiry_date || this.form.joining_date;

        try {
            const response = await window.axios.get(this.availableSeatsUrl, {
                params: {
                    hall_id: this.form.hall_id,
                    time_slot: this.form.time_slot,
                    joining_date: this.form.joining_date,
                    plan_expiry_date: expiry,
                    custom_start_time: this.form.time_slot === 'custom_hours' ? this.form.custom_start_time : null,
                    custom_end_time: this.form.time_slot === 'custom_hours' ? this.form.custom_end_time : null,
                },
            });
            this.availableSeats = response.data.seats || [];
            if (! this.availableSeats.find((seat) => seat.id === this.form.seat_id)) {
                this.form.seat_id = '';
            }
        } catch (e) {
            this.availableSeats = [];
        }
    },

    assignmentTimeError() {
        const seat = this.availableSeats.find((item) => String(item.id) === String(this.form.seat_id));

        return assignmentWindowConflict(seat, this.form);
    },

    async submitForm() {
        const timeError = this.assignmentTimeError();
        if (timeError) {
            showToast(timeError, 'error');
            return;
        }

        this.saving = true;
        this.error = '';
        try {
            const payload = { ...this.form };
            if (! payload.plan_expiry_date) delete payload.plan_expiry_date;
            const response = await window.axios.post(this.storeUrl, payload);
            this.rows.unshift(response.data.booking);
            showToast(response.data.message);
            this.formOpen = false;
        } catch (e) {
            showToast(e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Could not assign seat.', 'error');
        } finally {
            this.saving = false;
        }
    },

    async cancel(row) {
        if (! confirm('Cancel this seat assignment?')) return;
        try {
            const response = await window.axios.post(`/seat-assignments/${row.id}/cancel`);
            this.rows = this.rows.filter((item) => item.id !== row.id);
            this.selectedIds = this.selectedIds.filter((id) => id !== row.id);
            showToast(response.data.message);
        } catch (e) {
            showToast('Could not cancel assignment.', 'error');
        }
    },

    async openView(row) {
        this.viewOpen = true;
        this.viewLoading = true;
        this.viewAssignment = { ...row };

        try {
            const response = await window.axios.get(`/seat-assignments/${row.id}`);
            this.viewAssignment = response.data;
        } catch (e) {
            showToast('Could not load assignment details.', 'error');
            this.closeView();
        } finally {
            this.viewLoading = false;
        }
    },

    closeView() {
        this.viewOpen = false;
        this.viewLoading = false;
        this.viewAssignment = null;
    },

    async cancelFromView() {
        const assignment = this.viewAssignment;

        if (! assignment) {
            return;
        }

        if (! confirm('Cancel this seat assignment?')) {
            return;
        }

        try {
            const response = await window.axios.post(`/seat-assignments/${assignment.id}/cancel`);
            this.rows = this.rows.filter((item) => item.id !== assignment.id);
            this.selectedIds = this.selectedIds.filter((id) => id !== assignment.id);
            showToast(response.data.message);
            this.closeView();
        } catch (e) {
            showToast('Could not cancel assignment.', 'error');
        }
    },

    async bulkCancel() {
        if (this.selectedIds.length <= 1) {
            return;
        }

        if (! confirm(`Cancel ${this.selectedIds.length} selected assignment(s)?`)) {
            return;
        }

        try {
            const response = await window.axios.post(this.bulkCancelUrl, { ids: this.selectedIds });
            this.rows = this.rows.filter((row) => ! this.selectedIds.includes(row.id));
            this.selectedIds = [];
            showToast(response.data.message);
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not cancel selected assignments.', 'error');
        }
    },
}));

Alpine.data('feeTable', (config) => ({
    rows: config.rows || [],
    searchKeys: ['student_code', 'student_name', 'student_phone', 'hall_name', 'seat_number', 'time_slot', 'fee_type', 'fee_amount', 'payment_status', 'plan_expiry_date'],
    exportFileName: 'fees',
    exportColumns: [
        { label: 'Student Code', key: 'student_code' },
        { label: 'Student Name', key: 'student_name' },
        { label: 'Phone', key: 'student_phone' },
        { label: 'Hall', key: 'hall_name' },
        { label: 'Seat', key: 'seat_number' },
        { label: 'Time Slot', key: 'time_slot' },
        { label: 'Fee Type', key: 'fee_type' },
        { label: 'Fee Amount', key: 'fee_amount' },
        { label: 'Joining Date', key: 'joining_date' },
        { label: 'Plan Expiry', key: 'plan_expiry_date' },
        { label: 'Payment Status', key: 'payment_status' },
    ],
    ...createDataTableMixin(),

    init() {
        this.initDataTable();
    },
}));

Alpine.data('notificationTable', (config) => ({
    rows: config.rows || [],
    searchKeys: ['title', 'message', 'type', 'date'],
    exportFileName: 'notifications',
    exportColumns: [
        { label: 'Title', key: 'title' },
        { label: 'Message', key: 'message' },
        { label: 'Type', key: 'type' },
        { label: 'Date', key: 'date' },
    ],
    ...createDataTableMixin(),

    init() {
        this.initDataTable();
    },
}));

Alpine.data('branchHallsTable', (config) => ({
    rows: config.rows || [],
    searchKeys: ['name', 'seat_capacity', 'filled_seats_count'],
    exportFileName: 'branch-halls',
    exportColumns: [
        { label: 'Hall', key: 'name' },
        { label: 'Capacity', key: 'seat_capacity' },
        { label: 'Filled', key: 'filled_seats_count' },
    ],
    ...createDataTableMixin(),

    init() {
        this.initDataTable();
    },
}));

Alpine.data('branchSwitcher', (config) => ({
    branchId: null,
    switchUrl: config.switchUrl,

    init() {
        const select = this.$el.querySelector('select');
        this.branchId = select?.value || null;
    },

    async switchBranch() {
        if (! this.branchId) {
            return;
        }

        try {
            await window.axios.post(this.switchUrl, { branch_id: Number(this.branchId) });
            window.location.reload();
        } catch (e) {
            window.alert(e.response?.data?.message || 'Could not switch branch.');
        }
    },
}));

Alpine.data('settingsPage', (config) => ({
    settings: config.settings,
    platformSettings: config.platformSettings || {},
    isPlatformAdmin: config.isPlatformAdmin || false,
    form: {
        display_name: config.settings?.display_name || '',
        expiry_reminder_days: config.settings?.expiry_reminder_days || 10,
        library_open_time: config.settings?.library_open_time || '09:00',
        library_close_time: config.settings?.library_close_time || '18:00',
        is_open_24_hours: config.settings?.is_open_24_hours || false,
        logo_with_text: null,
        simple_logo: null,
        favicon: null,
    },
    platformForm: {
        student_code_prefix: config.platformSettings?.student_code_prefix || '',
        student_code_padding: config.platformSettings?.student_code_padding || 3,
    },
    saving: false,
    flash: '',
    error: '',
    updateUrl: config.updateUrl,
    platformUpdateUrl: config.platformUpdateUrl,
    timezone: config.timezone,

    init() {},

    previewCode() {
        const prefix = (this.platformForm.student_code_prefix || 'LIB').toUpperCase();
        const padding = Math.max(1, Math.min(6, this.platformForm.student_code_padding || 3));
        return `${prefix}-${String(1).padStart(padding, '0')}`;
    },

    buildFormData() {
        const data = new FormData();
        ['display_name', 'expiry_reminder_days', 'library_open_time', 'library_close_time'].forEach((key) => {
            if (this.form[key] !== null && this.form[key] !== undefined && this.form[key] !== '') {
                data.append(key, this.form[key]);
            }
        });
        data.append('is_open_24_hours', this.form.is_open_24_hours ? '1' : '0');
        if (this.form.logo_with_text) data.append('logo_with_text', this.form.logo_with_text);
        if (this.form.simple_logo) data.append('simple_logo', this.form.simple_logo);
        if (this.form.favicon) data.append('favicon', this.form.favicon);
        return data;
    },

    validatePlatformForm() {
        if (! this.isPlatformAdmin) {
            return null;
        }

        if (! String(this.platformForm.student_code_prefix || '').trim()) {
            return 'Student code prefix is required.';
        }

        if (!/^[A-Za-z0-9_-]+$/.test(String(this.platformForm.student_code_prefix || '').trim())) {
            return 'Use only letters, numbers, hyphens, or underscores in the prefix.';
        }

        const padding = Number(this.platformForm.student_code_padding);
        if (! padding || padding < 1 || padding > 6) {
            return 'Number padding must be between 1 and 6.';
        }

        return null;
    },

    async saveSettings() {
        const validationError = this.validatePlatformForm();
        if (validationError) {
            showToast(validationError, 'error');
            return;
        }

        this.saving = true;
        this.error = '';
        try {
            if (this.isPlatformAdmin) {
                const platformResponse = await window.axios.patch(this.platformUpdateUrl, {
                    student_code_prefix: String(this.platformForm.student_code_prefix || '').trim().toUpperCase(),
                    student_code_padding: this.platformForm.student_code_padding,
                });
                this.platformSettings = platformResponse.data.platform_settings;
                this.platformForm.student_code_prefix = this.platformSettings.student_code_prefix;
                this.platformForm.student_code_padding = this.platformSettings.student_code_padding;
            }

            const response = await window.axios.post(`${this.updateUrl}?_method=PATCH`, this.buildFormData(), {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            this.settings = response.data.settings;
            this.form.display_name = this.settings.display_name || '';
            this.form.expiry_reminder_days = this.settings.expiry_reminder_days;
            this.form.library_open_time = this.settings.library_open_time || '09:00';
            this.form.library_close_time = this.settings.library_close_time || '18:00';
            this.form.is_open_24_hours = Boolean(this.settings.is_open_24_hours);
            this.form.logo_with_text = null;
            this.form.simple_logo = null;
            this.form.favicon = null;
            showToast(this.isPlatformAdmin ? 'Global and branch settings saved.' : response.data.message);
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        } finally {
            this.saving = false;
        }
    },
}));

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initRealtime();
});
