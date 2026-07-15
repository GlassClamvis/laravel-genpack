<?php

namespace Nohara\Genpack\Http\Controllers\Operations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreateOperation
{
    /**
     * Returns HTML content for the AJAX create form modal
     */
    public function create()
    {
        $fields = $this->crud->getFields();
        $route = route($this->crud->getRoutePrefix() . '.store');
        $title = 'Tambah ' . $this->crud->getEntityNameSingular();
        
        return view('genpack::modal_form', compact('fields', 'route', 'title'));
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request)
    {
        $fields = $this->crud->getFields();
        
        // 1. Gather validation rules from fluent fields setup
        $rules = [];
        $messages = [];
        
        foreach ($fields as $name => $field) {
            if ($field['type'] === 'dynamic_list') {
                if ($field['required']) {
                    $rules[$name] = 'required|array';
                    $rules[$name . '.*'] = 'required|string';
                }
            } else {
                if ($field['required']) {
                    $rules[$name][] = 'required';
                }
                if (!empty($field['validation_rules'])) {
                    $rules[$name] = array_merge($rules[$name] ?? [], explode('|', $field['validation_rules']));
                }
            }
        }

        // 2. Validate request
        if (!empty($rules)) {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        // 3. Process uploads & save inside transaction (supporting HasMany relationships)
        $item = DB::transaction(function () use ($request, $fields) {
            $modelClass = $this->crud->getModel();
            $item = new $modelClass();
            
            // First pass: Save standard fields
            foreach ($fields as $name => $field) {
                if ($field['type'] === 'dynamic_list') {
                    continue;
                }
                
                if ($field['type'] === 'file') {
                    if ($request->hasFile($name)) {
                        $file = $request->file($name);
                        $path = $file->store('uploads/' . $this->crud->getRoutePrefix(), 'public');
                        $item->{$name} = $path;
                    }
                } elseif ($field['type'] === 'checkbox') {
                    $item->{$name} = $request->has($name) ? 1 : 0;
                } elseif ($request->has($name)) {
                    $item->{$name} = $request->input($name);
                }
            }
            
            $item->save();

            // Second pass: Save related dynamic_list items
            foreach ($fields as $name => $field) {
                if ($field['type'] === 'dynamic_list') {
                    $values = array_filter($request->input($name) ?? []); // Filter out empty inputs
                    $relationName = Str::camel($name);
                    
                    if (method_exists($item, $relationName)) {
                        // Eloquent hasMany relation creation
                        foreach ($values as $value) {
                            $item->{$relationName}()->create([
                                'content' => $value
                            ]);
                        }
                    } else {
                        // Fallback: Save as JSON array on main table column
                        $item->{$name} = json_encode(array_values($values));
                        $item->save();
                    }
                }
            }

            return $item;
        });

        return response()->json([
            'status' => 'success',
            'message' => $this->crud->getEntityNameSingular() . ' berhasil disimpan!',
            'data' => $item
        ]);
    }
}
