<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/admin/upload/image
     * Accepts a single image file, stores it under storage/app/public/rooms/
     * and returns the public URL.
     */
    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('image');
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('rooms', $name, 'public');

        return $this->success([
            'url'  => Storage::disk('public')->url($path),
            'path' => $path,
        ], 'Image uploaded successfully.', 201);
    }

    /**
     * DELETE /api/admin/upload/image
     * Removes an image by its storage path.
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        // Safety: only allow deleting from the rooms/ folder
        $path = $request->input('path');
        if (! Str::startsWith($path, 'rooms/')) {
            return $this->error('Invalid path.', 422);
        }

        Storage::disk('public')->delete($path);

        return $this->success(null, 'Image deleted.');
    }
}
