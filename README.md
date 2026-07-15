# laravel-genpack

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nohara/laravel-genpack.svg?style=flat-square)](https://packagist.org/packages/nohara/laravel-genpack)
[![Total Downloads](https://img.shields.io/packagist/dt/nohara/laravel-genpack.svg?style=flat-square)](https://packagist.org/packages/nohara/laravel-genpack)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

**laravel-genpack** adalah Laravel package untuk membuat Dynamic Form Builder dan Fluent CRUD Generator secara deklaratif dengan dukungan Ajax Modal, SweetAlert2, Yajra DataTables, dan multi-theme styling (Classic Bootstrap 5 & Porto Admin).

---

## Fitur Utama

- **Fluent CRUD Builder**: Definisikan kolom, form input, dan filter pencarian secara deklaratif langsung dari Controller Anda.
- **Dynamic Ajax Form Modal**: Pengisian form tambah & edit data menggunakan modal pop-up berbasis AJAX.
- **Dynamic List Table**: Integrasi Yajra DataTables dengan rendering kolom dinamis dan filter pencarian.
- **Multi-Theme Support**: Mendukung styling tema **Classic Bootstrap 5** dan **Porto Admin 2.2** secara otomatis.
- **Relational Field Support**: Dukungan drop-down Select2, Select2 Dependent (Dropdown Terikat), Input File (Upload), dan Dynamic List (HasMany).

---

## Instalasi

### 1. Install Package via Composer

Jalankan perintah berikut di direktori proyek Laravel Anda:

```bash
composer require nohara/laravel-genpack
```

> **Catatan:** Package ini mendukung Laravel Auto-Discovery, sehingga Service Provider `GenpackServiceProvider` akan didaftarkan secara otomatis.

### 2. Publish Views (Opsional)

Jika Anda ingin memodifikasi template tampilan default (tabel daftar atau modal form), Anda dapat mempublikasikan berkas view ke dalam proyek Anda:

```bash
php artisan vendor:publish --tag=genpack-views
```

File view akan disalin ke folder `resources/views/vendor/genpack/`.

---

## Cara Penggunaan

Berikut adalah panduan lengkap cara mengimplementasikan Fluent CRUD menggunakan package ini.

### 1. Definisikan Base Crud Controller
Buat abstract controller di dalam aplikasi Laravel Anda (misal: `app/Http/Controllers/Base/BaseCrudController.php`) sebagai jembatan penanganan CRUD:

```php
<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use Nohara\Genpack\CrudPanel;
use Illuminate\Http\Request;

abstract class BaseCrudController extends Controller
{
    /** @var CrudPanel */
    public $crud;

    public function __construct()
    {
        $this->crud = new CrudPanel();
        
        $this->middleware(function ($request, $next) {
            $this->setup();
            return $next($request);
        });
    }

    abstract protected function setup();
}
```

### 2. Buat Controller Konkret
Buat controller baru (misal: `app/Http/Controllers/MahasiswaCrudController.php`) yang mewarisi `BaseCrudController`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Base\BaseCrudController;
use App\Models\Mahasiswa;

class MahasiswaCrudController extends BaseCrudController
{
    protected function setup()
    {
        // 1. Konfigurasi Entitas & Model
        $this->crud->setModel(Mahasiswa::class);
        $this->crud->setEntityNameStrings('Mahasiswa', 'Mahasiswa');
        $this->crud->setRoutePrefix('mahasiswa');
        $this->crud->setThemeStyle('porto'); // Opsi: 'classic' atau 'porto'

        // 2. Definisikan Kolom Tabel (DataTables)
        $this->crud->addColumn([
            'name'  => 'nim',
            'label' => 'NIM',
            'type'  => 'text',
        ]);
        $this->crud->addColumn([
            'name'  => 'nama',
            'label' => 'Nama Lengkap',
            'type'  => 'text',
        ]);
        $this->crud->addColumn([
            'name'  => 'program_studi',
            'label' => 'Program Studi',
            'type'  => 'text',
        ]);

        // 3. Definisikan Field Input Form (Modal Form)
        $this->crud->addField([
            'name'        => 'nim',
            'label'       => 'NIM',
            'type'        => 'text',
            'placeholder' => 'Masukkan NIM...',
            'required'    => true,
            'col'         => 'col-md-6',
        ]);
        $this->crud->addField([
            'name'        => 'nama',
            'label'       => 'Nama Lengkap',
            'type'        => 'text',
            'placeholder' => 'Masukkan nama lengkap...',
            'required'    => true,
            'col'         => 'col-md-6',
        ]);
        $this->crud->addField([
            'name'        => 'program_studi',
            'label'       => 'Program Studi',
            'type'        => 'select2',
            'placeholder' => 'Pilih Program Studi',
            'required'    => true,
            'options'     => [
                ['value' => 'Teknik Informatika', 'label' => 'Teknik Informatika'],
                ['value' => 'Sistem Informasi', 'label' => 'Sistem Informasi'],
                ['value' => 'Teknologi Informasi', 'label' => 'Teknologi Informasi'],
            ],
            'col'         => 'col-md-12',
        ]);

        // 4. Definisikan Filter Pencarian (Header Filter)
        $this->crud->addFilter([
            'name'        => 'program_studi',
            'label'       => 'Filter Prodi',
            'type'        => 'select2',
            'options'     => [
                'Teknik Informatika' => 'Teknik Informatika',
                'Sistem Informasi'   => 'Sistem Informasi',
                'Teknologi Informasi'=> 'Teknologi Informasi',
            ],
        ]);
        
        // 5. Tambahkan Tombol Aksi Kustom (Opsional)
        $this->crud->addButton([
            'label' => 'Tambah Mahasiswa',
            'class' => 'btn-success btn-form-ajax-modal',
            'icon'  => 'ri-add-line',
            'url'   => route('mahasiswa.create'),
            'id'    => 'btnTambahMahasiswa',
        ]);
    }

    /**
     * Render Halaman Utama (Daftar Data)
     */
    public function index()
    {
        $configs = [
            'title'        => $this->crud->getEntityNamePlural(),
            'singular'     => $this->crud->getEntityNameSingular(),
            'route_data'   => route($this->crud->getRoutePrefix() . '.data'),
            'route_prefix' => $this->crud->getRoutePrefix(),
            'columns'      => $this->crud->getColumns(),
            'filters'      => $this->crud->getFilters(),
            'buttons'      => $this->crud->getButtons(),
            'themeStyle'   => $this->crud->getThemeStyle(),
            'modal'        => $this->crud->getModalConfig()
        ];

        return view('genpack::list', compact('configs'));
    }

    /**
     * Render Formulir Pembuatan Data (AJAX Modal)
     */
    public function create()
    {
        $route  = route($this->crud->getRoutePrefix() . '.store');
        $fields = $this->crud->getFields();

        return view('genpack::modal_form', compact('route', 'fields'));
    }
}
```

### 3. Daftarkan Routing
Tambahkan rute berikut di file `routes/web.php` Anda:

```php
use App\Http\Controllers\MahasiswaCrudController;

Route::get('mahasiswa', [MahasiswaCrudController::class, 'index'])->name('mahasiswa.index');
Route::get('mahasiswa/data', [MahasiswaCrudController::class, 'data'])->name('mahasiswa.data');
Route::get('mahasiswa/create', [MahasiswaCrudController::class, 'create'])->name('mahasiswa.create');
Route::post('mahasiswa', [MahasiswaCrudController::class, 'store'])->name('mahasiswa.store');
```

---

## Lisensi

Package ini dilisensikan di bawah [MIT License](LICENSE.md).
