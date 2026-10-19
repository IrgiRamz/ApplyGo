<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    /**
     * Membuat token API baru untuk user
     */
    public function store(Request $request)
    {
        $request->validate([
            'token_name' => 'required|string|max:100',
        ], [
            'token_name.required' => 'Nama token wajib diisi.',
            'token_name.max'      => 'Nama token maksimal 100 karakter.',
        ]);

        $token = $request->user()->createToken($request->token_name);

        return redirect()->route('profile.index')
            ->with('new_api_token', $token->plainTextToken)
            ->with('success', 'Token API berhasil dibuat! Salin token Anda sekarang.');
    }

    /**
     * Mencabut / menghapus token API tertentu
     */
    public function destroy(Request $request, $id)
    {
        $request->user()->tokens()->where('id', $id)->delete();

        return redirect()->route('profile.index')
            ->with('success', 'Token API berhasil dicabut.');
    }
}
