import Alpine from 'alpinejs';

const normalize = value => value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();

Alpine.data('phoneTaskPicker', config => ({
    expanded: false, search: '', results: [], busy: false, taskError: '', notice: '', requestId: 0,
    get selectedTasks() { return config.read(); },
    get previousTasks() { return config.copy(); },
    get choices() {
        const choices = new Map(this.results.map(task => [normalize(task.title), task]));
        for (const title of this.selectedTasks) if (!choices.has(normalize(title))) choices.set(normalize(title), { title, source: 'selected' });
        return [...choices.values()].filter(task => normalize(task.title).includes(normalize(this.search)));
    },
    get canAdd() { return this.search.trim().length > 0 && !this.busy && !this.taskError && !this.choices.some(task => normalize(task.title) === normalize(this.search)); },
    async toggleOpen() { this.expanded = !this.expanded; if (this.expanded) { this.$nextTick(() => this.$refs.search.focus()); await this.lookup(); } },
    async lookup() {
        const id = ++this.requestId; this.busy = true; this.taskError = '';
        try {
            const response = await fetch('/api/report-tasks?search=' + encodeURIComponent(this.search.slice(0, 100)), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Daftar task belum dimuat. Coba lagi.');
            const data = await response.json();
            if (id === this.requestId) this.results = data.tasks || [];
        } catch (error) { if (id === this.requestId) this.taskError = error.message; }
        finally { if (id === this.requestId) this.busy = false; }
    },
    toggleTask(title) {
        const key = normalize(title);
        const exists = this.selectedTasks.some(task => normalize(task) === key);
        if (!exists && this.selectedTasks.length >= 30) { this.notice = 'Maksimal 30 task per HP.'; return; }
        config.write(exists ? this.selectedTasks.filter(task => normalize(task) !== key) : [...this.selectedTasks, title]);
        this.notice = '';
    },
    addTask() {
        if (!this.canAdd) return;
        const title = this.search.trim().replace(/\s+/g, ' ');
        this.toggleTask(title); this.results.unshift({ title, source: 'report' }); this.search = '';
        this.notice = 'Task baru akan tersimpan bersama laporan.';
    },
    copyPrevious() {
        config.write([...this.previousTasks]); this.notice = 'Task disalin. Kamu bisa mengubah pilihan untuk HP ini.';
    },
}));
