<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use App\Http\Requests\GalleryRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Gallery",
    description: "API Endpoints for Gallery Management"
)]
class GalleryController extends Controller
{
    #[OA\Get(
        path: "/api/gallery",
        summary: "Get list of gallery images",
        description: "Retrieve a list of all images in the gallery",
        operationId: "getGallery",
        tags: ["Gallery"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Gallery images fetched successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "image", type: "string", example: "gallery/image.jpg"),
                                    new OA\Property(property: "image_url", type: "string", example: "http://localhost:8000/storage/gallery/image.jpg"),
                                    new OA\Property(property: "caption", type: "string", example: "A beautiful mountain view"),
                                    new OA\Property(property: "is_active", type: "boolean", example: true),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function index()
    {
        try {
            $gallery = Gallery::where('is_active', true)->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => true,
                'message' => 'Gallery images fetched successfully',
                'data' => $gallery,
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch gallery images',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/gallery",
        summary: "Add a new image to gallery",
        description: "Store a new image with optional caption (admin only)",
        operationId: "createGallery",
        tags: ["Gallery"],
        security: [["sanctum" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["image"],
                properties: [
                    new OA\Property(property: "image", type: "string", format: "binary", description: "Image to upload"),
                    new OA\Property(property: "caption", type: "string", maxLength: 255, example: "Trekking near Mt. Everest"),
                    new OA\Property(property: "is_active", type: "boolean", example: true)
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Image added to gallery successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Image added to gallery successfully")
            ]
        )
    )]
    #[OA\Response(response: 422, description: "Validation error")]
    #[OA\Response(response: 500, description: "Server error")]
    public function store(GalleryRequest $request)
    {
        try {
            $data = $request->only(['caption', 'is_active']);

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');

                // Generate unique filename
                $extension = $image->getClientOriginalExtension();
                $filename = 'gallery_' . time() . '_' . Str::random(10) . '.' . $extension;

                // Store in storage/app/public/gallery
                $path = $image->storeAs('gallery', $filename, 'public');
                $data['image'] = $path;
            }

            Gallery::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Image added to gallery successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to add image to gallery',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/gallery/{id}",
        summary: "Update a gallery image",
        description: "Update caption or image. Use POST with multipart/form-data for file uploads (admin only)",
        operationId: "updateGallery",
        tags: ["Gallery"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "Gallery ID",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "caption", type: "string", maxLength: 255, example: "Updated caption"),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "image", type: "string", format: "binary", description: "New image (optional)")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Gallery updated successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Gallery updated successfully")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Item not found")]
    #[OA\Response(response: 500, description: "Server error")]
    public function update(GalleryRequest $request, $id)
    {
        try {
            $gallery = Gallery::find($id);
            if (!$gallery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gallery item not found',
                ], 404);
            }

            $data = $request->only(['caption', 'is_active']);

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
                    Storage::disk('public')->delete($gallery->image);
                }

                $image = $request->file('image');
                $extension = $image->getClientOriginalExtension();
                $filename = 'gallery_' . time() . '_' . Str::random(10) . '.' . $extension;
                $path = $image->storeAs('gallery', $filename, 'public');
                $data['image'] = $path;
            }

            $gallery->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Gallery updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to update gallery',
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/gallery/{id}",
        summary: "Delete a gallery image",
        description: "Delete image and associated file (admin only)",
        operationId: "deleteGallery",
        tags: ["Gallery"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Deleted successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Gallery item deleted successfully")
            ]
        )
    )]
    public function destroy($id)
    {
        try {
            $gallery = Gallery::find($id);
            if (!$gallery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Gallery item not found',
                ], 404);
            }

            if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
                Storage::disk('public')->delete($gallery->image);
            }

            $gallery->delete();

            return response()->json([
                'status' => true,
                'message' => 'Gallery item deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete gallery item',
            ], 500);
        }
    }
}
