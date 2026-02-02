<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity;
use App\Http\Requests\ActivityRequest;
use App\Http\Resources\ActivityResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Activities",
    description: "API Endpoints for Other Activities (Paragliding, Rafting, etc.)"
)]
class ActivityController extends Controller
{
    #[OA\Get(
        path: "/api/activities",
        summary: "Get list of activities",
        tags: ["Activities"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "category",
                in: "query",
                description: "Filter activities by category",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "is_active",
                in: "query",
                description: "Filter by active status",
                required: false,
                schema: new OA\Schema(type: "boolean")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        try {
            $query = Activity::latest();

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $activities = $query->paginate(10);
            $data = ActivityResource::collection($activities);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'activities' => $data,
                    'pagination' => [
                        'total' => $activities->total(),
                        'per_page' => $activities->perPage(),
                        'current_page' => $activities->currentPage(),
                        'last_page' => $activities->lastPage(),
                    ]
                ],
                'message' => 'Activities retrieved successfully'
            ]);
        } catch (\Throwable $th) {
            \Log::error('Activity index error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activities'
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/activities",
        summary: "Create a new activity",
        tags: ["Activities"],
        security: [["sanctum" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["title", "location", "price", "currency", "duration", "difficulty", "category", "is_featured", "is_active"],
                properties: [
                    new OA\Property(property: "title", type: "string", maxLength: 255, example: "Paragliding in Pokhara"),
                    new OA\Property(property: "location", type: "string", maxLength: 255, example: "Pokhara"),
                    new OA\Property(property: "price", type: "number", format: "float", minimum: 0, example: 85),
                    new OA\Property(property: "currency", type: "string", maxLength: 10, example: "USD"),
                    new OA\Property(property: "duration", type: "string", maxLength: 100, example: "30 minutes"),
                    new OA\Property(property: "difficulty", type: "string", maxLength: 100, example: "Easy"),
                    new OA\Property(property: "category", type: "string", maxLength: 100, example: "Aerial"),
                    new OA\Property(property: "min_age", type: "integer", minimum: 0, example: 12),
                    new OA\Property(property: "max_participants", type: "integer", minimum: 1, example: 1),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "inclusions", type: "string", example: "Equipment, Insurance, Guide"),
                    new OA\Property(property: "requirements", type: "string", example: "Good health, No fear of heights"),
                    new OA\Property(property: "featured_image", type: "string", format: "binary"),
                    new OA\Property(property: "gallery_images[]", type: "array", items: new OA\Items(type: "string", format: "binary")),
                    new OA\Property(property: "is_featured", type: "boolean"),
                    new OA\Property(property: "is_active", type: "boolean"),
                    new OA\Property(property: "season", type: "string", maxLength: 100, example: "All Year")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Activity created successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "data", type: "object"),
                new OA\Property(property: "message", type: "string", example: "Activity created successfully")
            ]
        )
    )]
    public function store(ActivityRequest $request)
    {
        try {
            $data = $request->validated();
            
            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                $image = $request->file('featured_image');
                $imageName = uniqid('activity_') . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('activities', $imageName, 'public');
                $data['featured_image'] = $path;
            }
            
            // Handle gallery images upload
            if ($request->hasFile('gallery_images')) {
                $galleryPaths = [];
                foreach ($request->file('gallery_images') as $image) {
                    $imageName = uniqid('activity_gallery_') . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('activities/gallery', $imageName, 'public');
                    $galleryPaths[] = $path;
                }
                $data['gallery_images'] = $galleryPaths;
            }
            
            $activity = Activity::create($data);
            $data = new ActivityResource($activity);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Activity created successfully'
            ], 201);
        } catch (\Throwable $th) {
            \Log::error('Activity store error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create activity'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/activities/{id}",
        summary: "Get a single activity",
        tags: ["Activities"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function show($id)
    {
        try {
            $activity = Activity::findOrFail($id);
            $data = new ActivityResource($activity);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Activity retrieved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found'
            ], 404);
        } catch (\Throwable $th) {
            \Log::error('Activity show error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity'
            ], 500);
        }
    }

    #[OA\Put(
        path: "/api/activities/{id}",
        summary: "Update an activity",
        tags: ["Activities"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "title", type: "string", maxLength: 255),
                    new OA\Property(property: "location", type: "string", maxLength: 255),
                    new OA\Property(property: "price", type: "number", format: "float", minimum: 0),
                    new OA\Property(property: "currency", type: "string", maxLength: 10),
                    new OA\Property(property: "duration", type: "string", maxLength: 100),
                    new OA\Property(property: "difficulty", type: "string", maxLength: 100),
                    new OA\Property(property: "category", type: "string", maxLength: 100),
                    new OA\Property(property: "min_age", type: "integer", minimum: 0),
                    new OA\Property(property: "max_participants", type: "integer", minimum: 1),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "inclusions", type: "string"),
                    new OA\Property(property: "requirements", type: "string"),
                    new OA\Property(property: "featured_image", type: "string", format: "binary"),
                    new OA\Property(property: "gallery_images[]", type: "array", items: new OA\Items(type: "string", format: "binary")),
                    new OA\Property(property: "is_featured", type: "boolean"),
                    new OA\Property(property: "is_active", type: "boolean"),
                    new OA\Property(property: "season", type: "string", maxLength: 100)
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Updated",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "data", type: "object"),
                new OA\Property(property: "message", type: "string", example: "Activity updated successfully")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Not found")]
    public function update(ActivityRequest $request, $id)
    {
        try {
            $activity = Activity::findOrFail($id);
            $data = $request->validated();
            
            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                // Delete old image if exists
                if ($activity->featured_image && Storage::disk('public')->exists($activity->featured_image)) {
                    Storage::disk('public')->delete($activity->featured_image);
                }
                
                $image = $request->file('featured_image');
                $imageName = uniqid('activity_') . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('activities', $imageName, 'public');
                $data['featured_image'] = $path;
            }
            
            // Handle gallery images upload
            if ($request->hasFile('gallery_images')) {
                // Delete old gallery images if exist
                if ($activity->gallery_images) {
                    foreach ($activity->gallery_images as $oldImage) {
                        if (Storage::disk('public')->exists($oldImage)) {
                            Storage::disk('public')->delete($oldImage);
                        }
                    }
                }
                
                $galleryPaths = [];
                foreach ($request->file('gallery_images') as $image) {
                    $imageName = uniqid('activity_gallery_') . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('activities/gallery', $imageName, 'public');
                    $galleryPaths[] = $path;
                }
                $data['gallery_images'] = $galleryPaths;
            }
            
            $activity->update($data);
            $data = new ActivityResource($activity);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Activity updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found'
            ], 404);
        } catch (\Throwable $th) {
            \Log::error('Activity update error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity'
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/activities/{id}",
        summary: "Delete an activity",
        tags: ["Activities"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Deleted"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function destroy($id)
    {
        try {
            $activity = Activity::findOrFail($id);
            
            // Delete featured image if exists
            if ($activity->featured_image) {
                Storage::disk('public')->delete($activity->featured_image);
            }
            
            // Delete gallery images if exist
            if ($activity->gallery_images) {
                foreach ($activity->gallery_images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }
            
            $activity->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Activity deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Activity not found'
            ], 404);
        } catch (\Throwable $th) {
            \Log::error('Activity destroy error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete activity'
            ], 500);
        }
    }
}
