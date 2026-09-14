<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GuestDocumentRequest;
use App\Models\Guest;
use App\Models\GuestDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestDocumentController extends Controller
{
    public function store(GuestDocumentRequest $request, Guest $guest): JsonResponse
    {
        Gate::authorize('update', $guest);

        $file = $request->file('file');
        $fileName = Str::ulid().'.'.$file->extension();
        $path = $file->storeAs("guest-documents/{$guest->id}", $fileName, 'private');

        $document = $guest->documents()->create([
            ...$request->safe()->except('file'),
            'file_path' => $path,
        ]);

        return response()->json(['data' => $document], 201);
    }

    public function download(Request $request, GuestDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document->guest);

        DB::table('document_access_logs')->insert([
            'id' => (string) Str::ulid(),
            'guest_document_id' => $document->id,
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'accessed_at' => now(),
        ]);

        return Storage::disk('private')->download($document->file_path);
    }
}
