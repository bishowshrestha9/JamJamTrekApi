<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\Request;
use App\Http\Requests\LegalDocumentRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Legal Document",
    description: "API Endpoints for Legal Document Management"
)]
class LegalDocumentController extends Controller
{
    #[OA\Get(
        path: "/api/legal-documents",
        summary: "Get list of legal documents",
        description: "Retrieve a list of all images in the legal documents gallery",
        operationId: "getLegalDocuments",
        tags: ["Legal Document"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Legal documents fetched successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "image", type: "string", example: "legal_documents/image.jpg"),
                                    new OA\Property(property: "image_url", type: "string", example: "http://localhost:8000/storage/legal_documents/image.jpg"),
                                    new OA\Property(property: "title", type: "string", example: "Company Registration Certificate"),
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
            $documents = LegalDocument::where('is_active', true)->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => true,
                'message' => 'Legal documents fetched successfully',
                'data' => $documents,
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch legal documents',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/legal-documents",
        summary: "Add a new legal document",
        description: "Store a new document image with optional title (admin only)",
        operationId: "createLegalDocument",
        tags: ["Legal Document"],
        security: [["sanctum" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                required: ["images"],
                properties: [
                    new OA\Property(
                        property: "images[]",
                        type: "array",
                        items: new OA\Items(type: "string", format: "binary"),
                        description: "Array of document images to upload"
                    ),
                    new OA\Property(property: "title", type: "string", maxLength: 255, example: "Company Registration Document"),
                    new OA\Property(property: "is_active", type: "boolean", example: true)
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Legal document added successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Legal document added successfully")
            ]
        )
    )]
    #[OA\Response(response: 422, description: "Validation error")]
    #[OA\Response(response: 500, description: "Server error")]
    public function store(LegalDocumentRequest $request)
    {
        try {
            $baseData = $request->only(['title', 'is_active']);
            $createdCount = 0;

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    // Generate unique filename
                    $extension = $image->getClientOriginalExtension();
                    $filename = 'legal_doc_' . time() . '_' . Str::random(10) . '.' . $extension;

                    // Store in storage/app/public/legal_documents
                    $path = $image->storeAs('legal_documents', $filename, 'public');
                    
                    // Create individual record for each document image
                    LegalDocument::create(array_merge($baseData, ['image' => $path]));
                    $createdCount++;
                }
            }

            return response()->json([
                'status' => true,
                'message' => "{$createdCount} document(s) added successfully",
            ], 201);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to add legal document',
            ], 500);
        }
    }

    #[OA\Post(
        path: "/api/legal-documents/{id}",
        summary: "Update a legal document",
        description: "Update title or image. Use POST with multipart/form-data for file uploads (admin only)",
        operationId: "updateLegalDocument",
        tags: ["Legal Document"],
        security: [["sanctum" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        description: "Legal Document ID",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "title", type: "string", maxLength: 255, example: "Updated Company Document"),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "image", type: "string", format: "binary", description: "New image (optional)")
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Legal document updated successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Legal document updated successfully")
            ]
        )
    )]
    #[OA\Response(response: 404, description: "Item not found")]
    #[OA\Response(response: 500, description: "Server error")]
    public function update(LegalDocumentRequest $request, $id)
    {
        try {
            $document = LegalDocument::find($id);
            if (!$document) {
                return response()->json([
                    'status' => false,
                    'message' => 'Legal document not found',
                ], 404);
            }

            $data = $request->only(['title', 'is_active']);

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($document->image && Storage::disk('public')->exists($document->image)) {
                    Storage::disk('public')->delete($document->image);
                }

                $image = $request->file('image');
                $extension = $image->getClientOriginalExtension();
                $filename = 'legal_doc_' . time() . '_' . Str::random(10) . '.' . $extension;
                $path = $image->storeAs('legal_documents', $filename, 'public');
                $data['image'] = $path;
            }

            $document->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Legal document updated successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to update legal document',
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/legal-documents/{id}",
        summary: "Delete a legal document",
        description: "Delete image and associated file (admin only)",
        operationId: "deleteLegalDocument",
        tags: ["Legal Document"],
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
                new OA\Property(property: "message", type: "string", example: "Legal document deleted successfully")
            ]
        )
    )]
    public function destroy($id)
    {
        try {
            $document = LegalDocument::find($id);
            if (!$document) {
                return response()->json([
                    'status' => false,
                    'message' => 'Legal document not found',
                ], 404);
            }

            if ($document->image && Storage::disk('public')->exists($document->image)) {
                Storage::disk('public')->delete($document->image);
            }

            $document->delete();

            return response()->json([
                'status' => true,
                'message' => 'Legal document deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete legal document',
            ], 500);
        }
    }
}
