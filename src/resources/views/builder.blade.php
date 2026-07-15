<?php
// Otomatis Deteksi Seluruh Model di app/Models/
$modelsPath = app_path('Models');
$modelsList = [];
if (is_dir($modelsPath)) {
    $files = scandir($modelsPath);
    foreach ($files as $file) {
        if (str_ends_with($file, '.php')) {
            $modelsList[] = basename($file, '.php');
        }
    }
}
// Fallback jika belum ada model sama sekali
if (empty($modelsList)) {
    $modelsList = ['Mahasiswa', 'User', 'Product', 'Order'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nohara Genpack Form Builder</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-indigo-700 text-white shadow-md py-4 px-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-600 rounded-lg">
                <svg class="w-6 h-6 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight">Nohara Genpack</h1>
                <p class="text-xs text-indigo-200">Interactive Visual Form & CRUD Builder for Laravel</p>
            </div>
        </div>
        <span class="text-xs bg-indigo-600 px-3 py-1.5 rounded-full font-bold border border-indigo-500 text-indigo-100">Local Environment v1.0.1</span>
    </header>

    <!-- Main Workspace (AlpineJS State) -->
    <main x-data="{
        models: <?= json_encode($modelsList) ?>,
        selectedModel: '<?= $modelsList[0] ?? 'Mahasiswa' ?>',
        fields: [
            { name: 'nama', label: 'Nama Lengkap', type: 'text', placeholder: 'Masukkan nama lengkap...', required: true, col: 'col-md-6' },
            { name: 'email', label: 'Email Mahasiswa', type: 'text', placeholder: 'Masukkan email...', required: true, col: 'col-md-6' },
            { name: 'jenis_kelamin', label: 'Jenis Kelamin', type: 'select2', placeholder: 'Pilih jenis kelamin...', required: true, col: 'col-md-6' },
            { name: 'tanggal_lahir', label: 'Tanggal Lahir', type: 'flatpickr', placeholder: 'Pilih tanggal...', required: false, col: 'col-md-6' }
        ],
        newField: { name: '', label: '', type: 'text', placeholder: '', required: false, col: 'col-md-6' },
        activeTab: 'controller', // 'controller', 'migration', 'model'
        copied: false,

        addField() {
            if (!this.newField.name) {
                alert('Nama field wajib diisi!');
                return;
            }
            if (!this.newField.label) {
                this.newField.label = this.newField.name.charAt(0).toUpperCase() + this.newField.name.slice(1).replace('_', ' ');
            }
            this.fields.push({ ...this.newField });
            // Reset input form
            this.newField = { name: '', label: '', type: 'text', placeholder: '', required: false, col: 'col-md-6' };
        },

        removeField(index) {
            this.fields.splice(index, 1);
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },

        get controllerCode() {
            let modelClass = this.selectedModel;
            let controllerClass = modelClass + 'CrudController';
            let code = '<?php\n\n';
            code += 'namespace App\\Http\\Controllers;\n\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\BaseCrudController;\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\Operations\\ListOperation;\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\Operations\\CreateOperation;\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\Operations\\UpdateOperation;\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\Operations\\DeleteOperation;\n\n';
            code += 'class ' + controllerClass + ' extends BaseCrudController\n{\n';
            code += '    use ListOperation, CreateOperation, UpdateOperation, DeleteOperation;\n\n';
            code += '    public function setup()\n    {\n';
            code += '        $this->crud->setModel(\\\\App\\\\Models\\\\' + modelClass + '::class);\n';
            code += '        $this->crud->setRoutePrefix(\'' + modelClass.toLowerCase() + '\');\n';
            code += '        $this->crud->setEntityNameStrings(\'' + modelClass + '\', \'' + modelClass + 's\');\n\n';
            
            this.fields.forEach(f => {
                code += '        $this->crud->addField([\n';
                code += '            \'name\' => \'' + f.name + '\',\n';
                code += '            \'label\' => \'' + f.label + '\',\n';
                code += '            \'type\' => \'' + f.type + '\',\n';
                if (f.placeholder) {
                    code += '            \'placeholder\' => \'' + f.placeholder + '\',\n';
                }
                if (f.required) {
                    code += '            \'required\' => true,\n';
                }
                code += '            \'col\' => \'' + f.col + '\'\n';
                code += '        ]);\n\n';
            });
            code += '    }\n}';
            return code;
        },

        get migrationCode() {
            let tableName = this.selectedModel.toLowerCase() + 's';
            let code = '<?php\n\n';
            code += 'use Illuminate\\Database\\Migrations\\Migration;\n';
            code += 'use Illuminate\\Database\\Schema\\Blueprint;\n';
            code += 'use Illuminate\\Support\\Facades\\Schema;\n\n';
            code += 'return new class extends Migration\n{\n';
            code += '    public function up(): void\n    {\n';
            code += '        Schema::create(\'' + tableName + '\', function (Blueprint $table) {\n';
            code += '            $table->id();\n';
            
            this.fields.forEach(f => {
                let mType = 'string';
                if (f.type === 'textarea') mType = 'text';
                else if (f.type === 'number') mType = 'integer';
                else if (f.type === 'checkbox') mType = 'boolean';
                else if (f.type === 'flatpickr') mType = 'date';
                
                let line = '            $table->' + mType + '(\'' + f.name + '\')';
                if (!f.required) {
                    line += '->nullable()';
                }
                code += line + ';\n';
            });
            
            code += '            $table->timestamps();\n';
            code += '        });\n    }\n};';
            return code;
        },

        get modelCode() {
            let modelClass = this.selectedModel;
            let fieldsStr = this.fields.map(f => '\'' + f.name + '\'').join(', ');
            let code = '<?php\n\n';
            code += 'namespace App\\Models;\n\n';
            code += 'use Illuminate\\Database\\Eloquent\\Model;\n\n';
            code += 'class ' + modelClass + ' extends Model\n{\n';
            code += '    protected $fillable = [\n';
            code += '        ' + fieldsStr + '\n';
            code += '    ];\n}';
            return code;
        }
    }" class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 p-6">

        <!-- Sidebar / Field Configurator (4 columns) -->
        <section class="lg:col-span-4 bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-6 self-start">
            <!-- Model Dropdown (Deteksi Otomatis) -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 space-y-2.5">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <label class="block text-[11px] font-bold text-indigo-800 uppercase tracking-wider">🎯 Model Terdeteksi (Tinggal Pilih)</label>
                </div>
                <select x-model="selectedModel" class="w-full text-xs border border-indigo-200 rounded-lg p-2.5 bg-white focus:ring-2 focus:ring-indigo-500 outline-none font-semibold text-slate-700 cursor-pointer">
                    <template x-for="m in models">
                        <option :value="m" x-text="'App\\Models\\' + m" :selected="m === selectedModel"></option>
                    </template>
                </select>
                <p class="text-[10px] text-indigo-600/80 leading-normal">
                    *Membaca folder <code class="font-mono bg-indigo-100 px-1 py-0.5 rounded">app/Models/</code> secara otomatis. Pilih model untuk menyesuaikan controller, migration, dan class model yang digenerate.
                </p>
            </div>

            <hr class="border-slate-100" />

            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">🔧 Tambah Field Baru</h2>
                <p class="text-xs text-slate-500 mt-0.5">Konfigurasi data kolom form input baru</p>
            </div>

            <!-- Form input field kustom -->
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kolom (Database Field)</label>
                    <input type="text" x-model="newField.name" placeholder="contoh: alamat_rumah, usia, foto_profil" class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Label Formulir (User Friendly)</label>
                    <input type="text" x-model="newField.label" placeholder="contoh: Alamat Rumah, Usia" class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Input</label>
                        <select x-model="newField.type" class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="text">Text (Default)</option>
                            <option value="textarea">Textarea (Keterangan)</option>
                            <option value="number">Number</option>
                            <option value="select2">Select2 (Drop-down)</option>
                            <option value="flatpickr">Flatpickr (Tanggal)</option>
                            <option value="checkbox">Checkbox (Boolean)</option>
                            <option value="file">File/Gambar</option>
                            <option value="dynamic_list">Dynamic List</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Grid Lebar</label>
                        <select x-model="newField.col" class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="col-md-6">1/2 Lebar (col-md-6)</option>
                            <option value="col-md-12">Penuh (col-md-12)</option>
                            <option value="col-md-4">1/3 Lebar (col-md-4)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Placeholder (Keterangan Input)</label>
                    <input type="text" x-model="newField.placeholder" placeholder="contoh: Masukkan alamat..." class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="flex items-center gap-2 py-1">
                    <input type="checkbox" x-model="newField.required" id="reqCheckbox" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                    <label for="reqCheckbox" class="text-xs font-bold text-slate-700 select-none cursor-pointer">Wajib diisi (Required Validation)</label>
                </div>

                <button @click="addField()" class="w-full bg-indigo-700 hover:bg-indigo-800 text-white font-bold text-xs py-3 rounded-lg shadow transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambahkan ke Formulir
                </button>
            </div>

            <div class="border-t border-slate-100 pt-5 space-y-3">
                <h3 class="font-bold text-xs text-slate-800 uppercase tracking-wide">📦 Kolom Terpasang (<span x-text="fields.length"></span>)</h3>
                <div class="space-y-2 max-h-[220px] overflow-y-auto scrollbar-thin">
                    <template x-for="(field, index) in fields" :key="index">
                        <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-xs">
                            <div class="truncate">
                                <span class="font-bold text-slate-800 block truncate" x-text="field.label"></span>
                                <span class="font-mono text-[10px] text-slate-400" x-text="field.name + ' • ' + field.type"></span>
                            </div>
                            <button @click="removeField(index)" class="text-rose-500 hover:text-rose-700 font-bold px-2 py-1 hover:bg-rose-50 rounded transition-all cursor-pointer">
                                Hapus
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- Right Preview Workspace (8 columns) -->
        <section class="lg:col-span-8 flex flex-col gap-6">

            <!-- Realtime Responsive UI Form Preview -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">🖥️ Live Responsive Form Preview</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Representasi form bootstrap yang terdeteksi oleh sistem Nohara</p>
                    </div>
                    <span class="text-[10px] font-bold bg-indigo-50 border border-indigo-200 text-indigo-700 px-2 py-1 rounded">Interactive Preview</span>
                </div>

                <!-- Form Render Area -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-slate-50/50 p-4 rounded-xl border border-dashed border-slate-200">
                    <template x-for="(field, index) in fields" :key="index">
                        <div :class="field.col === 'col-md-6' ? 'md:col-span-6' : field.col === 'col-md-4' ? 'md:col-span-4' : 'md:col-span-12'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                <span x-text="field.label"></span>
                                <template x-if="field.required">
                                    <span class="text-rose-500 font-bold">*</span>
                                </template>
                            </label>

                            <!-- Different Input Renders -->
                            <template x-if="field.type === 'text'">
                                <input type="text" :placeholder="field.placeholder || 'Masukkan data...'" class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none">
                            </template>
                            <template x-if="field.type === 'textarea'">
                                <textarea :placeholder="field.placeholder || 'Masukkan deskripsi...'" class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none h-16"></textarea>
                            </template>
                            <template x-if="field.type === 'number'">
                                <input type="number" :placeholder="field.placeholder || '0'" class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none">
                            </template>
                            <template x-if="field.type === 'select2'">
                                <select class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none">
                                    <option x-text="field.placeholder || 'Pilih opsi...'"></option>
                                    <option value="1">Pilihan Default 1</option>
                                </select>
                            </template>
                            <template x-if="field.type === 'flatpickr'">
                                <div class="relative">
                                    <input type="text" :placeholder="field.placeholder || 'Pilih Tanggal...'" class="w-full text-xs border border-slate-200 rounded-lg p-2.5 pl-9 bg-white shadow-sm outline-none">
                                    <span class="absolute left-3 top-3 text-slate-400 font-bold text-xs">📅</span>
                                </div>
                            </template>
                            <template x-if="field.type === 'checkbox'">
                                <div class="flex items-center gap-2 py-3">
                                    <input type="checkbox" class="w-4 h-4 rounded text-indigo-600 border-slate-300">
                                    <span class="text-xs text-slate-600">Klik untuk menyetujui kriteria</span>
                                </div>
                            </template>
                            <template x-if="field.type === 'file'">
                                <div class="border border-dashed border-slate-300 bg-white p-3 rounded-lg text-center cursor-pointer hover:border-indigo-500 transition-all">
                                    <span class="text-xs text-indigo-600 font-bold block">📁 Unggah File/Gambar</span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Seret file ke sini atau klik</span>
                                </div>
                            </template>
                            <template x-if="field.type === 'dynamic_list'">
                                <div class="bg-indigo-50/50 border border-indigo-100 rounded-lg p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-indigo-700 uppercase" x-text="field.label + ' Item List'"></span>
                                        <button class="bg-indigo-600 text-white font-bold text-[10px] px-2 py-1 rounded shadow cursor-pointer">+ Tambah Baris</button>
                                    </div>
                                    <input type="text" placeholder="Masukkan baris baru..." class="w-full text-xs border border-slate-200 rounded-lg p-2 bg-white outline-none">
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Generated Codes Terminal panel -->
            <div class="bg-slate-900 border border-slate-950 rounded-xl p-5 shadow-lg space-y-4 flex-1 flex flex-col min-h-[350px]">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                    <div>
                        <h2 class="font-bold text-xs text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                            ⚡ Realtime PHP & Laravel Code Engine
                        </h2>
                    </div>

                    <!-- Code Tabs -->
                    <div class="flex items-center gap-1 bg-slate-850 p-1 rounded-lg border border-slate-800 self-start">
                        <button @click="activeTab = 'controller'" :class="activeTab === 'controller' ? 'bg-slate-800 text-indigo-400' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1 rounded text-[10.5px] font-bold cursor-pointer transition-all">
                            CrudController.php
                        </button>
                        <button @click="activeTab = 'migration'" :class="activeTab === 'migration' ? 'bg-slate-800 text-indigo-400' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1 rounded text-[10.5px] font-bold cursor-pointer transition-all">
                            Migration.php
                        </button>
                        <button @click="activeTab = 'model'" :class="activeTab === 'model' ? 'bg-slate-800 text-indigo-400' : 'text-slate-400 hover:text-slate-200'" class="px-2.5 py-1 rounded text-[10.5px] font-bold cursor-pointer transition-all">
                            Model.php
                        </button>
                    </div>
                </div>

                <!-- Code Terminal Content -->
                <div class="relative flex-1 bg-slate-950 rounded-xl p-4 overflow-y-auto font-mono text-[11px] text-indigo-200/90 leading-relaxed max-h-[300px] scrollbar-thin">
                    
                    <!-- Copy Button -->
                    <button @click="
                        if (activeTab === 'controller') copyToClipboard(controllerCode);
                        if (activeTab === 'migration') copyToClipboard(migrationCode);
                        if (activeTab === 'model') copyToClipboard(modelCode);
                    " class="absolute right-3 top-3 bg-slate-800 hover:bg-slate-700 text-white font-bold text-[10px] px-3 py-1.5 rounded-lg transition-all flex items-center gap-1 cursor-pointer">
                        <template x-if="!copied">
                            <span>📋 Copy Code</span>
                        </template>
                        <template x-if="copied">
                            <span class="text-emerald-400 font-bold">✔️ Copied!</span>
                        </template>
                    </button>

                    <!-- Render Codes -->
                    <template x-if="activeTab === 'controller'">
                        <pre class="whitespace-pre" x-text="controllerCode"></pre>
                    </template>
                    <template x-if="activeTab === 'migration'">
                        <pre class="whitespace-pre" x-text="migrationCode"></pre>
                    </template>
                    <template x-if="activeTab === 'model'">
                        <pre class="whitespace-pre" x-text="modelCode"></pre>
                    </template>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-3 text-center text-[11px] text-slate-400 mt-auto">
        &copy; {{ date('Y') }} Nohara Genpack - Crafted for Modern & Rapid Laravel CRUD.
    </footer>

</body>
</html>
