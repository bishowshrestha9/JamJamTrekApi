<?php

namespace App\Http\Controllers\api;
use App\Http\Controllers\Controller;
use App\Models\Blogs;
use Illuminate\Http\Request;
use App\Http\Requests\BlogRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Blogs",
    description: "API Endpoints for Blog Management"
)]
class BlogsController extends Controller
{

    #[OA\Get(
        path: "/api/blogs",
        summary: "Get list of blogs",
        description: "Retrieve a paginated list of all blogs",
        operationId: "getBlogs",
        tags: ["Blogs"],
        parameters: [
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Number of items per page",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Blogs fetched successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "title", type: "string", example: "My First Blog"),
                                    new OA\Property(property: "subtitle", type: "string", example: "A subtitle"),
                                    new OA\Property(property: "description", type: "string", example: "Blog description"),
                                    new OA\Property(property: "excerpt", type: "string", example: "Short excerpt"),
                                    new OA\Property(property: "author", type: "string", example: "John Doe"),
                                    new OA\Property(property: "slug", type: "string", example: "my-first-blog"),
                                    new OA\Property(property: "is_active", type: "boolean", example: true),
                                    new OA\Property(property: "image", type: "string", example: "blogs/image.jpg"),
                                    new OA\Property(property: "image_url", type: "string", example: "http://localhost:8000/storage/blogs/image.jpg"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
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
            ),
            new OA\Response(response: 404, description: "No blogs found"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $blogs = Blogs::paginate($perPage);

            if ($blogs->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No blogs found',
                ], 404);
            }

            // Add full image URL to each blog
            $blogs->getCollection()->transform(function ($blog) {
                $blog->image_url = $blog->image ? url('storage/' . $blog->image) : null;
                return $blog;
            });

