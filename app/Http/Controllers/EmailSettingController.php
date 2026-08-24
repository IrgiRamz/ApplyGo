<?php

namespace App\Http\Controllers;

use App\Models\EmailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class EmailSettingController extends Controller
{
    /**
     * Menampilkan form pengaturan SMTP
     */
    public function edit()
    {
        $emailSetting = auth()->user()->emailSetting;
        return view('email_settings.edit', compact('emailSetting'));
    }

    /**
     * Menyimpan/memperbarui pengaturan SMTP
     */
    public function update(Request $request)
    {
        $emailSetting = auth()->user()->emailSetting;

        $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'required|email|max:255',
            'mail_password' => $emailSetting ? 'nullable|string|min:4' : 'required|string|min:4',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'sender_name' => 'required|string|max:255',
        ]);

        $data = [
            'mail_host' => $request->mail_host,
            'mail_port' => $request->mail_port,
            'mail_username' => $request->mail_username,
            'mail_encryption' => $request->mail_encryption,
            'sender_name' => $request->sender_name,
        ];

        // Hanya enkripsi jika password diisi
        if ($request->filled('mail_password')) {
            $data['mail_password'] = Crypt::encryptString($request->mail_password);
        }

        // Simpan atau update pengaturan
        EmailSetting::updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        return redirect()->back()->with('success', 'Pengaturan SMTP berhasil disimpan.');
    }

    /**
     * Menguji koneksi SMTP dengan parameter terinput secara real-time
     */
    public function testConnection(Request $request)
    {
        $emailSetting = auth()->user()->emailSetting;

        $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'required|email|max:255',
            'mail_password' => $emailSetting ? 'nullable|string|min:4' : 'required|string|min:4',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'sender_name' => 'required|string|max:255',
        ]);

        $smtpPassword = $request->mail_password;
        if (empty($smtpPassword) && $emailSetting) {
            $smtpPassword = Crypt::decryptString($emailSetting->mail_password);
        }

        // Simpan konfigurasi asli
        $originalDefault = Config::get('mail.default');
        $originalHost = Config::get('mail.mailers.smtp.host');
        $originalPort = Config::get('mail.mailers.smtp.port');
        $originalUsername = Config::get('mail.mailers.smtp.username');
        $originalPassword = Config::get('mail.mailers.smtp.password');
        $originalEncryption = Config::get('mail.mailers.smtp.encryption');
        $originalFromAddress = Config::get('mail.from.address');
        $originalFromName = Config::get('mail.from.name');

        try {
            // Set dynamic mailer
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $request->mail_host);
            Config::set('mail.mailers.smtp.port', $request->mail_port);
            Config::set('mail.mailers.smtp.username', $request->mail_username);
            Config::set('mail.mailers.smtp.password', $smtpPassword);
            Config::set('mail.mailers.smtp.encryption', $request->mail_encryption);
            Config::set('mail.from.address', $request->mail_username);
            Config::set('mail.from.name', $request->sender_name);

            Mail::purge();

            // Kirim email dummy
            Mail::raw("Halo! Ini adalah email percobaan untuk memverifikasi pengaturan SMTP Anda pada aplikasi Auto Lamaran. Koneksi SMTP Anda telah berhasil terhubung!", function ($message) use ($request) {
                $message->to($request->mail_username)
                    ->subject("Test Koneksi SMTP Berhasil!");
            });

            // Kembalikan konfigurasi asli
            Config::set('mail.default', $originalDefault);
            Config::set('mail.mailers.smtp.host', $originalHost);
            Config::set('mail.mailers.smtp.port', $originalPort);
            Config::set('mail.mailers.smtp.username', $originalUsername);
            Config::set('mail.mailers.smtp.password', $originalPassword);
            Config::set('mail.mailers.smtp.encryption', $originalEncryption);
            Config::set('mail.from.address', $originalFromAddress);
            Config::set('mail.from.name', $originalFromName);
            Mail::purge();

            return response()->json([
                'success' => true,
                'message' => 'Koneksi SMTP Berhasil Terhubung!'
            ]);

        } catch (\Throwable $e) {
            // Kembalikan konfigurasi asli
            Config::set('mail.default', $originalDefault);
            Config::set('mail.mailers.smtp.host', $originalHost);
            Config::set('mail.mailers.smtp.port', $originalPort);
            Config::set('mail.mailers.smtp.username', $originalUsername);
            Config::set('mail.mailers.smtp.password', $originalPassword);
            Config::set('mail.mailers.smtp.encryption', $originalEncryption);
            Config::set('mail.from.address', $originalFromAddress);
            Config::set('mail.from.name', $originalFromName);
            Mail::purge();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Helper: Set dynamic mailer configuration
     */
    public static function setDynamicMailer(int $userId): void
    {
        $settings = EmailSetting::where('user_id', $userId)->first();

        if (!$settings) {
            throw new \Exception("Pengaturan SMTP pengiriman email belum dikonfigurasi.");
        }

        $smtpPassword = Crypt::decryptString($settings->mail_password);

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings->mail_host);
        Config::set('mail.mailers.smtp.port', $settings->mail_port);
        Config::set('mail.mailers.smtp.username', $settings->mail_username);
        Config::set('mail.mailers.smtp.password', $smtpPassword);
        Config::set('mail.mailers.smtp.encryption', $settings->mail_encryption);
        Config::set('mail.from.address', $settings->mail_username);
        Config::set('mail.from.name', $settings->sender_name ?? ($settings->user->name ?? 'Pengirim'));

        Mail::purge();
    }
}
