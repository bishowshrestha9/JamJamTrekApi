<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trek;
use App\Http\Requests\TrekRequest;
use App\Http\Resources\TrekResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Treks",
    description: "API Endpoints for Treks"
)]
class TrekController extends Controller
{
    #[OA\Get(
        path: "/api/treks",
        summary: "Get list of treks",
        tags: ["Treks"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "data_type",
                in: "query",
                description: "Filter treks by type (trek or package)",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["trek", "package"])
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
            $query = Trek::latest();

            if ($request->has('data_type')) {
                $query->where('data_type', $request->data_type);
            }
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            $treks = $query->paginate(10);
            $data = TrekResource::collection($treks);
            return response()->json([
                'success' => true,
                'data' => [
                    'treks' => $data,
                    'pagination' => [
                        'total' => $treks->total(),
                        'per_page' => $treks->perPage(),
                        'current_page' => $treks->currentPage(),
                        'last_page' => $treks->lastPage(),
                    ]
                ],
                'message' => 'Treks retrieved successfully'
            ]);
        } catch (\Throwable $th) {
            \Log::error('Trek index error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve treks'
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/treks",
        summary: "Create a new trek",
        tags: ["Treks"],
        security: [["sanctum" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["title", "location", "price", "currency", "duration", "difficulty", "type", "distance_km", "data_type", "is_featured", "is_active", "trek_days"],
                properties: [
                    new OA\Property(property: "title", type: "string", maxLength: 255),
                    new OA\Property(property: "data_type", type: "string", enum: ["trek", "package"]),
                    new OA\Property(property: "location", type: "string", maxLength: 255),
                    new OA\Property(property: "price", type: "number", format: "float", minimum: 0),
                    new OA\Property(property: "currency", type: "string", maxLength: 100),
                    new OA\Property(property: "duration", type: "string", maxLength: 100),
                    new OA\Property(property: "difficulty", type: "string", maxLength: 100),
                    new OA\Property(property: "type", type: "string", maxLength: 100),
                    new OA\Property(property: "distance_km", type: "number", format: "float", minimum: 0),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "featured_image", type: "string", format: "binary"),
                    new OA\Property(property: "is_featured", type: "boolean"),
                    new OA\Property(property: "is_active", type: "boolean"),
                    new OA\Property(property: "trek_days", type: "array", items: new OA\Items(type: "string"))
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Trek created successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "data", type: "object"),
                new OA\Property(property: "message", type: "string", example: "Trek created successfully")
            ]
        )
    )]
    public function store(TrekRequest $request)
    {
        try {
            $data = $request->validated();
            if ($request->hasFile('featured_image')) {
                $image = $request->file('featured_image');
                $imageName = uniqid('trek_') . '.' . $image->getClientOriginalExtension();
                
                // Store in storage/app/public/treks (automatically creates directory)
                $path = $image->storeAs('treks', $imageName, 'public');
                $data['featured_image'] = $path;
            }
            $trek = Trek::create($data);
            $data = new TrekResource($trek);
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Trek created successfully'
            ], 201);
        } catch (\Throwable $th) {
            \Log::error('Trek store error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create trek'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/treks/{id}",
        summary: "Get a single trek",
        tags: ["Treks"],
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
            $trek = Trek::findOrFail($id);
            $data = new TrekResource($trek);
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Trek retrieved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Trek not found'
            ], 404);
        } catch (\Throwable $th) {
            \Log::error('Trek show error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve trek'
            ], 500);
        }
    }

    #[OA\Put(
        path: "/api/treks/{id}",
        summary: "Update a trek",
        tags: ["Treks"],
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
                    new OA\Property(property: "data_type", type: "string", enum: ["trek", "package"]),
                    new OA\Property(property: "location", type: "string", maxLength: 255),
                    new OA\Property(property: "price", type: "number", format: "float", minimum: 0),
                    new OA\Property(property: "currency", type: "string", maxLength: 100),
                    new OA\Property(property: "duration", type: "string", maxLength: 100),
                    new OA\Property(property: "difficulty", type: "string", maxLength: 100),
                    new OA\Property(property: "type", type: "string", maxLength: 100),
                    new OA\Property(property: "distance_km", type: "number", format: "float", minimum: 0),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "featured_image", type: "string", format: "binary"),
                    new OA\Property(property: "is_featured", type: "boolean"),
                    new OA\Property(property: "is_active", type: "boolean"),
                    new OA\Property(property: "trek_days", type: "array", items: new OA\Items(type: "string"))
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
                new OA\Property(property: "message", type: "string", example: "Trek updated successfully")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Not found")]
    public function update(TrekRequest $request, $id)
    {
        try {
            $trek = Trek::findOrFail($id);
            $data = $request->validated();
            if ($request->hasFile('featured_image')) {
                // Delete old image if exists
                if ($trek->featured_image && Storage::disk('public')->exists($trek->featured_image)) {
                    Storage::disk('public')->delete($trek->featured_image);
                }
                
                $image = $request->file('featured_image');
                $imageName = uniqid('trek_') . '.' . $image->getClientOriginalExtension();
                
                // Store in storage/app/public/treks (automatically creates directory)
                $path = $image->storeAs('treks', $imageName, 'public');
                $data['featured_image'] = $path;
            }
            $trek->update($data);
            $data = new TrekResource($trek);
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Trek updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Trek not found'
            ], 404);
        } catch (\Throwable $th) {
            \Log::error('Trek update error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update trek'
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/treks/{id}",
        summary: "Delete a trek",
        tags: ["Treks"],
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
            $trek = Trek::findOrFail($id);
            if ($trek->featured_image) {
                Storage::disk('public')->delete($trek->featured_image);
            }
            $trek->delete();
            return response()->json([
                'success' => true,
                'message' => 'Trek deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Trek not found'
            ], 404);
        } catch (\Throwable $th) {
            \Log::error('Trek destroy error: ' . $th->getMessage(), ['file' => $th->getFile(), 'line' => $th->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete trek'
            ], 500);
        }
    }
}
