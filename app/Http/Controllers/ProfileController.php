<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Upload custom avatar to S3
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
        ]);

        try {
            $user = $request->user();

            // Delete old custom avatar from S3 if exists
            if ($user->custom_avatar) {
                $this->deleteS3File($user->custom_avatar);
            }

            // Generate unique filename
            $filename = 'avatars/' . $user->id . '/' . Str::random(40) . '.' . $request->file('avatar')->getClientOriginalExtension();

            // Upload to S3
            $path = Storage::disk('s3')->put($filename, file_get_contents($request->file('avatar')), 'public');

            // Get S3 URL
            $url = Storage::disk('s3')->url($filename);

            // Update user record
            $user->setCustomAvatar($url);

            return response()->json([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'avatar_url' => $url,
                'user' => [
                    'id' => $user->id,
                    'avatar_url' => $user->getAvatarUrl(),
                    'custom_avatar' => $user->custom_avatar,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Avatar upload error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload avatar. Please try again.',
            ], 500);
        }
    }

    /**
     * Upload custom banner to S3
     */
    public function uploadBanner(Request $request): JsonResponse
    {
        $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // Max 10MB
        ]);

        try {
            $user = $request->user();

            // Delete old custom banner from S3 if exists
            if ($user->custom_banner) {
                $this->deleteS3File($user->custom_banner);
            }

            // Generate unique filename
            $filename = 'banners/' . $user->id . '/' . Str::random(40) . '.' . $request->file('banner')->getClientOriginalExtension();

            // Upload to S3
            $path = Storage::disk('s3')->put($filename, file_get_contents($request->file('banner')), 'public');

            // Get S3 URL
            $url = Storage::disk('s3')->url($filename);

            // Update user record
            $user->setCustomBanner($url);

            return response()->json([
                'success' => true,
                'message' => 'Banner uploaded successfully',
                'banner_url' => $url,
                'user' => [
                    'id' => $user->id,
                    'banner_url' => $user->getBannerUrl(),
                    'custom_banner' => $user->custom_banner,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Banner upload error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload banner. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove custom avatar (revert to Discord avatar)
     */
    public function removeAvatar(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->custom_avatar) {
                return response()->json([
                    'success' => false,
                    'message' => 'No custom avatar to remove',
                ], 400);
            }

            // Delete from S3
            $this->deleteS3File($user->custom_avatar);

            // Remove from database
            $user->removeCustomAvatar();

            return response()->json([
                'success' => true,
                'message' => 'Custom avatar removed successfully',
                'user' => [
                    'id' => $user->id,
                    'avatar_url' => $user->getAvatarUrl(), // Will now fallback to Discord avatar
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Avatar removal error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove avatar. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove custom banner (revert to Discord banner)
     */
    public function removeBanner(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->custom_banner) {
                return response()->json([
                    'success' => false,
                    'message' => 'No custom banner to remove',
                ], 400);
            }

            // Delete from S3
            $this->deleteS3File($user->custom_banner);

            // Remove from database
            $user->removeCustomBanner();

            return response()->json([
                'success' => true,
                'message' => 'Custom banner removed successfully',
                'user' => [
                    'id' => $user->id,
                    'banner_url' => $user->getBannerUrl(), // Will now fallback to Discord banner
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Banner removal error: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove banner. Please try again.',
            ], 500);
        }
    }

    /**
     * Get current user profile images
     */
    public function getProfileImages(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'avatar_url' => $user->getAvatarUrl(),
                'banner_url' => $user->getBannerUrl(),
                'accent_color' => $user->getAccentColor(),
                'has_custom_avatar' => !is_null($user->custom_avatar),
                'has_custom_banner' => !is_null($user->custom_banner),
                'has_discord_avatar' => !is_null($user->discord_avatar),
                'has_discord_banner' => !is_null($user->discord_banner),
                'custom_avatar' => $user->custom_avatar,
                'custom_banner' => $user->custom_banner,
                'discord_avatar' => $user->discord_avatar,
                'discord_banner' => $user->discord_banner,
            ],
        ]);
    }

    /**
     * Delete file from S3
     */
    private function deleteS3File(string $url): void
    {
        try {
            // Extract the path from the full S3 URL
            $parsedUrl = parse_url($url);
            $path = ltrim($parsedUrl['path'] ?? '', '/');

            // Remove bucket name from path if present
            $bucketName = config('filesystems.disks.s3.bucket');
            if (str_starts_with($path, $bucketName . '/')) {
                $path = substr($path, strlen($bucketName) + 1);
            }

            if (Storage::disk('s3')->exists($path)) {
                Storage::disk('s3')->delete($path);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete S3 file: ' . $url, [
                'exception' => $e->getMessage(),
            ]);
            // Don't throw - we still want to update the database even if S3 delete fails
        }
    }
}
