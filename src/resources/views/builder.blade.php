<?php
// Otomatis Deteksi Seluruh Model di app/Models/ (termasuk subfolder)
$modelsPath = app_path('Models');
$modelsList = [];
$modelColumns = [];

if (is_dir($modelsPath)) {
    $files = Illuminate\Support\Facades\File::allFiles($modelsPath);
    foreach ($files as $file) {
        if (str_ends_with($file->getFilename(), '.php')) {
            $relativePath = $file->getRelativePathname();
            $relativePathWithoutExtension = substr($relativePath, 0, -4);
            $modelName = str_replace('/', '\\', $relativePathWithoutExtension);
            $modelsList[] = $modelName;

            // Ambil kolom database untuk model ini
            try {
                $className = 'App\\Models\\' . $modelName;
                if (class_exists($className)) {
                    $instance = new $className();
                    $table = $instance->getTable();
                    $columns = Illuminate\Support\Facades\Schema::getColumns($table);

                    $colsData = [];
                    foreach ($columns as $col) {
                        $typeName = strtolower($col['type_name']);
                        $suggestedType = 'text';
                        if (in_array($typeName, ['bool', 'boolean'])) {
                            $suggestedType = 'checkbox';
                        } elseif (in_array($typeName, ['int', 'int4', 'int8', 'integer', 'bigint', 'numeric', 'float', 'double', 'decimal'])) {
                            $suggestedType = 'number';
                        } elseif (in_array($typeName, ['date', 'time', 'timestamp', 'datetime'])) {
                            $suggestedType = 'flatpickr';
                        } elseif (in_array($typeName, ['text', 'longtext', 'mediumtext'])) {
                            $suggestedType = 'textarea';
                        }

                        $colsData[] = [
                            'name' => $col['name'],
                            'type' => $suggestedType,
                            'required' => empty($col['nullable']) ? true : false,
                        ];
                    }
                    $modelColumns[$modelName] = $colsData;
                }
            } catch (\Throwable $e) {
                // Diabaikan jika tabel belum ada atau error lainnya
            }
        }
    }
    // Urutkan alfabetis agar lebih rapi
    sort($modelsList);
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
                <svg class="w-6 h-6 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                    </path>
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight">Nohara Genpack</h1>
                <p class="text-xs text-indigo-200">Interactive Visual Form & CRUD Builder for Laravel</p>
            </div>
        </div>
        <span
            class="text-xs bg-indigo-600 px-3 py-1.5 rounded-full font-bold border border-indigo-500 text-indigo-100">Local
            Environment v1.0.1</span>
    </header>

    <!-- Main Workspace (AlpineJS State) -->
    <main x-data="{
        models: {{ json_encode($modelsList) }},
        selectedModel: {{ Js::from($modelsList[0] ?? 'Mahasiswa') }},
        fields: [
            { name: 'nama', label: 'Nama Lengkap', type: 'text', placeholder: 'Masukkan nama lengkap...', required: true, col: 'col-md-6' },
            { name: 'email', label: 'Email Mahasiswa', type: 'text', placeholder: 'Masukkan email...', required: true, col: 'col-md-6' },
            { name: 'jenis_kelamin', label: 'Jenis Kelamin', type: 'select2', placeholder: 'Pilih jenis kelamin...', required: true, col: 'col-md-6' },
            { name: 'tanggal_lahir', label: 'Tanggal Lahir', type: 'flatpickr', placeholder: 'Pilih tanggal...', required: false, col: 'col-md-6' }
        ],
        newField: { name: '', label: '', type: 'text', placeholder: '', required: false, col: 'col-md-6', dependsOn: '', entity: '', attribute: 'nama', model: '' },
        activeTab: 'controller',
        copied: false,
        modelsColumns: {{ json_encode($modelColumns) }},
        themeStyle: 'classic',

        get dbColumns() {
            return this.modelsColumns[this.selectedModel] || [];
        },

        selectDbColumn(event) {
            let colName = event.target.value;
            if (!colName) return;
            let colData = this.dbColumns.find(c => c.name === colName);
            if (colData) {
                this.newField.name = colData.name;
                this.newField.type = colData.type;
                this.newField.required = colData.required;
                this.newField.dependsOn = '';
                this.newField.entity = '';
                this.newField.attribute = 'nama';
                this.newField.model = '';

                if (colData.name.endsWith('_id')) {
                    this.newField.type = 'select2_relationship';
                    let relBase = colData.name.slice(0, -3);
                    let relClean = relBase.replace(/^api_/, '');
                    let relCamel = relClean.replace(/_([a-z])/g, g => g[1].toUpperCase());
                    this.newField.entity = relCamel;

                    let matched = this.models.find(m => {
                        let mClean = m.replace(/.*\\/, '').replace(/^M_Api/, '').replace(/^M_/, '').toLowerCase();
                        return mClean === relClean.replace(/_/g, '') || mClean === relBase.replace(/_/g, '');
                    });

                    if (matched) {
                        this.newField.model = matched;
                        let relatedCols = this.modelsColumns[matched] || [];
                        let hasNama = relatedCols.some(c => c.name === 'nama');
                        let hasName = relatedCols.some(c => c.name === 'name');
                        if (hasNama) this.newField.attribute = 'nama';
                        else if (hasName) this.newField.attribute = 'name';
                    }
                }

                let label = colData.name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                if (label.endsWith(' Id')) {
                    label = label.slice(0, -3) + ' ID';
                }
                this.newField.label = label;
                this.newField.placeholder = 'Pilih ' + label.toLowerCase() + '...';
            }
        },

        addField() {
            if (!this.newField.name) {
                alert('Nama field wajib diisi!');
                return;
            }
            if (!this.newField.label) {
                this.newField.label = this.newField.name.charAt(0).toUpperCase() + this.newField.name.slice(1).replace('_', ' ');
            }
            this.fields.push({ ...this.newField });
            this.newField = { name: '', label: '', type: 'text', placeholder: '', required: false, col: 'col-md-6', dependsOn: '', entity: '', attribute: 'nama', model: '' };
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
            let parts = modelClass.split('\\');
            let modelBaseName = parts[parts.length - 1];
            let subNamespace = parts.length > 1 ? '\\' + parts.slice(0, -1).join('\\') : '';
            
            let controllerClass = modelBaseName + 'CrudController';
            let code = '<' + '?php\n\n';
            code += 'namespace App\\Http\\Controllers' + subNamespace + ';\n\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\BaseCrudController;\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\Operations\\ListOperation;\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\Operations\\CreateOperation;\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\Operations\\UpdateOperation;\n';
            code += 'use Nohara\\Genpack\\Http\\Controllers\\Operations\\DeleteOperation;\n\n';
            code += 'class ' + controllerClass + ' extends BaseCrudController\n{\n';
            code += '    use ListOperation, CreateOperation, UpdateOperation, DeleteOperation;\n\n';
            code += '    public function setup()\n    {\n';
            code += '        $this->crud->setModel(\\\\App\\\\Models\\\\' + modelClass + '::class);\n';
            code += '        $this->crud->setRoutePrefix(\'' + modelBaseName.toLowerCase() + '\');\n';
            code += '        $this->crud->setEntityNameStrings(\'' + modelBaseName + '\', \'' + modelBaseName + 's\');\n\n';
            code += '        $this->crud->setModalConfig([\n';
            code += '            \'size\' => \'modal-lg\',\n';
            code += '            \'fullscreen\' => false,\n';
            code += '            \'scrollable\' => true\n';
            code += '        ])->setThemeStyle(\'' + this.themeStyle + '\');\n\n';

            this.fields.forEach(f => {
                code += '        $this->crud->addField([\n';
                code += '            \'name\' => \'' + f.name + '\',\n';
                code += '            \'label\' => \'' + f.label + '\',\n';
                if (f.type === 'select2_relationship') {
                    code += '            \'type\' => \'select2\',\n';
                    if (f.entity) {
                        code += '            \'entity\' => \'' + f.entity + '\',\n';
                    }
                    if (f.attribute) {
                        code += '            \'attribute\' => \'' + f.attribute + '\',\n';
                    }
                    if (f.model) {
                        code += '            \'model\' => \\\\App\\\\Models\\\\' + f.model + '::class,\n';
                    }
                } else {
                    code += '            \'type\' => \'' + f.type + '\',\n';
                }
                if (f.placeholder) {
                    code += '            \'placeholder\' => \'' + f.placeholder + '\',\n';
                }
                if (f.required) {
                    code += '            \'required\' => true,\n';
                }
                if (f.type === 'select2_dependent' && f.dependsOn) {
                    code += '            \'dependsOn\' => \'' + f.dependsOn + '\',\n';
                }
                code += '            \'col\' => \'' + f.col + '\'\n';
                code += '        ]);\n\n';
            });
            code += '    }\n}';
            return code;
        },

        get migrationCode() {
            let modelClass = this.selectedModel;
            let parts = modelClass.split('\\');
            let modelBaseName = parts[parts.length - 1];
            let tableName = modelBaseName.toLowerCase() + 's';
            let code = '<' + '?php\n\n';
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
                else if (f.type === 'select2_multiple') mType = 'json';
                else if (f.type === 'select2_relationship') mType = 'bigInteger';

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
            let parts = modelClass.split('\\');
            let modelBaseName = parts[parts.length - 1];
            let subNamespace = parts.length > 1 ? '\\' + parts.slice(0, -1).join('\\') : '';
            
            let fieldsStr = this.fields.map(f => '\'' + f.name + '\'').join(', ');
            let code = '<' + '?php\n\n';
            code += 'namespace App\\Models' + subNamespace + ';\n\n';
            code += 'use Illuminate\\Database\\Eloquent\\Model;\n\n';
            code += 'class ' + modelBaseName + ' extends Model\n{\n';
            code += '    protected $fillable = [\n';
            code += '        ' + fieldsStr + '\n';
            code += '    ];\n}';
            return code;
        }
    }" class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 p-6">

        <!-- Sidebar / Field Configurator (4 columns) -->
        <section class="lg:col-span-4 bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-6 self-start">
            <!-- Model Dropdown (Deteksi Otomatis) -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 space-y-2.5"
                x-data="{ isOpen: false, searchQuery: '' }"
                @click.away="isOpen = false">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                    <label class="block text-[11px] font-bold text-indigo-800 uppercase tracking-wider">🎯 Model
                        Terdeteksi (Tinggal Pilih)</label>
                </div>
                <div class="relative">
                    <button type="button" @click="isOpen = !isOpen"
                        class="w-full flex items-center justify-between text-xs border border-indigo-200 rounded-lg p-2.5 bg-white focus:ring-2 focus:ring-indigo-500 outline-none font-semibold text-slate-700 cursor-pointer text-left shadow-sm">
                        <span x-text="'App\\Models\\' + selectedModel"></span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="isOpen"
                        x-transition
                        class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg p-1.5 space-y-1">
                        <input type="text" x-model="searchQuery" placeholder="Cari model..."
                            @click.stop
                            class="w-full text-xs border border-slate-250 rounded-md p-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none mb-1 font-medium">
                        <div class="max-h-48 overflow-y-auto space-y-0.5 scrollbar-thin">
                            <template x-for="m in models.filter(item => ('App\\Models\\' + item).toLowerCase().includes(searchQuery.toLowerCase()))">
                                <button type="button"
                                    @click="selectedModel = m; isOpen = false; searchQuery = '';"
                                    :class="selectedModel === m ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-750 hover:bg-slate-50'"
                                    class="w-full text-left text-xs p-2 rounded-md transition-all font-semibold block truncate cursor-pointer">
                                    <span x-text="'App\\Models\\' + m"></span>
                                </button>
                            </template>
                            <template x-if="models.filter(item => ('App\\Models\\' + item).toLowerCase().includes(searchQuery.toLowerCase())).length === 0">
                                <div class="text-[11px] text-slate-400 text-center py-3">Model tidak ditemukan</div>
                            </template>
                        </div>
                    </div>
                </div>
                <p class="text-[10px] text-indigo-600/80 leading-normal">
                    *Membaca folder <code class="font-mono bg-indigo-100 px-1 py-0.5 rounded">app/Models/</code> secara
                    otomatis. Pilih model untuk menyesuaikan controller, migration, dan class model yang digenerate.
                </p>
            </div>

            <!-- Theme Style Selection -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">🎨 Gaya Desain Tema (Theme Style)</label>
                <select x-model="themeStyle"
                    class="w-full text-xs border border-indigo-200 rounded-lg p-2.5 bg-white focus:ring-2 focus:ring-indigo-500 outline-none font-semibold text-slate-700 cursor-pointer shadow-sm">
                    <option value="classic">Standard Bootstrap 5 (Classic Accent)</option>
                    <option value="porto">Porto Admin Theme v2.2 (Section Card, FontAwesome)</option>
                    <option value="akorn">Akorn Admin Theme (Elegant Minimalist Card, Rounded Accent)</option>
                </select>
                <p class="text-[10px] text-indigo-600/80 leading-normal">*Menyesuaikan layout template controller CRUD yang digenerate.</p>
            </div>

            <hr class="border-slate-100" />

            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">🔧 Tambah Field Baru</h2>
                <p class="text-xs text-slate-500 mt-0.5">Konfigurasi data kolom form input baru</p>
            </div>

            <!-- Form input field kustom -->
            <div class="space-y-4">
                <template x-if="dbColumns.length > 0">
                    <div>
                        <label class="block text-[10px] font-bold text-indigo-700 uppercase tracking-wider mb-1">💡 Pilih dari Kolom Database</label>
                        <select @change="selectDbColumn($event)"
                            class="w-full text-xs border border-indigo-200 rounded-lg p-2.5 bg-indigo-50/50 hover:bg-indigo-50 focus:ring-2 focus:ring-indigo-500 outline-none font-semibold text-slate-700 cursor-pointer">
                            <option value="">-- Pilih kolom untuk auto-fill (Opsional) --</option>
                            <template x-for="col in dbColumns">
                                <option :value="col.name" x-text="col.name + ' (' + col.type + (col.required ? ' • required' : '') + ')'"></option>
                            </template>
                        </select>
                    </div>
                </template>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kolom (Database Field)</label>
                    <input type="text" x-model="newField.name" placeholder="contoh: alamat_rumah, usia, foto_profil"
                        class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Label Formulir (User Friendly)</label>
                    <input type="text" x-model="newField.label" placeholder="contoh: Alamat Rumah, Usia"
                        class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tipe Input</label>
                        <select x-model="newField.type"
                            class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="text">Text (Default)</option>
                            <option value="textarea">Textarea (Keterangan)</option>
                            <option value="number">Number</option>
                            <option value="select2">Select2 (Drop-down)</option>
                            <option value="select2_multiple">Select2 (Multiple Tagging)</option>
                            <option value="select2_dependent">Select2 Dependent (Terikat)</option>
                            <option value="select2_relationship">Select2 Reference (Relasi Model)</option>
                            <option value="flatpickr">Flatpickr (Tanggal)</option>
                            <option value="checkbox">Checkbox (Boolean)</option>
                            <option value="file">File/Gambar</option>
                            <option value="dynamic_list">Dynamic List</option>
                            <option value="password">Password</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Grid Lebar</label>
                        <select x-model="newField.col"
                            class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="col-md-6">1/2 Lebar (col-md-6)</option>
                            <option value="col-md-12">Penuh (col-md-12)</option>
                            <option value="col-md-4">1/3 Lebar (col-md-4)</option>
                            <option value="col-md-3">1/4 Lebar (col-md-3)</option>
                            <option value="col-md-8">2/3 Lebar (col-md-8)</option>
                            <option value="col-md-9">3/4 Lebar (col-md-9)</option>
                        </select>
                    </div>
                </div>

                <!-- Dependent Selector (Depends On) -->
                <template x-if="newField.type === 'select2_dependent'">
                    <div class="p-3.5 bg-indigo-50/50 border border-indigo-100 rounded-xl space-y-1.5">
                        <label class="block text-[11px] font-bold text-indigo-800 uppercase tracking-wider">🔗 Terikat
                            dengan Field (Depends On)</label>
                        <select x-model="newField.dependsOn"
                            class="w-full text-xs border border-indigo-200 bg-white rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none font-semibold text-slate-700 cursor-pointer">
                            <option value="">-- Pilih Field Induk --</option>
                            <template x-for="f in fields">
                                <option :value="f.name" x-text="f.label + ' (' + f.name + ')'"></option>
                            </template>
                        </select>
                        <p class="text-[10px] text-indigo-600/80 leading-normal">*Tentukan field induk yang mengontrol
                            pilihan dropdown ini.</p>
                    </div>
                </template>

                <!-- Relationship Selector -->
                <template x-if="newField.type === 'select2_relationship'">
                    <div class="p-3.5 bg-indigo-50/50 border border-indigo-100 rounded-xl space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold text-indigo-800 uppercase tracking-wider mb-1">📦 Model Relasi (Related Model)</label>
                            <select x-model="newField.model"
                                class="w-full text-xs border border-indigo-200 bg-white rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none font-semibold text-slate-700 cursor-pointer">
                                <option value="">-- Pilih Model --</option>
                                <template x-for="m in models">
                                    <option :value="m" x-text="'App\\Models\\' + m"></option>
                                </template>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-800 uppercase tracking-wider mb-1">🔗 Method Relasi</label>
                                <input type="text" x-model="newField.entity" placeholder="contoh: tahunAjaran"
                                    class="w-full text-xs border border-indigo-200 bg-white rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none font-semibold text-slate-700">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-800 uppercase tracking-wider mb-1">🏷️ Atribut Relasi</label>
                                <input type="text" x-model="newField.attribute" placeholder="contoh: nama"
                                    class="w-full text-xs border border-indigo-200 bg-white rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none font-semibold text-slate-700">
                            </div>
                        </div>
                        <p class="text-[10px] text-indigo-600/80 leading-normal">*Pilih model relasi, isi nama method Eloquent relationship, dan kolom yang ditampilkan.</p>
                    </div>
                </template>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Placeholder (Keterangan Input)</label>
                    <input type="text" x-model="newField.placeholder" placeholder="contoh: Masukkan alamat..."
                        class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="flex items-center gap-2 py-1">
                    <input type="checkbox" x-model="newField.required" id="reqCheckbox"
                        class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                    <label for="reqCheckbox" class="text-xs font-bold text-slate-700 select-none cursor-pointer">Wajib
                        diisi (Required Validation)</label>
                </div>

                <button @click="addField()"
                    class="w-full bg-indigo-700 hover:bg-indigo-800 text-white font-bold text-xs py-3 rounded-lg shadow transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                        </path>
                    </svg>
                    Tambahkan ke Formulir
                </button>
            </div>

            <div class="border-t border-slate-100 pt-5 space-y-3">
                <h3 class="font-bold text-xs text-slate-800 uppercase tracking-wide">📦 Kolom Terpasang (<span
                        x-text="fields.length"></span>)</h3>
                <div class="space-y-2 max-h-[220px] overflow-y-auto scrollbar-thin">
                    <template x-for="(field, index) in fields" :key="index">
                        <div
                            class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-xs">
                            <div class="truncate">
                                <span class="font-bold text-slate-800 block truncate" x-text="field.label"></span>
                                <span class="font-mono text-[10px] text-slate-400"
                                    x-text="field.name + ' • ' + field.type"></span>
                            </div>
                            <button @click="removeField(index)"
                                class="text-rose-500 hover:text-rose-700 font-bold px-2 py-1 hover:bg-rose-50 rounded transition-all cursor-pointer">
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
                        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">🖥️ Live Responsive Form
                            Preview</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Representasi form bootstrap yang terdeteksi oleh
                            sistem Nohara</p>
                    </div>
                    <span
                        class="text-[10px] font-bold bg-indigo-50 border border-indigo-200 text-indigo-700 px-2 py-1 rounded">Interactive
                        Preview</span>
                </div>

                <!-- Form Render Area -->
                <div
                    class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-slate-50/50 p-4 rounded-xl border border-dashed border-slate-200">
                    <template x-for="(field, index) in fields" :key="index">
                        <div
                            :class="field.col === 'col-md-6' ? 'md:col-span-6' : field.col === 'col-md-4' ? 'md:col-span-4' :
                                field.col === 'col-md-3' ? 'md:col-span-3' : field.col === 'col-md-8' ?
                                'md:col-span-8' : field.col === 'col-md-9' ? 'md:col-span-9' : 'md:col-span-12'">
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                <span x-text="field.label"></span>
                                <template x-if="field.required">
                                    <span class="text-rose-500 font-bold">*</span>
                                </template>
                            </label>

                            <!-- Different Input Renders -->
                            <template x-if="field.type === 'text'">
                                <input type="text" :placeholder="field.placeholder || 'Masukkan data...'"
                                    class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none">
                            </template>
                            <template x-if="field.type === 'textarea'">
                                <textarea :placeholder="field.placeholder || 'Masukkan deskripsi...'"
                                    class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none h-16"></textarea>
                            </template>
                            <template x-if="field.type === 'number'">
                                <input type="number" :placeholder="field.placeholder || '0'"
                                    class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none">
                            </template>
                            <template x-if="field.type === 'select2'">
                                <select
                                    class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none">
                                    <option x-text="field.placeholder || 'Pilih opsi...'"></option>
                                    <option value="1">Pilihan Default 1</option>
                                </select>
                            </template>
                            <template x-if="field.type === 'select2_multiple'">
                                <div
                                    class="w-full border border-slate-200 rounded-lg p-2 bg-white shadow-sm flex flex-wrap gap-1 items-center min-h-[38px]">
                                    <span
                                        class="bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded font-semibold flex items-center gap-1">Opsi
                                        Terpilih 1 <span class="text-indigo-400 font-bold">×</span></span>
                                    <span
                                        class="bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded font-semibold flex items-center gap-1">Opsi
                                        Terpilih 2 <span class="text-indigo-400 font-bold">×</span></span>
                                    <input type="text" placeholder="Cari..."
                                        class="border-0 p-0 text-xs focus:ring-0 focus:outline-none flex-1 min-w-[60px] bg-transparent">
                                </div>
                            </template>
                            <template x-if="field.type === 'select2_dependent'">
                                <div class="space-y-1.5">
                                    <select
                                        class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none">
                                        <option x-text="field.placeholder || 'Pilih opsi (Terikat)...'"></option>
                                        <option value="1">Pilihan Tergantung Nilai Induk</option>
                                    </select>
                                    <span class="text-[9px] text-indigo-600 block leading-tight font-medium"
                                        x-text="'*Terikat dengan field: ' + (field.dependsOn || 'Belum dipilih')"></span>
                                </div>
                            </template>
                            <template x-if="field.type === 'select2_relationship'">
                                <div class="space-y-1.5">
                                    <select
                                        class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none font-semibold text-slate-700">
                                        <option x-text="field.placeholder || 'Pilih opsi (Relasi)...'"></option>
                                        <option value="1" x-text="'Pilihan dari ' + (field.model ? 'App\\Models\\' + field.model : 'Model Relasi')"></option>
                                    </select>
                                    <span class="text-[9px] text-indigo-600 block leading-tight font-medium"
                                        x-text="'*Relasi: App\\Models\\' + (field.model || '...') + ' (' + (field.entity || '...') + ')'"></span>
                                </div>
                            </template>
                            <template x-if="field.type === 'flatpickr'">
                                <div class="relative">
                                    <input type="text" :placeholder="field.placeholder || 'Pilih Tanggal...'"
                                        class="w-full text-xs border border-slate-200 rounded-lg p-2.5 pl-9 bg-white shadow-sm outline-none">
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
                                <div
                                    class="border border-dashed border-slate-300 bg-white p-3 rounded-lg text-center cursor-pointer hover:border-indigo-500 transition-all">
                                    <span class="text-xs text-indigo-600 font-bold block">📁 Unggah File/Gambar</span>
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Seret file ke sini atau
                                        klik</span>
                                </div>
                            </template>
                            <template x-if="field.type === 'dynamic_list'">
                                <div class="bg-indigo-50/50 border border-indigo-100 rounded-lg p-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-indigo-700 uppercase"
                                            x-text="field.label + ' Item List'"></span>
                                        <button
                                            class="bg-indigo-600 text-white font-bold text-[10px] px-2 py-1 rounded shadow cursor-pointer">+
                                            Tambah Baris</button>
                                    </div>
                                    <input type="text" placeholder="Masukkan baris baru..."
                                        class="w-full text-xs border border-slate-200 rounded-lg p-2 bg-white outline-none">
                                </div>
                            </template>
                            <template x-if="field.type === 'password'">
                                <div class="relative">
                                    <input type="password" placeholder="••••••••"
                                        class="w-full text-xs border border-slate-200 rounded-lg p-2.5 bg-white shadow-sm outline-none">
                                    <span
                                        class="absolute right-3 top-3.5 text-slate-400 font-bold text-xs cursor-pointer select-none">👁️</span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Generated Codes Terminal panel -->
            <div
                class="bg-slate-900 border border-slate-950 rounded-xl p-5 shadow-lg space-y-4 flex-1 flex flex-col min-h-[350px]">
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
                    <div>
                        <h2
                            class="font-bold text-xs text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                            ⚡ Realtime PHP & Laravel Code Engine
                        </h2>
                    </div>

                    <!-- Code Tabs -->
                    <div
                        class="flex items-center gap-1 bg-slate-850 p-1 rounded-lg border border-slate-800 self-start">
                        <button @click="activeTab = 'controller'"
                            :class="activeTab === 'controller' ? 'bg-slate-800 text-indigo-400' :
                                'text-slate-400 hover:text-slate-200'"
                            class="px-2.5 py-1 rounded text-[10.5px] font-bold cursor-pointer transition-all">
                            CrudController.php
                        </button>
                        <button @click="activeTab = 'migration'"
                            :class="activeTab === 'migration' ? 'bg-slate-800 text-indigo-400' :
                                'text-slate-400 hover:text-slate-200'"
                            class="px-2.5 py-1 rounded text-[10.5px] font-bold cursor-pointer transition-all">
                            Migration.php
                        </button>
                        <button @click="activeTab = 'model'"
                            :class="activeTab === 'model' ? 'bg-slate-800 text-indigo-400' :
                                'text-slate-400 hover:text-slate-200'"
                            class="px-2.5 py-1 rounded text-[10.5px] font-bold cursor-pointer transition-all">
                            Model.php
                        </button>
                    </div>
                </div>

                <!-- Code Terminal Content -->
                <div
                    class="relative flex-1 bg-slate-950 rounded-xl p-4 overflow-y-auto font-mono text-[11px] text-indigo-200/90 leading-relaxed max-h-[300px] scrollbar-thin">

                    <!-- Copy Button -->
                    <button
                        @click="
                        if (activeTab === 'controller') copyToClipboard(controllerCode);
                        if (activeTab === 'migration') copyToClipboard(migrationCode);
                        if (activeTab === 'model') copyToClipboard(modelCode);
                    "
                        class="absolute right-3 top-3 bg-slate-800 hover:bg-slate-700 text-white font-bold text-[10px] px-3 py-1.5 rounded-lg transition-all flex items-center gap-1 cursor-pointer">
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
        &copy; <?= date('Y') ?> Nohara Genpack - Crafted for Modern & Rapid Laravel CRUD.
    </footer>

</body>

</html>
