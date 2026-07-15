/**
 * _list.js
 * Handler umum & dynamic untuk list CRUD AJAX berbasis Modal & DataTables
 */

$(document).ready(function () {
    // =========================================================================
    // 1. INITIALIZE DATATABLE DYNAMICALLY
    // =========================================================================
    var table = $("#genericTable").DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: window.listConfig.routeData,
            data: function (d) {
                // Collect dynamic filter values automatically
                $(".dynamic-filter").each(function () {
                    d[$(this).attr("name")] = $(this).val();
                });
            },
        },
        columns: window.listConfig.columns,
    });

    // =========================================================================
    // 2. INITIALIZE SELECT2 DYNAMIC FILTERS IN LIST HEADER
    // =========================================================================
    $(".dynamic-filter").each(function () {
        $(this).select2({
            placeholder: $(this).data("placeholder"),
            allowClear: true,
            dropdownParent: $(this).parent(),
        });
    });

    // Trigger Table Reload immediately on cleared filter
    $(".dynamic-filter").on("select2:clear", function () {
        table.ajax.reload();
    });

    // Trigger Table Reload on select (checking required filters)
    $(".dynamic-filter").on("select2:select", function () {
        let isValid = true;
        $(".dynamic-filter").each(function () {
            let required = $(this).data("required");
            let value = $(this).val();
            if (required && !value) {
                isValid = false;
            }
        });
        if (isValid) {
            table.ajax.reload();
        }
    });

    // =========================================================================
    // 3. GENERIC AJAX GET MODAL FORM HANDLER (TAMBAH / EDIT)
    // =========================================================================
    $(document).on("click", ".btn-form-ajax-modal", function (e) {
        e.preventDefault();

        const url = $(this).data("url");
        const title = $(this).data("title") ?? "Form Entry";

        // Show loading spinner inside modal first
        $("#modalContent").html(`
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Sedang menyiapkan formulir...</div>
            </div>
        `);
        
        $("#genericModal .modal-title").text(title);
        
        // Show Modal
        const modalEl = document.getElementById("genericModal");
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        // Fetch dynamic HTML/Blade view from controller
        $.ajax({
            url: url,
            type: "GET",
            success: function (htmlContent) {
                // Animate transition smoothly
                $("#modalContent").css({ opacity: 0 }).html(htmlContent).animate({ opacity: 1 }, 200);
            },
            error: function (xhr) {
                let msg = "Gagal memuat formulir.";
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                
                $("#modalContent").html(`
                    <div class="alert alert-danger m-3">
                        <i class="ri-error-warning-line me-1"></i> ${msg}
                    </div>
                `);
            },
        });
    });

    // =========================================================================
    // 4. GENERIC AJAX FORM SUBMIT HANDLER (STORE / UPDATE)
    // =========================================================================
    $(document).on("submit", ".ajax-form", function (e) {
        e.preventDefault();

        let form = $(this);
        let url = form.attr("action");
        let method = form.attr("method") ?? "POST";
        let submitBtn = form.find('button[type="submit"]');

        // Gunakan FormData untuk mendukung upload file otomatis!
        let formData = new FormData(form[0]);

        // Support PUT / PATCH method spoofing in Laravel FormData uploads
        if (method.toUpperCase() === 'PUT' || method.toUpperCase() === 'PATCH') {
            formData.append('_method', method);
            method = 'POST'; // Send as post request with method overriding
        }

        submitBtn.prop("disabled", true);

        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false, // Wajib untuk FormData
            contentType: false, // Wajib untuk FormData
            beforeSend: function () {
                Swal.fire({
                    title: "Menyimpan...",
                    text: "Memproses unggahan dan penyimpanan data.",
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });
            },
            success: function (res) {
                Swal.close();

                if (res.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil",
                        text: res.message || "Data berhasil disimpan!",
                        timer: 1000,
                        showConfirmButton: false,
                    }).then(() => {
                        // Tutup modal
                        let modalEl = document.getElementById("genericModal");
                        let modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        // Reload table dynamically
                        table.ajax.reload(null, false);
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal",
                        text: res.message || "Terjadi kesalahan.",
                    });
                }
            },
            error: function (xhr) {
                Swal.close();
                submitBtn.prop("disabled", false);

                // Validation errors (HTTP 422)
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    let errors = xhr.responseJSON.errors;
                    let errorList = Object.values(errors).flat().map(e => `<li>${e}</li>`).join("");
                    
                    Swal.fire({
                        icon: "warning",
                        title: "Validasi Gagal",
                        html: `<ul class="text-start text-danger mb-0" style="font-size:0.9rem;">${errorList}</ul>`,
                    });
                    return;
                }

                // General server error (HTTP 500)
                let message = xhr.responseJSON?.message || "Kesalahan internal sistem.";
                Swal.fire({ icon: "error", title: "Gagal Menyimpan", text: message });
            }
        });
    });

    // =========================================================================
    // 5. GENERIC DELETE HANDLER WITH SWEETALERT2
    // =========================================================================
    $(document).on("click", ".btn-delete-ajax", function (e) {
        e.preventDefault();

        let btn = $(this);
        let url = btn.data("url");
        let name = btn.data("name") ?? "Data ini";

        Swal.fire({
            title: "Hapus Data?",
            html: `Apakah Anda yakin ingin menghapus data <b>${name}</b> secara permanen?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Batal",
            confirmButtonColor: "#d33",
            reverseButtons: true,
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: url,
                type: "DELETE",
                data: { 
                    _token: $('meta[name="csrf-token"]').attr("content") 
                },
                success: function (res) {
                    Swal.fire({
                        icon: "success",
                        title: "Dihapus",
                        text: res.message || "Data berhasil dihapus.",
                        timer: 900,
                        showConfirmButton: false,
                    });

                    // Reload table
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    let msg = xhr.responseJSON?.message || "Gagal menghapus data.";
                    Swal.fire({ icon: "error", title: "Error", text: msg });
                },
            });
        });
    });
});
