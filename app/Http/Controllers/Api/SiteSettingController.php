<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    /**
     * Display a listing of the settings.
     */
    public function index()
    {
        $settings = DB::table('site_settings')->pluck('value', 'key');
        return response()->json($settings);
    }

    /**
     * Update site settings.
     */
    public function update(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'app_name'          => 'nullable|string|max:255',
            'app_subtitle'      => 'nullable|string|max:255',
            'instansi_name'     => 'nullable|string|max:255',
            'instansi_sub'      => 'nullable|string|max:255',
            'login_heading'     => 'nullable|string',
            'login_description' => 'nullable|string',
            'footer_copyright'  => 'nullable|string',
            'app_logo'          => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'instansi_logo'     => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        DB::transaction(function () use ($request, $validated) {
            // 1. Simpan Teks
            $textKeys = [
                'app_name', 
                'app_subtitle', 
                'instansi_name', 
                'instansi_sub', 
                'login_heading', 
                'login_description', 
                'footer_copyright'
            ];
            
            foreach ($textKeys as $key) {
                if (array_key_exists($key, $validated)) {
                    DB::table('site_settings')->updateOrInsert(
                        ['key' => $key],
                        [
                            'value'      => $validated[$key] ?? '', 
                            'updated_at' => now()
                        ]
                    );
                }
            }

            // 2. Handle Upload Logo Aplikasi
            if ($request->hasFile('app_logo')) {
                // Hapus logo lama jika ada
                $oldLogo = DB::table('site_settings')->where('key', 'app_logo_url')->value('value');
                if ($oldLogo) {
                    $oldPath = str_replace('/storage/', '', parse_url($oldLogo, PHP_URL_PATH));
                    Storage::disk('public')->delete($oldPath);
                }

                // Simpan logo baru
                $path = $request->file('app_logo')->store('settings', 'public');
                $url = asset('storage/' . $path);

                DB::table('site_settings')->updateOrInsert(
                    ['key' => 'app_logo_url'],
                    ['value' => $url, 'updated_at' => now()]
                );
            }

            // 3. Handle Upload Logo Instansi
            if ($request->hasFile('instansi_logo')) {
                // Hapus logo lama jika ada
                $oldLogo = DB::table('site_settings')->where('key', 'instansi_logo_url')->value('value');
                if ($oldLogo) {
                    $oldPath = str_replace('/storage/', '', parse_url($oldLogo, PHP_URL_PATH));
                    Storage::disk('public')->delete($oldPath);
                }

                // Simpan logo baru
                $path = $request->file('instansi_logo')->store('settings', 'public');
                $url = asset('storage/' . $path);

                DB::table('site_settings')->updateOrInsert(
                    ['key' => 'instansi_logo_url'],
                    ['value' => $url, 'updated_at' => now()]
                );
            }
        });

        // Ambil data terbaru untuk dikembalikan ke frontend
        $updatedSettings = DB::table('site_settings')->pluck('value', 'key');

        return response()->json([
            'message'  => 'Pengaturan situs berhasil diperbarui.',
            'settings' => $updatedSettings
        ]);
    }
}