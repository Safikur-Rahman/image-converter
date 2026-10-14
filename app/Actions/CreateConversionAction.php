<?php

namespace App\Actions;

use App\Enums\ConversionQuality;
use App\Enums\ConversionStatus;
use App\Enums\ConversionType;
use App\Enums\PathOptimization;
use App\Models\Conversion;
use App\Models\ConversionFile;
use App\Models\User;
use App\Services\Storage\StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validates, stores and creates a conversion record atomically, then returns
 * it ready for queue dispatch.
 */
class CreateConversionAction
{
    public function __construct(protected StorageService $storage) {}

    public function execute(
        User $user,
        UploadedFile $file,
        bool $removeBackground,
        string $conversionType,
        string $quality,
        string $pathOptimization,
    ): Conversion {
        $this->validateFile($file, $user);

        $uuid = (string) Str::uuid();

        // Real-image verification via GD — no trusting of extensions.
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($image === false) {
            throw ValidationException::withMessages([
                'image' => 'The uploaded file is not a valid image.',
            ]);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        // MIME must be verified by the underlying file, but we also require a
        // plausible format against our allow-list.
        $format = strtolower($file->getClientOriginalExtension());
        if (!in_array($format, config('imageconverter.allowed_formats'), true)) {
            throw ValidationException::withMessages([
                'image' => 'Unsupported image format. Allowed formats: png, jpg, jpeg, webp.',
            ]);
        }

        $conversion = DB::transaction(function () use ($user, $file, $removeBackground, $conversionType, $quality, $pathOptimization, $uuid, $width, $height) {
            $conversion = Conversion::create([
                'uuid' => $uuid,
                'user_id' => $user->id,
                'status' => ConversionStatus::Pending,
                'original_filename' => $file->getClientOriginalName(),
                'original_format' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'remove_background' => $removeBackground,
                'conversion_type' => $conversionType,
                'quality' => $quality,
                'path_optimization' => $pathOptimization,
                'disk' => $this->storage->driver(),
            ]);

            $stored = $this->storage->storeOriginal($file, $user->id, $uuid);

            ConversionFile::create([
                'conversion_id' => $conversion->id,
                'type' => 'original',
                'disk' => $stored['disk'],
                'path' => $stored['path'],
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $stored['mime_type'],
                'size' => $stored['size'],
            ]);

            return $conversion;
        });

        return $conversion;
    }

    protected function validateFile(UploadedFile $file, User $user): void
    {
        $validated = Validator::make(
            ['image' => $file],
            [
                'image' => [
                    'required',
                    'file',
                    'image',
                    'max:'.(int) ceil(app(\App\Services\Usage\UsageLimitService::class)->maxFileSizeBytes($user) / 1024),
                    'dimensions:max_width='.app(\App\Services\Usage\UsageLimitService::class)->maxDimensions($user)
                        .',max_height='.app(\App\Services\Usage\UsageLimitService::class)->maxDimensions($user),
                    'mimes:png,jpg,jpeg,webp',
                ],
            ]
        );

        if ($validated->fails()) {
            throw ValidationException::withMessages($validated->errors()->toArray());
        }
    }
}