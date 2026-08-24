<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    /**
     * Menampilkan daftar template email
     */
    public function index()
    {
        $emailTemplates = EmailTemplate::where('user_id', auth()->id())->latest()->get();
        return view('email_templates.index', compact('emailTemplates'));
    }

    /**
     * Menampilkan form tambah template
     */
    public function create()
    {
        return view('email_templates.create');
    }

    /**
     * Menyimpan template email baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        EmailTemplate::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()->route('email-templates.index')->with('success', 'Template email berhasil dibuat.');
    }

    /**
     * Menampilkan form edit template
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        // Pastikan user hanya bisa edit template miliknya sendiri
        if ($emailTemplate->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        return view('email_templates.edit', compact('emailTemplate'));
    }

    /**
     * Memperbarui template email
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        if ($emailTemplate->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $emailTemplate->update([
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()->route('email-templates.index')->with('success', 'Template email berhasil diperbarui.');
    }

    /**
     * Menghapus template email
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        if ($emailTemplate->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        $emailTemplate->delete();

        return redirect()->route('email-templates.index')->with('success', 'Template email berhasil dihapus.');
    }
}
