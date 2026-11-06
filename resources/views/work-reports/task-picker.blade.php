<div class="mt-3 border-t border-slate-200 pt-3" x-data="phoneTaskPicker({ read: () => {{ $taskMap }}[key] || [], write: tasks => {{ $taskMap }}[key] = tasks, copy: () => {{ $taskMap }}[{{ $phoneList }}[{{ $phoneList }}.indexOf(key) - 1]] || [] })">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm font-bold text-slate-800">Task yang dikerjakan</span>
        <button type="button" x-show="previousTasks.length" @click="copyPrevious()" title="Salin task dari HP sebelumnya" class="min-h-11 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-green-900">Task sama</button>
    </div>
    <p class="mt-1 break-words text-sm text-slate-600" x-text="selectedTasks.length ? selectedTasks.join(' · ') : 'Pilih minimal satu task untuk HP ini.'"></p>
    <button type="button" @click="toggleOpen()" :aria-expanded="expanded" class="mt-2 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-left text-sm font-semibold" x-text="expanded ? 'Selesai memilih' : (selectedTasks.length ? 'Ubah task (' + selectedTasks.length + ')' : 'Pilih task')"></button>
    <div x-show="expanded" @keydown.escape.stop="expanded = false" class="mt-2 rounded-xl border border-slate-200 bg-white p-3 space-y-2">
        <input x-ref="search" type="search" x-model="search" @input="++requestId; busy = true" @input.debounce.300ms="lookup()" maxlength="200" :aria-label="'Cari task HP ' + label" placeholder="Cari nama task…" class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 px-3 text-base">
        <p x-show="busy" role="status" class="text-xs text-slate-500">Mencari task…</p>
        <p x-show="taskError" role="alert" x-text="taskError" class="text-xs text-red-700"></p>
        <button type="button" x-show="taskError" @click="lookup()" class="min-h-11 text-sm font-semibold">Coba lagi</button>
        <div class="max-h-60 overflow-y-auto overscroll-contain divide-y divide-slate-100">
            <template x-for="task in choices" :key="task.title">
                <label class="flex min-h-11 cursor-pointer items-center gap-3 py-2 text-sm">
                    <input type="checkbox" :checked="selectedTasks.some(title => title.trim().replace(/\s+/g, ' ').toLowerCase() === task.title.trim().replace(/\s+/g, ' ').toLowerCase())" @change="toggleTask(task.title)" class="h-5 w-5 shrink-0 rounded accent-green-800">
                    <span class="min-w-0 break-words"><span x-text="task.title"></span><span x-show="task.source === 'report'" class="block text-xs text-slate-500">Ditambahkan tim · belum ditemukan di Atlas</span></span>
                </label>
            </template>
        </div>
        <p x-show="!busy && !taskError && !choices.length" class="text-xs text-slate-500">Task belum ditemukan. Ketik judul task untuk menambahkannya.</p>
        <button type="button" x-show="canAdd" @click="addTask()" class="min-h-11 w-full break-words rounded-lg bg-green-50 px-3 py-2 text-left text-sm font-bold text-green-900" x-text="'+ Tambah “' + search.trim() + '”'"></button>
    </div>
    <p x-show="notice" x-text="notice" role="status" class="mt-2 text-xs text-green-800"></p>
</div>
