<?php

namespace Nohara\Genpack\Http\Controllers;

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

    /**
     * Define the entity and its columns, fields, and filters.
     * This method is called in the constructor middleware.
     */
    abstract protected function setup();

    /*
    |--------------------------------------------------------------------------
    | HOOKS / HELPER OVERRIDES
    |--------------------------------------------------------------------------
    */

    /**
     * Set a custom query logic for the DataTable source
     */
    protected function baseQuery()
    {
        $model = $this->crud->getModel();
        return $model::query();
    }

    /**
     * Optional post-processing of Yajra Datatable instance
     */
    protected function dataTable($dataTable)
    {
        return $dataTable;
    }

    /**
     * Override this to custom-filter queries before datatable binding
     */
    protected function applyFilters($query, Request $request)
    {
        foreach ($this->crud->getFilters() as $filter) {
            if ($request->filled($filter['name'])) {
                $query->where($filter['name'], $request->get($filter['name']));
            }
        }
        return $query;
    }

    /**
     * Optional custom row class for DataTables row
     */
    protected function rowClass($row)
    {
        return null;
    }
}
