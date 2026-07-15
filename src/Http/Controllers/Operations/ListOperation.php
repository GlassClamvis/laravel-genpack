<?php

namespace Nohara\Genpack\Http\Controllers\Operations;

use Illuminate\Http\Request;

trait ListOperation
{
    /**
     * Display the dynamic lists page (Table, Filters, Modal anchors)
     */
    public function index()
    {
        // Merge configurations for blade consumption
        $configs = [
            'title' => $this->crud->getEntityNamePlural(),
            'singular' => $this->crud->getEntityNameSingular(),
            'route_data' => route($this->crud->getRoutePrefix() . '.data'),
            'route_prefix' => $this->crud->getRoutePrefix(),
            'columns' => $this->crud->getColumns(),
            'filters' => $this->crud->getFilters(),
            'buttons' => $this->crud->getButtons(),
            'themeStyle' => $this->crud->getThemeStyle(),
            'modal' => $this->crud->getModalConfig()
        ];

        // If 'tambah' button is not explicitly overridden, we inject it automatically
        if (empty($configs['buttons'])) {
            $configs['buttons'][] = [
                'label' => 'Tambah ' . $this->crud->getEntityNameSingular(),
                'icon' => 'ri-add-line',
                'class' => 'btn-primary btn-form-ajax-modal',
                'url' => route($this->crud->getRoutePrefix() . '.create'),
                'data' => [
                    'title' => 'Tambah ' . $this->crud->getEntityNameSingular()
                ]
            ];
        }

        return view('genpack::list', compact('configs'));
    }

    /**
     * Server-side processing handler for Yajra DataTables
     */
    public function data(Request $request)
    {
        $query = $this->baseQuery();

        // Automatically apply defined dynamic filters
        $query = $this->applyFilters($query, $request);

        $columns = $this->crud->getColumns();
        $routePrefix = $this->crud->getRoutePrefix();

        $dt = datatables()
            ->of($query)
            ->addIndexColumn()
            ->setRowClass(function ($row) {
                return $this->rowClass($row);
            });

        // Generate default Action buttons column dynamically!
        $dt->addColumn('action', function ($row) use ($routePrefix) {
            $editUrl = route($routePrefix . '.edit', $row->id);
            $deleteUrl = route($routePrefix . '.destroy', $row->id);
            
            return '
                <div class="d-flex gap-1 justify-content-center">
                    <button class="btn btn-sm btn-light-warning btn-form-ajax-modal text-warning border-0" 
                            data-url="' . $editUrl . '" 
                            data-id="' . $row->id . '" 
                            data-title="Edit ' . $this->crud->getEntityNameSingular() . '">
                        <i class="ri-edit-line"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-light-danger btn-delete-ajax text-danger border-0" 
                            data-url="' . $deleteUrl . '" 
                            data-id="' . $row->id . '"
                            data-name="' . ($row->nama ?? $row->name ?? $row->nim ?? $row->id) . '">
                        <i class="ri-delete-bin-line"></i> Hapus
                    </button>
                </div>
            ';
        });

        // Allow HTML rendering in action and any badge/custom column
        $rawColumns = ['action'];
        foreach ($columns as $col) {
            if (isset($col['type']) && ($col['type'] === 'badge' || $col['type'] === 'html')) {
                $rawColumns[] = $col['name'];
            }
        }
        $dt->rawColumns($rawColumns);

        return $this->dataTable($dt)->make(true);
    }
}
