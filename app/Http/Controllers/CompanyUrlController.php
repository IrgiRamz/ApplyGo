<?php

namespace App\Http\Controllers;

use App\Models\CompanyUrl;
use Illuminate\Http\Request;

class CompanyUrlController extends Controller
{
    /**
     * Menampilkan daftar link karir perusahaan milik user
     */
    public function index()
    {
        $companyUrls = CompanyUrl::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('company_urls.index', compact('companyUrls'));
    }

    /**
     * Menyimpan link karir perusahaan baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'career_url'   => 'required|url',
        ], [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'company_name.max'      => 'Nama perusahaan maksimal 255 karakter.',
            'career_url.required'   => 'URL website karir wajib diisi.',
            'career_url.url'        => 'Format URL website karir tidak valid (contoh: https://karir.pt.com).',
        ]);

        CompanyUrl::create([
            'user_id'      => auth()->id(),
            'company_name' => $request->company_name,
            'career_url'   => $request->career_url,
        ]);

        return redirect()->route('company-urls.index')
            ->with('success', 'Link karir ' . $request->company_name . ' berhasil ditambahkan!');
    }

    /**
     * Perbarui data link karir perusahaan
     */
    public function update(Request $request, $id)
    {
        $companyUrl = CompanyUrl::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'company_name' => 'required|string|max:255',
            'career_url'   => 'required|url',
        ], [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'company_name.max'      => 'Nama perusahaan maksimal 255 karakter.',
            'career_url.required'   => 'URL website karir wajib diisi.',
            'career_url.url'        => 'Format URL website karir tidak valid (contoh: https://karir.pt.com).',
        ]);

        $companyUrl->update([
            'company_name' => $request->company_name,
            'career_url'   => $request->career_url,
        ]);

        return redirect()->route('company-urls.index')
            ->with('success', 'Link karir ' . $request->company_name . ' berhasil diperbarui!');
    }

    /**
     * Hapus data link karir perusahaan
     */
    public function destroy($id)
    {
        $companyUrl = CompanyUrl::where('user_id', auth()->id())->findOrFail($id);
        $companyUrl->delete();

        return redirect()->back()->with('success', 'Link karir berhasil dihapus.');
    }
}
