<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Upload image to S3
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('image');

            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Upload to S3 in 'product-images' folder
            $path = Storage::disk('s3')->putFileAs(
                'product-images',
                $file,
                $filename
            );

            // Get the public URL
            $url = Storage::disk('s3')->url($path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultipleImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        try {
            $uploadedFiles = [];

            foreach ($request->file('images') as $file) {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

                $path = Storage::disk('s3')->putFileAs(
                    'product-images',
                    $file,
                    $filename
                );

                $uploadedFiles[] = [
                    'url' => Storage::disk('s3')->url($path),
                    'path' => $path
                ];
            }

            return response()->json([
                'success' => true,
                'files' => $uploadedFiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload game logo
     */
    public function uploadGameLogo(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            $file = $request->file('image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $path = Storage::disk('s3')->putFileAs(
                'games/logos',
                $file,
                $filename
            );

            $url = Storage::disk('s3')->url($path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload logo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload game icon
     */
    public function uploadGameIcon(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:1024',
        ]);

        try {
            $file = $request->file('image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $path = Storage::disk('s3')->putFileAs(
                'games/icons',
                $file,
                $filename
            );

            $url = Storage::disk('s3')->url($path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload icon: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete image from S3
     */
    public function deleteImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            Storage::disk('s3')->delete($request->path);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }
}
