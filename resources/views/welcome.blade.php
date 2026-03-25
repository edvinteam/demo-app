<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Task Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.1.0/dist/echo.iife.js"></script>
    <script>window.EchoConstructor = Echo;</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .glass { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        .card-hover { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-1px); box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.3); }
        .search-glow:focus { box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15), 0 0 20px rgba(99, 102, 241, 0.1); }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-up { animation: fadeUp 0.3s ease-out; }
        .drop-zone-active { border-color: rgb(99, 102, 241) !important; background: rgba(99, 102, 241, 0.05) !important; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen font-sans antialiased">

<!-- Background gradient -->
<div class="fixed inset-0 bg-gradient-to-br from-gray-950 via-gray-950 to-indigo-950/30 pointer-events-none"></div>

<div x-data="taskBoard()" x-init="init()" x-cloak class="relative max-w-7xl mx-auto px-6 py-8">

    <!-- Header -->
    <header class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Task Board</h1>
                <p class="text-sm text-gray-500">Real-time collaborative task management</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- Connection indicator -->
            <div x-show="connected" class="flex items-center gap-2 text-xs font-medium text-emerald-400 bg-emerald-400/10 px-3 py-1.5 rounded-full">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                </span>
                Live
            </div>
            <div x-show="!connected" class="flex items-center gap-2 text-xs font-medium text-amber-400 bg-amber-400/10 px-3 py-1.5 rounded-full">
                <span class="w-2 h-2 bg-amber-400 rounded-full"></span>
                Connecting
            </div>

            <!-- PDF Export -->
            <button x-show="features.pdf_export" x-cloak
                    @click="exportPdf()"
                    :disabled="exporting"
                    class="flex items-center gap-2 bg-gray-800/80 hover:bg-gray-700/80 border border-gray-700/50 text-gray-300 hover:text-white px-4 py-2 rounded-xl text-sm font-medium transition-all disabled:opacity-50"
                    title="Export as PDF">
                <svg x-show="!exporting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <svg x-show="exporting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="exporting ? 'Generating...' : 'Export PDF'"></span>
            </button>

            <!-- New task -->
            <button @click="openForm()"
                    class="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white px-5 py-2 rounded-xl text-sm font-medium transition-all shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                New Task
            </button>
        </div>
    </header>

    <!-- Search bar -->
    <div x-show="features.search" x-cloak class="mb-6 animate-fade-up">
        <div class="relative max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input x-model="searchQuery"
                   @input.debounce.300ms="performSearch()"
                   type="text"
                   placeholder="Search tasks..."
                   class="w-full bg-gray-900/80 border border-gray-800 hover:border-gray-700 rounded-xl pl-10 pr-10 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500/50 search-glow transition-all">
            <button x-show="searchQuery"
                    @click="clearSearch()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <p x-show="searchQuery && !searching" class="text-xs text-gray-500 mt-2 ml-1">
            <span x-text="filteredTasks.length"></span> result(s) for &ldquo;<span x-text="searchQuery"></span>&rdquo;
            <span class="text-indigo-400/60 ml-1">via Meilisearch</span>
        </p>
    </div>

    <!-- Toast notifications -->
    <div class="fixed top-6 right-6 z-50 space-y-3">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 :class="toast.type === 'error' ? 'border-red-500/30 bg-red-950/60' : 'glass border-gray-700/50'"
                 class="border rounded-xl px-4 py-3 shadow-2xl text-sm max-w-sm">
                <div class="flex gap-2">
                    <template x-if="toast.type === 'error'">
                        <svg class="w-4 h-4 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                    </template>
                    <div>
                        <span :class="toast.type === 'error' ? 'text-red-200' : 'text-gray-200'" x-text="toast.message"></span>
                        <p x-show="toast.hint" class="text-xs mt-1" :class="toast.type === 'error' ? 'text-red-400/70' : 'text-gray-500'" x-text="toast.hint"></p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- New task modal -->
    <div x-show="showForm" x-transition.opacity class="fixed inset-0 z-40 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div x-show="showForm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             @click.outside="closeForm()"
             @keydown.escape.window="closeForm()"
             class="bg-gray-900 border border-gray-800 rounded-2xl p-6 w-full max-w-md shadow-2xl shadow-black/50">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold">Create Task</h2>
            </div>
            <form @submit.prevent="createTask()">
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wider">Title</label>
                    <input x-model="newTask.title" x-ref="titleInput" type="text" required
                           class="w-full bg-gray-800/80 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50 transition-all"
                           placeholder="What needs to be done?">
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wider">Description</label>
                    <textarea x-model="newTask.description" rows="3"
                              class="w-full bg-gray-800/80 border border-gray-700 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/50 transition-all resize-none"
                              placeholder="Add some details..."></textarea>
                </div>

                <!-- File attachment -->
                <div class="mb-6">
                    <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wider">Attachment</label>
                    <div x-ref="dropZone"
                         @dragover.prevent="$refs.dropZone.classList.add('drop-zone-active')"
                         @dragleave.prevent="$refs.dropZone.classList.remove('drop-zone-active')"
                         @drop.prevent="handleDrop($event); $refs.dropZone.classList.remove('drop-zone-active')"
                         @click="$refs.fileInput.click()"
                         class="border-2 border-dashed border-gray-700 rounded-xl p-4 text-center cursor-pointer hover:border-gray-600 transition-all">
                        <input x-ref="fileInput" type="file" class="hidden" @change="handleFileSelect($event)">

                        <template x-if="!selectedFile">
                            <div class="text-gray-500">
                                <svg class="w-6 h-6 mx-auto mb-1.5 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                                </svg>
                                <p class="text-xs">Drop a file here or <span class="text-indigo-400">browse</span></p>
                                <p class="text-xs text-gray-600 mt-0.5">Max 10 MB</p>
                            </div>
                        </template>

                        <template x-if="selectedFile">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                                    </svg>
                                    <span class="text-sm text-gray-300 truncate" x-text="selectedFile.name"></span>
                                    <span class="text-xs text-gray-500 shrink-0" x-text="formatFileSize(selectedFile.size)"></span>
                                </div>
                                <button type="button" @click.stop="removeFile()"
                                        class="text-gray-500 hover:text-red-400 transition-colors shrink-0 ml-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" @click="closeForm()"
                            class="px-4 py-2 text-sm text-gray-400 hover:text-white rounded-lg hover:bg-gray-800 transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 disabled:opacity-50 text-white px-5 py-2 rounded-xl text-sm font-medium transition-all shadow-lg shadow-indigo-500/20">
                        <span x-show="!submitting">Create Task</span>
                        <span x-show="submitting" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Creating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Board columns -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <template x-for="column in columns" :key="column.status">
            <div class="bg-gray-900/50 border border-gray-800/80 rounded-2xl overflow-hidden">
                <!-- Column header -->
                <div class="px-5 py-4 border-b border-gray-800/80">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full" :class="column.color"></span>
                            <h2 class="font-semibold text-sm text-gray-200" x-text="column.label"></h2>
                        </div>
                        <span class="text-xs font-medium text-gray-500 bg-gray-800/80 px-2.5 py-1 rounded-lg"
                              x-text="displayTasks(column.status).length"></span>
                    </div>
                </div>

                <!-- Tasks -->
                <div class="p-3 space-y-2.5 min-h-[200px]">
                    <template x-for="task in displayTasks(column.status)" :key="task.id">
                        <div class="bg-gray-800/60 border border-gray-700/50 rounded-xl p-4 group card-hover">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <h3 class="text-sm font-medium text-white leading-snug" x-text="task.title"></h3>
                                <button @click="deleteTask(task.id)"
                                        class="text-gray-600 hover:text-red-400 transition-all opacity-0 group-hover:opacity-100 shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <p x-show="task.description" class="text-xs text-gray-400 mb-3 leading-relaxed" x-text="task.description"></p>

                            <!-- Attachment badge -->
                            <a x-show="task.attachment_name"
                               :href="`/api/tasks/${task.id}/attachment`"
                               @click.stop
                               class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 bg-indigo-400/10 hover:bg-indigo-400/15 px-2.5 py-1 rounded-lg transition-all mb-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                                </svg>
                                <span x-text="task.attachment_name" class="truncate max-w-[150px]"></span>
                            </a>

                            <!-- Status buttons -->
                            <div class="flex gap-1.5 mt-2">
                                <template x-for="col in columns" :key="col.status">
                                    <button x-show="col.status !== task.status"
                                            @click="moveTask(task.id, col.status)"
                                            class="text-xs px-2.5 py-1 rounded-lg border border-gray-700/50 text-gray-500 hover:text-white hover:border-gray-600 hover:bg-gray-700/50 transition-all"
                                            x-text="col.shortLabel">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div x-show="displayTasks(column.status).length === 0"
                         class="flex flex-col items-center justify-center py-12 text-gray-600">
                        <svg class="w-8 h-8 mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                        </svg>
                        <span class="text-sm">No tasks</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Footer -->
    <footer class="mt-10 text-center">
        <div class="flex items-center justify-center gap-3 text-xs text-gray-600">
            <span>Built with Laravel, Reverb, Alpine.js</span>
            <span class="text-gray-800">&middot;</span>
            <span x-show="features.search" class="text-indigo-400/50">Meilisearch</span>
            <span x-show="features.search" class="text-gray-800">&middot;</span>
            <span x-show="features.pdf_export" class="text-purple-400/50">Gotenberg</span>
            <span x-show="features.pdf_export" class="text-gray-800">&middot;</span>
            <span>Deployed on the hosting platform</span>
        </div>
    </footer>
</div>

<script>
function taskBoard() {
    return {
        tasks: [],
        filteredTasks: [],
        searchQuery: '',
        searching: false,
        showForm: false,
        submitting: false,
        exporting: false,
        connected: false,
        toasts: [],
        features: { search: false, pdf_export: false },
        newTask: { title: '', description: '' },
        selectedFile: null,
        columns: [
            { status: 'todo', label: 'To Do', shortLabel: 'To Do', color: 'bg-blue-500' },
            { status: 'in-progress', label: 'In Progress', shortLabel: 'Progress', color: 'bg-amber-500' },
            { status: 'done', label: 'Done', shortLabel: 'Done', color: 'bg-emerald-500' },
        ],

        async init() {
            await Promise.all([this.fetchTasks(), this.fetchFeatures()]);
            this.connectWebSocket();
        },

        async fetchFeatures() {
            try {
                const res = await fetch('/api/features');
                this.features = await res.json();
            } catch (e) {
                console.error('Failed to fetch features:', e);
            }
        },

        async fetchTasks() {
            try {
                const res = await fetch('/api/tasks');
                this.tasks = await res.json();
            } catch (e) {
                console.error('Failed to fetch tasks:', e);
            }
        },

        displayTasks(status) {
            const source = this.searchQuery ? this.filteredTasks : this.tasks;
            return source.filter(t => t.status === status);
        },

        async performSearch() {
            if (!this.searchQuery.trim()) {
                this.filteredTasks = [];
                return;
            }
            this.searching = true;
            try {
                const res = await fetch(`/api/tasks/search?q=${encodeURIComponent(this.searchQuery)}`);
                this.filteredTasks = await res.json();
            } catch (e) {
                console.error('Search failed:', e);
            } finally {
                this.searching = false;
            }
        },

        clearSearch() {
            this.searchQuery = '';
            this.filteredTasks = [];
        },

        async exportPdf() {
            this.exporting = true;
            try {
                const res = await fetch('/api/tasks/export-pdf');
                if (res.ok) {
                    const blob = await res.blob();
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `task-board-report-${new Date().toISOString().slice(0,10)}.pdf`;
                    a.click();
                    URL.revokeObjectURL(url);
                    this.showToast('PDF report downloaded');
                } else {
                    this.showToast('PDF export unavailable');
                }
            } catch (e) {
                console.error('Export failed:', e);
                this.showToast('PDF export failed');
            } finally {
                this.exporting = false;
            }
        },

        openForm() {
            this.showForm = true;
            this.$nextTick(() => this.$refs.titleInput?.focus());
        },

        closeForm() {
            this.showForm = false;
            this.newTask = { title: '', description: '' };
            this.selectedFile = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) this.selectedFile = file;
        },

        handleDrop(event) {
            const file = event.dataTransfer.files[0];
            if (file) this.selectedFile = file;
        },

        removeFile() {
            this.selectedFile = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },

        async createTask() {
            if (!this.newTask.title.trim()) return;
            this.submitting = true;

            try {
                const formData = new FormData();
                formData.append('title', this.newTask.title);
                formData.append('description', this.newTask.description || '');
                if (this.selectedFile) {
                    formData.append('attachment', this.selectedFile);
                }

                const res = await fetch('/api/tasks', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (res.ok) {
                    const task = await res.json();
                    const exists = this.tasks.find(t => t.id === task.id);
                    if (!exists) {
                        this.tasks.unshift(task);
                    }
                    this.closeForm();
                } else if (res.status === 422) {
                    const data = await res.json();
                    const messages = Object.values(data.errors || {}).flat();
                    this.showToast(messages[0] || 'Validation failed', 'error');
                } else {
                    this.showToast(
                        'Upload failed — file too large',
                        'error',
                        'The server rejected the request. Increase upload_max_filesize and post_max_size in your PHP settings.'
                    );
                }
            } catch (e) {
                console.error('Failed to create task:', e);
                this.showToast(
                    'Upload failed — file too large',
                    'error',
                    'The server rejected the request. Increase upload_max_filesize and post_max_size in your PHP settings.'
                );
            } finally {
                this.submitting = false;
            }
        },

        async moveTask(taskId, newStatus) {
            const task = this.tasks.find(t => t.id === taskId);
            if (!task) return;

            const oldStatus = task.status;
            task.status = newStatus;

            try {
                const res = await fetch(`/api/tasks/${taskId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: newStatus }),
                });

                if (!res.ok) {
                    task.status = oldStatus;
                }
            } catch (e) {
                task.status = oldStatus;
                console.error('Failed to move task:', e);
            }
        },

        async deleteTask(taskId) {
            const task = this.tasks.find(t => t.id === taskId);
            this.tasks = this.tasks.filter(t => t.id !== taskId);

            try {
                const res = await fetch(`/api/tasks/${taskId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                if (!res.ok && task) {
                    this.tasks.unshift(task);
                }
            } catch (e) {
                if (task) this.tasks.unshift(task);
                console.error('Failed to delete task:', e);
            }
        },

        connectWebSocket() {
            try {
                const wsHost = window.location.hostname;
                const wsPort = window.location.port || (window.location.protocol === 'https:' ? 443 : 80);
                const wsScheme = window.location.protocol === 'https:' ? 'https' : 'http';

                window.Echo = new window.EchoConstructor({
                    broadcaster: 'reverb',
                    key: '{{ env("REVERB_APP_KEY", "demo-key") }}',
                    wsHost: wsHost,
                    wsPort: wsPort,
                    wssPort: wsPort,
                    forceTLS: wsScheme === 'https',
                    wsPath: '/app',
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                });

                window.Echo.connector.pusher.connection.bind('connected', () => {
                    this.connected = true;
                });
                window.Echo.connector.pusher.connection.bind('disconnected', () => {
                    this.connected = false;
                });

                window.Echo.channel('tasks')
                    .listen('TaskUpdated', (e) => {
                        this.handleEvent(e);
                    });
            } catch (e) {
                console.error('WebSocket connection failed:', e);
            }
        },

        handleEvent(e) {
            if (e.action === 'created' && e.task) {
                const exists = this.tasks.find(t => t.id === e.task.id);
                if (!exists) {
                    this.tasks.unshift(e.task);
                    this.showToast(`New task: ${e.task.title}`);
                }
            } else if (e.action === 'updated' && e.task) {
                const idx = this.tasks.findIndex(t => t.id === e.task.id);
                if (idx !== -1) {
                    this.tasks[idx] = e.task;
                    this.showToast(`Task updated: ${e.task.title}`);
                }
            } else if (e.action === 'deleted') {
                const id = e.task_id;
                this.tasks = this.tasks.filter(t => t.id !== id);
                this.showToast('Task deleted');
            }
        },

        showToast(message, type = 'info', hint = null) {
            const id = Date.now();
            const toast = { id, message, type, hint, visible: true };
            this.toasts.push(toast);
            const duration = type === 'error' ? 8000 : 3000;
            setTimeout(() => {
                toast.visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 300);
            }, duration);
        },
    };
}
</script>

</body>
</html>
