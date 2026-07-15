{{-- File: resources/views/crud/modal_form.blade.php --}}
<form action="{{ $route }}" method="POST" class="ajax-form" enctype="multipart/form-data">
    @csrf
    @if(isset($method))
        @method($method)
    @endif

    <div class="row g-3">
        @foreach($fields as $name => $field)
            <div class="{{ $field['col'] ?? 'col-md-12' }}">
                <label class="form-label fw-semibold">
                    {{ $field['label'] }}
                    @if($field['required']) <span class="text-danger">*</span> @endif
                </label>

                {{-- INPUT TYPE TEXT / NUMBER / PASSWORD --}}
                @if(in_array($field['type'], ['text', 'number', 'password', 'email']))
                    <input type="{{ $field['type'] }}" 
                           name="{{ $name }}" 
                           class="form-control" 
                           placeholder="{{ $field['placeholder'] }}"
                           value="{{ $field['value'] ?? '' }}"
                           {{ $field['required'] ? 'required' : '' }}>

                {{-- INPUT TYPE TEXTAREA --}}
                @elseif($field['type'] === 'textarea')
                    <textarea name="{{ $name }}" 
                              class="form-control" 
                              rows="3" 
                              placeholder="{{ $field['placeholder'] }}"
                              {{ $field['required'] ? 'required' : '' }}>{{ $field['value'] ?? '' }}</textarea>

                {{-- INPUT TYPE FLATPICKR (DATE PICKER) --}}
                @elseif($field['type'] === 'flatpickr')
                    <div class="input-group">
                        <span class="input-group-text"><i class="ri-calendar-line"></i></span>
                        <input type="text" 
                               name="{{ $name }}" 
                               class="form-control flatpickr-field" 
                               placeholder="{{ $field['placeholder'] ?? 'Pilih Tanggal...' }}"
                               value="{{ $field['value'] ?? '' }}"
                               {{ $field['required'] ? 'required' : '' }}>
                    </div>

                {{-- INPUT TYPE SELECT2 SINGLE --}}
                @elseif($field['type'] === 'select2')
                    <select name="{{ $name }}" 
                            class="form-select select2-field" 
                            style="width: 100%"
                            data-placeholder="{{ $field['placeholder'] }}"
                            {{ $field['required'] ? 'required' : '' }}>
                        <option></option>
                        @foreach($field['options'] as $option)
                            @php
                                $optVal = is_array($option) ? ($option['value'] ?? '') : $option;
                                $optLabel = is_array($option) ? ($option['label'] ?? $option) : $option;
                                $selected = (isset($field['value']) && $field['value'] == $optVal) ? 'selected' : '';
                            @endphp
                            <option value="{{ $optVal }}" {{ $selected }}>{{ $optLabel }}</option>
                        @endforeach
                    </select>

                {{-- INPUT TYPE SELECT2 MULTIPLE (TAGGING) --}}
                @elseif($field['type'] === 'select2_multiple')
                    <select name="{{ $name }}[]" 
                            class="form-select select2-multiple-field" 
                            style="width: 100%"
                            multiple="multiple"
                            data-placeholder="{{ $field['placeholder'] ?? 'Pilih beberapa...' }}"
                            {{ $field['required'] ? 'required' : '' }}>
                        @foreach($field['options'] as $option)
                            @php
                                $optVal = is_array($option) ? ($option['value'] ?? '') : $option;
                                $optLabel = is_array($option) ? ($option['label'] ?? $option) : $option;
                                $selected = '';
                                if (isset($field['value'])) {
                                    $values = is_array($field['value']) ? $field['value'] : explode(',', $field['value']);
                                    $selected = in_array($optVal, array_map('trim', $values)) ? 'selected' : '';
                                }
                            @endphp
                            <option value="{{ $optVal }}" {{ $selected }}>{{ $optLabel }}</option>
                        @endforeach
                    </select>

                {{-- INPUT TYPE SELECT2 DEPENDENT (TERIKAT) --}}
                @elseif($field['type'] === 'select2_dependent')
                    @php
                        $parentField = $field['dependsOn'] ?? '';
                    @endphp
                    <select name="{{ $name }}" 
                            class="form-select select2-dependent-field" 
                            style="width: 100%"
                            data-placeholder="{{ $field['placeholder'] }}"
                            data-depends-on="{{ $parentField }}"
                            {{ $field['required'] ? 'required' : '' }}>
                        <option></option>
                        @foreach($field['options'] as $option)
                            @php
                                $optVal = is_array($option) ? ($option['value'] ?? '') : $option;
                                $optLabel = is_array($option) ? ($option['label'] ?? $option) : $option;
                                $parentVal = is_array($option) ? ($option['parentValue'] ?? '') : '';
                                $selected = (isset($field['value']) && $field['value'] == $optVal) ? 'selected' : '';
                            @endphp
                            <option value="{{ $optVal }}" data-parent-value="{{ $parentVal }}" {{ $selected }}>{{ $optLabel }}</option>
                        @endforeach
                    </select>

                {{-- INPUT TYPE FILE (BOOTSTRAP ENHANCED FILE INPUT) --}}
                @elseif($field['type'] === 'file')
                    <div class="card bg-light border border-dashed rounded-3 p-3 text-center">
                        <div class="mb-2">
                            <i class="ri-upload-cloud-2-line text-secondary fs-2"></i>
                        </div>
                        <p class="small fw-semibold text-dark mb-1">Seret berkas ke sini, atau klik tombol di bawah</p>
                        <p class="text-[10px] text-muted mb-2">JPG, PNG, PDF, Word, atau Excel (Max 2MB)</p>
                        
                        <input type="file" 
                               id="file_input_{{ $name }}"
                               name="{{ $name }}" 
                               class="form-control d-none custom-file-trigger"
                               {{ $field['required'] && !isset($field['value']) ? 'required' : '' }}>
                               
                        <label for="file_input_{{ $name }}" class="btn btn-sm btn-outline-primary px-3 cursor-pointer">
                            Pilih Berkas
                        </label>

                        @if(isset($field['value']))
                            @php
                                $filename = basename($field['value']);
                                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                                $icon = 'ri-file-3-line text-secondary';
                                if (in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])) {
                                    $icon = 'ri-image-line text-info';
                                } elseif (strtolower($ext) === 'pdf') {
                                    $icon = 'ri-file-pdf-line text-danger';
                                } elseif (in_array(strtolower($ext), ['docx','doc'])) {
                                    $icon = 'ri-file-word-line text-primary';
                                } elseif (in_array(strtolower($ext), ['xlsx','xls','csv'])) {
                                    $icon = 'ri-file-excel-line text-success';
                                }
                            @endphp
                            <div class="mt-3 p-2 bg-white rounded border d-flex align-items-center justify-content-between text-start">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <i class="{{ $icon }} fs-3"></i>
                                    <div class="min-w-0">
                                        <p class="small text-dark fw-bold mb-0 text-truncate" style="max-width: 250px;">{{ $filename }}</p>
                                        <span class="text-[10px] text-muted text-uppercase">{{ $ext }}</span>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $field['value']) }}" target="_blank" class="btn btn-sm btn-light-primary text-primary px-2.5 py-1">
                                    <i class="ri-eye-line"></i> Lihat
                                </a>
                            </div>
                        @endif
                    </div>

                {{-- INPUT TYPE CHECKBOX / SWITCH --}}
                @elseif($field['type'] === 'checkbox')
                    <div class="form-check form-switch pt-1">
                        <input type="checkbox" 
                               name="{{ $name }}" 
                               class="form-check-input" 
                               value="1" 
                               {{ (isset($field['value']) && $field['value']) ? 'checked' : '' }}>
                        <span class="text-muted small">{{ $field['placeholder'] }}</span>
                    </div>

                {{-- INPUT TYPE DYNAMIC LIST (HAS MANY RELATION RELATIONSHIP) --}}
                @elseif($field['type'] === 'dynamic_list')
                    <div class="dynamic-list-container border border-dashed rounded p-3 bg-light/30" id="dynamic_list_container_{{ $name }}">
                        @php
                            $items = [''];
                            if (isset($field['value'])) {
                                $items = is_array($field['value']) ? $field['value'] : $field['value'];
                                if (empty($items)) $items = [''];
                            }
                        @endphp
                        <div class="dynamic-list-wrapper" id="wrapper_{{ $name }}">
                            @foreach($items as $index => $item)
                                <div class="input-group mb-2 dynamic-list-item align-items-center gap-1">
                                    <span class="input-group-text bg-light text-muted font-monospace py-1.5 text-xs border-end" style="font-size: 11px; width: 40px; justify-content: center;">#{{ $index + 1 }}</span>
                                    <input type="text" 
                                           name="{{ $name }}[]" 
                                           class="form-control" 
                                           value="{{ $item }}" 
                                           placeholder="{{ $field['placeholder'] ?? 'Masukkan data...' }} #{{ $index + 1 }}"
                                           {{ $field['required'] && $index === 0 ? 'required' : '' }}>
                                    <button type="button" class="btn btn-outline-danger remove-dynamic-list-item" style="padding: 0.375rem 0.75rem;">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-1 add-dynamic-list-item" data-field-name="{{ $name }}" data-placeholder="{{ $field['placeholder'] ?? 'Masukkan data...' }}">
                            <i class="ri-add-line me-1"></i> Tambah Baris Baru
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- FOOTER BUTTONS --}}
    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary px-4">
            <i class="ri-save-line me-1"></i> Simpan
        </button>
    </div>
