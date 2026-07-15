# laravel-genpack

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nohara/laravel-genpack.svg?style=flat-square)](https://packagist.org/packages/nohara/laravel-genpack)
[![Total Downloads](https://img.shields.io/packagist/dt/nohara/laravel-genpack.svg?style=flat-square)](https://packagist.org/packages/nohara/laravel-genpack)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

**laravel-genpack** adalah Laravel package untuk membuat Dynamic Form Builder dan Fluent CRUD Generator secara deklaratif dengan dukungan Ajax Modal, SweetAlert2, Yajra DataTables, dan multi-theme styling (Classic Bootstrap 5 & Porto Admin).

---

## Fitur Utama

- **Fluent CRUD Builder**: Definisikan kolom, form input, dan filter pencarian secara deklaratif langsung dari Controller Anda.
- **Base Controller & Traits Bawaan**: Pewarisan kelas controller dasar (`BaseCrudController`) serta trait operasi modular (`ListOperation`, `CreateOperation`, `UpdateOperation`, `DeleteOperation`) langsung dari package.
- **Dynamic Ajax Form Modal**: Pengisian form tambah & edit data menggunakan modal pop-up berbasis AJAX.
- **Dynamic List Table**: Integrasi Yajra DataTables dengan rendering kolom dinamis dan filter pencarian.
- **Visual GUI Builder**: Akses halaman visual builder interaktif langsung di dalam proyek Laravel Anda untuk mendesain kolom secara instan.
- **Multi-Theme Support**: Mendukung styling tema **Classic Bootstrap 5** dan **Porto Admin 2.2** secara otomatis.
- **Relational Field Support**: Dukungan drop-down Select2, Select2 Dependent (Dropdown Terikat), Input File (Upload), dan Dynamic List (HasMany).

---

## Instalasi

### 1. Install Package via Composer

Jalankan perintah berikut di direktori proyek Laravel Anda:

```bash
composer require nohara/laravel-genpack
```

### 2. Publikasikan Aset & Views Package

Ekspor berkas tampilan (blade) dan aset javascript penunjang DataTable ke dalam proyek utama Anda:

```bash
# Publikasikan views (tabel utama & form modal)
php artisan vendor:publish --tag=genpack-views

# Publikasikan asset javascript (_list.js)
php artisan vendor:publish --tag=genpack-assets
```

File view akan disalin ke folder `resources/views/vendor/genpack/` dan berkas JS ke `public/vendor/genpack/`.

---

## Cara Penggunaan

### 1. Buat Controller Baru
Buat controller baru (misal: `app/Http/Controllers/MahasiswaCrudController.php`) yang mewarisi `BaseCrudController` bawaan package:

```php
<?php

namespace App\Http\Controllers;

use Nohara\Genpack\Http\Controllers\BaseCrudController;
use Nohara\Genpack\Http\Controllers\Operations\ListOperation;
use Nohara\Genpack\Http\Controllers\Operations\CreateOperation;
use Nohara\Genpack\Http\Controllers\Operations\UpdateOperation;
use Nohara\Genpack\Http\Controllers\Operations\DeleteOperation;
use App\Models\Mahasiswa;

class MahasiswaCrudController extends BaseCrudController
{
    // Gunakan Trait Operations secara modular sesuai kebutuhan
    use ListOperation, CreateOperation, UpdateOperation, DeleteOperation;

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
    }
}
```

### 2. Daftarkan Rute CRUD
Tambahkan rute di file `routes/web.php` Anda:

```php
use App\Http\Controllers\MahasiswaCrudController;

Route::get('mahasiswa', [MahasiswaCrudController::class, 'index'])->name('mahasiswa.index');
Route::get('mahasiswa/data', [MahasiswaCrudController::class, 'data'])->name('mahasiswa.data');
Route::get('mahasiswa/create', [MahasiswaCrudController::class, 'create'])->name('mahasiswa.create');
Route::post('mahasiswa', [MahasiswaCrudController::class, 'store'])->name('mahasiswa.store');
Route::get('mahasiswa/{id}/edit', [MahasiswaCrudController::class, 'edit'])->name('mahasiswa.edit');
Route::put('mahasiswa/{id}', [MahasiswaCrudController::class, 'update'])->name('mahasiswa.update');
Route::delete('mahasiswa/{id}', [MahasiswaCrudController::class, 'destroy'])->name('mahasiswa.destroy');
```

---

## Visual GUI Builder (Interactive Editor)

Package ini menyertakan visual builder interaktif agar Anda dapat merancang kolom form langsung dari antarmuka visual browser.

### Cara Memasang Builder:
1. Daftarkan rute di file `routes/web.php` Anda:
   ```php
   Route::get('genpack/builder', function () {
       return view('vendor.genpack.builder');
   });
   ```
2. Pastikan Anda telah mempublikasikan view package:
   ```bash
   php artisan vendor:publish --tag=genpack-views
   ```
3. Akses builder di browser Anda: `http://localhost:8000/genpack/builder`. Builder akan otomatis mendeteksi kelas model Anda di direktori `app/Models/`!

---

## Lisensi

Package ini dilisensikan di bawah [MIT License](LICENSE.md).
