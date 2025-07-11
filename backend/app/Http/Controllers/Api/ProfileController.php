<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    private ProfileImageService $profileImageService;

    public function __construct(ProfileImageService $profileImageService)
    {
        $this->profileImageService = $profileImageService;
    }

    /**
     * プロフィール情報を取得
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->hasProfileImage() ? [
                    'urls' => $user->getProfileImageUrls(),
                    'uploaded_at' => $user->profile_image_uploaded_at?->format('c'),
                ] : null,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * プロフィール情報を更新
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $user->update($validator->validated());

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->hasProfileImage() ? [
                    'urls' => $user->getProfileImageUrls(),
                    'uploaded_at' => $user->profile_image_uploaded_at?->format('c'),
                ] : null,
                'updated_at' => $user->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * プロフィール画像をアップロード
     */
    public function uploadProfileImage(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make(
            $request->all(),
            $this->profileImageService->getValidationRules()
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $success = $this->profileImageService->uploadProfileImage(
            $user,
            $request->file('profile_image')
        );

        if (!$success) {
            return response()->json([
                'error' => 'Failed to upload profile image',
            ], 500);
        }

        // 更新されたユーザー情報を再取得
        $user->refresh();

        return response()->json([
            'data' => [
                'profile_image' => [
                    'urls' => $user->getProfileImageUrls(),
                    'uploaded_at' => $user->profile_image_uploaded_at?->format('c'),
                ],
            ],
        ], 201);
    }

    /**
     * プロフィール画像を削除
     */
    public function deleteProfileImage(Request $request)
    {
        $user = $request->user();

        if (!$user->hasProfileImage()) {
            return response()->json([
                'error' => 'No profile image to delete',
            ], 404);
        }

        $success = $this->profileImageService->deleteProfileImage($user);

        if (!$success) {
            return response()->json([
                'error' => 'Failed to delete profile image',
            ], 500);
        }

        return response()->json([
            'message' => 'Profile image deleted successfully',
        ]);
    }

    /**
     * プロフィール画像のURLを取得
     */
    public function getProfileImageUrl(Request $request)
    {
        $user = $request->user();
        $size = $request->get('size', 'medium');

        $url = $this->profileImageService->getProfileImageUrl($user, $size);

        if (!$url) {
            return response()->json([
                'error' => 'No profile image found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'url' => $url,
                'size' => $size,
            ],
        ]);
    }
}