</form>

{{-- Inisialisasi JS Dependensi Form (Select2, Flatpickr, Dependent Dropdown) --}}
<script>
    $(document).ready(function () {
        // 1. Inisialisasi Select2 Single
        $('.select2-field').each(function () {
            $(this).select2({
                placeholder: $(this).data('placeholder'),
                allowClear: true,
                dropdownParent: $('#genericModal')
            });
        });

        // 2. Inisialisasi Select2 Multiple (Tagging)
        $('.select2-multiple-field').each(function () {
            $(this).select2({
                placeholder: $(this).data('placeholder'),
                allowClear: true,
                multiple: true,
                closeOnSelect: false,
                dropdownParent: $('#genericModal')
            });
        });

        // 3. Inisialisasi Flatpickr
        if (typeof flatpickr !== 'undefined') {
            $('.flatpickr-field').flatpickr({
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'j F Y',
                allowInput: true
            });
        }

        // 4. Inisialisasi Select2 Dependent (Dropdown Terikat)
        $('.select2-dependent-field').each(function () {
            let dependentSelect = $(this);
            let parentName = dependentSelect.data('depends-on');
            let parentSelect = $('[name="' + parentName + '"]');
            
            // Backup all option elements
            let allOptions = dependentSelect.find('option').clone();

            function filterDependentOptions() {
                let parentVal = parentSelect.val();
                dependentSelect.empty();
                
                // Add empty option
                dependentSelect.append('<option></option>');

                if (parentVal) {
                    // Filter matching options
                    allOptions.each(function () {
                        let opt = $(this);
                        if (opt.val() && opt.data('parent-value') == parentVal) {
                            dependentSelect.append(opt.clone());
                        }
                    });
                    dependentSelect.prop('disabled', false);
                } else {
                    dependentSelect.prop('disabled', true);
                }
                
                // Refresh Select2
                dependentSelect.select2({
                    placeholder: dependentSelect.data('placeholder'),
                    allowClear: true,
                    dropdownParent: $('#genericModal')
                }).trigger('change');
            }

            if (parentSelect.length) {
                // Listen to change on parent select
                parentSelect.on('change', function () {
                    filterDependentOptions();
                });
                // Run on initial load
                filterDependentOptions();
            }
        });

        // 5. Inisialisasi Dynamic List Add/Remove Handler
        $(document).on('click', '.add-dynamic-list-item', function () {
            let fieldName = $(this).data('field-name');
            let placeholder = $(this).data('placeholder');
            let wrapper = $('#wrapper_' + fieldName);
            let count = wrapper.find('.dynamic-list-item').length;
            
            let newItemHtml = `
                <div class="input-group mb-2 dynamic-list-item align-items-center gap-1">
                    <span class="input-group-text bg-light text-muted font-monospace py-1.5 text-xs border-end" style="font-size: 11px; width: 40px; justify-content: center;">#${count + 1}</span>
                    <input type="text" 
                           name="${fieldName}[]" 
                           class="form-control" 
                           placeholder="${placeholder} #${count + 1}">
                    <button type="button" class="btn btn-outline-danger remove-dynamic-list-item" style="padding: 0.375rem 0.75rem;">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            `;
            wrapper.append(newItemHtml);
        });

        $(document).on('click', '.remove-dynamic-list-item', function () {
            let item = $(this).closest('.dynamic-list-item');
            let container = item.closest('.dynamic-list-container');
            let itemsList = container.find('.dynamic-list-item');
            
            if (itemsList.length > 1) {
                item.remove();
                // Re-index remaining list elements
                container.find('.dynamic-list-item').each(function (idx) {
                    $(this).find('.input-group-text').text('#' + (idx + 1));
                });
            } else {
                item.find('input').val('');
            }
        });
    });
</script>
