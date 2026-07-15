@extends('layouts.manage.manage')

@push('styles')
    <!-- Datatables -->
    <link rel="stylesheet" href="{{ asset('assets/libs/datatables-bs5/core/dataTables.bootstrap5.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/libs/datatables-bs5/responsive/responsive.bootstrap5.min.css') }}" />

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Sweet Alert & RemixIcon -->
    <link rel="stylesheet" href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #444 !important;
            line-height: 36px;
        }
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da;
            display: flex;
            align-items: center;
        }
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
    </style>
@endpush

@push('modals')
    <!-- MODAL DYNAMIC AJAX -->
    <div class="modal fade" id="genericModal" tabindex="-1">
        <div class="modal-dialog 
            {{ ($configs['modal']['fullscreen'] ?? false) ? 'modal-fullscreen' : ($configs['modal']['size'] ?? 'modal-lg') }} 
            {{ ($configs['modal']['scrollable'] ?? true) ? 'modal-dialog-scrollable' : '' }} 
            animate__animated animate__fadeInUp">
            <div class="modal-content border-0 shadow-lg">

                {{-- HEADER --}}
                <div class="modal-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="modal-title text-white fw-bold mb-0">Memuat...</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body bg-light">
                    <div id="modalContent">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary"></div>
                            <div class="mt-2 text-muted">Memuat form data...</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endpush

@section('content')
    <div class="row animate__animated animate__fadeInUp">
        <div class="col-lg-12">
            @if (($configs['themeStyle'] ?? 'classic') === 'porto')
                {{-- PORTO ADMIN 2.2 CARD STRUCTURE --}}
                <section class="card border-0 shadow-sm">
                    <header class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                        <h2 class="card-title text-white fw-bold mb-0 d-flex align-items-center gap-2" style="font-size: 1.15rem;">
                            <i class="fas fa-table text-white"></i> Data {{ $configs['title'] }}
                        </h2>

                        <div class="flex-grow-1 ms-4">
                            <div class="row g-2 justify-content-end align-items-center">
                                @if (isset($configs['filters']) && count($configs['filters']) > 0)
                                    @foreach ($configs['filters'] as $filter)
                                        <div class="{{ $filter['col'] ?? 'col-md-3' }}">
                                            <select name="{{ $filter['name'] }}" class="form-select dynamic-filter"
                                                data-placeholder="{{ $filter['label'] }}"
                                                data-required="{{ $filter['required'] ?? false }}">
                                                <option></option>
                                                @foreach ($filter['options'] as $key => $item)
                                                    @php
                                                        if (isset($filter['id_key']) && isset($filter['label_key'])) {
                                                            $val  = data_get($item, $filter['id_key']);
                                                            $text = data_get($item, $filter['label_key']);
                                                        } elseif (is_array($item) && isset($item['value'])) {
                                                            $val  = $item['value'];
                                                            $text = $item['label'];
                                                        } else {
                                                            $val  = $key;
                                                            $text = $item;
                                                        }
                                                    @endphp
                                                    <option value="{{ $val }}">{{ $text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                @endif

                                @if (isset($configs['buttons']) && count($configs['buttons']) > 0)
                                    @foreach ($configs['buttons'] as $btn)
                                        <div class="col-auto">
                                            <button type="button"
                                                class="btn {{ $btn['class'] ?? 'btn-primary' }}"
                                                data-url="{{ $btn['url'] ?? '#' }}"
                                                @if (isset($btn['id'])) id="{{ $btn['id'] }}" @endif
                                                @if (isset($btn['data']))
                                                    @foreach ($btn['data'] as $key => $val)
                                                        data-{{ $key }}="{{ $val }}"
                                                    @endforeach
                                                @endif>
                                                @if (isset($btn['icon']))
                                                    @php
                                                        $pIcon = str_replace('ri-add-line', 'fas fa-plus', $btn['icon']);
                                                    @endphp
                                                    <i class="{{ $pIcon }} me-1"></i>
                                                @endif
                                                {{ $btn['label'] }}
                                            </button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </header>
                    <div class="card-body">
            @else
                {{-- CLASSIC BOOTSTRAP 5 CARD STRUCTURE --}}
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">

                        <div class="fs-5 fw-bold text-nowrap me-3 text-white">
                            <i class="ri-table-line me-1"></i> Data {{ $configs['title'] }}
                        </div>

                        <div class="flex-grow-1">
                            <div class="row g-2 justify-content-end align-items-center">

                                @if (isset($configs['filters']) && count($configs['filters']) > 0)
                                    @foreach ($configs['filters'] as $filter)
                                        <div class="{{ $filter['col'] ?? 'col-md-3' }}">
                                            <select name="{{ $filter['name'] }}" class="form-select dynamic-filter"
                                                data-placeholder="{{ $filter['label'] }}"
                                                data-required="{{ $filter['required'] ?? false }}">
                                                <option></option>
                                                @foreach ($filter['options'] as $key => $item)
                                                    @php
                                                        if (isset($filter['id_key']) && isset($filter['label_key'])) {
                                                            $val  = data_get($item, $filter['id_key']);
                                                            $text = data_get($item, $filter['label_key']);
                                                        } elseif (is_array($item) && isset($item['value'])) {
                                                            $val  = $item['value'];
                                                            $text = $item['label'];
                                                        } else {
                                                            $val  = $key;
                                                            $text = $item;
                                                        }
                                                    @endphp
                                                    <option value="{{ $val }}">{{ $text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                @endif

                                @if (isset($configs['buttons']) && count($configs['buttons']) > 0)
                                    @foreach ($configs['buttons'] as $btn)
                                        <div class="col-auto">
                                            <button type="button"
                                                class="btn {{ $btn['class'] ?? 'btn-primary' }}"
                                                data-url="{{ $btn['url'] ?? '#' }}"
                                                @if (isset($btn['id'])) id="{{ $btn['id'] }}" @endif
                                                @if (isset($btn['data']))
                                                    @foreach ($btn['data'] as $key => $val)
                                                        data-{{ $key }}="{{ $val }}"
                                                    @endforeach
                                                @endif>
                                                @if (isset($btn['icon']))
                                                    <i class="{{ $btn['icon'] }} me-1"></i>
                                                @endif
                                                {{ $btn['label'] }}
                                            </button>
                                        </div>
                                    @endforeach
                                @endif

                            </div>
                        </div>

                    </div>

                    <div class="card-body">
            @endif

                    <table class="table align-middle table-hover table-striped @if(($configs['themeStyle'] ?? 'classic') === 'porto') table-bordered @endif" id="genericTable" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">No</th>
                                @foreach ($configs['columns'] as $col)
                                    <th>{{ $col['label'] }}</th>
                                @endforeach
                                <th width="150" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            @if (($configs['themeStyle'] ?? 'classic') === 'porto')
                </section>
            @else
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Libraries -->
    <script src="{{ asset('assets/libs/select2-4.1.0/dist/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables-bs5/core/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables-bs5/core/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables-bs5/responsive/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    {{-- Inject PHP configs dynamically into global client-side variables --}}
    <script>
        window.listConfig = {
            routeData: "{{ $configs['route_data'] }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                @foreach ($configs['columns'] as $col)
                    { data: "{{ $col['name'] }}", name: "{{ $col['name'] }}" },
                @endforeach
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ]
        };
    </script>

    <script src="{{ asset('assets/js/pages/_list.js') }}"></script>
@endpush
