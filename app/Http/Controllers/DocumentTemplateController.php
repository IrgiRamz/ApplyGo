<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentTemplateController extends Controller
{
    /**
     * Menampilkan daftar template dokumen
     */
    public function index()
    {
        $documentTemplates = DocumentTemplate::where('user_id', auth()->id())->latest()->get();
        return view('document_templates.index', compact('documentTemplates'));
    }

    /**
     * Menampilkan form upload template
     */
    public function create()
    {
        return view('document_templates.create');
    }

    /**
     * Menyimpan template dokumen baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:docx|max:10240', // Max 10MB, DOCX only
        ]);

        // Simpan file ke storage privat
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $filePath = $file->storeAs(
            'templates',
            'template_' . auth()->id() . '_' . time() . '_' . $originalName,
            'private'
        );

        DocumentTemplate::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'file_path' => $filePath,
        ]);

        return redirect()->route('document-templates.index')->with('success', 'Template dokumen berhasil diunggah.');
    }

    /**
     * Menghapus template dokumen
     */
    public function destroy(DocumentTemplate $documentTemplate)
    {
        if ($documentTemplate->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        // Hapus file fisik dari storage
        $documentTemplate->deleteFile();

        // Hapus record dari database
        $documentTemplate->delete();

        return redirect()->route('document-templates.index')->with('success', 'Template dokumen berhasil dihapus.');
    }
}
