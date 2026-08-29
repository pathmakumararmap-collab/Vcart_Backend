<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ContentImageController extends Controller
{
    #[OA\Post(
        path: '/admin/content-images',
        tags: ['Admin'],
        summary: 'Upload an image to embed inside rich text content (e.g. a product description)',
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 201, description: 'Uploaded image URL')],
    )]
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('products.update') || $request->user()->can('products.create'), 403);

        $request->validate([
            'image' => ['required', 'image', 'max:4096'],
        ]);

        $path = $request->file('image')->store('content', 'public');

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
