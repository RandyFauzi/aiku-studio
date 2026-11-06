<div class="mt-3 border-t border-slate-200 pt-3" @task-labels-updated.window="lookup()" x-data="phoneTaskPicker({ read: () => {{ $taskMap }}[key] || [], write: tasks => {{ $taskMap }}[key] = tasks, copy: () => {{ $taskMap }}[{{ $phoneList }}[{{ $phoneList }}.indexOf(key) - 1]] || [] })">
    <!-- Header: Title, Status Badge & Action -->
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-slate-800">Task yang dikerjakan</span>
            <span x-show="selectedTasks.length > 0" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-900 border border-emerald-300">
                <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span x-text="selectedTasks.length + ' task dipilih'"></span>
            </span>
            <span x-show="!selectedTasks.length" class="text-[11px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                Wajib pilih minimal 1
            </span>
        </div>
        <button type="button" x-show="previousTasks.length" @click="copyPrevious()" title="Salin task dari HP sebelumnya" class="min-h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 px-2.5 py-1 text-xs font-bold text-emerald-800 transition cursor-pointer flex items-center gap-1 shadow-2xs">
            <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span>Task sama</span>
        </button>
    </div>

    <!-- Active Selected Task Chips -->
    <div x-show="selectedTasks.length > 0" class="flex flex-wrap gap-1.5 mt-2">
        <template x-for="title in selectedTasks" :key="title">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-900 border border-emerald-300 shadow-2xs">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0"></span>
                <span class="break-words" x-text="labelFor(title)"></span>
                <button type="button" @click="toggleTask(title)" class="text-emerald-700 hover:text-emerald-950 font-black ml-0.5 text-sm leading-none cursor-pointer p-0.5" :aria-label="'Hapus task ' + labelFor(title)">&times;</button>
            </span>
        </template>
    </div>

    <!-- Direct Checklist Box (No Button to open/close, No Selesai Memilih confirmation) -->
    <div class="mt-2.5 rounded-2xl border border-slate-200 bg-slate-50/70 p-3 space-y-2.5">
        <!-- Search Input -->
        <div class="relative">
            <input x-ref="search" type="search" x-model="search" @input="++requestId; busy = true" @input.debounce.300ms="lookup()" maxlength="200" :aria-label="'Cari task HP ' + label" placeholder="Cari task... (misal: bungkus kado, kardus, rak)" class="min-h-10 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs sm:text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-500">
        </div>

        <p class="text-[11px] text-slate-500 font-medium">Centang langsung task yang kamu kerjakan di HP ini:</p>
        <p x-show="busy" role="status" class="text-xs text-slate-500 font-medium">Memuat task…</p>
        <div x-show="taskError" class="flex items-center justify-between text-xs text-red-700 bg-red-50 p-2 rounded-lg border border-red-200">
            <span x-text="taskError"></span>
            <button type="button" @click="lookup()" class="font-bold underline ml-2 cursor-pointer">Coba lagi</button>
        </div>

        <!-- Task Checklist with Scroll -->
        <div class="max-h-56 overflow-y-auto overscroll-contain divide-y divide-slate-200/60 rounded-xl border border-slate-200 bg-white p-1">
            <template x-for="task in choices" :key="task.title">
                <div class="flex items-center justify-between gap-2 p-1.5 rounded-lg transition"
                     :class="isTaskSelected(task.title) ? 'bg-emerald-50/90 font-bold text-emerald-950' : 'hover:bg-slate-50 text-slate-700'">
                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3 py-1 px-1 select-none">
                        <input type="checkbox"
                               :checked="isTaskSelected(task.title)"
                               @change="toggleTask(task.title)"
                               class="h-4.5 w-4.5 shrink-0 rounded border-slate-300 text-emerald-700 accent-emerald-700 cursor-pointer">
                        <span class="min-w-0 break-words text-xs sm:text-sm">
                            <span x-text="task.label || labelFor(task.title)"></span>
                            <span x-show="task.source === 'report'" class="block text-[10px] text-slate-500 font-normal">Ditambahkan tim · belum ditemukan di Atlas</span>
                            <span x-show="canEditLabels && task.label_status === 'suggested'" class="block text-[10px] text-amber-700 font-normal">Padanan otomatis · dapat disesuaikan</span>
                            <span x-show="task.source === 'atlas' && task.label_status === 'original'" class="block text-[10px] text-amber-700 font-normal">Padanan Indonesia belum tersedia</span>
                        </span>
                    </label>
                    <button type="button" x-show="canEditLabels" @click="editTranslation(task)" :aria-label="'Ubah nama Indonesia task ' + (task.label || labelFor(task.title))" title="Ubah nama Indonesia / kata kunci" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 cursor-pointer">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m16 4 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <p x-show="!busy && !taskError && !choices.length" class="text-xs text-slate-500">Task tidak ditemukan. Ketik nama task untuk menambahkannya.</p>
        <button type="button" x-show="canAdd" @click="addTask()" class="min-h-10 w-full break-words rounded-xl bg-emerald-100/80 hover:bg-emerald-200/80 px-3 py-2 text-left text-xs sm:text-sm font-bold text-emerald-950 border border-emerald-300 transition cursor-pointer" x-text="'+ Tambah “' + search.trim() + '”'"></button>

        <!-- Admin Edit Translation Panel -->
        <div x-show="editingTitle && canEditLabels" class="rounded-xl bg-slate-100 p-3 space-y-2 border border-slate-200">
            <p class="break-words text-xs text-slate-600 font-semibold" x-text="'Judul asli recording: ' + editingTitle"></p>
            <label class="block text-xs font-bold text-slate-700">Nama Indonesia
                <input type="text" x-model="editLabel" maxlength="200" class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-800">
            </label>
            <label class="block text-xs font-bold text-slate-700">Kata kunci tambahan
                <input type="text" x-model="editKeywords" maxlength="500" placeholder="Pisahkan dengan koma" class="mt-1 min-h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-800">
            </label>
            <p x-show="labelError" x-text="labelError" role="alert" class="text-xs text-red-700"></p>
            <div class="flex flex-wrap gap-2 pt-1">
                <button type="button" @click="saveTranslation()" :disabled="labelSaving" class="min-h-8 rounded-lg bg-emerald-800 px-3 text-xs font-bold text-white hover:bg-emerald-900 disabled:opacity-50 cursor-pointer">Simpan nama</button>
                <button type="button" @click="editingTitle = ''" :disabled="labelSaving" class="min-h-8 px-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 cursor-pointer">Batal</button>
            </div>
        </div>
    </div>
    <p x-show="notice" x-text="notice" role="status" class="mt-2 text-xs text-emerald-800 font-semibold"></p>
</div>
