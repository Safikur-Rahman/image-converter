<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminSettingsRequest;
use App\Support\AppSettings;

class AdminSettingController extends Controller
{
    public function __construct(private AppSettings $settings) {}

    public function index()
    {
        return view('admin.settings.index', [
            'settings' => $this->settings->all(true),
            'providerConfigured' => config('imageconverter.background_removal.provider') !== '',
            'providerName' => config('imageconverter.background_removal.provider') ?: null,
            'storage' => [
                'disk' => config('filesystems.default'),
                'root' => storage_path('app/private'),
            ],
        ]);
    }

    public function update(AdminSettingsRequest $request)
    {
        $data = $request->validated();

        // Never allow the "off" value to sneak in as empty string for booleans.
        $this->settings->setMany([
            'max_upload_size' => $data['max_upload_size'] ?? null,
            'allowed_formats' => $data['allowed_formats'] ?? null,
            'background_removal_available' => isset($data['background_removal_available']) ? (int) $data['background_removal_available'] : 0,
            'default_svg_quality' => $data['default_svg_quality'] ?? null,
            'storage_retention_days' => $data['storage_retention_days'] ?? null,
            'maintenance_message' => $data['maintenance_message'] ?? null,
        ]);

        return back()->with('flash', 'Settings saved.');
    }
}