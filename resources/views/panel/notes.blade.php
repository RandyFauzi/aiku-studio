<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catatan &amp; Delegasi Admin - AIKu Studio</title>
    <meta name="theme-color" content="#edf6d9">
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AIKu Studio">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')), currentUserId: {{ auth()->id() }} };</script>
    <script src="/assets/session.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" 
      x-data="adminNotesApp()" 
      x-init="init()" 
      x-cloak>

    <!-- LEFT SIDEBAR -->
    @include('layouts.sidebar')

    <!-- MAIN APP CONTENT AREA -->
    <main id="admin-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Buka atau tutup menu" aria-label="Buka atau tutup menu" aria-controls="workspace-sidebar" :aria-expanded="sidebarOpen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Catatan &amp; Delegasi Admin</h1>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-[#009B7E] border border-emerald-200">Internal Admin</span>
                    </div>
                    <p class="text-xs text-slate-500 hidden sm:block">Papan informasi operasional harian, delegasi tugas antar admin, dan to-do list shift.</p>
                </div>
            </div>

            <!-- Right Header Controls -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button @click="fetchNotes()" 
                        :disabled="loading"
                        class="p-2 sm:px-3 sm:py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200/90 rounded-xl text-xs sm:text-sm font-semibold transition shadow-2xs flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#00B090]" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span class="hidden md:inline">Segarkan</span>
                </button>

                <button @click="openCreateModal()" 
                        class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition shadow-sm flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>+ Catatan / To-Do Baru</span>
                </button>
            </div>
        </header>

        <!-- CONTAINER BODY -->
        <div class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-5">

            <!-- DATE NAVIGATION & STATS ROW -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">
                <!-- Left: Date Navigator -->
                <div class="xl:col-span-6 bg-white p-2.5 sm:p-3 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    <button type="button" @click="changeDateOffset(-1)" class="p-2 hover:bg-slate-100 rounded-xl text-slate-500 hover:text-slate-800 transition" title="Hari Sebelumnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>

                    <button type="button" 
                            @click="setDateToToday()" 
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition"
                            :class="isTodaySelected() ? 'bg-emerald-50 text-[#009B7E] border border-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        Hari Ini
                    </button>

                    <div class="relative flex-1 min-w-[170px]">
                        <input type="date" 
                               x-model="selectedDate" 
                               @change="fetchNotes()" 
                               class="w-full bg-slate-50 hover:bg-slate-100/80 border border-slate-200/90 text-slate-800 font-bold text-xs sm:text-sm py-1.5 px-3 rounded-xl focus:outline-none focus:border-[#00B090] transition cursor-pointer">
                    </div>

                    <button type="button" @click="changeDateOffset(1)" class="p-2 hover:bg-slate-100 rounded-xl text-slate-500 hover:text-slate-800 transition" title="Hari Berikutnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>

                    <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                    <button type="button" 
                            @click="toggleAllDates()" 
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition cursor-pointer"
                            :class="allDates ? 'bg-[#1E293B] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        <span x-text="allDates ? 'Semua Tanggal ✓' : 'Tampilkan Semua'"></span>
                    </button>
                </div>

                <!-- Right: Stats Chips -->
                <div class="xl:col-span-6 grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <!-- Total Catatan -->
                    <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Item</span>
                            <div class="text-xl font-black text-slate-900" x-text="stats.totalNotes">0</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold">📋</div>
                    </div>

                    <!-- To-Do Pending -->
                    <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600">Pending To-Do</span>
                            <div class="text-xl font-black text-amber-700" x-text="stats.pendingTodos">0</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">⏳</div>
                    </div>

                    <!-- To-Do Selesai -->
                    <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">To-Do Selesai</span>
                            <div class="text-xl font-black text-emerald-700" x-text="stats.completedTodos">0</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">✅</div>
                    </div>

                    <!-- Urgent Items -->
                    <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Mendesak</span>
                            <div class="text-xl font-black text-rose-700" x-text="stats.urgentCount">0</div>
                        </div>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">🔥</div>
                    </div>
                </div>
            </div>

            <!-- FILTERS BAR -->
            <div class="bg-white rounded-2xl p-3 sm:p-4 border border-slate-200/80 shadow-2xs flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <!-- Left: Type Tabs -->
                <div class="flex items-center gap-1 overflow-x-auto custom-scrollbar pb-1 md:pb-0">
                    <button type="button" 
                            @click="filters.type = 'all'; fetchNotes()"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap"
                            :class="filters.type === 'all' ? 'bg-[#00B090] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100'">
                        Semua
                    </button>
                    <button type="button" 
                            @click="filters.type = 'todo_and_delegation'; fetchNotes()"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap"
                            :class="filters.type === 'todo_and_delegation' ? 'bg-[#00B090] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100'">
                        To-Do &amp; Delegasi
                    </button>
                    <button type="button" 
                            @click="filters.type = 'note'; fetchNotes()"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap"
                            :class="filters.type === 'note' ? 'bg-[#00B090] text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100'">
                        Catatan Operasional
                    </button>
                    <button type="button" 
                            @click="filters.status = (filters.status === 'completed' ? 'all' : 'completed'); fetchNotes()"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap"
                            :class="filters.status === 'completed' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100'">
                        Telah Selesai ✓
                    </button>
                </div>

                <!-- Right: Priority & Assignee Filter Dropdowns + Search -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Filter Prioritas -->
                    <select x-model="filters.priority" @change="fetchNotes()" 
                            class="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold py-1.5 px-3 rounded-xl focus:outline-none focus:border-[#00B090] cursor-pointer">
                        <option value="all">Semua Prioritas</option>
                        <option value="urgent">🔥 Mendesak (Urgent)</option>
                        <option value="high">⚠️ Penting (High)</option>
                        <option value="normal">🟢 Normal</option>
                    </select>

                    <!-- Filter Delegasi Admin -->
                    <select x-model="filters.assigned_to" @change="fetchNotes()"
                            class="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold py-1.5 px-3 rounded-xl focus:outline-none focus:border-[#00B090] cursor-pointer">
                        <option value="all">Semua Delegasi</option>
                        <option value="me">Didelegasikan ke Saya</option>
                        <option value="unassigned">Untuk Semua Admin</option>
                        <template x-for="adm in admins" :key="adm.id">
                            <option :value="adm.id" x-text="'Untuk: ' + adm.name"></option>
                        </template>
                    </select>

                    <!-- Search Input -->
                    <div class="relative flex-1 sm:w-48">
                        <input type="text" 
                               x-model="filters.search" 
                               @input.debounce.300ms="fetchNotes()" 
                               placeholder="Cari catatan..." 
                               class="w-full bg-slate-50 border border-slate-200 text-slate-800 placeholder-slate-400 text-xs py-1.5 pl-7 pr-3 rounded-xl focus:outline-none focus:border-[#00B090] transition">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- NOTES LIST CONTAINER -->
            <div>
                <!-- Loading State -->
                <div x-show="loading" class="py-16 text-center text-slate-400 space-y-3">
                    <svg class="w-8 h-8 mx-auto animate-spin text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <p class="text-xs font-semibold">Memuat catatan &amp; to-do admin...</p>
                </div>

                <!-- Empty State -->
                <div x-show="!loading && notes.length === 0" class="bg-white rounded-3xl p-10 text-center border border-slate-200/80 shadow-2xs space-y-3">
                    <div class="w-16 h-16 rounded-3xl bg-emerald-50 text-[#00B090] flex items-center justify-center text-3xl mx-auto">
                        📝
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Belum Ada Catatan atau To-Do</h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto">
                        Tidak ada catatan untuk tanggal <strong x-text="allDates ? 'yang dipilih' : selectedDate"></strong>. Buat catatan baru untuk menginformasikan kejadian penting, delegasi tugas antar admin, atau to-do shift.
                    </p>
                    <button type="button" 
                            @click="openCreateModal()" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-[#00B090] hover:bg-[#009B7E] text-white rounded-xl text-xs font-bold transition shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Buat Catatan Sekarang</span>
                    </button>
                </div>

                <!-- Notes Cards Grid -->
                <div x-show="!loading && notes.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <template x-for="note in notes" :key="note.id">
                        <div class="bg-white rounded-3xl border transition duration-200 p-4 sm:p-5 flex flex-col justify-between shadow-xs hover:shadow-md"
                             :class="{
                                 'border-amber-300 ring-2 ring-amber-100 bg-amber-50/20': note.is_pinned && !note.is_completed,
                                 'border-rose-300 ring-1 ring-rose-200': note.priority === 'urgent' && !note.is_completed && !note.is_pinned,
                                 'border-slate-200/90': !note.is_pinned && note.priority !== 'urgent' && !note.is_completed,
                                 'opacity-60 bg-slate-50 border-slate-200': note.is_completed
                             }">
                            
                            <!-- Card Header -->
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <!-- Pin Badge -->
                                        <button type="button" 
                                                @click="togglePin(note)" 
                                                :title="note.is_pinned ? 'Lepas Pin' : 'Pin ke Atas'"
                                                class="p-1 rounded-lg transition"
                                                :class="note.is_pinned ? 'text-amber-500 hover:bg-amber-100' : 'text-slate-300 hover:text-slate-500 hover:bg-slate-100'">
                                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                                        </button>

                                        <!-- Type Badge -->
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider"
                                              :class="{
                                                  'bg-blue-50 text-blue-700 border border-blue-200': note.type === 'note',
                                                  'bg-emerald-50 text-emerald-800 border border-emerald-200': note.type === 'todo',
                                                  'bg-indigo-50 text-indigo-700 border border-indigo-200': note.type === 'delegation'
                                              }"
                                              x-text="getTypeLabel(note.type)"></span>

                                        <!-- Priority Badge -->
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider flex items-center gap-1"
                                              :class="{
                                                  'bg-rose-50 text-rose-700 border border-rose-200 font-extrabold': note.priority === 'urgent',
                                                  'bg-amber-50 text-amber-700 border border-amber-200': note.priority === 'high',
                                                  'bg-slate-100 text-slate-600': note.priority === 'normal'
                                              }">
                                            <span x-show="note.priority === 'urgent'" class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                            <span x-text="getPriorityLabel(note.priority)"></span>
                                        </span>
                                    </div>

                                    <!-- Action Menu Buttons -->
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" 
                                                @click="openEditModal(note)" 
                                                title="Edit Catatan"
                                                class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button type="button" 
                                                @click="deleteNote(note)" 
                                                title="Hapus Catatan"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Note Title -->
                                <div class="flex items-start gap-2.5 my-2">
                                    <template x-if="note.type !== 'note'">
                                        <button type="button" 
                                                @click="toggleComplete(note)" 
                                                class="mt-0.5 w-5 h-5 rounded-lg border flex items-center justify-center transition shrink-0 cursor-pointer"
                                                :class="note.is_completed ? 'bg-emerald-500 border-emerald-500 text-white shadow-2xs' : 'border-slate-300 hover:border-emerald-500 bg-white'">
                                            <span x-show="note.is_completed" class="text-xs font-black">✓</span>
                                        </button>
                                    </template>
                                    <h4 class="text-sm font-bold text-slate-900 leading-snug break-words"
                                        :class="note.is_completed ? 'line-through text-slate-400' : ''"
                                        x-text="note.title"></h4>
                                </div>

                                <!-- Content text -->
                                <p x-show="note.content" 
                                   class="text-xs text-slate-600 leading-relaxed whitespace-pre-line break-words my-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-100"
                                   x-text="note.content"></p>

                                <!-- Sub-Checklist Items -->
                                <div x-show="note.checklist_items && note.checklist_items.length > 0" class="my-3 space-y-1.5">
                                    <template x-for="(item, idx) in (note.checklist_items || [])" :key="idx">
                                        <div class="flex items-start gap-2 text-xs py-1 px-2 rounded-lg hover:bg-slate-50 transition cursor-pointer"
                                             @click="toggleChecklistItem(note, idx)">
                                            <input type="checkbox" 
                                                   :checked="item.completed" 
                                                   class="mt-0.5 rounded text-[#00B090] focus:ring-[#00B090] accent-[#00B090] cursor-pointer"
                                                   @click.stop="toggleChecklistItem(note, idx)">
                                            <span class="break-words select-none" 
                                                  :class="item.completed ? 'line-through text-slate-400' : 'text-slate-700'"
                                                  x-text="item.text"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Card Footer: Author & Assignee Info -->
                            <div class="pt-3 border-t border-slate-100 mt-2 flex flex-col gap-2 text-[11px] text-slate-500">
                                <div class="flex items-center justify-between gap-2">
                                    <!-- Delegated Assignee -->
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="font-bold text-slate-400">Target:</span>
                                        <template x-if="note.assigned_to">
                                            <span class="inline-flex items-center gap-1 font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-md truncate">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></span>
                                                <span class="truncate" x-text="note.assigned_to.name"></span>
                                            </span>
                                        </template>
                                        <template x-if="!note.assigned_to">
                                            <span class="font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">Semua Admin</span>
                                        </template>
                                    </div>

                                    <!-- Date Badge -->
                                    <span class="font-mono text-slate-400" x-text="formatDateShort(note.note_date)"></span>
                                </div>

                                <div class="flex items-center justify-between gap-2 text-[10px] text-slate-400">
                                    <div class="truncate">
                                        <span>Dibuat: </span>
                                        <strong class="text-slate-600" x-text="note.user ? note.user.name : 'Admin'"></strong>
                                        <span> · </span>
                                        <span x-text="formatTimeOnly(note.created_at)"></span>
                                    </div>

                                    <template x-if="note.is_completed">
                                        <span class="font-bold text-emerald-600 flex items-center gap-1">
                                            <span>✓ Selesai</span>
                                            <span x-show="note.completed_by" x-text="'(' + note.completed_by.name + ')'"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>
            </div>

        </div>
    </main>

    <!-- MODAL BUAT / EDIT CATATAN -->
    <div x-show="showModal" 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full p-5 sm:p-6 space-y-4 max-h-[90vh] flex flex-col justify-between overflow-hidden"
             @click.outside="closeModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-[#00B090] flex items-center justify-center font-bold">
                        ✏️
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900" x-text="isEditMode ? 'Edit Catatan / To-Do' : 'Buat Catatan / To-Do Baru'"></h3>
                        <p class="text-xs text-slate-500">Dapat dibaca dan ditindaklanjuti oleh seluruh administrator.</p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>

            <!-- Modal Form Body -->
            <div class="overflow-y-auto custom-scrollbar pr-1 space-y-4 flex-1">
                <!-- Title -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Judul Catatan / Tugas <span class="text-rose-500">*</span></label>
                    <input type="text" 
                           x-model="form.title" 
                           placeholder="Contoh: Cek rekaman HP Putih, Briefing shift malam..." 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs sm:text-sm text-slate-800 focus:bg-white focus:outline-none focus:border-[#00B090] transition font-medium">
                </div>

                <!-- Type & Priority Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tipe</label>
                        <select x-model="form.type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-[#00B090] cursor-pointer">
                            <option value="note">📝 Catatan Informasi</option>
                            <option value="todo">✅ To-Do Checklist</option>
                            <option value="delegation">👤 Delegasi Tugas</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tingkat Prioritas</label>
                        <select x-model="form.priority" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-[#00B090] cursor-pointer">
                            <option value="normal">🟢 Normal</option>
                            <option value="high">⚠️ Penting (High)</option>
                            <option value="urgent">🔥 Mendesak (Urgent)</option>
                        </select>
                    </div>
                </div>

                <!-- Delegasi Target & Date Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Delegasikan Ke</label>
                        <select x-model="form.assigned_to_user_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-[#00B090] cursor-pointer">
                            <option value="">Semua Admin (Umum)</option>
                            <template x-for="adm in admins" :key="adm.id">
                                <option :value="adm.id" x-text="adm.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal Berlaku</label>
                        <input type="date" 
                               x-model="form.note_date" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-[#00B090] cursor-pointer">
                    </div>
                </div>

                <!-- Description / Content -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Rincian / Keterangan (Opsional)</label>
                    <textarea x-model="form.content" 
                              rows="3" 
                              placeholder="Tuliskan detail kejadian, instruksi, nomor HP / rekaman terkait, atau catatan serah terima..." 
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-[#00B090] transition leading-relaxed"></textarea>
                </div>

                <!-- Sub-checklist items builder -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Sub-Checklist / Langkah Pengerjaan</label>
                        <button type="button" @click="addChecklistItem()" class="text-[11px] font-bold text-[#009B7E] hover:underline">+ Tambah Poin</button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(item, index) in form.checklist_items" :key="index">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400 font-bold w-4" x-text="(index + 1) + '.'"></span>
                                <input type="text" 
                                       x-model="item.text" 
                                       placeholder="Langkah atau sub-task..." 
                                       class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-[#00B090]">
                                <button type="button" @click="removeChecklistItem(index)" class="p-1 text-slate-400 hover:text-rose-600 rounded-lg">✕</button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Pin to top toggle -->
                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="form.is_pinned" class="rounded text-[#00B090] focus:ring-[#00B090] accent-[#00B090] w-4 h-4 cursor-pointer">
                        <span class="text-xs font-bold text-slate-700">📌 Sematkan catatan ini di paling atas (Pin)</span>
                    </label>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5 shrink-0">
                <button type="button" 
                        @click="closeModal()" 
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-bold transition">
                    Batal
                </button>
                <button type="button" 
                        @click="saveNote()" 
                        :disabled="submitting || !form.title.trim()"
                        class="px-5 py-2 rounded-xl bg-[#00B090] hover:bg-[#009B7E] text-white text-xs font-bold transition shadow-xs disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="submitting" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span x-text="isEditMode ? 'Simpan Perubahan' : 'Terbitkan Catatan'"></span>
                </button>
            </div>

        </div>
    </div>

    <script>
        function adminNotesApp() {
            return {
                sidebarOpen: window.innerWidth >= 768 && (localStorage.getItem('aiku_sidebar_open') !== 'false'),
                loading: false,
                submitting: false,
                notes: [],
                admins: [],
                stats: {
                    totalNotes: 0,
                    pendingTodos: 0,
                    completedTodos: 0,
                    urgentCount: 0
                },
                today: new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date()),
                selectedDate: new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Makassar', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date()),
                allDates: false,
                filters: {
                    type: 'all',
                    priority: 'all',
                    status: 'all',
                    assigned_to: 'all',
                    search: ''
                },
                showModal: false,
                isEditMode: false,
                editId: null,
                form: {
                    title: '',
                    content: '',
                    type: 'note',
                    priority: 'normal',
                    assigned_to_user_id: '',
                    note_date: '',
                    is_pinned: false,
                    checklist_items: []
                },

                async init() {
                    this.form.note_date = this.selectedDate;
                    await this.fetchNotes();
                },

                isTodaySelected() {
                    return !this.allDates && this.selectedDate === this.today;
                },

                setDateToToday() {
                    this.allDates = false;
                    this.selectedDate = this.today;
                    this.fetchNotes();
                },

                changeDateOffset(days) {
                    this.allDates = false;
                    const d = new Date(this.selectedDate + 'T00:00:00');
                    d.setDate(d.getDate() + days);
                    this.selectedDate = d.toISOString().split('T')[0];
                    this.fetchNotes();
                },

                toggleAllDates() {
                    this.allDates = !this.allDates;
                    this.fetchNotes();
                },

                async fetchNotes() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.allDates) {
                            params.append('all_dates', '1');
                        } else {
                            params.append('date', this.selectedDate);
                        }
                        if (this.filters.type !== 'all') params.append('type', this.filters.type);
                        if (this.filters.priority !== 'all') params.append('priority', this.filters.priority);
                        if (this.filters.status !== 'all') params.append('status', this.filters.status);
                        if (this.filters.assigned_to !== 'all') params.append('assigned_to', this.filters.assigned_to);
                        if (this.filters.search.trim()) params.append('search', this.filters.search.trim());

                        const res = await fetch('/api/admin-notes?' + params.toString(), {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.notes = data.notes || [];
                            if (data.stats) this.stats = data.stats;
                            if (data.admins) this.admins = data.admins;
                            if (data.today) this.today = data.today;
                        }
                    } catch (e) {
                        console.error('Error fetching admin notes:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                openCreateModal() {
                    this.isEditMode = false;
                    this.editId = null;
                    this.form = {
                        title: '',
                        content: '',
                        type: 'note',
                        priority: 'normal',
                        assigned_to_user_id: '',
                        note_date: this.selectedDate || this.today,
                        is_pinned: false,
                        checklist_items: []
                    };
                    this.showModal = true;
                },

                openEditModal(note) {
                    this.isEditMode = true;
                    this.editId = note.id;
                    this.form = {
                        title: note.title,
                        content: note.content || '',
                        type: note.type || 'note',
                        priority: note.priority || 'normal',
                        assigned_to_user_id: note.assigned_to_user_id || '',
                        note_date: note.note_date,
                        is_pinned: Boolean(note.is_pinned),
                        is_completed: Boolean(note.is_completed),
                        checklist_items: (note.checklist_items || []).map(i => ({ text: i.text, completed: Boolean(i.completed) }))
                    };
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                },

                addChecklistItem() {
                    this.form.checklist_items.push({ text: '', completed: false });
                },

                removeChecklistItem(idx) {
                    this.form.checklist_items.splice(idx, 1);
                },

                async saveNote() {
                    if (!this.form.title.trim()) return;
                    this.submitting = true;
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    try {
                        const payload = {
                            ...this.form,
                            assigned_to_user_id: this.form.assigned_to_user_id ? this.form.assigned_to_user_id : null,
                            checklist_items: this.form.checklist_items.filter(i => i.text && i.text.trim())
                        };

                        const url = this.isEditMode ? `/api/admin-notes/${this.editId}` : '/api/admin-notes';
                        const method = this.isEditMode ? 'PUT' : 'POST';

                        const res = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify(payload)
                        });

                        if (res.ok) {
                            this.closeModal();
                            await this.fetchNotes();
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: this.isEditMode ? 'Catatan Diperbarui' : 'Catatan Diterbitkan',
                                    timer: 1200,
                                    showConfirmButton: false
                                });
                            }
                        } else {
                            const err = await res.json().catch(() => ({}));
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal Menyimpan',
                                    text: err.message || 'Terjadi kesalahan pada server saat menyimpan catatan.'
                                });
                            } else {
                                alert(err.message || 'Gagal menyimpan catatan');
                            }
                        }
                    } catch (e) {
                        console.error('Error saving note:', e);
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Kesalahan Koneksi',
                                text: 'Gagal terhubung ke server. Periksa koneksi internet Anda.'
                            });
                        } else {
                            alert('Terjadi kesalahan koneksi.');
                        }
                    } finally {
                        this.submitting = false;
                    }
                },

                async toggleComplete(note) {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    try {
                        const res = await fetch(`/api/admin-notes/${note.id}/toggle`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            }
                        });
                        if (res.ok) {
                            const updated = await res.json();
                            const idx = this.notes.findIndex(n => n.id === note.id);
                            if (idx !== -1) this.notes[idx] = updated;
                            // Recalculate stats
                            const pending = this.notes.filter(n => ['todo', 'delegation'].includes(n.type) && !n.is_completed).length;
                            const done = this.notes.filter(n => ['todo', 'delegation'].includes(n.type) && n.is_completed).length;
                            this.stats.pendingTodos = pending;
                            this.stats.completedTodos = done;
                        }
                    } catch (e) {
                        console.error('Error toggling complete:', e);
                    }
                },

                async togglePin(note) {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    try {
                        const res = await fetch(`/api/admin-notes/${note.id}/pin`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            }
                        });
                        if (res.ok) {
                            const updated = await res.json();
                            const idx = this.notes.findIndex(n => n.id === note.id);
                            if (idx !== -1) {
                                this.notes[idx] = updated;
                                this.notes.sort((a, b) => (b.is_pinned - a.is_pinned) || (new Date(b.created_at) - new Date(a.created_at)));
                            }
                        }
                    } catch (e) {
                        console.error('Error toggling pin:', e);
                    }
                },

                async toggleChecklistItem(note, idx) {
                    if (!note.checklist_items || !note.checklist_items[idx]) return;
                    note.checklist_items[idx].completed = !note.checklist_items[idx].completed;
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    try {
                        await fetch(`/api/admin-notes/${note.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                title: note.title,
                                content: note.content,
                                type: note.type,
                                priority: note.priority,
                                note_date: note.note_date,
                                assigned_to_user_id: note.assigned_to_user_id,
                                is_pinned: note.is_pinned,
                                is_completed: note.is_completed,
                                checklist_items: note.checklist_items
                            })
                        });
                    } catch (e) {
                        console.error('Error updating checklist item:', e);
                    }
                },

                async deleteNote(note) {
                    if (typeof Swal !== 'undefined') {
                        const result = await Swal.fire({
                            title: 'Hapus Catatan?',
                            text: `Yakin ingin menghapus catatan "${note.title}"?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#e11d48',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Ya, Hapus',
                            cancelButtonText: 'Batal'
                        });
                        if (!result.isConfirmed) return;
                    } else {
                        if (!confirm(`Yakin ingin menghapus catatan "${note.title}"?`)) return;
                    }

                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    try {
                        const res = await fetch(`/api/admin-notes/${note.id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            }
                        });
                        if (res.ok) {
                            this.notes = this.notes.filter(n => n.id !== note.id);
                            this.stats.totalNotes = Math.max(0, this.stats.totalNotes - 1);
                        }
                    } catch (e) {
                        console.error('Error deleting note:', e);
                    }
                },

                getTypeLabel(type) {
                    switch(type) {
                        case 'todo': return 'To-Do';
                        case 'delegation': return 'Delegasi';
                        default: return 'Catatan';
                    }
                },

                getPriorityLabel(priority) {
                    switch(priority) {
                        case 'urgent': return 'Mendesak';
                        case 'high': return 'Penting';
                        default: return 'Normal';
                    }
                },

                formatDateShort(dateStr) {
                    if (!dateStr) return '';
                    try {
                        const [y, m, d] = dateStr.split('-');
                        return `${d}/${m}/${y}`;
                    } catch (e) {
                        return dateStr;
                    }
                },

                formatTimeOnly(timeStr) {
                    if (!timeStr) return '';
                    try {
                        const d = new Date(timeStr);
                        return new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Makassar',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d) + ' WITA';
                    } catch (e) {
                        return '';
                    }
                }
            };
        }
    </script>
</body>
</html>