            return response()->json([
                'status' => true,
                'message' => 'Blogs fetched successfully',
                'data' => $blogs->items(),
                'pagination' => [
                    'current_page' => $blogs->currentPage(),
                    'last_page' => $blogs->lastPage(),
                    'per_page' => $blogs->perPage(),
                    'total' => $blogs->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch blogs',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/blogs",
        summary: "Create a new blog",
        description: "Store a new blog post with image upload",
        operationId: "createBlog",
        tags: ["Blogs"],
        security: [["sanctum" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["title", "description", "is_active", "slug", "image"],
                properties: [
                    new OA\Property(property: "title", type: "string", maxLength: 255, example: "My Blog Title"),
                    new OA\Property(property: "subtitle", type: "string", maxLength: 255, example: "An interesting subtitle"),
                    new OA\Property(property: "description", type: "string", example: "Full blog description"),
                    new OA\Property(property: "excerpt", type: "string", maxLength: 500, example: "Short excerpt"),
                    new OA\Property(property: "author", type: "string", maxLength: 255, example: "John Doe"),
                    new OA\Property(
                        property: "content",
                        type: "string",
                        description: "JSON string of content array with heading and paragraph",
                        example: '[{"heading":"Introduction","paragraph":"This is the intro"},{"heading":"Conclusion","paragraph":"Final thoughts"}]'
                    ),
                    new OA\Property(property: "conclusion", type: "string", example: "Final conclusion text"),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "slug", type: "string", example: "my-blog-title"),
                    new OA\Property(property: "image", type: "string", format: "binary", description: "Blog featured image")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Blog created successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Blog created successfully")
            ]
        )
    )]
    #[OA\Response(response: 422, description: "Validation error")]
    #[OA\Response(response: 500, description: "Server error")]
    public function store(BlogRequest $request)
    {
        try {
            $data = $request->only(['title', 'subtitle', 'description', 'excerpt', 'author', 'content', 'conclusion', 'is_active', 'slug']);

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');

                // Generate unique filename
                $extension = $image->getClientOriginalExtension();
                $filename = 'blog_' . time() . '_' . Str::random(10) . '.' . $extension;

                // Store in storage/app/public/blogs (automatically creates directory)
                $path = $image->storeAs('blogs', $filename, 'public');
                $data['image'] = $path;
            }

            $blog = Blogs::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Blog created successfully',
            ], 201);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to create blog',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/blogs/{title}",
        summary: "Get a single blog",
        description: "Retrieve a blog by its title slug (lowercase with dashes)",
        operationId: "getBlog",
        tags: ["Blogs"],
        parameters: [
            new OA\Parameter(
                name: "title",
                in: "path",
                description: "Blog title slug (e.g., 'my-first-blog')",
                required: true,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Blog fetched successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "title", type: "string", example: "My First Blog"),
                                new OA\Property(property: "subtitle", type: "string", example: "A subtitle"),
                                new OA\Property(property: "description", type: "string", example: "Blog description"),
                                new OA\Property(property: "excerpt", type: "string", example: "Short excerpt"),
                                new OA\Property(property: "author", type: "string", example: "John Doe"),
                                new OA\Property(property: "slug", type: "string", example: "my-first-blog"),
                                new OA\Property(property: "is_active", type: "boolean", example: true),
                                new OA\Property(property: "image", type: "string", example: "blogs/image.jpg"),
                                new OA\Property(property: "image_url", type: "string", example: "http://localhost:8000/storage/blogs/image.jpg"),
                                new OA\Property(
                                    property: "content",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "heading", type: "string"),
                                            new OA\Property(property: "paragraph", type: "string")
                                        ]
                                    )
                                ),
                                new OA\Property(property: "conclusion", type: "string"),
                                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                new OA\Property(property: "updated_at", type: "string", format: "date-time")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Blog not found"),
            new OA\Response(response: 500, description: "Server error")
        ]
    )]
    public function show($title)
    {
        try {

            //lower case and space to dash comparison
            $blog = Blogs::whereRaw('LOWER(REPLACE(title, " ", "-")) = ?', [strtolower($title)])->first();

            if (!$blog) {
                return response()->json([
                    'status' => false,
                    'message' => 'Blog not found',
                ], 404);
            }

            // Add full image URL
            $blog->image_url = $blog->image ? url('storage/' . $blog->image) : null;

            return response()->json([
                'status' => true,
                'message' => 'Blog fetched successfully',
                'data' => $blog,
            ], 200);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch blog',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/blogs/{id}",
        summary: "Update a blog",
        description: "Update an existing blog post. Use POST method with multipart/form-data for file uploads",
        operationId: "updateBlog",
        tags: ["Blogs"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "Blog ID",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "title", type: "string", maxLength: 255, example: "Updated Blog Title"),
                    new OA\Property(property: "subtitle", type: "string", maxLength: 255, example: "Updated subtitle"),
                    new OA\Property(property: "description", type: "string", example: "Updated description"),
                    new OA\Property(property: "excerpt", type: "string", maxLength: 500, example: "Updated excerpt"),
                    new OA\Property(property: "author", type: "string", maxLength: 255, example: "Jane Doe"),
                    new OA\Property(
                        property: "content",
                        type: "string",
                        description: "JSON string of content array",
                        example: '[{"heading":"Updated Section","paragraph":"Updated content"}]'
                    ),
                    new OA\Property(property: "conclusion", type: "string", example: "Updated conclusion"),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "slug", type: "string", example: "updated-blog-title"),
                    new OA\Property(property: "image", type: "string", format: "binary", description: "New blog image (optional)")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Blog updated successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Blog updated successfully")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Blog not found")]
    #[OA\Response(response: 422, description: "Validation error")]
    #[OA\Response(response: 500, description: "Server error")]
    public function update(BlogRequest $request, $id)
    {
        try {
            $blog = Blogs::find($id);
            if (!$blog) {
                return response()->json([
                    'status' => false,
                    'message' => 'Blog not found',
                ], 404);
            }

            $data = $request->only(['title', 'subtitle', 'description', 'excerpt', 'author', 'content', 'conclusion', 'is_active', 'slug']);

            // Handle image upload if new image is provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                    Storage::disk('public')->delete($blog->image);
                }

                $image = $request->file('image');

                // Generate unique filename
                $extension = $image->getClientOriginalExtension();
                $filename = 'blog_' . time() . '_' . Str::random(10) . '.' . $extension;

                // Store in storage/app/public/blogs (automatically creates directory)
                $path = $image->storeAs('blogs', $filename, 'public');
                $data['image'] = $path;
            }

            $blog->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Blog updated successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to update blog',
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/blogs/{id}",
        summary: "Delete a blog",
        description: "Delete a blog post and its associated image",
        operationId: "deleteBlog",
        tags: ["Blogs"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "Blog ID",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Blog deleted successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Blog deleted successfully")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Blog not found")]
    #[OA\Response(response: 500, description: "Server error")]
    public function destroy($id)
    {
        try {
            $blog = Blogs::find($id);
            if (!$blog) {
                return response()->json([
                    'status' => false,
                    'message' => 'Blog not found',
                ], 404);
            }

            // Delete associated image file
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }

            $blog->delete();

            return response()->json([
                'status' => true,
                'message' => 'Blog deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete blog',
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/blogs/total",
        summary: "Get total blogs count",
        description: "Retrieve the total number of blogs in the system",
        operationId: "getTotalBlogs",
        tags: ["Blogs"],
        security: [["sanctum" => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "Success",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "total_blogs", type: "integer", example: 42)
            ]
        )
    )]
    public function getTotalBlogs()
    {
        $total = Blogs::count();
        return response()->json([
            'status' => true,
            'total_blogs' => $total
        ]);
    }

}