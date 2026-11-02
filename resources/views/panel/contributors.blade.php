<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Teams & Kontributor</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="contributorsListData()" x-init="fetchContributors()" x-cloak>

    <!-- LEFT SIDEBAR -->
    <aside class="w-64 bg-white border-r border-slate-200/80 flex flex-col shrink-0 min-h-screen transition-all duration-300 z-30"
           :class="sidebarOpen ? 'translate-x-0 w-64' : '-translate-x-full w-0 md:w-0 overflow-hidden hidden'">
        
        <!-- Brand Header -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-slate-100">
            <div class="flex items-center gap-3 cursor-pointer group">
                <div class="relative flex items-center justify-center w-9 h-9">
                    <div class="w-7 h-7 rounded-full border-[3px] border-[#00B090] absolute -left-0.5"></div>
                    <div class="w-7 h-7 rounded-full border-[3px] border-emerald-400 absolute -right-0.5 mix-blend-multiply opacity-80"></div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xl font-extrabold tracking-tight text-slate-900">AIKu</span>
                    <span class="text-xs font-bold text-[#00B090] bg-emerald-50 px-1.5 py-0.5 rounded-md border border-emerald-100">Studio</span>
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Sidebar Navigation Menu -->
        <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
                        <div class="space-y-1.5">
                <div class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Menu Utama</div>
                <a href="index.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span>Dashboard</span>
                </a>
                <a href="recordings.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span>Task List (Rekaman)</span>
                </a>
                <a href="payslips.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Riwayat Slip Gaji</span>
                </a>
                <a href="shifts.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Pengaturan Shift</span>
                </a>
                <a href="create.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Buat Slip Gaji</span>
                </a>
                <a href="contributors.html" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition text-[#009B7E] bg-[#EDFAF8] shadow-xs">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-[#00B090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Teams & Kontributor</span>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-[#00B090]"></span>
                </a>
                <a href="profit.html" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition text-slate-600 hover:text-slate-900 hover:bg-slate-50">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    <span>Reporting & Profit</span>
                </a>
            </div>
        </div>

        <!-- Sidebar Footer Admin -->
        <div class="p-4 border-t border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3 truncate">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-400 text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-sm">
                    AK
                </div>
                <div class="truncate">
                    <div class="text-xs font-bold text-slate-800 truncate">AIKu Studio Admin</div>
                    <div class="text-[11px] text-slate-400 truncate">admin@aikustudio.site</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN APP CONTENT AREA -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-20 bg-white border-b border-slate-200/80 px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Toggle Sidebar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>

            <!-- Right Header Controls -->
            <div class="flex items-center gap-3">
                <a href="create.html" class="bg-[#00B090] hover:bg-[#009B7E] text-white px-3.5 py-2 rounded-xl text-xs md:text-sm font-semibold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span class="hidden sm:inline">Buat Slip Gaji</span>
                </a>

                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-9 h-9 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs shadow-xs">
                        AM
                    </div>
                    <div class="hidden lg:block text-left">
                        <div class="text-xs font-bold text-slate-800 leading-tight">Abdul Mughni</div>
                        <div class="text-[11px] text-slate-400 leading-tight">AIKu Studio Admin</div>
                    </div>
                </div>
            </div>
        </header>
        <div class="w-full max-w-full px-3 sm:px-5 lg:px-7 py-5 space-y-6">

            <!-- Breadcrumb & Header -->
            <div class="mb-8">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2">Overview / Contributors / List</p>
                <div class="flex justify-between items-end">
                    <h2 class="text-3xl font-extrabold text-gray-900">Contributors List</h2>
                    <button @click="openModal()" class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm flex items-center gap-2">
                        + Add Contributor
                    </button>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white border border-gray-200/80 rounded-2xl overflow-hidden shadow-sm"><div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Domisili</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kontak (WA / Email)</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Shift / Tim</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Rekening</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="isLoading">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400 font-medium">
                                    <div class="flex justify-center items-center gap-2">
                                        <svg class="animate-spin h-5 w-5 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Loading records...
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!isLoading && contributors.length === 0">
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400 font-medium">No contributors found in database.</td>
                            </tr>
                        </template>
                        <template x-for="c in contributors" :key="c.id">
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 text-brand flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                            <span x-text="c.nama.substring(0,2)"></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900" x-text="c.nama"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-600" x-text="c.domisili"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900" x-text="c.no_wa || '-'"></div>
                                    <div class="text-xs text-gray-500" x-text="c.email || '-'"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900" x-text="c.hp"></div>
                                    <div class="text-xs text-gray-500">Shift: <span x-text="c.shift"></span></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700" x-text="c.no_rek || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="openModal(c)" class="text-brand hover:text-indigo-900 mr-3">Edit</button>
                                        <button @click="deleteContributor(c.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Placeholder -->
            <div class="mt-6 flex justify-between items-center text-sm text-gray-500">
                <span x-text="'Showing ' + contributors.length + ' contributors'"></span>
            </div>
        
        </div>
    </main>

<!-- Modal Form -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-900 opacity-50 backdrop-blur-sm" @click="closeModal()"></div>
            </div>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form @submit.prevent="saveContributor">
                    <div class="bg-white px-6 pt-6 pb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6" x-text="isEditing ? 'Edit Contributor' : 'Add New Contributor'"></h3>
                        
                        <div class="grid grid-cols-1 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Nama Lengkap</label>
                                <input type="text" x-model="formData.nama" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">No WhatsApp</label>
                                    <input type="text" x-model="formData.no_wa" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Email</label>
                                    <input type="email" x-model="formData.email" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Domisili</label>
                                <input type="text" x-model="formData.domisili" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Shift</label>
                                    <input type="text" x-model="formData.shift" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Tim / HP</label>
                                    <input type="text" x-model="formData.hp" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">No. Rekening</label>
                                <input type="text" x-model="formData.no_rek" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                        <button type="submit" class="bg-brand hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition shadow-sm">
                            Save Changes
                        </button>
                        <button type="button" @click="closeModal()" class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-semibold transition shadow-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <script src="/assets/app.js"></script>
<form method="post" action="/logout" class="fixed bottom-3 left-3 z-50">@csrf<button class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-600 shadow" title="Keluar dari akun panel">Keluar akun</button></form>
</body>
</html>