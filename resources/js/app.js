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

        bulkDeleteUrl: null,

        extraFilter() {
            return true;
        },

        initDataTable() {
            this.$watch('search', () => { this.page = 1; });
            this.$watch('perPage', () => { this.page = 1; });
        },

        filteredRows() {
            const term = this.search.trim().toLowerCase();
            let rows = this.rows || [];

            if (typeof this.extraFilter === 'function') {
                rows = rows.filter((row) => this.extraFilter(row));
            }

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

        async bulkDelete() {
            if (! this.bulkDeleteUrl || this.selectedIds.length < 2) {
                return;
            }

            if (! confirm(`Delete ${this.selectedIds.length} selected items?`)) {
                return;
            }

            try {
                const response = await window.axios.post(this.bulkDeleteUrl, { ids: this.selectedIds });
                const removed = new Set(this.selectedIds.map((id) => String(id)));
                this.rows = this.rows.filter((row) => ! removed.has(String(row.id)));
                this.selectedIds = [];
                showToast(response.data.message);
            } catch (e) {
                showToast(e.response?.data?.message || 'Could not delete the selected items.', 'error');
            }
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
        registrationQrPreviewOpen: false,
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
            branch_id: null,
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
                branch_id: this.defaultBranchId || this.branches?.[0]?.id || '',
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

            if ((this.branches || []).length > 1 && ! this.studentForm.branch_id) {
                errors.branch_id = 'Select a branch.';
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

            const emailError = validateEmail(this.studentForm.email, { required: true });
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
            if (this.selectedSeat?.branch_id) {
                this.studentForm.branch_id = this.selectedSeat.branch_id;
            }
            this.studentCreateOpen = true;
            this.registrationInvite = null;
            this.registrationQrPreviewOpen = false;

            await this.$nextTick();
            await this.createRegistrationInvite();
        },

        closeStudentCreate() {
            this.studentCreateOpen = false;
            this.studentSaving = false;
            this.registrationInvite = null;
            this.registrationQrPreviewOpen = false;
            this.studentFormErrors = {};
        },

        async createRegistrationInvite() {
            if (! this.inviteStoreUrl) {
                return;
            }

            try {
                const payload = this.studentForm.branch_id ? { branch_id: this.studentForm.branch_id } : {};
                const response = await window.axios.post(this.inviteStoreUrl, payload);
                this.registrationInvite = response.data.invite;
                await this.renderRegistrationQr(this.registrationInvite.url);
            } catch (e) {
                showToast(e.response?.data?.message || 'Could not create registration link.', 'error');
            }
        },

        async renderRegistrationQr(url, canvasRef = 'registrationQr', size = null) {
            const canvas = this.$refs[canvasRef];
            if (! url || ! canvas) {
                return;
            }

            try {
                const { default: QRCode } = await import('qrcode');
                await QRCode.toCanvas(canvas, url, {
                    width: size || this.qrCanvasSize,
                    margin: 1,
                });
            } catch (e) {
                // QR rendering is optional; link copy still works.
            }
        },

        async openRegistrationQrPreview() {
            if (! this.registrationInvite?.url) {
                return;
            }

            this.registrationQrPreviewOpen = true;
            await this.$nextTick();
            await this.renderRegistrationQr(this.registrationInvite.url, 'registrationQrLarge', 280);
        },

        closeRegistrationQrPreview() {
            this.registrationQrPreviewOpen = false;
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
            ['name', 'gender', 'date_of_birth', 'phone', 'email', 'father_name', 'address', 'id_proof_type', 'student_type', 'branch_id'].forEach((key) => {
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

function createStudentPickerMixin({ formKey = 'assignForm', idKey = 'student_id', showAddNew = true } = {}) {
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

function occupiedWindows(seat) {
    return (seat?.today_windows || []).filter((window) => window.type !== 'free');
}

function seatHasOccupiedHours(seat) {
    return occupiedWindows(seat).length > 0;
}

function minutesToHm(minutes) {
    if (minutes >= 24 * 60) {
        return '23:59';
    }

    const safe = Math.max(0, minutes);
    const hours = Math.floor(safe / 60) % 24;
    const mins = safe % 60;

    return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}

function firstFreeWindow(seat) {
    return (seat?.today_windows || []).find((window) => window.type === 'free') || null;
}

function libraryHoursForSeat(seat) {
    if (seat?.is_open_24_hours) {
        return { open: 0, close: (24 * 60) - 1, is24: true, openHm: '00:00', closeHm: '23:59' };
    }

    const openHm = String(seat?.library_open_time || '09:00').slice(0, 5);
    const closeHm = String(seat?.library_close_time || '18:00').slice(0, 5);
    const open = minutesFromHm(openHm);
    const close = minutesFromHm(closeHm);

    return {
        open: open === null ? 9 * 60 : open,
        close: close === null ? 18 * 60 : close,
        is24: false,
        openHm,
        closeHm,
    };
}

function snapCustomTimesToLibraryHours(form, seat = null) {
    if (! form || form.time_slot !== 'custom_hours') {
        return;
    }

    const hours = libraryHoursForSeat(seat);
    let start = minutesFromHm(form.custom_start_time);
    let end = minutesFromHm(form.custom_end_time);

    if (start === null) {
        start = hours.open;
    }
    if (end === null) {
        end = hours.close;
    }

    if (! hours.is24) {
        if (start < hours.open) {
            start = hours.open;
        }
        if (start >= hours.close) {
            start = hours.open;
        }
        if (end > hours.close) {
            end = hours.close;
        }
        if (end <= hours.open) {
            end = hours.close;
        }
    } else if (end >= 24 * 60) {
        end = (24 * 60) - 1;
    }

    if (end <= start) {
        end = Math.min(hours.close, start + 60);
        if (end <= start) {
            start = hours.open;
            end = hours.close > hours.open ? hours.close : Math.min((24 * 60) - 1, start + 60);
        }
    }

    form.custom_start_time = minutesToHm(start);
    form.custom_end_time = minutesToHm(end);
}

function defaultCustomTimes(seat) {
    const hours = libraryHoursForSeat(seat);
    const free = firstFreeWindow(seat);

    if (free) {
        let start = Number(free.start_minutes);
        let end = Number(free.end_minutes);

        if (end >= 24 * 60) {
            end = (24 * 60) - 1;
        }

        if (! hours.is24) {
            start = Math.max(start, hours.open);
            end = Math.min(end, hours.close);
        }

        if (Number.isFinite(start) && Number.isFinite(end) && end > start) {
            return {
                start: minutesToHm(start),
                end: minutesToHm(end),
            };
        }
    }

    return { start: hours.openHm, end: hours.closeHm };
}

function ensureValidCustomTimes(form, seat = null) {
    if (! form || form.time_slot !== 'custom_hours') {
        return;
    }

    let start = minutesFromHm(form.custom_start_time);
    let end = minutesFromHm(form.custom_end_time);

    if (start === null || end === null || end <= start) {
        const defaults = defaultCustomTimes(seat);
        form.custom_start_time = defaults.start;
        form.custom_end_time = defaults.end;
    }

    snapCustomTimesToLibraryHours(form, seat);
}

function assignableTimeSlotOptions(options, seat) {
    const disableFullDay = seatHasOccupiedHours(seat);

    return (options || []).map((option) => ({
        ...option,
        disabled: disableFullDay && option.value === 'full_day',
    }));
}

function snapCustomTimesOffOccupied(seat, form) {
    if (! seat || form.time_slot !== 'custom_hours') {
        return;
    }

    const occupied = occupiedWindows(seat);
    if (! occupied.length) {
        return;
    }

    let start = minutesFromHm(form.custom_start_time);
    let end = minutesFromHm(form.custom_end_time);

    occupied.forEach((window) => {
        const windowStart = Number(window.start_minutes);
        const windowEnd = Number(window.end_minutes);

        if (start !== null && start >= windowStart && start < windowEnd) {
            start = windowEnd;
            form.custom_start_time = minutesToHm(start);
        }

        if (end !== null && end > windowStart && end <= windowEnd) {
            end = windowStart;
            form.custom_end_time = minutesToHm(end);
        }
    });

    start = minutesFromHm(form.custom_start_time);
    end = minutesFromHm(form.custom_end_time);

    if (start === null || end === null) {
        return;
    }

    const overlap = occupied.find((window) => (
        start < Number(window.end_minutes) && end > Number(window.start_minutes)
    ));

    if (! overlap) {
        return;
    }

    if (start <= Number(overlap.start_minutes)) {
        form.custom_end_time = minutesToHm(Number(overlap.start_minutes));
    } else {
        form.custom_start_time = minutesToHm(Number(overlap.end_minutes));
    }
}

function assignmentWindowConflict(seat, form) {
    if (! seat) {
        return null;
    }

    const assignmentDate = form.joining_date || form.trial_start;
    if (assignmentDate && assignmentDate !== todayYmd()) {
        return null;
    }

    if (form.time_slot === 'full_day' && seatHasOccupiedHours(seat)) {
        return 'Full day is not available because this seat already has booked hours. Choose a vacant custom window.';
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

    const overlap = occupiedWindows(seat).find((window) => (
        start < Number(window.end_minutes)
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

function isCustomHoursSlot(seat, window = null) {
    const slot = window?.time_slot || seat?.time_slot;

    return slot === 'custom_hours';
}

function occupiedDisplayStatus(seat, window = null) {
    return isCustomHoursSlot(seat, window) ? 'occupied_custom' : 'occupied';
}

function seatHasTrialBooking(seat) {
    if (! seat) {
        return false;
    }

    if (occupiedWindows(seat).some((window) => window.type === 'trial')) {
        return true;
    }

    return String(seat.student_type || '') === 'trial';
}

function seatShowsTrialDot(seat) {
    const status = displaySeatStatus(seat);

    if (status === 'available' || status === 'expired' || status === 'on_trial') {
        return false;
    }

    return seatHasTrialBooking(seat);
}

function displaySeatStatus(seat) {
    if (! seat) {
        return '';
    }

    if (seat.status === 'expired') {
        return 'expired';
    }

    const occupied = occupiedWindows(seat);

    // Prefer today's booked windows so custom-hours seats update immediately
    // even when the current clock time falls in a free gap.
    if (occupied.length > 0) {
        const regular = occupied.filter((window) => window.type === 'booked');
        const trials = occupied.filter((window) => window.type === 'trial');

        if (regular.length === 0 && trials.length > 0) {
            return 'on_trial';
        }

        if (seat.status === 'expiring_soon') {
            return 'expiring_soon';
        }

        const anyCustom = regular.some((window) => (window.time_slot || '') === 'custom_hours');
        const anyFullDay = regular.some((window) => (window.time_slot || '') === 'full_day');

        if (anyCustom && ! anyFullDay) {
            return 'occupied_custom';
        }

        if (anyFullDay) {
            return 'occupied';
        }

        if (regular.length > 0) {
            return occupiedDisplayStatus(seat, regular[0]);
        }

        return 'on_trial';
    }

    if (seat.status === 'on_trial') {
        return 'on_trial';
    }

    if (seat.status === 'expiring_soon') {
        return 'expiring_soon';
    }

    if (seat.status === 'occupied') {
        return occupiedDisplayStatus(seat);
    }

    return 'available';
}

function isSeatVacantNow(seat) {
    if (! seat) {
        return false;
    }

    if (seat.status === 'expired') {
        return true;
    }

    // Current clock window can be free even when the seat has custom bookings later today.
    const window = currentWindowForSeat(seat);

    if (! window) {
        return displaySeatStatus(seat) === 'available';
    }

    return window.type === 'free';
}

function matchesStatusFilter(seat, statusFilter) {
    if (! statusFilter) {
        return true;
    }

    return displaySeatStatus(seat) === statusFilter;
}

function visibleOnRegularMap(seat) {
    if (displaySeatStatus(seat) === 'expired' && seat.expired_from_trial) {
        return false;
    }

    return true;
}

function visibleOnTrialMap(seat) {
    if (seat.has_regular_assignment) {
        return false;
    }

    if (displaySeatStatus(seat) === 'expired') {
        return Boolean(seat.expired_from_trial);
    }

    return true;
}

function seatTileClasses(seat) {
    const base = 'relative flex aspect-square min-h-[76px] w-full cursor-pointer flex-col items-center justify-between rounded-xl p-2 text-center shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:scale-[1.03] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-400';
    const status = displaySeatStatus(seat);

    return {
        available: `${base} bg-[#E5E7EB] text-gray-700 hover:bg-gray-300`,
        occupied: `${base} bg-[#16A34A] text-white hover:bg-green-700`,
        occupied_custom: `${base} bg-[#6366F1] text-white hover:bg-indigo-600`,
        expiring_soon: `${base} bg-[#F59E0B] text-amber-950 hover:bg-amber-500`,
        expired: `${base} bg-[#EF4444] text-white hover:bg-red-600`,
        on_trial: `${base} bg-[#06B6D4] text-cyan-950 hover:bg-cyan-500`,
        cancelled: `${base} bg-[#E5E7EB] text-gray-600 opacity-70`,
    }[status] || `${base} bg-white text-gray-700`;
}

function seatStatusLabel(status) {
    return {
        available: 'Vacant',
        occupied: 'Occupied (Full Day)',
        occupied_custom: 'Occupied (Custom Hours)',
        expiring_soon: 'Expiring Soon',
        expired: 'Expired',
        on_trial: 'Trial',
        cancelled: 'Cancelled',
    }[status] || status;
}

function createSeatScheduleMixin() {
    return {
        scheduleOpen: false,
        scheduleLoading: false,
        scheduleSaving: false,
        scheduleSeat: null,
        scheduleDate: new Date().toISOString().slice(0, 10),
        scheduleDateLabel: '',
        scheduleBookings: [],

        async openFullSchedule(seat = null) {
            const target = seat || this.hoverSeat || this.selectedSeat;
            if (! target?.id) {
                return;
            }

            this.cancelSeatHover?.(true);
            this.scheduleSeat = target;
            this.scheduleDate = new Date().toISOString().slice(0, 10);
            this.scheduleOpen = true;
            await this.loadSeatSchedule();
        },

        closeFullSchedule() {
            this.scheduleOpen = false;
            this.scheduleSeat = null;
            this.scheduleBookings = [];
            this.scheduleSaving = false;
        },

        async shiftScheduleDate(days) {
            const current = new Date(`${this.scheduleDate}T00:00:00`);
            current.setDate(current.getDate() + days);
            this.scheduleDate = current.toISOString().slice(0, 10);
            await this.loadSeatSchedule();
        },

        async loadSeatSchedule() {
            if (! this.scheduleSeat?.id) {
                return;
            }

            this.scheduleLoading = true;

            try {
                const response = await window.axios.get(`/seats/${this.scheduleSeat.id}/schedule`, {
                    params: { date: this.scheduleDate },
                });
                this.scheduleBookings = response.data.bookings || [];
                this.scheduleDateLabel = response.data.date_label || this.scheduleDate;
                if (response.data.seat) {
                    this.scheduleSeat = {
                        ...this.scheduleSeat,
                        ...response.data.seat,
                    };
                }
            } catch (e) {
                showToast(e.response?.data?.message || 'Could not load seat schedule.', 'error');
            } finally {
                this.scheduleLoading = false;
            }
        },

        async cancelScheduleBooking(booking) {
            if (! booking?.id) {
                return;
            }

            if (! confirm(`Cancel booking for ${booking.student_name || 'this student'}?`)) {
                return;
            }

            this.scheduleSaving = true;

            try {
                const response = await window.axios.post(`/seat-assignments/${booking.id}/cancel`);
                showToast(response.data.message || 'Booking cancelled.');
                await this.refreshSeats?.();
                await this.loadSeatSchedule();

                if (this.scheduleBookings.length === 0 && this.selectedSeat?.id === this.scheduleSeat?.id) {
                    this.closeDetail?.();
                }
            } catch (e) {
                showToast(e.response?.data?.message || 'Could not cancel booking.', 'error');
            } finally {
                this.scheduleSaving = false;
            }
        },

        async convertScheduleBookingToRegular(booking) {
            if (! booking?.id) {
                return;
            }

            if (! confirm(`Convert ${booking.student_name || 'this trial student'} to a regular student?`)) {
                return;
            }

            this.scheduleSaving = true;

            try {
                const response = await window.axios.post(`/seat-assignments/${booking.id}/convert-to-regular`);
                showToast(response.data.message || 'Converted to regular.');
                await this.refreshSeats?.();
                await this.loadSeatSchedule();
            } catch (e) {
                showToast(e.response?.data?.message || 'Could not convert to regular.', 'error');
            } finally {
                this.scheduleSaving = false;
            }
        },
    };
}

Alpine.data('seatMap', (config) => ({
    halls: config.halls || [],
    seats: config.seats || [],
    students: config.students || [],
    assignedStudents: config.assignedStudents || [],
    branches: config.branches || [],
    defaultBranchId: config.defaultBranchId || null,
    viewingAll: config.viewingAll || false,
    timeSlotOptions: config.timeSlotOptions || [],
    selectedHallId: config.selectedHallId || 'all',
    statusFilter: '',
    storeUrl: config.storeUrl,
    transferUrl: config.transferUrl || '/seat-assignments/transfer',
    availableSeatsUrl: config.availableSeatsUrl || '/seat-assignments/available-seats',
    dataUrl: config.dataUrl || '/seats/data',
    zoom: 100,
    detailOpen: false,
    selectedSeat: null,
    assignMode: false,
    saving: false,
    hoverSeat: null,
    hoverOpen: false,
    hoverTimer: null,
    hoverLeaveTimer: null,
    hoverStyle: { top: 0, left: 0 },
    transferOpen: false,
    transferStep: 'form',
    transferSaving: false,
    transferStudentOpen: false,
    transferStudentQuery: '',
    transferSuccessMessage: '',
    transferForm: {
        student_id: '',
        booking_id: '',
        hall_id: '',
        seat_id: '',
        time_slot: 'full_day',
        custom_start_time: '09:00',
        custom_end_time: '18:00',
    },
    assignForm: {
        student_id: '',
        hall_id: '',
        seat_id: '',
        time_slot: 'full_day',
        custom_start_time: '09:00',
        custom_end_time: '18:00',
        fee_type: 'monthly',
        payment_plan: 'full',
        fee_amount: 0,
        joining_date: new Date().toISOString().slice(0, 10),
        plan_expiry_date: planEndFromStart('monthly', new Date().toISOString().slice(0, 10)),
        membership_mode: 'assigned_seat',
        receive_payment: false,
        amount_received: 0,
        payment_method: 'cash',
        payment_date: new Date().toISOString().slice(0, 10),
        payment_reference: '',
        payment_notes: '',
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
    ...createStudentPickerMixin({ formKey: 'assignForm', showAddNew: true }),
    ...createSeatScheduleMixin(),

    init() {
        window.addEventListener('libspace:seats-updated', (event) => {
            if (event.detail?.seats) {
                this.seats = event.detail.seats.filter((seat) => visibleOnRegularMap(seat));
            }
            if (event.detail?.time_slot_options) {
                this.timeSlotOptions = event.detail.time_slot_options;
            }
        });

        this._statusTick = window.setInterval(() => {
            this.tick += 1;
        }, 60000);

        this.$watch('assignForm.time_slot', () => {
            ensureValidCustomTimes(this.assignForm, this.selectedSeat);
            this.snapAssignTimes();
        });
        this.$watch('assignForm.custom_start_time', () => this.snapAssignTimes());
        this.$watch('assignForm.custom_end_time', () => this.snapAssignTimes());
        this.$watch('assignForm.fee_type', (feeType) => {
            if (feeType === 'one_time') {
                this.assignForm.payment_plan = 'full';
            }
            this.syncAssignPlanExpiry();
        });
        this.$watch('assignForm.joining_date', () => this.syncAssignPlanExpiry());
    },

    syncAssignPlanExpiry() {
        const feeType = this.assignForm.fee_type || 'monthly';
        if (feeType === 'custom') {
            return;
        }

        this.assignForm.plan_expiry_date = planEndFromStart(feeType, this.assignForm.joining_date);
    },

    tick: 0,

    filteredSeats() {
        let seats = this.seats.filter((seat) => visibleOnRegularMap(seat));

        if (this.selectedHallId !== 'all') {
            seats = seats.filter((seat) => String(seat.hall_id) === String(this.selectedHallId));
        }

        seats = seats.filter((seat) => matchesStatusFilter(seat, this.statusFilter));

        return this.sortSeatsByNumber(seats);
    },

    filteredSeatGroups() {
        const seats = this.filteredSeats();
        const groups = [];
        const byHall = new Map();

        seats.forEach((seat) => {
            const hallId = String(seat.hall_id);
            if (! byHall.has(hallId)) {
                byHall.set(hallId, {
                    hall_id: seat.hall_id,
                    hall_name: seat.hall_name || 'Hall',
                    seats: [],
                });
                groups.push(byHall.get(hallId));
            }
            byHall.get(hallId).seats.push(seat);
        });

        groups.sort((a, b) => String(a.hall_name || '').localeCompare(String(b.hall_name || ''), undefined, { sensitivity: 'base' }));

        return groups;
    },

    filteredStudentsForPicker() {
        const assignedIds = new Set((this.assignedStudents || []).map((student) => String(student.id)));
        const query = this.studentPickerQuery.trim().toLowerCase();
        const seatBranchId = this.selectedSeat?.branch_id;

        return (this.students || []).filter((student) => {
            if (assignedIds.has(String(student.id))) {
                return false;
            }

            if (seatBranchId != null && seatBranchId !== '' && String(student.branch_id) !== String(seatBranchId)) {
                return false;
            }

            if (! query) {
                return true;
            }

            return String(student.name || '').toLowerCase().includes(query)
                || String(student.student_code || '').toLowerCase().includes(query)
                || String(student.phone || '').includes(query);
        });
    },

    setHall(hallId) {
        this.selectedHallId = hallId;
    },

    toggleStatusFilter(status) {
        this.statusFilter = status || '';
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
        const times = defaultCustomTimes(seat);
        const hasOccupied = seatHasOccupiedHours(seat);
        this.assignForm = {
            student_id: '',
            hall_id: seat.hall_id,
            seat_id: seat.id,
            time_slot: hasOccupied ? 'custom_hours' : 'full_day',
            custom_start_time: times.start,
            custom_end_time: times.end,
            fee_type: 'monthly',
            payment_plan: 'full',
            fee_amount: 0,
            joining_date: new Date().toISOString().slice(0, 10),
            plan_expiry_date: planEndFromStart('monthly', new Date().toISOString().slice(0, 10)),
            membership_mode: 'assigned_seat',
            receive_payment: false,
            amount_received: 0,
            payment_method: 'cash',
            payment_date: new Date().toISOString().slice(0, 10),
            payment_reference: '',
            payment_notes: '',
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
            this.seats = (response.data.seats || []).filter((seat) => visibleOnRegularMap(seat));
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

    assignableSlotOptions() {
        return assignableTimeSlotOptions(this.timeSlotOptions, this.selectedSeat);
    },

    snapAssignTimes() {
        ensureValidCustomTimes(this.assignForm, this.selectedSeat);
        snapCustomTimesToLibraryHours(this.assignForm, this.selectedSeat);
        snapCustomTimesOffOccupied(this.selectedSeat, this.assignForm);
        snapCustomTimesToLibraryHours(this.assignForm, this.selectedSeat);
    },

    occupiedSchedule(seat) {
        return occupiedWindows(seat);
    },

    canCancel() {
        return Boolean(this.selectedSeat?.booking_id);
    },

    canAddAnotherStudent() {
        return Boolean(
            this.selectedSeat
            && ! this.assignMode
            && firstFreeWindow(this.selectedSeat),
        );
    },

    startAddStudent() {
        if (! this.canAddAnotherStudent()) {
            return;
        }

        const times = defaultCustomTimes(this.selectedSeat);
        this.assignMode = true;
        this.assignForm.student_id = '';
        this.assignForm.hall_id = this.selectedSeat.hall_id;
        this.assignForm.seat_id = this.selectedSeat.id;
        this.assignForm.time_slot = 'custom_hours';
        this.assignForm.custom_start_time = times.start;
        this.assignForm.custom_end_time = times.end;
        this.assignForm.payment_plan = 'full';
        this.assignForm.receive_payment = false;
        this.assignForm.amount_received = 0;
        this.assignForm.payment_method = 'cash';
        this.assignForm.payment_date = new Date().toISOString().slice(0, 10);
        this.assignForm.payment_reference = '';
        this.assignForm.payment_notes = '';
        this.syncAssignPlanExpiry();
    },

    toggleAssignReceivePayment() {
        this.assignForm.receive_payment = ! this.assignForm.receive_payment;
        if (! this.assignForm.receive_payment) {
            this.assignForm.amount_received = 0;
            return;
        }

        if (! this.assignForm.amount_received) {
            this.assignForm.amount_received = this.assignForm.payment_plan === 'full'
                ? Number(this.assignForm.fee_amount || 0)
                : 0;
        }
        if (! this.assignForm.payment_date) {
            this.assignForm.payment_date = new Date().toISOString().slice(0, 10);
        }
    },

    assignPaymentAmount() {
        if (! this.assignForm.receive_payment) {
            return 0;
        }

        return Math.max(0, Number(this.assignForm.amount_received || 0));
    },

    assignRemainingAmount() {
        const total = Math.max(0, Number(this.assignForm.fee_amount || 0));
        return Math.max(0, Math.round((total - this.assignPaymentAmount()) * 100) / 100);
    },

    assignPaymentError() {
        if (! this.assignForm.receive_payment) {
            return '';
        }

        const amount = Number(this.assignForm.amount_received || 0);
        const total = Number(this.assignForm.fee_amount || 0);

        if (! amount || amount <= 0) {
            return 'Payment amount must be greater than 0.';
        }

        if (amount > total + 0.009) {
            return 'Payment amount cannot exceed the remaining fee.';
        }

        if (! this.assignForm.payment_method) {
            return 'Select a payment method.';
        }

        if (! this.assignForm.payment_date) {
            return 'Select a payment date.';
        }

        return '';
    },

    async submitAssign() {
        if (! this.selectedSeat) {
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

        if (! this.assignForm.plan_expiry_date) {
            showToast('Plan expiry date is required.', 'error');
            return;
        }

        if (this.assignForm.plan_expiry_date < this.assignForm.joining_date) {
            showToast('Plan expiry date must be on or after the joining date.', 'error');
            return;
        }

        const paymentError = this.assignPaymentError();
        if (paymentError) {
            showToast(paymentError, 'error');
            return;
        }

        this.saving = true;

        try {
            const payload = { ...this.assignForm };
            payload.fee_amount = Number(payload.fee_amount) || 0;
            if (! this.assignForm.receive_payment || Number(payload.amount_received || 0) <= 0) {
                payload.amount_received = 0;
                delete payload.payment_method;
                delete payload.payment_date;
                delete payload.payment_reference;
                delete payload.payment_notes;
            }

            delete payload.receive_payment;
            delete payload.installment_frequency;
            delete payload.installment_count;
            delete payload.first_due_date;

            const response = await window.axios.post(this.storeUrl, payload);
            await this.refreshSeats();
            await this.refreshAssignedStudents();
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

    openTransferModal() {
        this.transferOpen = true;
        this.transferStep = 'form';
        this.transferSuccessMessage = '';
        this.transferStudentOpen = false;
        this.transferStudentQuery = '';
        this.transferForm = {
            student_id: '',
            booking_id: '',
            hall_id: '',
            seat_id: '',
            time_slot: 'full_day',
            custom_start_time: '09:00',
            custom_end_time: '18:00',
        };
    },

    closeTransferModal() {
        this.transferOpen = false;
        this.transferStep = 'form';
        this.transferSaving = false;
        this.transferStudentOpen = false;
        this.transferStudentQuery = '';
    },

    transferCurrentStudent() {
        return (this.assignedStudents || []).find((student) => String(student.booking_id) === String(this.transferForm.booking_id))
            || null;
    },

    transferSelectedStudentLabel() {
        const student = this.transferCurrentStudent();

        return student ? `${student.student_code} — ${student.name}` : '';
    },

    filteredAssignedStudents() {
        const query = this.transferStudentQuery.trim().toLowerCase();
        const list = this.assignedStudents || [];

        if (! query) {
            return list;
        }

        return list.filter((student) =>
            String(student.name || '').toLowerCase().includes(query)
            || String(student.student_code || '').toLowerCase().includes(query)
            || String(student.phone || '').includes(query),
        );
    },

    selectTransferStudent(student) {
        this.transferForm.student_id = student.id;
        this.transferForm.booking_id = student.booking_id;
        this.transferForm.hall_id = '';
        this.transferForm.seat_id = '';
        this.transferForm.time_slot = 'full_day';
        this.transferForm.custom_start_time = '09:00';
        this.transferForm.custom_end_time = '18:00';
        this.transferStudentQuery = '';
        this.transferStudentOpen = false;
    },

    onTransferStudentChange(bookingId) {
        const student = (this.assignedStudents || []).find((item) => String(item.booking_id) === String(bookingId));

        if (! student) {
            this.transferForm.student_id = '';
            this.transferForm.booking_id = '';
            this.transferForm.hall_id = '';
            this.transferForm.seat_id = '';
            this.transferForm.time_slot = 'full_day';
            return;
        }

        this.selectTransferStudent(student);
    },

    transferCurrentSummary() {
        const student = this.transferCurrentStudent();
        if (! student) {
            return '';
        }

        return `Seat ${student.seat_number} · ${student.hall_name} · ${student.time_slot_label}`;
    },

    transferNewSummary() {
        const hall = (this.halls || []).find((item) => String(item.id) === String(this.transferForm.hall_id));
        const seat = this.selectedTransferSeat();
        const slotOption = (this.timeSlotOptions || []).find((item) => item.value === this.transferForm.time_slot);
        let slotLabel = slotOption?.label || this.transferForm.time_slot;

        if (this.transferForm.time_slot === 'custom_hours') {
            slotLabel = `Custom ${this.transferForm.custom_start_time} – ${this.transferForm.custom_end_time}`;
        }

        return `Seat ${seat?.seat_number || '—'} · ${hall?.name || '—'} · ${slotLabel}`;
    },

    sortSeatsByNumber(seats) {
        return [...(seats || [])].sort((a, b) => {
            const left = Number(a.seat_number);
            const right = Number(b.seat_number);

            if (Number.isFinite(left) && Number.isFinite(right) && left !== right) {
                return left - right;
            }

            return String(a.seat_number || '').localeCompare(String(b.seat_number || ''), undefined, { numeric: true });
        });
    },

    transferSeatsForHall() {
        const current = this.transferCurrentStudent();
        if (! this.transferForm.hall_id) {
            return [];
        }

        return this.sortSeatsByNumber(
            (this.seats || []).filter((seat) => {
                if (String(seat.hall_id) !== String(this.transferForm.hall_id)) {
                    return false;
                }

                if (current && String(seat.id) === String(current.seat_id)) {
                    return false;
                }

                return true;
            }),
        );
    },

    selectedTransferSeat() {
        return this.transferSeatsForHall().find((seat) => String(seat.id) === String(this.transferForm.seat_id))
            || (this.seats || []).find((seat) => String(seat.id) === String(this.transferForm.seat_id))
            || null;
    },

    transferSeatFullyBooked(seat) {
        const free = (seat?.today_windows || []).filter((window) => window.type === 'free');

        return free.length === 0 && occupiedWindows(seat).length > 0;
    },

    transferSeatOptionLabel(seat) {
        if (this.transferSeatFullyBooked(seat)) {
            return `${seat.seat_number} — Full Day Booked`;
        }

        if (seatHasOccupiedHours(seat)) {
            return `${seat.seat_number} — Custom hours booked`;
        }

        return `${seat.seat_number} — Vacant`;
    },

    onTransferHallChange() {
        this.transferForm.seat_id = '';
        this.transferForm.time_slot = 'full_day';
        this.transferForm.custom_start_time = '09:00';
        this.transferForm.custom_end_time = '18:00';
    },

    onTransferSeatChange() {
        const seat = this.selectedTransferSeat();
        const times = defaultCustomTimes(seat);

        if (seatHasOccupiedHours(seat)) {
            this.transferForm.time_slot = 'custom_hours';
            this.transferForm.custom_start_time = times.start;
            this.transferForm.custom_end_time = times.end;
        } else {
            this.transferForm.time_slot = 'full_day';
            this.transferForm.custom_start_time = times.start;
            this.transferForm.custom_end_time = times.end;
        }
    },

    onTransferTimeChange() {
        if (this.transferForm.time_slot === 'custom_hours') {
            this.snapTransferTimes();
        }
    },

    transferSlotOptions() {
        return assignableTimeSlotOptions(this.timeSlotOptions, this.selectedTransferSeat());
    },

    snapTransferTimes() {
        ensureValidCustomTimes(this.transferForm, this.selectedTransferSeat());
        snapCustomTimesToLibraryHours(this.transferForm, this.selectedTransferSeat());
        snapCustomTimesOffOccupied(this.selectedTransferSeat(), this.transferForm);
        snapCustomTimesToLibraryHours(this.transferForm, this.selectedTransferSeat());
    },

    transferTimeError() {
        if (this.transferForm.time_slot !== 'custom_hours') {
            return '';
        }

        if (! this.transferForm.custom_start_time || ! this.transferForm.custom_end_time) {
            return 'Start and end time are required.';
        }

        if (this.transferForm.custom_start_time >= this.transferForm.custom_end_time) {
            return 'End time must be after start time.';
        }

        return '';
    },

    transferConflictError() {
        if (this.transferTimeError()) {
            return this.transferTimeError();
        }

        return assignmentWindowConflict(this.selectedTransferSeat(), this.transferForm) || '';
    },

    canPreviewTransfer() {
        const seat = this.selectedTransferSeat();

        return Boolean(
            this.transferForm.booking_id
            && this.transferForm.hall_id
            && this.transferForm.seat_id
            && seat
            && ! this.transferSeatFullyBooked(seat)
            && ! this.transferTimeError()
            && ! this.transferConflictError(),
        );
    },

    goTransferConfirm() {
        if (! this.canPreviewTransfer()) {
            showToast('Select student, hall, seat, and an available time slot.', 'error');
            return;
        }

        this.transferStep = 'confirm';
    },

    async submitTransfer() {
        if (! this.canPreviewTransfer()) {
            return;
        }

        this.transferSaving = true;

        try {
            const payload = {
                booking_id: this.transferForm.booking_id,
                hall_id: Number(this.transferForm.hall_id),
                seat_id: Number(this.transferForm.seat_id),
                time_slot: this.transferForm.time_slot,
            };

            if (this.transferForm.time_slot === 'custom_hours') {
                payload.custom_start_time = this.transferForm.custom_start_time;
                payload.custom_end_time = this.transferForm.custom_end_time;
            }

            const response = await window.axios.post(this.transferUrl, payload);
            this.transferSuccessMessage = response.data.message || 'Transfer successful!';
            this.transferStep = 'success';
            await this.refreshSeats();
            await this.refreshAssignedStudents();
            showToast(this.transferSuccessMessage);
        } catch (e) {
            showToast(
                e.response?.data?.message
                || Object.values(e.response?.data?.errors || {})[0]?.[0]
                || 'Could not transfer seat.',
                'error',
            );
            this.transferStep = 'form';
        } finally {
            this.transferSaving = false;
        }
    },

    async refreshAssignedStudents() {
        try {
            const response = await window.axios.get(this.dataUrl);
            if (Array.isArray(response.data.assigned_students)) {
                this.assignedStudents = response.data.assigned_students;
            }
        } catch (e) {
            // Keep existing assigned student list if refresh fails.
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

    showsTrialDot(seat) {
        void this.tick;

        return seatShowsTrialDot(seat);
    },

    seatHasTrial(seat) {
        return seatHasTrialBooking(seat);
    },

    startSeatHover(seat, event) {
        this.cancelSeatHover(true);
    
        if (!seat || displaySeatStatus(seat) === 'available' || this.detailOpen || this.transferOpen) {
            return;
        }
    
        const rect = event.currentTarget.getBoundingClientRect();
    
        this.hoverSeat = seat;
        this.hoverOpen = true;
        this.positionSeatHover(rect);
    },

    keepSeatHover() {
        if (this.hoverLeaveTimer) {
            window.clearTimeout(this.hoverLeaveTimer);
            this.hoverLeaveTimer = null;
        }
    },

    cancelSeatHover(immediate = false) {
        if (this.hoverTimer) {
            window.clearTimeout(this.hoverTimer);
            this.hoverTimer = null;
        }

        if (this.hoverLeaveTimer) {
            window.clearTimeout(this.hoverLeaveTimer);
            this.hoverLeaveTimer = null;
        }

        if (immediate) {
            this.hoverOpen = false;
            this.hoverSeat = null;
            return;
        }

        this.hoverLeaveTimer = window.setTimeout(() => {
            this.hoverOpen = false;
            this.hoverSeat = null;
            this.hoverLeaveTimer = null;
        }, 200);
    },

    positionSeatHover(rect) {
        const width = 320;
        const approxHeight = 380;
        let left = rect.right + 12;
        let top = rect.top;

        if (left + width > window.innerWidth - 12) {
            left = Math.max(12, rect.left - width - 12);
        }

        if (top + approxHeight > window.innerHeight - 12) {
            top = Math.max(12, window.innerHeight - approxHeight - 12);
        }

        this.hoverStyle = { top, left };
    },

    hoverScheduleDateLabel() {
        const now = new Date();

        return now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    },

    hoverSeatTypeLabel(seat = null) {
        const target = seat || this.hoverSeat;
        if (! target) {
            return '—';
        }

        const occupied = occupiedWindows(target);
        if (occupied.some((window) => (window.time_slot || '') === 'full_day') || target.time_slot === 'full_day') {
            return 'Full Day';
        }

        if (occupied.some((window) => (window.time_slot || '') === 'custom_hours') || target.time_slot === 'custom_hours') {
            return 'Custom Hours';
        }

        return target.time_slot_label || '—';
    },

    badgeClasses(seat) {
        const status = displaySeatStatus(seat);
        if (status === 'occupied' || status === 'occupied_custom') {
            return 'rounded bg-black/25 px-1 py-0.5 text-[9px] normal-case text-white';
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
    branches: config.branches || [],
    defaultBranchId: config.defaultBranchId || null,
    viewingAll: config.viewingAll || false,
    timeSlotOptions: config.timeSlotOptions || [],
    selectedHallId: config.selectedHallId || 'all',
    statusFilter: '',
    storeUrl: config.storeUrl,
    dataUrl: config.dataUrl || '/trial-seats/data',
    availableSeatsUrl: config.availableSeatsUrl,
    zoom: 100,
    detailOpen: false,
    selectedSeat: null,
    assignMode: false,
    saving: false,
    tick: 0,
    hoverSeat: null,
    hoverOpen: false,
    hoverTimer: null,
    hoverLeaveTimer: null,
    hoverStyle: { top: 0, left: 0 },
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
    ...createStudentPickerMixin({ formKey: 'assignForm', showAddNew: true }),
    ...createSeatScheduleMixin(),

    init() {
        window.addEventListener('libspace:seats-updated', (event) => {
            if (event.detail?.seats) {
                this.seats = event.detail.seats.filter((seat) => visibleOnTrialMap(seat));
            }
            if (event.detail?.time_slot_options) {
                this.timeSlotOptions = event.detail.time_slot_options;
            }
            if (event.detail?.students) {
                this.students = event.detail.students;
            }
        });

        this._statusTick = window.setInterval(() => {
            this.tick += 1;
        }, 60000);

        this.$watch('assignForm.time_slot', () => {
            ensureValidCustomTimes(this.assignForm, this.selectedSeat);
            this.snapAssignTimes();
        });
        this.$watch('assignForm.custom_start_time', () => this.snapAssignTimes());
        this.$watch('assignForm.custom_end_time', () => this.snapAssignTimes());
    },

    filteredSeats() {
        let seats = this.seats.filter((seat) => visibleOnTrialMap(seat));

        if (this.selectedHallId !== 'all') {
            seats = seats.filter((seat) => String(seat.hall_id) === String(this.selectedHallId));
        }

        seats = seats.filter((seat) => matchesStatusFilter(seat, this.statusFilter));

        return this.sortSeatsByNumber(seats);
    },

    filteredSeatGroups() {
        const seats = this.filteredSeats();
        const groups = [];
        const byHall = new Map();

        seats.forEach((seat) => {
            const hallId = String(seat.hall_id);
            if (! byHall.has(hallId)) {
                byHall.set(hallId, {
                    hall_id: seat.hall_id,
                    hall_name: seat.hall_name || 'Hall',
                    seats: [],
                });
                groups.push(byHall.get(hallId));
            }
            byHall.get(hallId).seats.push(seat);
        });

        groups.sort((a, b) => String(a.hall_name || '').localeCompare(String(b.hall_name || ''), undefined, { sensitivity: 'base' }));

        return groups;
    },

    sortSeatsByNumber(seats) {
        return [...(seats || [])].sort((a, b) => {
            const left = Number(a.seat_number);
            const right = Number(b.seat_number);

            if (Number.isFinite(left) && Number.isFinite(right) && left !== right) {
                return left - right;
            }

            return String(a.seat_number || '').localeCompare(String(b.seat_number || ''), undefined, { numeric: true });
        });
    },

    setHall(hallId) {
        this.selectedHallId = hallId;
    },

    toggleStatusFilter(status) {
        this.statusFilter = status || '';
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
        const times = defaultCustomTimes(seat);
        const hasOccupied = seatHasOccupiedHours(seat);
        this.assignForm = {
            student_id: '',
            hall_id: seat.hall_id,
            seat_id: seat.id,
            time_slot: hasOccupied ? 'custom_hours' : 'full_day',
            custom_start_time: times.start,
            custom_end_time: times.end,
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

    canAddAnotherStudent() {
        return Boolean(
            this.selectedSeat
            && ! this.assignMode
            && firstFreeWindow(this.selectedSeat),
        );
    },

    startAddStudent() {
        if (! this.canAddAnotherStudent()) {
            return;
        }

        const times = defaultCustomTimes(this.selectedSeat);
        this.assignMode = true;
        this.assignForm.student_id = '';
        this.assignForm.hall_id = this.selectedSeat.hall_id;
        this.assignForm.seat_id = this.selectedSeat.id;
        this.assignForm.time_slot = 'custom_hours';
        this.assignForm.custom_start_time = times.start;
        this.assignForm.custom_end_time = times.end;
        this.assignForm.trial_start = new Date().toISOString().slice(0, 10);
        this.assignForm.trial_days = 1;
        this.assignForm.fee_amount = 0;
    },

    async refreshSeats() {
        try {
            const response = await window.axios.get(this.dataUrl);
            this.seats = (response.data.seats || []).filter((seat) => visibleOnTrialMap(seat));
            if (response.data.halls) {
                this.halls = response.data.halls;
            }
            if (response.data.time_slot_options) {
                this.timeSlotOptions = response.data.time_slot_options;
            }
            if (response.data.students) {
                this.students = response.data.students;
            }
        } catch (e) {
            // Trial map will refresh on next poll or page reload.
        }
    },

    filteredStudentsForPicker() {
        const query = this.studentPickerQuery.trim().toLowerCase();
        const seatBranchId = this.selectedSeat?.branch_id;

        return (this.students || []).filter((student) => {
            if (String(student.student_type || '') !== 'trial') {
                return false;
            }

            if (seatBranchId != null && seatBranchId !== '' && String(student.branch_id) !== String(seatBranchId)) {
                return false;
            }

            if (! query) {
                return true;
            }

            return String(student.name || '').toLowerCase().includes(query)
                || String(student.student_code || '').toLowerCase().includes(query)
                || String(student.phone || '').includes(query);
        });
    },

    async submitAssign() {
        if (! this.selectedSeat || ! this.assignMode) {
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
            payload.fee_amount = Number(payload.fee_amount) || 0;
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

    assignableSlotOptions() {
        return assignableTimeSlotOptions(this.timeSlotOptions, this.selectedSeat);
    },

    snapAssignTimes() {
        ensureValidCustomTimes(this.assignForm, this.selectedSeat);
        snapCustomTimesToLibraryHours(this.assignForm, this.selectedSeat);
        snapCustomTimesOffOccupied(this.selectedSeat, this.assignForm);
        snapCustomTimesToLibraryHours(this.assignForm, this.selectedSeat);
    },

    occupiedSchedule(seat) {
        return occupiedWindows(seat);
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

    showsTrialDot(seat) {
        void this.tick;

        return seatShowsTrialDot(seat);
    },

    seatHasTrial(seat) {
        return seatHasTrialBooking(seat);
    },

    startSeatHover(seat, event) {
        this.cancelSeatHover(true);

        if (! seat || displaySeatStatus(seat) === 'available' || this.detailOpen) {
            return;
        }

        const rect = event.currentTarget.getBoundingClientRect();

        this.hoverSeat = seat;
        this.hoverOpen = true;
        this.positionSeatHover(rect);
    },

    keepSeatHover() {
        if (this.hoverLeaveTimer) {
            window.clearTimeout(this.hoverLeaveTimer);
            this.hoverLeaveTimer = null;
        }
    },

    cancelSeatHover(immediate = false) {
        if (this.hoverTimer) {
            window.clearTimeout(this.hoverTimer);
            this.hoverTimer = null;
        }

        if (this.hoverLeaveTimer) {
            window.clearTimeout(this.hoverLeaveTimer);
            this.hoverLeaveTimer = null;
        }

        if (immediate) {
            this.hoverOpen = false;
            this.hoverSeat = null;
            return;
        }

        this.hoverLeaveTimer = window.setTimeout(() => {
            this.hoverOpen = false;
            this.hoverSeat = null;
            this.hoverLeaveTimer = null;
        }, 200);
    },

    positionSeatHover(rect) {
        const width = 320;
        const approxHeight = 380;
        let left = rect.right + 12;
        let top = rect.top;

        if (left + width > window.innerWidth - 12) {
            left = Math.max(12, rect.left - width - 12);
        }

        if (top + approxHeight > window.innerHeight - 12) {
            top = Math.max(12, window.innerHeight - approxHeight - 12);
        }

        this.hoverStyle = { top, left };
    },

    hoverScheduleDateLabel() {
        const now = new Date();

        return now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    },

    hoverSeatTypeLabel(seat = null) {
        const target = seat || this.hoverSeat;
        if (! target) {
            return '—';
        }

        const occupied = occupiedWindows(target);
        if (occupied.some((window) => (window.time_slot || '') === 'full_day') || target.time_slot === 'full_day') {
            return 'Full Day';
        }

        if (occupied.some((window) => (window.time_slot || '') === 'custom_hours') || target.time_slot === 'custom_hours') {
            return 'Custom Hours';
        }

        return target.time_slot_label || '—';
    },

    badgeClasses(seat) {
        const status = displaySeatStatus(seat);
        if (status === 'occupied' || status === 'occupied_custom') {
            return 'rounded bg-black/25 px-1 py-0.5 text-[9px] normal-case text-white';
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
    formErrors: {},
    form: { id: null, branch_id: null, name: '', seat_capacity: 10, min_seat_capacity: 1, description: '', continue_seat_numbering: false, continue_from_hall_id: null },
    branches: config.branches || [],
    planSnapshot: config.planSnapshot || null,
    defaultBranchId: config.defaultBranchId || null,
    viewingAll: config.viewingAll || false,
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
    bulkDeleteUrl: config.bulkDeleteUrl,

    init() {
        this.initDataTable();
    },

    canAddHall() {
        const remaining = this.planSnapshot?.remaining?.halls;
        return remaining === null || remaining === undefined || remaining > 0;
    },

    maxSeatCapacityForForm() {
        const limits = this.planSnapshot?.limits;
        const usage = this.planSnapshot?.usage;
        if (! limits || limits.max_seats === null || limits.max_seats === undefined) {
            return 500;
        }

        let currentHallCapacity = 0;
        if (this.formMode === 'edit' && this.form.id) {
            const existing = this.rows.find((row) => row.id === this.form.id);
            currentHallCapacity = Number(existing?.seat_capacity || 0);
        }

        const otherSeats = Number(usage?.seats || 0) - currentHallCapacity;
        const remaining = Math.max(0, Number(limits.max_seats) - otherSeats);

        return Math.max(this.form.min_seat_capacity || 1, Math.min(500, remaining || 1));
    },

    resolvedSeatNumberStart() {
        if (! this.form.continue_seat_numbering) {
            return 1;
        }

        const sourceHall = this.hallsForSelectedBranch().find(
            (hall) => Number(hall.id) === Number(this.form.continue_from_hall_id),
        );

        return Number(sourceHall?.max_seat_number || 0) + 1;
    },

    seatNumberPreview() {
        if (! this.form.continue_seat_numbering) {
            return '';
        }

        const capacity = Number(this.form.seat_capacity);
        if (! Number.isInteger(capacity) || capacity < 1) {
            return '';
        }

        const start = this.resolvedSeatNumberStart();
        const end = start + capacity - 1;

        return start === end ? `${start}` : `${start}–${end}`;
    },

    hallsForSelectedBranch() {
        const branchId = Number(this.form.branch_id);
        if (! branchId) {
            return [];
        }

        return this.rows
            .filter((hall) => Number(hall.branch_id) === branchId)
            .sort((left, right) => String(left.name || '').localeCompare(String(right.name || '')));
    },

    onContinueSeatNumberingToggle() {
        if (! this.form.continue_seat_numbering) {
            this.form.continue_from_hall_id = null;
            return;
        }

        const halls = this.hallsForSelectedBranch();
        if (! halls.length) {
            this.form.continue_seat_numbering = false;
            return;
        }

        const preferredHall = halls.reduce((best, hall) => (
            Number(hall.max_seat_number || 0) >= Number(best.max_seat_number || 0) ? hall : best
        ), halls[0]);

        this.form.continue_from_hall_id = preferredHall?.id || null;
    },

    onBranchChangeForHallForm() {
        this.form.continue_seat_numbering = false;
        this.form.continue_from_hall_id = null;
    },

    openCreate() {
        if (! this.canAddHall()) {
            showToast('Your plan has reached the maximum number of halls.', 'error');
            return;
        }

        this.formMode = 'create';
        this.form = {
            id: null,
            branch_id: this.defaultBranchId || this.branches[0]?.id || null,
            name: '',
            seat_capacity: 10,
            description: '',
            continue_seat_numbering: false,
            continue_from_hall_id: null,
        };
        this.formOpen = true;
        this.error = '';
        this.formErrors = {};
    },

    openEdit(hall) {
        this.formMode = 'edit';
        this.form = {
            id: hall.id,
            branch_id: hall.branch_id,
            name: hall.name,
            seat_capacity: hall.seat_capacity,
            min_seat_capacity: hall.min_seat_capacity ?? 1,
            description: hall.description || '',
        };
        this.formOpen = true;
        this.error = '';
        this.formErrors = {};
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

    validateHallForm() {
        const errors = {};
        const name = String(this.form.name || '').trim();

        if (! this.form.branch_id) {
            errors.branch_id = 'Select a branch.';
        }

        if (! name) {
            errors.name = 'Hall name is required.';
        } else if (name.length < 2) {
            errors.name = 'Hall name must be at least 2 characters.';
        } else if (/^\d+$/.test(name)) {
            errors.name = 'Hall name cannot contain only numbers.';
        }

        const capacity = Number(this.form.seat_capacity);
        const maxCapacity = this.maxSeatCapacityForForm();
        if (! Number.isInteger(capacity) || capacity < 1) {
            errors.seat_capacity = 'Seat capacity must be at least 1.';
        } else if (capacity > maxCapacity) {
            errors.seat_capacity = `Seat capacity cannot exceed ${maxCapacity} on your current plan.`;
        } else if (capacity > 500) {
            errors.seat_capacity = 'Seat capacity cannot exceed 500.';
        } else if (this.formMode === 'edit' && this.form.min_seat_capacity && capacity < this.form.min_seat_capacity) {
            errors.seat_capacity = `Capacity cannot be reduced below ${this.form.min_seat_capacity} while students are assigned.`;
        }

        if (this.formMode === 'create' && this.form.continue_seat_numbering) {
            if (! this.form.continue_from_hall_id) {
                errors.continue_from_hall_id = 'Select which hall to continue seat numbering from.';
            } else if (! this.hallsForSelectedBranch().some((hall) => Number(hall.id) === Number(this.form.continue_from_hall_id))) {
                errors.continue_from_hall_id = 'Selected hall must belong to the same branch.';
            }
        }

        this.formErrors = errors;

        return Object.keys(errors).length === 0;
    },

    async submitForm() {
        if (! this.validateHallForm()) {
            showToast(Object.values(this.formErrors)[0], 'error');
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

            if (this.formMode === 'create' && this.form.continue_seat_numbering) {
                payload.continue_seat_numbering = true;
                payload.continue_from_hall_id = Number(this.form.continue_from_hall_id);
            }

            const response = this.formMode === 'create'
                ? await window.axios.post(this.storeUrl, payload)
                : await window.axios.patch(`/halls/${this.form.id}`, payload);

            const hall = response.data.hall;

            if (this.formMode === 'create') {
                if (String(hall.branch_id) === String(this.defaultBranchId) || this.rows.some((row) => String(row.branch_id) === String(hall.branch_id))) {
                    this.rows.unshift(hall);
                }
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
    planSnapshot: config.planSnapshot || null,
    storeUrl: config.storeUrl,
    createOpen: false,
    editOpen: false,
    viewOpen: false,
    passwordResetOpen: false,
    passwordResetBranch: null,
    passwordResetForm: { password: '' },
    hallViewOpen: false,
    hallEditOpen: false,
    saving: false,
    flash: '',
    error: '',
    viewBranch: null,
    hallView: null,
    createFormErrors: {},
    editFormErrors: {},
    createForm: {
        name: '',
        contact_person: '',
        phone: '',
        email: '',
        password: '',
        address: '',
    },
    editForm: {
        id: null,
        name: '',
        contact_person: '',
        phone: '',
        email: '',
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

    canAddBranch() {
        const remaining = this.planSnapshot?.remaining?.branches;
        return remaining === null || remaining === undefined || remaining > 0;
    },

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
            password: '',
            address: '',
        };
    },

    validateBranchForm(form, { requireName = true, requirePassword = false } = {}) {
        const errors = {};
        const name = String(form.name || '').trim();

        if (requireName && ! name) {
            errors.name = 'Branch name is required.';
        } else if (name && name.length < 2) {
            errors.name = 'Branch name must be at least 2 characters.';
        }

        const phoneError = validateIndianPhone(form.phone);
        if (phoneError) errors.phone = phoneError;

        const emailError = validateEmail(form.email, { required: true });
        if (emailError) errors.email = emailError;

        if (requirePassword && String(form.password || '').length < 8) {
            errors.password = 'Password must be at least 8 characters.';
        }

        if (! requirePassword && form.password && String(form.password).length < 8) {
            errors.password = 'Password must be at least 8 characters.';
        }

        return errors;
    },

    openCreate() {
        if (! this.canAddBranch()) {
            showToast('Your plan has reached the maximum number of branches.', 'error');
            return;
        }

        this.resetCreateForm();
        this.createFormErrors = {};
        this.createOpen = true;
        this.error = '';
    },

    openEdit(branch) {
        this.editForm = {
            id: branch.id,
            name: branch.name || '',
            contact_person: branch.contact_person || '',
            phone: branch.phone || '',
            email: branch.email || branch.login_email || '',
            password: '',
            address: branch.address || '',
        };
        this.editOpen = true;
        this.editFormErrors = {};
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
        const errors = this.validateBranchForm(this.createForm, { requirePassword: true });
        this.createFormErrors = errors;
        if (Object.keys(errors).length) {
            showToast(Object.values(errors)[0], 'error');
            return;
        }

        this.saving = true;
        try {
            const response = await window.axios.post(this.storeUrl, {
                ...this.createForm,
                phone: String(this.createForm.phone || '').trim() || null,
                email: String(this.createForm.email || '').trim(),
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
        const errors = this.validateBranchForm(this.editForm);
        this.editFormErrors = errors;
        if (Object.keys(errors).length) {
            showToast(Object.values(errors)[0], 'error');
            return;
        }

        this.saving = true;
        try {
            const payload = {
                ...this.editForm,
                phone: String(this.editForm.phone || '').trim() || null,
                email: String(this.editForm.email || '').trim(),
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

    generatePassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        let value = '';
        for (let i = 0; i < 12; i++) {
            value += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        return value;
    },

    openPasswordReset(branch) {
        if (! branch?.id) {
            return;
        }

        this.passwordResetBranch = branch;
        this.passwordResetForm = { password: this.generatePassword() };
        this.passwordResetOpen = true;
    },

    async submitPasswordReset() {
        const password = String(this.passwordResetForm.password || '');
        if (password.length < 8) {
            showToast('Password must be at least 8 characters.', 'error');
            return;
        }

        this.saving = true;
        try {
            const response = await window.axios.post(`/branch/${this.passwordResetBranch.id}/reset-password`, { password });
            if (this.viewBranch) {
                this.viewBranch.temporary_password = response.data.password;
                this.viewBranch.email = response.data.login_email;
                this.viewBranch.login_email = response.data.login_email;
            }
            this.passwordResetOpen = false;
            showToast(response.data.message);
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        } finally {
            this.saving = false;
        }
    },

    async resetBranchPassword(branch) {
        this.openPasswordReset(branch);
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
            min_seat_capacity: hall.min_seat_capacity ?? 1,
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
    branches: config.branches || [],
    defaultBranchId: config.defaultBranchId || null,
    viewingAll: config.viewingAll || false,
    searchKeys: ['student_code', 'name', 'phone', 'email', 'status', 'student_type', 'student_type_label', 'branch_name'],
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
    bulkDeleteUrl: config.bulkDeleteUrl,
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

        const emailError = validateEmail(this.editForm.email, { required: true });
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
    branches: config.branches || [],
    defaultBranchId: config.defaultBranchId || null,
    viewingAll: config.viewingAll || false,
    searchKeys: ['name', 'phone', 'email', 'status', 'student_code', 'message', 'branch_name'],
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
    bulkDeleteUrl: config.bulkDeleteUrl,

    init() {
        this.initDataTable();
    },

    openCreate() {
        this.formMode = 'create';
        this.form = { id: null, name: '', phone: '', email: '', message: '', status: 'new', branch_id: this.defaultBranchId || this.branches[0]?.id || '' };
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
        payment_plan: 'full',
        fee_amount: 0,
        joining_date: new Date().toISOString().slice(0, 10),
        plan_expiry_date: '',
        membership_mode: 'assigned_seat',
    },

    init() {
        this.initDataTable();
        this.$watch('form.time_slot', () => snapCustomTimesOffOccupied(this.selectedAvailableSeat(), this.form));
        this.$watch('form.custom_start_time', () => snapCustomTimesOffOccupied(this.selectedAvailableSeat(), this.form));
        this.$watch('form.custom_end_time', () => snapCustomTimesOffOccupied(this.selectedAvailableSeat(), this.form));
        this.$watch('form.seat_id', () => {
            const seat = this.selectedAvailableSeat();
            if (seatHasOccupiedHours(seat) && this.form.time_slot === 'full_day') {
                this.form.time_slot = 'custom_hours';
                const times = defaultCustomTimes(seat);
                this.form.custom_start_time = times.start;
                this.form.custom_end_time = times.end;
            }
        });
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

    selectedAvailableSeat() {
        return this.availableSeats.find((item) => String(item.id) === String(this.form.seat_id)) || null;
    },

    assignableSlotOptions() {
        return assignableTimeSlotOptions(this.timeSlotOptions, this.selectedAvailableSeat());
    },

    occupiedSchedule(seat) {
        return occupiedWindows(seat);
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

function formatFeeDateLabel(iso) {
    if (! iso) {
        return '';
    }

    const date = new Date(`${iso}T00:00:00`);
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function toIsoLocal(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${d}`;
}

function addMonthsKeepDay(iso, months) {
    const [year, month, day] = iso.split('-').map(Number);
    const date = new Date(year, month - 1 + months, day);

    return toIsoLocal(date);
}

function planEndFromStart(feeType, startIso) {
    if (! startIso) {
        return '';
    }

    if (feeType === 'one_time') {
        return startIso;
    }

    if (feeType === 'yearly' || feeType === 'membership') {
        const next = addMonthsKeepDay(startIso, 12);
        const date = new Date(`${next}T00:00:00`);
        date.setDate(date.getDate() - 1);

        return toIsoLocal(date);
    }

    if (feeType === 'monthly') {
        const next = addMonthsKeepDay(startIso, 1);
        const date = new Date(`${next}T00:00:00`);
        date.setDate(date.getDate() - 1);

        return toIsoLocal(date);
    }

    return '';
}

function frequencyIntervalMonths(frequency) {
    if (frequency === 'quarterly') return 3;
    if (frequency === 'half_yearly') return 6;
    if (frequency === 'yearly') return 12;
    if (frequency === 'weekly') return 0;

    return 1;
}

function splitInstallmentAmounts(total, count) {
    const cents = Math.round(Number(total || 0) * 100);
    const parts = Math.max(1, Math.min(12, Number(count) || 1));
    const base = Math.floor(cents / parts);
    const amounts = [];

    for (let i = 0; i < parts; i += 1) {
        amounts.push(i === parts - 1 ? (cents - base * (parts - 1)) / 100 : base / 100);
    }

    return amounts.map((amount) => amount.toFixed(2));
}

function addInstallmentDue(iso, frequency, index) {
    const date = new Date(`${iso}T00:00:00`);
    if (frequency === 'weekly') {
        date.setDate(date.getDate() + (7 * index));
    } else {
        date.setMonth(date.getMonth() + (frequencyIntervalMonths(frequency) * index));
    }

    return toIsoLocal(date);
}

function suggestedInstallmentCount(firstDueIso, planEndIso, frequency) {
    if (! firstDueIso || ! planEndIso) {
        return 4;
    }

    const start = new Date(`${firstDueIso}T00:00:00`);
    const end = new Date(`${planEndIso}T00:00:00`);

    if (frequency === 'weekly') {
        const weeks = Math.max(1, Math.ceil((end - start) / (7 * 24 * 60 * 60 * 1000)) + 1);

        return Math.max(2, Math.min(12, weeks));
    }

    const months = Math.max(1, ((end.getFullYear() - start.getFullYear()) * 12) + (end.getMonth() - start.getMonth()) + 1);
    const interval = Math.max(1, frequencyIntervalMonths(frequency));

    return Math.max(2, Math.min(12, Math.ceil(months / interval)));
}

Alpine.data('feeTable', (config) => ({
    rows: config.rows || [],
    students: config.students || [],
    halls: config.halls || [],
    timeSlotOptions: config.timeSlotOptions || [],
    availableSeats: [],
    storeUrl: config.storeUrl,
    availableSeatsUrl: config.availableSeatsUrl,
    formOpen: false,
    formMode: 'create',
    editingId: null,
    viewOpen: false,
    viewRow: null,
    receiveOpen: false,
    receiveRow: null,
    receiveSaving: false,
    receiveForm: {
        fee_amount: '',
        amount: '',
        note: '',
        payment_method: 'cash',
        payment_date: '',
    },
    saving: false,
    searchKeys: ['student_code', 'student_name', 'student_phone', 'hall_name', 'seat_number', 'time_slot', 'fee_type', 'fee_type_label', 'fee_amount', 'payment_status', 'plan_status', 'payment_plan', 'plan_expiry_date'],
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
        { label: 'Payment Status', key: 'payment_status_label' },
        { label: 'Plan Status', key: 'plan_status_label' },
        { label: 'Payment Plan', key: 'payment_plan_label' },
    ],
    ...createDataTableMixin(),
    bulkDeleteUrl: config.bulkDeleteUrl,
    planStatusFilter: '',
    paymentStatusFilter: '',
    dateFrom: '',
    dateTo: '',
    ...createStudentPickerMixin({ formKey: 'form', showAddNew: false }),
    assignmentLocked: false,
    assignmentSummary: '',
    form: {
        student_id: '',
        hall_id: '',
        seat_id: '',
        time_slot: 'full_day',
        custom_start_time: '09:00',
        custom_end_time: '18:00',
        fee_type: 'monthly',
        fee_amount: '',
        joining_date: new Date().toISOString().slice(0, 10),
        plan_expiry_date: '',
        membership_mode: 'assigned_seat',
        payment_plan: 'full',
        installment_count: 4,
        installment_frequency: 'quarterly',
        first_due_date: new Date().toISOString().slice(0, 10),
        existing_booking_id: null,
        time_slot_label: '',
        hall_name: '',
        seat_number: '',
    },

    extraFilter(row) {
        if (this.planStatusFilter === 'expiring_or_expired') {
            if (row.plan_status !== 'expiring_soon' && row.plan_status !== 'expired') {
                return false;
            }
        } else if (this.planStatusFilter && row.plan_status !== this.planStatusFilter) {
            return false;
        }

        if (this.paymentStatusFilter && row.payment_status !== this.paymentStatusFilter) {
            return false;
        }

        const dateValue = row.joining_date_iso || '';

        if (this.dateFrom && dateValue && dateValue < this.dateFrom) {
            return false;
        }

        if (this.dateTo && dateValue && dateValue > this.dateTo) {
            return false;
        }

        return true;
    },

    init() {
        this.initDataTable();
        this.syncPlanEnd();
        this.$watch('planStatusFilter', () => { this.page = 1; });
        this.$watch('paymentStatusFilter', () => { this.page = 1; });
        this.$watch('dateFrom', () => { this.page = 1; });
        this.$watch('dateTo', () => { this.page = 1; });
    },

    emptyForm() {
        const today = new Date().toISOString().slice(0, 10);

        return {
            student_id: '',
            hall_id: '',
            seat_id: '',
            time_slot: 'full_day',
            custom_start_time: '09:00',
            custom_end_time: '18:00',
            fee_type: 'monthly',
            fee_amount: '',
            joining_date: today,
            plan_expiry_date: planEndFromStart('monthly', today),
            membership_mode: 'assigned_seat',
            payment_plan: 'full',
            installment_count: 4,
            installment_frequency: 'quarterly',
            first_due_date: today,
            existing_booking_id: null,
            time_slot_label: '',
            hall_name: '',
            seat_number: '',
        };
    },

    lockedFieldClass() {
        return 'mt-1 block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-600 shadow-sm';
    },

    showsEndDate() {
        return this.form.fee_type !== 'one_time'
            && ! (this.form.payment_plan === 'installments' && this.form.installment_frequency === 'custom');
    },

    endDateEditable() {
        return this.form.fee_type === 'custom';
    },

    endDateRequired() {
        return this.form.fee_type === 'custom'
            || (this.form.payment_plan === 'installments' && this.form.installment_frequency === 'custom');
    },

    isFrequencyDisabled(frequency) {
        return this.form.fee_type === 'monthly' && frequency === 'monthly';
    },

    defaultFrequencyForFeeType(feeType) {
        if (feeType === 'monthly') {
            return 'quarterly';
        }

        return 'monthly';
    },

    onFeeOptionsChanged() {
        if (this.form.fee_type === 'one_time') {
            this.form.payment_plan = 'full';
            this.form.installment_frequency = this.defaultFrequencyForFeeType(this.form.fee_type);
            this.form.installment_count = 4;
        }

        if (this.form.payment_plan !== 'installments') {
            this.form.installment_frequency = this.defaultFrequencyForFeeType(this.form.fee_type);
        } else if (this.isFrequencyDisabled(this.form.installment_frequency)) {
            this.form.installment_frequency = this.defaultFrequencyForFeeType(this.form.fee_type);
        }

        this.syncPlanEnd();

        if (this.form.payment_plan === 'installments' && this.form.installment_frequency !== 'custom') {
            const end = this.computedExpiry() || this.form.plan_expiry_date;
            this.form.installment_count = Math.max(2, suggestedInstallmentCount(
                this.form.first_due_date || this.form.joining_date,
                end,
                this.form.installment_frequency,
            ));
        }
    },

    selectStudentFromPicker(student) {
        this.setStudentSelectId(student.id);
        this.closeStudentPicker();
        this.applyStudentAssignment(student);
    },

    applyStudentAssignment(student) {
        const assignment = student?.current_assignment;

        if (! assignment) {
            const studentId = this.form.student_id;
            this.form = { ...this.emptyForm(), student_id: studentId };
            this.assignmentLocked = false;
            this.assignmentSummary = '';
            this.availableSeats = [];
            return;
        }

        this.assignmentLocked = true;
        this.assignmentSummary = [
            assignment.hall_name,
            `Seat ${assignment.seat_number}`,
            assignment.time_slot_label || '',
        ].filter(Boolean).join(' • ');
        const feeType = ['monthly', 'yearly', 'membership', 'one_time', 'custom'].includes(assignment.fee_type)
            ? assignment.fee_type
            : 'monthly';
        const paymentPlan = feeType === 'one_time'
            ? 'full'
            : (assignment.payment_plan || 'full');
        let frequency = assignment.installment_frequency || this.defaultFrequencyForFeeType(feeType);
        if (feeType === 'monthly' && frequency === 'monthly') {
            frequency = 'quarterly';
        }

        this.form = {
            ...this.emptyForm(),
            student_id: student.id,
            hall_id: assignment.hall_id,
            seat_id: assignment.seat_id,
            time_slot: assignment.time_slot || 'full_day',
            custom_start_time: assignment.custom_start_time || '09:00',
            custom_end_time: assignment.custom_end_time || '18:00',
            fee_type: feeType,
            fee_amount: Number(assignment.fee_amount || 0) || '',
            joining_date: assignment.joining_date || this.form.joining_date,
            plan_expiry_date: assignment.plan_expiry_date || planEndFromStart(feeType, assignment.joining_date),
            membership_mode: assignment.membership_mode || 'assigned_seat',
            payment_plan: paymentPlan,
            installment_count: assignment.installment_count || 4,
            installment_frequency: frequency,
            first_due_date: assignment.first_due_date || assignment.joining_date || '',
            existing_booking_id: assignment.id,
            time_slot_label: assignment.time_slot_label || '',
            hall_name: assignment.hall_name || '',
            seat_number: assignment.seat_number || '',
        };
        this.onFeeOptionsChanged();
        this.availableSeats = assignment.seat_id
            ? [{ id: assignment.seat_id, seat_number: assignment.seat_number }]
            : [];
    },

    openCreate() {
        this.formMode = 'create';
        this.editingId = null;
        this.form = this.emptyForm();
        this.assignmentLocked = false;
        this.assignmentSummary = '';
        this.availableSeats = [];
        this.formOpen = true;
        this.onFeeOptionsChanged();
    },

    openEdit(row) {
        if (! row) {
            return;
        }

        this.formMode = 'edit';
        this.editingId = row.id;
        let frequency = row.installment_frequency || this.defaultFrequencyForFeeType(row.fee_type || 'monthly');
        if ((row.fee_type || 'monthly') === 'monthly' && frequency === 'monthly') {
            frequency = 'quarterly';
        }

        this.form = {
            ...this.emptyForm(),
            fee_type: row.fee_type || 'monthly',
            fee_amount: Number(row.fee_amount || 0),
            joining_date: row.joining_date_iso || '',
            plan_expiry_date: row.plan_expiry_date_iso || '',
            membership_mode: row.membership_mode || 'assigned_seat',
            payment_plan: row.fee_type === 'one_time' ? 'full' : (row.payment_plan || (row.is_installment ? 'installments' : 'full')),
            installment_count: row.installment_count || 4,
            installment_frequency: frequency,
            first_due_date: row.first_due_date || row.joining_date_iso || '',
        };
        this.assignmentLocked = true;
        this.assignmentSummary = [
            row.hall_name,
            `Seat ${row.seat_number}`,
            row.time_slot_label || '',
        ].filter(Boolean).join(' • ');
        this.viewRow = row;
        this.formOpen = true;
        this.onFeeOptionsChanged();
    },

    editSummary() {
        const row = this.viewRow;
        if (! row) {
            return '';
        }

        return [
            row.student_name,
            row.hall_name,
            `Seat ${row.seat_number}`,
            row.time_slot_label || '',
        ].filter(Boolean).join(' • ');
    },

    openView(row) {
        this.viewRow = row;
        this.viewOpen = true;
    },

    closeView() {
        this.viewOpen = false;
    },

    async loadSeats() {
        if (this.assignmentLocked || this.formMode !== 'create' || ! this.form.hall_id || ! this.form.joining_date) {
            return;
        }

        const expiry = this.computedExpiry() || this.form.joining_date;

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

    selectedAvailableSeat() {
        return this.availableSeats.find((item) => String(item.id) === String(this.form.seat_id)) || null;
    },

    assignableSlotOptions() {
        return assignableTimeSlotOptions(this.timeSlotOptions, this.selectedAvailableSeat());
    },

    computedExpiry() {
        if (this.form.fee_type === 'custom' || (this.form.payment_plan === 'installments' && this.form.installment_frequency === 'custom')) {
            return this.form.plan_expiry_date;
        }

        return planEndFromStart(this.form.fee_type, this.form.joining_date);
    },

    syncPlanEnd() {
        if (this.form.fee_type === 'custom' || (this.form.payment_plan === 'installments' && this.form.installment_frequency === 'custom')) {
            if (! this.form.first_due_date) {
                this.form.first_due_date = this.form.joining_date;
            }

            return;
        }

        this.form.plan_expiry_date = this.computedExpiry();

        if (! this.form.first_due_date) {
            this.form.first_due_date = this.form.joining_date;
        }
    },

    installmentPreview() {
        if (this.form.payment_plan !== 'installments' || this.form.installment_frequency === 'custom') {
            return [];
        }

        const count = Math.max(2, Math.min(12, Number(this.form.installment_count) || 2));
        const firstDue = this.form.first_due_date || this.form.joining_date;
        const amounts = splitInstallmentAmounts(this.form.fee_amount, count);

        return amounts.map((amount, index) => {
            const due = addInstallmentDue(firstDue, this.form.installment_frequency || 'monthly', index);

            return {
                number: index + 1,
                amount,
                label: formatFeeDateLabel(due),
            };
        });
    },

    feePayload() {
        const payload = {
            fee_type: this.form.fee_type,
            fee_amount: this.form.fee_amount,
            joining_date: this.form.joining_date,
            plan_expiry_date: this.computedExpiry(),
            membership_mode: this.form.membership_mode,
            payment_plan: this.form.fee_type === 'one_time' ? 'full' : this.form.payment_plan,
        };

        if (payload.payment_plan === 'installments') {
            payload.installment_frequency = this.form.installment_frequency || this.defaultFrequencyForFeeType(this.form.fee_type);
            payload.first_due_date = this.form.first_due_date || this.form.joining_date;

            if (payload.installment_frequency !== 'custom') {
                payload.installment_count = Math.max(2, this.form.installment_count || suggestedInstallmentCount(
                    payload.first_due_date,
                    payload.plan_expiry_date,
                    payload.installment_frequency,
                ));
            }
        }

        return payload;
    },

    async submitForm() {
        this.saving = true;
        try {
            if (this.formMode === 'edit') {
                const response = await window.axios.patch(`/fees/${this.editingId}`, this.feePayload());
                this.rows = this.rows.map((row) => row.id === this.editingId ? response.data.row : row);
                showToast(response.data.message);
            } else if (this.form.existing_booking_id) {
                const response = await window.axios.patch(`/fees/${this.form.existing_booking_id}`, this.feePayload());
                const updated = response.data.row;
                const exists = this.rows.some((row) => row.id === updated.id);
                this.rows = exists
                    ? this.rows.map((row) => row.id === updated.id ? updated : row)
                    : [updated, ...this.rows];
                showToast(response.data.message);
            } else {
                const payload = { ...this.form, ...this.feePayload() };
                delete payload.existing_booking_id;
                delete payload.time_slot_label;
                delete payload.hall_name;
                delete payload.seat_number;
                if (! payload.plan_expiry_date) delete payload.plan_expiry_date;
                const response = await window.axios.post(this.storeUrl, payload);
                this.rows.unshift(response.data.row);
                showToast(response.data.message);
            }
            this.formOpen = false;
        } catch (e) {
            showToast(e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Could not save the fee.', 'error');
        } finally {
            this.saving = false;
        }
    },

    async payInstallment(item) {
        if (! this.viewRow || ! item?.id) {
            return;
        }

        try {
            const response = await window.axios.post(`/fees/${this.viewRow.id}/installments/${item.id}/pay`);
            this.rows = this.rows.map((row) => row.id === this.viewRow.id ? response.data.row : row);
            this.viewRow = response.data.row;
            showToast(response.data.message);
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not mark that installment as paid.', 'error');
        }
    },

    openReceivePayment(row) {
        if (! row) {
            return;
        }

        this.receiveRow = row;
        const fee = Number(row.fee_amount || 0);
        const due = this.feeAmountDue(row);
        const next = (row.installments || []).find((item) => ! item.paid);
        this.receiveForm = {
            fee_amount: fee > 0 ? fee : '',
            amount: fee > 0 ? (next ? Number(next.amount) : due || '') : '',
            note: '',
            payment_method: 'cash',
            payment_date: new Date().toISOString().slice(0, 10),
        };
        this.receiveOpen = true;
    },

    feeAmountDue(row) {
        if (! row) {
            return 0;
        }

        if (row.amount_due != null && row.amount_due !== '') {
            return Math.max(0, Number(row.amount_due));
        }

        return Math.max(0, Number(row.fee_amount || 0) - Number(row.amount_paid || 0));
    },

    receiveMaxAmount() {
        const fee = Number(this.receiveForm?.fee_amount || this.receiveRow?.fee_amount || 0);
        const paid = Number(this.receiveRow?.amount_paid || 0);
        return Math.max(0, Math.round((fee - paid) * 100) / 100);
    },

    receiveRemainingPreview() {
        const max = this.receiveMaxAmount();
        if (Number(this.receiveRow?.fee_amount || 0) <= 0 && ! this.receiveForm?.fee_amount) {
            return '—';
        }

        return max;
    },

    closeReceivePayment() {
        this.receiveOpen = false;
        this.receiveRow = null;
        this.receiveForm = { fee_amount: '', amount: '', note: '', payment_method: 'cash', payment_date: '' };
    },

    nextUnpaidInstallmentLabel() {
        const row = this.receiveRow;
        if (! row || row.is_flexible_installment || ! row.is_installment) {
            return '';
        }

        const next = (row.installments || []).find((item) => ! item.paid);
        if (! next) {
            return '';
        }

        return `Next installment: #${next.number} due ${next.due_date} — ₹${next.amount}`;
    },

    async submitReceivePayment() {
        if (! this.receiveRow?.id) {
            return;
        }

        this.receiveSaving = true;
        try {
            const payload = {
                amount: this.receiveForm.amount,
                note: this.receiveForm.note || null,
                payment_method: this.receiveForm.payment_method || 'cash',
                payment_date: this.receiveForm.payment_date || null,
            };

            if (Number(this.receiveRow.fee_amount || 0) <= 0) {
                payload.fee_amount = this.receiveForm.fee_amount;
            }

            const response = await window.axios.post(`/fees/${this.receiveRow.id}/payments`, payload);
            this.rows = this.rows.map((row) => row.id === this.receiveRow.id ? response.data.row : row);
            if (this.viewRow?.id === this.receiveRow.id) {
                this.viewRow = response.data.row;
            }
            showToast(response.data.message);
            this.closeReceivePayment();
        } catch (e) {
            showToast(e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Could not record the payment.', 'error');
        } finally {
            this.receiveSaving = false;
        }
    },

    async markFeePaid() {
        if (! this.viewRow?.id) {
            return;
        }

        try {
            const response = await window.axios.post(`/fees/${this.viewRow.id}/pay`);
            this.rows = this.rows.map((row) => row.id === this.viewRow.id ? response.data.row : row);
            this.viewRow = response.data.row;
            showToast(response.data.message);
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not mark this fee as paid.', 'error');
        }
    },
}));

Alpine.data('notificationTable', (config) => ({
    rows: config.rows || [],
    markReadUrl: config.markReadUrl,
    markAllUrl: config.markAllUrl,
    viewOpen: false,
    viewRow: null,
    searchKeys: ['title', 'message', 'type', 'type_label', 'date'],
    exportFileName: 'notifications',
    exportColumns: [
        { label: 'Title', key: 'title' },
        { label: 'Message', key: 'message' },
        { label: 'Type', key: 'type_label' },
        { label: 'Date', key: 'date' },
    ],
    ...createDataTableMixin(),
    bulkDeleteUrl: config.bulkDeleteUrl,

    init() {
        this.initDataTable();
    },

    unreadCount() {
        return (this.rows || []).filter((row) => row.unread).length;
    },

    async markKeys(keys) {
        if (! keys.length) {
            return;
        }

        try {
            await window.axios.post(this.markReadUrl, { keys });
            this.rows = this.rows.map((row) => keys.includes(row.id) ? { ...row, unread: false } : row);
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not update notifications.', 'error');
        }
    },

    async markAllRead() {
        try {
            await window.axios.post(this.markAllUrl);
            this.rows = this.rows.map((row) => ({ ...row, unread: false }));
            showToast('All notifications marked as read.');
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not mark notifications as read.', 'error');
        }
    },

    async openView(row) {
        this.viewRow = row;
        this.viewOpen = true;
        if (row.unread) {
            await this.markKeys([row.id]);
            this.viewRow = { ...row, unread: false };
        }
    },

    closeView() {
        this.viewOpen = false;
        this.viewRow = null;
    },
}));

Alpine.data('notificationBell', (config) => ({
    open: false,
    alerts: config.alerts || [],
    unreadCount: config.unreadCount || 0,
    markReadUrl: config.markReadUrl,
    markAllUrl: config.markAllUrl,
    allUrl: config.allUrl,

    async markAllRead() {
        try {
            await window.axios.post(this.markAllUrl);
            this.alerts = this.alerts.map((alert) => ({ ...alert, unread: false }));
            this.unreadCount = 0;
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not mark notifications as read.', 'error');
        }
    },

    async openAlert(alert) {
        if (alert.unread) {
            try {
                await window.axios.post(this.markReadUrl, { keys: [alert.id] });
            } catch (e) {
                // still navigate
            }
        }

        window.location.href = alert.url || this.allUrl;
    },
}));

Alpine.data('activityLogTable', (config) => ({
    rows: config.rows || [],
    isPlatformAdmin: config.isPlatformAdmin || false,
    actorFilter: '',
    viewOpen: false,
    viewLoading: false,
    viewLog: null,
    searchKeys: ['description', 'user_name', 'branch_name', 'created_at', 'actor_label', 'actor_type', 'change_summary'],
    exportFileName: 'activity-logs',
    exportColumns: [
        { label: 'When', key: 'created_at' },
        { label: 'Who', key: 'user_name' },
        { label: 'What happened', key: 'description' },
        { label: 'Library', key: 'branch_name' },
    ],
    ...createDataTableMixin(),
    bulkDeleteUrl: config.bulkDeleteUrl,

    extraFilter(row) {
        if (! this.actorFilter) {
            return true;
        }

        return row.actor_type === this.actorFilter;
    },

    init() {
        this.initDataTable();
        this.$watch('actorFilter', () => { this.page = 1; });
    },

    setActorFilter(value) {
        this.actorFilter = value || '';
    },

    async openView(row) {
        this.viewOpen = true;
        this.viewLoading = true;
        this.viewLog = { ...row };

        try {
            const response = await window.axios.get(`/activity-logs/${row.id}`);
            this.viewLog = response.data;
        } catch (e) {
            showToast('Could not load activity details.', 'error');
            this.closeView();
        } finally {
            this.viewLoading = false;
        }
    },

    closeView() {
        this.viewOpen = false;
        this.viewLoading = false;
        this.viewLog = null;
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
            const payload = this.branchId === 'all'
                ? { branch_id: 'all' }
                : { branch_id: Number(this.branchId) };
            await window.axios.post(this.switchUrl, payload);
            window.location.reload();
        } catch (e) {
            window.alert(e.response?.data?.message || 'Could not switch branch.');
        }
    },
}));

Alpine.data('settingsPage', (config) => ({
    settings: config.settings,
    platformSettings: config.platformSettings || {},
    planSnapshot: config.planSnapshot || {},
    planForm: {
        plan_tier: config.planForm?.plan_tier || 'starter',
        max_seats_override: config.planForm?.max_seats_override ?? '',
        max_halls_override: config.planForm?.max_halls_override ?? '',
        max_branches_override: config.planForm?.max_branches_override ?? '',
    },
    planTiers: config.planTiers || ['starter', 'pro', 'custom'],
    isPlatformAdmin: config.isPlatformAdmin || false,
    isDeveloperAdmin: config.isDeveloperAdmin || false,
    viewingAll: config.viewingAll || false,
    form: {
        display_name: config.settings?.display_name || '',
        expiry_reminder_days: config.settings?.expiry_reminder_days || 10,
        library_open_time: config.settings?.library_open_time || '09:00',
        library_close_time: config.settings?.library_close_time || '18:00',
        is_open_24_hours: config.settings?.is_open_24_hours || false,
    },
    platformForm: {
        student_code_prefix: config.platformSettings?.student_code_prefix || '',
        student_code_padding: config.platformSettings?.student_code_padding || 3,
        display_name: config.platformSettings?.display_name || '',
        logo_with_text: null,
        simple_logo: null,
        favicon: null,
    },
    saving: false,
    savingPlan: false,
    clearingCache: false,
    flash: '',
    error: '',
    updateUrl: config.updateUrl,
    platformUpdateUrl: config.platformUpdateUrl,
    platformPlanUpdateUrl: config.platformPlanUpdateUrl,
    clearCacheUrl: config.clearCacheUrl,
    licenseServerEnabled: config.licenseServerEnabled || false,
    deploymentsUrl: config.deploymentsUrl,
    timezone: config.timezone,

    init() {},

    formatClock(value) {
        const raw = String(value || '').slice(0, 5);
        const [hourPart, minutePart] = raw.split(':');
        let hour = Number(hourPart);
        const minute = Number(minutePart || 0);
        if (! Number.isFinite(hour)) {
            return raw || '—';
        }
        const period = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12;
        if (hour === 0) {
            hour = 12;
        }
        return `${hour}:${String(minute).padStart(2, '0')} ${period}`;
    },

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
        return data;
    },

    validatePlatformForm() {
        if (! this.isPlatformAdmin) {
            return null;
        }

        if (! String(this.platformForm.student_code_prefix || '').trim()) {
            return 'Student ID letters are required.';
        }

        if (!/^[A-Za-z0-9_-]+$/.test(String(this.platformForm.student_code_prefix || '').trim())) {
            return 'Use only letters and numbers at the start of the student ID.';
        }

        const padding = Number(this.platformForm.student_code_padding);
        if (! padding || padding < 1 || padding > 6) {
            return 'Choose between 1 and 6 digits.';
        }

        return null;
    },

    async clearApplicationCache() {
        if (! this.isDeveloperAdmin || ! this.clearCacheUrl) {
            return;
        }

        this.clearingCache = true;
        try {
            const response = await window.axios.post(this.clearCacheUrl);
            showToast(response.data.message || 'Application cache cleared.');
        } catch (e) {
            showToast(extractAxiosError(e), 'error');
        } finally {
            this.clearingCache = false;
        }
    },

    async savePlanSettings() {
        if (! this.isDeveloperAdmin) {
            return;
        }

        this.savingPlan = true;
        try {
            const payload = {
                plan_tier: this.planForm.plan_tier,
                max_seats_override: this.planForm.max_seats_override || null,
                max_halls_override: this.planForm.max_halls_override || null,
                max_branches_override: this.planForm.max_branches_override || null,
            };
            const response = await window.axios.patch(this.platformPlanUpdateUrl, payload);
            this.planSnapshot = response.data.plan;
            this.planForm.plan_tier = this.planSnapshot.limits.plan_tier;
            showToast(response.data.message || 'Plan settings saved.');
        } catch (e) {
            showToast(e.response?.data?.message || 'Could not save plan settings.', 'error');
        } finally {
            this.savingPlan = false;
        }
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
                const platformData = new FormData();
                platformData.append('student_code_prefix', String(this.platformForm.student_code_prefix || '').trim().toUpperCase());
                platformData.append('student_code_padding', String(this.platformForm.student_code_padding || 3));
                if (this.platformForm.display_name) {
                    platformData.append('display_name', this.platformForm.display_name);
                }
                if (this.platformForm.logo_with_text) platformData.append('logo_with_text', this.platformForm.logo_with_text);
                if (this.platformForm.simple_logo) platformData.append('simple_logo', this.platformForm.simple_logo);
                if (this.platformForm.favicon) platformData.append('favicon', this.platformForm.favicon);

                const platformResponse = await window.axios.post(`${this.platformUpdateUrl}?_method=PATCH`, platformData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                this.platformSettings = platformResponse.data.platform_settings;
                this.platformForm.student_code_prefix = this.platformSettings.student_code_prefix;
                this.platformForm.student_code_padding = this.platformSettings.student_code_padding;
                this.platformForm.display_name = this.platformSettings.display_name || '';
                this.platformForm.logo_with_text = null;
                this.platformForm.simple_logo = null;
                this.platformForm.favicon = null;
            }

            if (! this.viewingAll) {
            const response = await window.axios.post(`${this.updateUrl}?_method=PATCH`, this.buildFormData(), {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            this.settings = response.data.settings;
            this.form.display_name = this.settings.display_name || '';
            this.form.expiry_reminder_days = this.settings.expiry_reminder_days;
            this.form.library_open_time = this.settings.library_open_time || '09:00';
            this.form.library_close_time = this.settings.library_close_time || '18:00';
            this.form.is_open_24_hours = Boolean(this.settings.is_open_24_hours);
            }

            showToast(this.isPlatformAdmin ? 'Settings saved.' : (this.viewingAll ? 'Global settings saved.' : 'Settings saved.'));
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
