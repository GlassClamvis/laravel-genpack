<?php

namespace Nohara\Genpack\Http\Controllers\Operations;

use Illuminate\Http\Request;

trait DeleteOperation
{
    /**
     * Remove the specified resource from storage (AJAX DELETE)
     */
    public function destroy($id, Request $request)
    {
        $modelClass = $this->crud->getModel();
        $item = $modelClass::findOrFail($id);

        // Support for deletion with optional password confirmation (like btn-delete-ajax-confirm)
        if ($request->has('password')) {
            $user = auth()->user();
            if (!\Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Konfirmasi gagal. Password yang Anda masukkan salah.'
                ], 401);
            }
        }

        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => $this->crud->getEntityNameSingular() . ' berhasil dihapus.'
        ]);
    }
}
