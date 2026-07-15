<?php

namespace Nohara\Genpack;

class CrudPanel
{
    protected $entityNameSingular = 'Entry';
    protected $entityNamePlural = 'Entries';
    protected $model;
    protected $routePrefix;
    
    protected $columns = [];
    protected $fields = [];
    protected $filters = [];
    protected $buttons = [];
    protected $themeStyle = 'classic';
    protected $modalConfig = [
        'fullscreen' => false,
        'scrollable' => true
    ];

    public function setEntityNameStrings(string $singular, string $plural)
    {
        $this->entityNameSingular = $singular;
        $this->entityNamePlural = $plural;
        return $this;
    }

    public function setThemeStyle(string $theme)
    {
        $this->themeStyle = $theme;
        return $this;
    }

    public function getThemeStyle(): string
    {
        return $this->themeStyle;
    }

    public function setModel(string $modelClass)
    {
        $this->model = $modelClass;
        return $this;
    }

    public function setRoutePrefix(string $route)
    {
        $this->routePrefix = $route;
        return $this;
    }

    public function getEntityNameSingular(): string
    {
        return $this->entityNameSingular;
    }

    public function getEntityNamePlural(): string
    {
        return $this->entityNamePlural;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    /*
    |--------------------------------------------------------------------------
    | COLUMNS (DATATABLE LIST)
    |--------------------------------------------------------------------------
    */

    public function addColumn(array $column)
    {
        if (!isset($column['name'])) {
            throw new \InvalidArgumentException("Column must have a 'name' attribute.");
        }
        $this->columns[$column['name']] = array_merge([
            'name' => $column['name'],
            'label' => ucwords(str_replace('_', ' ', $column['name'])),
            'type' => 'text',
            'visible' => true,
            'orderable' => true,
            'searchable' => true,
        ], $column);

        return $this;
    }

    public function getColumns(): array
    {
        return array_values($this->columns);
    }

    /*
    |--------------------------------------------------------------------------
    | FIELDS (MODAL FORM BUILDER)
    |--------------------------------------------------------------------------
    */

    public function addField(array $field)
    {
        if (!isset($field['name'])) {
            throw new \InvalidArgumentException("Field must have a 'name' attribute.");
        }
        $this->fields[$field['name']] = array_merge([
            'name' => $field['name'],
            'label' => ucwords(str_replace('_', ' ', $field['name'])),
            'type' => 'text', // text, select2, file, textarea, checkbox, password
            'placeholder' => '',
            'required' => false,
            'options' => [], // for select2
            'validation_rules' => '',
        ], $field);

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    /*
    |--------------------------------------------------------------------------
    | FILTERS (DYNAMIC HEADER FILTERS)
    |--------------------------------------------------------------------------
    */

    public function addFilter(array $filter)
    {
        if (!isset($filter['name'])) {
            throw new \InvalidArgumentException("Filter must have a 'name' attribute.");
        }
        $this->filters[$filter['name']] = array_merge([
            'name' => $filter['name'],
            'label' => ucwords(str_replace('_', ' ', $filter['name'])),
            'col' => 'col-md-3',
            'required' => false,
            'options' => [],
            'id_key' => null,
            'label_key' => null,
        ], $filter);

        return $this;
    }

    public function getFilters(): array
    {
        return array_values($this->filters);
    }

    /*
    |--------------------------------------------------------------------------
    | BUTTONS & MODAL CONFIG
    |--------------------------------------------------------------------------
    */

    public function setModalConfig(array $config)
    {
        $this->modalConfig = array_merge($this->modalConfig, $config);
        return $this;
    }

    public function getModalConfig(): array
    {
        return $this->modalConfig;
    }

    public function addButton(array $button)
    {
        $this->buttons[] = $button;
        return $this;
    }

    public function getButtons(): array
    {
        return $this->buttons;
    }
}
