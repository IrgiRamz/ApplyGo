<?php

namespace App\Http\Controllers;

use App\Models\AttachmentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentDocumentController extends Controller
{
    /**
     * Menampilkan daftar dokumen lampiran
     */
    public function index()
    {
        $attachmentDocuments = AttachmentDocument::where('user_id', auth()->id())->latest()->get();
        return view('attachment_documents.index', compact('attachmentDocuments'));
    }

    /**
     * Menampilkan form upload dokumen
     */
    public function create()
    {
        return view('attachment_documents.create');
    }

    /**
     * Menyimpan dokumen lampiran baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:5120', // Max 5MB, PDF only
        ]);

        // Simpan file ke storage privat
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->storeAs(
            'attachments',
            'cv_' . auth()->id() . '_' . time() . '_' . $fileName,
            'private'
        );

        AttachmentDocument::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'file_path' => $filePath,
            'file_name' => $fileName,
        ]);

        return redirect()->route('attachment-documents.index')->with('success', 'Dokumen lampiran berhasil diunggah.');
    }

    /**
     * Menghapus dokumen lampiran
     */
    public function destroy(AttachmentDocument $attachmentDocument)
    {
        if ($attachmentDocument->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        // Hapus file fisik dari storage
        $attachmentDocument->deleteFile();

        // Hapus record dari database
        $attachmentDocument->delete();

        return redirect()->route('attachment-documents.index')->with('success', 'Dokumen lampiran berhasil dihapus.');
    }
}
