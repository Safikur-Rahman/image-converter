<?php

namespace Database\Seeders;

use App\Models\Conversion;
use App\Models\ConversionFile;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user.
        $admin = User::updateOrCreate(
            ['email' => 'admin@imageconverter.test'],
            [
                'name' => 'Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'plan_id' => Plan::where('slug', 'business')->value('id'),
            ]
        );

        // Demo user.
        $demo = User::updateOrCreate(
            ['email' => 'demo@imageconverter.test'],
            [
                'name' => 'Demo User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
                'plan_id' => Plan::where('slug', 'pro')->value('id'),
            ]
        );

        Subscription::updateOrCreate(
            ['user_id' => $demo->id, 'status' => 'active'],
            [
                'uuid' => (string) Str::uuid(),
                'plan_id' => Plan::where('slug', 'pro')->value('id'),
                'starts_at' => now(),
                'renews_at' => now()->addMonth(),
                'ends_at' => now()->addMonth(),
            ]
        );

        // Seed a couple of realistic (failed + completed) conversions for demo UX.
        $this->seedDemoConversions($demo);
    }

    protected function seedDemoConversions(User $user): void
    {
        foreach ([
            ['hello-world-logo.png', 'png', 84213, 640, 480, 'completed', 'color', 'high'],
            ['product-photo.jpg', 'jpg', 187420, 1200, 800, 'failed', 'color', 'medium'],
        ] as [$name, $fmt, $size, $w, $h, $status, $type, $quality]) {
            $uuid = (string) Str::uuid();

            $conversion = Conversion::firstOrCreate(
                ['uuid' => $uuid],
                [
                    'user_id' => $user->id,
                    'status' => $status,
                    'original_filename' => $name,
                    'original_format' => $fmt,
                    'file_size' => $size,
                    'width' => $w,
                    'height' => $h,
                    'remove_background' => false,
                    'conversion_type' => $type,
                    'quality' => $quality,
                    'path_optimization' => 'balanced',
                    'disk' => 'local',
                    'started_at' => $status === 'failed' ? now()->subMinutes(10) : now()->subMinutes(5),
                    'completed_at' => $status === 'completed' ? now()->subMinutes(4) : null,
                    'error_message' => $status === 'failed' ? 'Something went wrong while processing your image.' : null,
                ]
            );

            ConversionFile::firstOrCreate(
                ['conversion_id' => $conversion->id, 'type' => 'original'],
                [
                    'disk' => 'local',
                    'path' => 'originals/'.$user->id.'/'.$conversion->uuid.'.'.$fmt,
                    'filename' => $name,
                    'mime_type' => 'image/'.$fmt,
                    'size' => $size,
                ]
            );
        }
    }
}