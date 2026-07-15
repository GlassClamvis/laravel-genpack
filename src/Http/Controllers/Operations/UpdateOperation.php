<?php

namespace Nohara\Genpack\Http\Controllers\Operations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait UpdateOperation
{
    /**
     * Returns pre-populated HTML content for the AJAX edit form modal
     */
    public function edit($id)
    {
        $modelClass = $this->crud->getModel();
        $entry = $modelClass::findOrFail($id);
        
        $fields = $this->crud->getFields();
        
        // Populate current values to fields helper
        foreach ($fields as $name => &$field) {
            if ($field['type'] === 'dynamic_list') {
                $relationName = Str::camel($name);
                if (method_exists($entry, $relationName)) {
                    // Pull relationship contents
                    $field['value'] = $entry->{$relationName}->pluck('content')->toArray();
                } else {
                    // Pull from JSON column fallback
                    $decoded = json_decode($entry->{$name}, true);
                    $field['value'] = is_array($decoded) ? $decoded : [];
                }
            } else {
                $field['value'] = $entry->{$name};
            }
        }
        
        $route = route($this->crud->getRoutePrefix() . '.update', $id);
        $title = 'Edit ' . $this->crud->getEntityNameSingular();
        $method = 'PUT'; // For ajax form override
        
        return view('genpack::modal_form', compact('fields', 'route', 'title', 'method', 'entry'));
    }

    /**
     * Update the specified resource in storage
     */
    public function update($id, Request $request)
    {
        $modelClass = $this->crud->getModel();
        $item = $modelClass::findOrFail($id);
        
        $fields = $this->crud->getFields();
        
        // Gather validation rules
        $rules = [];
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

        if (!empty($rules)) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        // Process fields inside transaction
        DB::transaction(function () use ($request, $fields, $item) {
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
                        // Eloquent hasMany relation syncing (re-create records)
                        $item->{$relationName}()->delete();
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
        });

        return response()->json([
            'status' => 'success',
            'message' => $this->crud->getEntityNameSingular() . ' berhasil diperbarui!',
            'data' => $item
        ]);
    }
}
