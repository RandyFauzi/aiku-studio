import Alpine from 'alpinejs';

const normalize = value => value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();
const labels = new Map();
const requests = new Map();
const initialLabel = title => title;

async function getOptions(url) {
    const cached = requests.get(url);
    if (cached && Date.now() - cached.at < 30000) return cached.promise;
    const promise = fetch(url, { headers: { Accept: 'application/json' } }).then(async response => {
        if (!response.ok) {
            if ([401, 419].includes(response.status)) throw new Error('Sesi berakhir. Masuk kembali untuk memuat task.');
            if (response.status === 404) throw new Error('Layanan task belum tersedia. Minta admin memperbarui aplikasi.');
            throw new Error('Daftar task belum dimuat. Coba lagi.');
        }
        return response.json();
    }).catch(error => { requests.delete(url); throw error; });
    requests.set(url, { promise, at: Date.now() });
    return promise;
}

Alpine.data('phoneTaskPicker', config => ({
    expanded: true, search: '', results: [], busy: false, taskError: '', notice: '', requestId: 0,
    canEditLabels: false, editingTitle: '', editLabel: '', editKeywords: '', labelSaving: false, labelError: '', labelVersion: 0,
    init() { this.lookup(); },
    labelFor(title) { return title; },
    get selectedTasks() { return config.read(); },
    get previousTasks() { return config.copy(); },
    isTaskSelected(title) {
        if (!title) return false;
        const key = normalize(title);
        return (this.selectedTasks || []).some(t => normalize(t) === key);
    },
    get choices() {
        const choices = new Map(this.results.map(task => [normalize(task.title), task]));
        if (!this.search.trim()) for (const title of this.selectedTasks) if (!choices.has(normalize(title))) choices.set(normalize(title), { title, label: title, source: 'selected' });
        return this.taskError ? [] : [...choices.values()];
    },
    get canAdd() { return this.search.trim().length > 0 && !this.busy && !this.taskError && !this.choices.some(task => normalize(task.title) === normalize(this.search)); },
    async toggleOpen() { this.expanded = !this.expanded; if (this.expanded) { this.$nextTick(() => this.$refs.search.focus()); await this.lookup(); } },
    async lookup() {
        const id = ++this.requestId; this.busy = true; this.taskError = '';
        try {
            const params = new URLSearchParams({ search: this.search.slice(0, 100) });
            this.selectedTasks.forEach((title, index) => params.append(`selected[${index}]`, title));
            const data = await getOptions('/api/report-tasks?' + params);
            if (id === this.requestId) {
                this.results = (data.tasks || []).map(task => ({ ...task, label: task.title }));
                this.results.forEach(task => labels.set(normalize(task.title), task.title));
                this.labelVersion++;
                this.canEditLabels = false;
            }
        } catch (error) { if (id === this.requestId) this.taskError = error.message; }
        finally { if (id === this.requestId) this.busy = false; }
    },
    toggleTask(title) {
        const key = normalize(title);
        const exists = this.selectedTasks.some(task => normalize(task) === key);
        if (!exists && this.selectedTasks.length >= 30) { this.notice = 'Maksimal 30 task per HP.'; return false; }
        config.write(exists ? this.selectedTasks.filter(task => normalize(task) !== key) : [...this.selectedTasks, title]);
        this.notice = '';
        return true;
    },
    addTask() {
        if (!this.canAdd) return;
        const title = this.search.trim().replace(/\s+/g, ' ');
        if (this.toggleTask(title) === false) return;
        this.results.unshift({ title, label: title, source: 'report' }); this.search = '';
        this.notice = 'Task baru akan tersimpan bersama laporan.';
    },
    copyPrevious() {
        config.write([...this.previousTasks]); this.notice = 'Task disalin. Kamu bisa mengubah pilihan untuk HP ini.';
    },
}));
