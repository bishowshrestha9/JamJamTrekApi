<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TourRequest;
use App\Http\Resources\TourResource;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Tours",
    description: "API Endpoints for Tour Packages"
)]
class TourController extends Controller
{
    #[OA\Get(
        path: "/api/tours",
        summary: "Get list of tour packages",
        tags: ["Tours"],
        parameters: [
            new OA\Parameter(
                name: "is_active",
                in: "query",
                description: "Filter by active status",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),
            new OA\Parameter(
                name: "is_featured",
                in: "query",
                description: "Filter featured tours",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),
            new OA\Parameter(
                name: "is_popular",
                in: "query",
                description: "Filter popular tours",
                required: false,
                schema: new OA\Schema(type: "boolean")
            ),
            new OA\Parameter(
                name: "tour_type",
                in: "query",
                description: "Filter by tour type",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "difficulty_level",
                in: "query",
                description: "Filter by difficulty level",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Easy", "Moderate", "Challenging", "Extreme"])
            ),
            new OA\Parameter(
                name: "destination",
                in: "query",
                description: "Search by destination",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Search by title",
                required: false,
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Sort field",
                required: false,
                schema: new OA\Schema(type: "string", default: "created_at")
            ),
            new OA\Parameter(
                name: "sort_order",
                in: "query",
                description: "Sort order",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "desc")
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function index(Request $request)
    {
        try {
            $query = Tour::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by featured
            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->boolean('is_featured'));
            }

            // Filter by popular
            if ($request->has('is_popular')) {
                $query->where('is_popular', $request->boolean('is_popular'));
            }

            // Filter by tour type
            if ($request->has('tour_type')) {
                $query->where('tour_type', $request->tour_type);
            }

            // Filter by difficulty level
            if ($request->has('difficulty_level')) {
                $query->where('difficulty_level', $request->difficulty_level);
            }

            // Filter by destination
            if ($request->has('destination')) {
                $query->where('destination', 'like', '%' . $request->destination . '%');
            }

            // Search by title
            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $tours = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => [
                    'tours' => TourResource::collection($tours),
                    'pagination' => [
                        'total' => $tours->total(),
                        'per_page' => $tours->perPage(),
                        'current_page' => $tours->currentPage(),
                        'last_page' => $tours->lastPage(),
                    ]
                ],
                'message' => 'Tours retrieved successfully'
            ]);
        } catch (\Throwable $th) {
            \Log::error('Tour index error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tours'
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/tours",
        summary: "Create a new tour package",
        tags: ["Tours"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["title", "destination", "price", "currency", "duration_days", "duration_nights", "difficulty_level", "max_group_size", "min_group_size", "tour_type", "available_slots"],
                    properties: [
                        new OA\Property(property: "title", type: "string"),
                        new OA\Property(property: "destination", type: "string"),
                        new OA\Property(property: "description", type: "string"),
                        new OA\Property(property: "featured_image", type: "string", format: "binary"),
                        new OA\Property(property: "gallery_images", type: "array", items: new OA\Items(type: "string", format: "binary")),
                        new OA\Property(property: "price", type: "number", format: "float"),
                        new OA\Property(property: "currency", type: "string"),
                        new OA\Property(property: "discount_price", type: "number", format: "float"),
                        new OA\Property(property: "duration_days", type: "integer"),
                        new OA\Property(property: "duration_nights", type: "integer"),
                        new OA\Property(property: "difficulty_level", type: "string", enum: ["Easy", "Moderate", "Challenging", "Extreme"]),
                        new OA\Property(property: "max_group_size", type: "integer"),
                        new OA\Property(property: "min_group_size", type: "integer"),
                        new OA\Property(property: "tour_type", type: "string"),
                        new OA\Property(property: "available_slots", type: "integer")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Tour created successfully"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function store(TourRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                $featuredImage = $request->file('featured_image');
                $featuredImageName = uniqid('tour_featured_') . '.' . $featuredImage->getClientOriginalExtension();
                $featuredImagePath = $featuredImage->storeAs('tours/featured', $featuredImageName, 'public');
                $data['featured_image'] = $featuredImagePath;
            }

            // Handle gallery images upload
            if ($request->hasFile('gallery_images')) {
                $galleryPaths = [];
                foreach ($request->file('gallery_images') as $image) {
                    $imageName = uniqid('tour_gallery_') . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('tours/gallery', $imageName, 'public');
                    $galleryPaths[] = $imagePath;
                }
                $data['gallery_images'] = $galleryPaths;
            }

            // Convert JSON strings to arrays if needed
            $jsonFields = ['inclusions', 'exclusions', 'accommodation_details', 'meal_plan', 'itinerary', 'tags'];
            foreach ($jsonFields as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $data[$field] = json_decode($data[$field], true);
                }
            }

            $tour = Tour::create($data);

            return response()->json([
                'success' => true,
                'data' => new TourResource($tour),
                'message' => 'Tour created successfully'
            ], 201);
        } catch (\Throwable $th) {
            \Log::error('Tour store error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tour',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/tours/{id}",
        summary: "Get a specific tour package",
        tags: ["Tours"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Tour not found")
        ]
    )]
    public function show($id)
    {
        try {
            $tour = Tour::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new TourResource($tour),
                'message' => 'Tour retrieved successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Tour not found'
            ], 404);
        }
    }

    #[OA\Post(
        path: "/api/tours/{id}",
        summary: "Update a tour package",
        tags: ["Tours"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(mediaType: "multipart/form-data")
        ),
        responses: [
            new OA\Response(response: 200, description: "Tour updated successfully"),
            new OA\Response(response: 404, description: "Tour not found"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function update(TourRequest $request, $id)
    {
        try {
            $tour = Tour::findOrFail($id);
            $data = $request->validated();

            // Handle featured image upload
            if ($request->hasFile('featured_image')) {
                // Delete old image
                if ($tour->featured_image) {
                    Storage::disk('public')->delete($tour->featured_image);
                }

                $featuredImage = $request->file('featured_image');
                $featuredImageName = uniqid('tour_featured_') . '.' . $featuredImage->getClientOriginalExtension();
                $featuredImagePath = $featuredImage->storeAs('tours/featured', $featuredImageName, 'public');
                $data['featured_image'] = $featuredImagePath;
            }

            // Handle gallery images upload
            if ($request->hasFile('gallery_images')) {
                // Delete old gallery images
                if ($tour->gallery_images) {
                    foreach ($tour->gallery_images as $oldImage) {
                        Storage::disk('public')->delete($oldImage);
                    }
                }

                $galleryPaths = [];
                foreach ($request->file('gallery_images') as $image) {
                    $imageName = uniqid('tour_gallery_') . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('tours/gallery', $imageName, 'public');
                    $galleryPaths[] = $imagePath;
                }
                $data['gallery_images'] = $galleryPaths;
            }

            // Convert JSON strings to arrays if needed
            $jsonFields = ['inclusions', 'exclusions', 'accommodation_details', 'meal_plan', 'itinerary', 'tags'];
            foreach ($jsonFields as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $data[$field] = json_decode($data[$field], true);
                }
            }

            $tour->update($data);

            return response()->json([
                'success' => true,
                'data' => new TourResource($tour),
                'message' => 'Tour updated successfully'
            ]);
        } catch (\Throwable $th) {
            \Log::error('Tour update error: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tour'
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/tours/{id}",
        summary: "Delete a tour package",
        tags: ["Tours"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Tour deleted successfully"),
            new OA\Response(response: 404, description: "Tour not found"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function destroy($id)
    {
        try {
            $tour = Tour::findOrFail($id);

            // Delete featured image
            if ($tour->featured_image) {
                Storage::disk('public')->delete($tour->featured_image);
            }

            // Delete gallery images
            if ($tour->gallery_images) {
                foreach ($tour->gallery_images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }

            $tour->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tour deleted successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tour'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/tours/featured",
        summary: "Get featured tour packages",
        tags: ["Tours"],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function featured()
    {
        try {
            $tours = Tour::where('is_featured', true)
                ->where('is_active', true)
                ->latest()
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
                'message' => 'Featured tours retrieved successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured tours'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/tours/popular",
        summary: "Get popular tour packages",
        tags: ["Tours"],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 500, description: "Server Error")
        ]
    )]
    public function popular()
    {
        try {
            $tours = Tour::where('is_popular', true)
                ->where('is_active', true)
                ->latest()
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
                'message' => 'Popular tours retrieved successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve popular tours'
            ], 500);
        }
    }
}
