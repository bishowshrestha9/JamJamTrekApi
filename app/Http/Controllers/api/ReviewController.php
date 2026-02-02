<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ReviewsRequest;
use App\Models\Reviews;
// use App\Models\Lead; // TODO: Uncomment when Lead model is created
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Reviews",
    description: "API Endpoints for managing customer reviews"
)]
class ReviewController extends Controller
{
    #[OA\Post(
        path: "/api/reviews",
        operationId: "submitReview",
        summary: "Submit a new review",
        description: "Creates a new review with rate limiting (one review per hour per email)",
        tags: ["Reviews"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(
                required: ["name", "email", "review", "rating"],
                properties: [
                    new OA\Property(property: "name", type: "string", maxLength: 255, example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", maxLength: 255, example: "john@example.com"),
                    new OA\Property(property: "review", type: "string", example: "Great service! Highly recommended."),
                    new OA\Property(property: "rating", type: "number", format: "float", minimum: 1, maximum: 5, example: 4.5),
                    new OA\Property(property: "status", type: "boolean", example: false, description: "Optional: Review approval status")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Review created successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Review created successfully")
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: "Validation error",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                new OA\Property(property: "errors", type: "object")
            ]
        )
    )]
    #[OA\Response(
        response: 429,
        description: "Rate limit exceeded",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "You can only submit one review per hour with this email address.")
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: "Server error",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Failed to create review")
            ]
        )
    )]
    public function submitReview(ReviewsRequest $request)
    {
        try {
            // Check for existing review from this email in the last hour
            $recentReview = Reviews::where('email', $request->email)
                ->where('created_at', '>=', now()->subHour())
                ->first();
            if ($recentReview) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only submit one review per hour with this email address.',
                ], 429);
            }

            // Create the review
            $review = Reviews::create($request->all());

            // Store review as a lead for lead management
            // TODO: Uncomment when Lead model is created
            // Lead::create([
            //     'name' => $request->name,
            //     'email' => $request->email,
            //     'message' => $request->review,
            //     'source' => 'review',
            //     'status' => 'pending',
            //     'metadata' => [
            //         'review_id' => $review->id,
            //         'rating' => $request->rating,
            //         'review_text' => $request->review,
            //         'status' => $request->status,
            //     ],
            // ]);


            return response()->json([
                'status' => true,
                'message' => 'Review created successfully',
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Review submit error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to create review',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/reviews",
        operationId: "getReviews",
        summary: "Get all reviews with pagination",
        description: "Retrieves all reviews with trek information and pagination support",
        tags: ["Reviews"],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of reviews per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10, example: 10)
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: "Reviews fetched successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Reviews fetched successfully"),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "John Doe"),
                            new OA\Property(property: "email", type: "string", example: "john@example.com"),
                            new OA\Property(property: "review", type: "string", example: "Great service!"),
                            new OA\Property(property: "rating", type: "number", example: 4.5),
                            new OA\Property(property: "status", type: "boolean", example: true),
                            new OA\Property(property: "trek", type: "string", example: "Everest Base Camp Trek", nullable: true),
                            new OA\Property(property: "created_at", type: "string", format: "date", example: "2026-01-30")
                        ]
                    )
                ),
                new OA\Property(
                    property: "pagination",
                    properties: [
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "last_page", type: "integer", example: 5),
                        new OA\Property(property: "per_page", type: "integer", example: 10),
                        new OA\Property(property: "total", type: "integer", example: 50)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: "No reviews found")]
    #[OA\Response(response: 500, description: "Server error")]
    public function getReviews(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $reviews = Reviews::with('trek')->paginate($perPage);

            if ($reviews->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No reviews found',
                ], 404);
            }
            $data = [];
            foreach ($reviews as $review) {

                $data[] = [
                    'id' => $review->id,
                    'name' => $review->name,
                    'email' => $review->email,
                    'review' => $review->review,
                    'rating' => $review->rating,
                    'status' => $review->status,
                    'trek' => $review->trek ? $review->trek->title : null,
                    'created_at' => $review->created_at->toDateString(),
                ];
            }
            return response()->json([
                'status' => true,
                'message' => 'Reviews fetched successfully',
                'data' => $data,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Get reviews error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch reviews',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/reviews/publishable",
        operationId: "getPublishableReviews",
        summary: "Get approved/publishable reviews",
        description: "Retrieves only approved reviews (status=1) with pagination, ordered by most recent",
        tags: ["Reviews"],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of reviews per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 8, example: 8)
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: "Publishable reviews fetched successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Publishable reviews fetched successfully"),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "John Doe"),
                            new OA\Property(property: "email", type: "string", example: "john@example.com"),
                            new OA\Property(property: "review", type: "string", example: "Great service!"),
                            new OA\Property(property: "rating", type: "number", example: 4.5),
                            new OA\Property(property: "trek", type: "string", example: "Everest Base Camp Trek", nullable: true),
                            new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-01-30T12:00:00.000000Z")
                        ]
                    )
                ),
                new OA\Property(
                    property: "pagination",
                    properties: [
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "last_page", type: "integer", example: 3),
                        new OA\Property(property: "per_page", type: "integer", example: 8),
                        new OA\Property(property: "total", type: "integer", example: 24)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: "No publishable reviews found")]
    #[OA\Response(response: 500, description: "Server error")]
    public function getPublishableReviews(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 8);
            $reviews = Reviews::with('trek')->where('status', 1)->orderBy('created_at', 'desc')->paginate($perPage);

            if ($reviews->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No publishable reviews found',
                ], 404);
            }
            $data = [];
            foreach ($reviews as $review) {
                $data[] = [
                    'id' => $review->id,
                    'name' => $review->name,
                    'email' => $review->email,
                    'review' => $review->review,
                    'rating' => $review->rating,
                    'trek' => $review->trek ? $review->trek->title : null,
                    'created_at' => $review->created_at,
                ];
            }
            return response()->json([
                'status' => true,
                'message' => 'Publishable reviews fetched successfully',
                'data' => $data,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Get publishable reviews error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch publishable reviews',
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/reviews/{id}",
        operationId: "deleteReview",
        summary: "Delete a review",
        description: "Permanently deletes a review by ID",
        tags: ["Reviews"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Review ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: "Review deleted successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Reviews deleted successfully")
            ]
        )
    )]
    #[OA\Response(response: 500, description: "Server error")]
    public function delete($id)
    {
        try {
            $review = Reviews::find($id);
            
            if (!$review) {
                return response()->json([
                    'status' => false,
                    'message' => 'Review not found',
                ], 404);
            }
            
            $review->delete();

            return response()->json([
                'status' => true,
                'message' => 'Review deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Delete review error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete review',
            ], 500);
        }
    }

    #[OA\Put(
        path: "/api/reviews/{id}/approve",
        operationId: "approveReview",
        summary: "Approve a review",
        description: "Approves a review by setting its status to true",
        tags: ["Reviews"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Review ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: "Review approved successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Review approved successfully")
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Review not found",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Review not found")
            ]
        )
    )]
    #[OA\Response(response: 500, description: "Server error")]
    public function approveReview($id)
    {
        try {
            $review = Reviews::find($id);
            if (!$review) {
                return response()->json([
                    'status' => false,
                    'message' => 'Review not found',
                ], 404);
            }
            $review->status = true;
            $review->save();
            return response()->json([
                'status' => true,
                'message' => 'Review approved successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Approve review error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to approve review',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/reviews/latest",
        operationId: "getFourReviews",
        summary: "Get latest 4 approved reviews",
        description: "Retrieves the 4 most recent approved reviews for display on homepage or featured sections",
        tags: ["Reviews"]
    )]
    #[OA\Response(
        response: 200,
        description: "Reviews fetched successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Reviews fetched successfully"),
                new OA\Property(
                    property: "data",
                    type: "array",
                    maxItems: 4,
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "name", type: "string", example: "John Doe"),
                            new OA\Property(property: "email", type: "string", example: "john@example.com"),
                            new OA\Property(property: "review", type: "string", example: "Great service!"),
                            new OA\Property(property: "rating", type: "number", example: 4.5),
                            new OA\Property(property: "trek", type: "string", example: "Everest Base Camp Trek", nullable: true),
                            new OA\Property(property: "created_at", type: "string", format: "date", example: "2026-01-30")
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: "No reviews found")]
    #[OA\Response(response: 500, description: "Server error")]
    public function getFourReviews()
    {
        try {
            $reviews = Reviews::with('trek')->where('status', true)->orderBy('created_at', 'desc')->take(4)->get();
            if ($reviews->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No reviews found',
                ], 404);
            }
            $data = [];
            foreach ($reviews as $review) {
                $data[] = [
                    'id' => $review->id,
                    'name' => $review->name,
                    'email' => $review->email,
                    'review' => $review->review,
                    'rating' => $review->rating,
                    'trek' => $review->trek ? $review->trek->title : null,
                    'created_at' => $review->created_at->toDateString(),
                ];
            }
            return response()->json([
                'status' => true,
                'message' => 'Reviews fetched successfully',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Get four reviews error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch reviews',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/reviews/stats",
        operationId: "getPositiveAndNegativeReviewsCount",
        summary: "Get review statistics",
        description: "Returns count of positive reviews (rating >= 4) and negative reviews (rating < 3)",
        tags: ["Reviews"]
    )]
    #[OA\Response(
        response: 200,
        description: "Review statistics retrieved successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "positive_reviews", type: "integer", example: 45, description: "Count of reviews with rating >= 4"),
                        new OA\Property(property: "negative_reviews", type: "integer", example: 5, description: "Count of reviews with rating < 3")
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: "Server error")]
    public function getPositiveAndNegativeReviewsCount()
    {
        try {
            $positiveCount = Reviews::where('rating', '>=', 4)->count();
            $negativeCount = Reviews::where('rating', '<', 3)->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'positive_reviews' => $positiveCount,
                    'negative_reviews' => $negativeCount
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Get review stats error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve review statistics'
            ], 500);
        }
    }
}
