<div class="mt-3 border-t border-slate-200 pt-3" @task-labels-updated.window="lookup()" x-data="phoneTaskPicker({ read: () => {{ $taskMap }}[key] || [], write: tasks => {{ $taskMap }}[key] = tasks, copy: () => {{ $taskMap }}[{{ $phoneList }}[{{ $phoneList }}.indexOf(key) - 1]] || [] })">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm font-bold text-slate-800">Task yang dikerjakan</span>
        <button type="button" x-show="previousTasks.length" @click="copyPrevious()" title="Salin task dari HP sebelumnya" class="min-h-11 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-green-900">Task sama</button>
    </div>
    <p class="mt-1 break-words text-sm text-slate-600" x-text="selectedTasks.length ? selectedTasks.map(title => labelFor(title)).join(' · ') : 'Pilih minimal satu task untuk HP ini.'"></p>
    <button type="button" @click="toggleOpen()" :aria-expanded="expanded" class="mt-2 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-left text-sm font-semibold" x-text="expanded ? 'Selesai memilih' : (selectedTasks.length ? 'Ubah task (' + selectedTasks.length + ')' : 'Pilih task')"></button>
    <div x-show="expanded" @keydown.escape.stop="expanded = false" class="mt-2 rounded-xl border border-slate-200 bg-white p-3 space-y-2">
        <input x-ref="search" type="search" x-model="search" @input="++requestId; busy = true" @input.debounce.300ms="lookup()" maxlength="200" :aria-label="'Cari task HP ' + label" placeholder="Cari: bungkus kado, kardus, rak buku…" class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3 text-base">
        <p class="text-xs text-slate-500">Centang semua task yang dikerjakan. Bisa mencari dengan beberapa kata atau istilah sehari-hari.</p>
        <p x-show="busy" role="status" class="text-xs text-slate-500">Mencari task…</p>
        <p x-show="taskError" role="alert" x-text="taskError" class="text-xs text-red-700"></p>
        <button type="button" x-show="taskError" @click="lookup()" class="min-h-11 text-sm font-semibold">Coba lagi</button>
        <div class="max-h-60 overflow-y-auto overscroll-contain divide-y divide-slate-100">
            <template x-for="task in choices" :key="task.title">
                <div class="flex items-start gap-2 py-1">
                <label class="flex min-h-11 min-w-0 flex-1 cursor-pointer items-center gap-3 py-2 text-sm">
                    <input type="checkbox" :checked="selectedTasks.some(title => title.trim().replace(/\s+/g, ' ').toLowerCase() === task.title.trim().replace(/\s+/g, ' ').toLowerCase())" @change="toggleTask(task.title)" class="h-5 w-5 shrink-0 rounded accent-green-800">
                    <span class="min-w-0 break-words"><span x-text="task.label || labelFor(task.title)"></span><span x-show="task.source === 'report'" class="block text-xs text-slate-500">Ditambahkan tim · belum ditemukan di Atlas</span><span x-show="canEditLabels && task.label_status === 'suggested'" class="block text-xs text-amber-700">Padanan otomatis · dapat disesuaikan</span><span x-show="task.source === 'atlas' && task.label_status === 'original'" class="block text-xs text-amber-700">Padanan Indonesia belum tersedia</span></span>
                </label>
                <button type="button" x-show="canEditLabels" @click="editTranslation(task)" :aria-label="'Ubah nama Indonesia task ' + (task.label || labelFor(task.title))" title="Ubah nama Indonesia / kata kunci" class="flex min-h-11 w-11 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m16 4 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z"/></svg></button>
                </div>
            </template>
        </div>
        <p x-show="!busy && !taskError && !choices.length" class="text-xs text-slate-500">Task belum ditemukan. Ketik judul task untuk menambahkannya.</p>
        <button type="button" x-show="canAdd" @click="addTask()" class="min-h-11 w-full break-words rounded-lg bg-green-50 px-3 py-2 text-left text-sm font-bold text-green-900" x-text="'+ Tambah “' + search.trim() + '”'"></button>
        <div x-show="editingTitle && canEditLabels" class="rounded-xl bg-slate-50 p-3 space-y-2">
            <p class="break-words text-xs text-slate-500" x-text="'Judul asli recording: ' + editingTitle"></p>
            <label class="block text-sm">Nama Indonesia<input type="text" x-model="editLabel" maxlength="200" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base"></label>
            <label class="block text-sm">Kata kunci tambahan<input type="text" x-model="editKeywords" maxlength="500" placeholder="Pisahkan dengan koma" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base"></label>
            <p x-show="labelError" x-text="labelError" role="alert" class="text-xs text-red-700"></p>
            <div class="flex flex-wrap gap-2"><button type="button" @click="saveTranslation()" :disabled="labelSaving" class="min-h-11 rounded-lg bg-green-900 px-3 text-sm font-bold text-white disabled:opacity-50">Simpan nama</button><button type="button" @click="editingTitle = ''" :disabled="labelSaving" class="min-h-11 px-3 text-sm">Batal</button></div>
        </div>
    </div>
    <p x-show="notice" x-text="notice" role="status" class="mt-2 text-xs text-green-800"></p>
</div>
