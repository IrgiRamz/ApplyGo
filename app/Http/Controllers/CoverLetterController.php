<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CoverLetterController extends Controller
{
    /**
     * Menampilkan form generator surat lamaran
     */
    public function showForm()
    {
        $documentTemplates = DocumentTemplate::where('user_id', auth()->id())->get();
        return view('cover_letter.generate', compact('documentTemplates'));
    }

    /**
     * Generate dan download surat lamaran
     */
    public function generate(Request $request)
    {
        $request->validate([
            'document_template_id' => 'required|exists:document_templates,id',
            'company_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'format' => 'required|in:docx,pdf',
        ]);

        $template = DocumentTemplate::where('user_id', auth()->id())
            ->findOrFail($request->document_template_id);

        $originalPath = Storage::path($template->file_path);

        // Cek apakah file ada
        if (!file_exists($originalPath)) {
            return back()->with('error', 'File template tidak ditemukan.');
        }

        try {
            // Inisialisasi TemplateProcessor
            $templateProcessor = new TemplateProcessor($originalPath);

            // Replace placeholder
            $templateProcessor->setValue('NAMA_PERUSAHAAN', $request->company_name);
            $templateProcessor->setValue('POSISI', $request->position);

            // Buat nama file output
            $tempFileName = 'temp_' . auth()->id() . '_' . time();
            $tempDir = storage_path('app/public/temp');

            // Buat direktori temp jika belum ada
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempDocxPath = $tempDir . '/' . $tempFileName . '.docx';

            // Simpan file DOCX sementara
            $templateProcessor->saveAs($tempDocxPath);

            if ($request->format === 'docx') {
                // Download sebagai DOCX
                return response()->download($tempDocxPath,
                    'Surat_Lamaran_' . str_replace(' ', '_', $request->company_name) . '.docx'
                )->deleteFileAfterSend(true);
            }

            if ($request->format === 'pdf') {
                // Konversi ke PDF menggunakan DomPDF
                try {
                    $tempPdfPath = $tempDir . '/' . $tempFileName . '.pdf';

                    // Load dan render DOCX ke PDF
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempDocxPath);

                    // Konfigurasi DomPDF
                    \PhpOffice\PhpWord\Settings::setPdfRendererName(\PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF);
                    \PhpOffice\PhpWord\Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

                    $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
                    $pdfWriter->save($tempPdfPath);

                    // Hapus file DOCX temporary
                    if (file_exists($tempDocxPath)) {
                        unlink($tempDocxPath);
                    }

                    return response()->download($tempPdfPath,
                        'Surat_Lamaran_' . str_replace(' ', '_', $request->company_name) . '.pdf'
                    )->deleteFileAfterSend(true);

                } catch (\Exception $e) {
                    // Hapus file temp jika terjadi error
                    if (file_exists($tempDocxPath)) {
                        unlink($tempDocxPath);
                    }

                    return back()->with('error', 'Gagal memproses file PDF: ' . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses template: ' . $e->getMessage());
        }
    }
}
