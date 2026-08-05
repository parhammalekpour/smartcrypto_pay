<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KycController extends Controller
{
    /**
     * Store uploaded KYC documents and selfie (supports file upload and base64 selfie)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Validate files (documents can be multiple images or pdf)
        $rules = [
            'documents.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf',
            'selfie' => 'nullable|file|image|max:5120',
            'selfie_data' => 'nullable|string',
        ];

        $request->validate($rules);

        $storedDocs = $user->kyc_documents ?? [];

        // Handle uploaded documents
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                if (!$file->isValid()) continue;
                $path = $file->storeAs('kyc/' . $user->id, time() . '_' . Str::random(8) . '_' . $file->getClientOriginalName());
                $storedDocs[] = $path;
            }
        }

        // Handle selfie file upload
        if ($request->hasFile('selfie') && $request->file('selfie')->isValid()) {
            $file = $request->file('selfie');
            $path = $file->storeAs('kyc/' . $user->id, 'selfie_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension());
            $user->kyc_selfie = $path;
        }

        // Handle base64 selfie (from camera capture)
        if ($request->filled('selfie_data')) {
            // Expected data:image/png;base64,....
            if (preg_match('/^data:(image\/[a-zA-Z]+);base64,(.+)$/', $request->input('selfie_data'), $matches)) {
                $mime = $matches[1];
                $data = base64_decode($matches[2]);
                $ext = explode('/', $mime)[1] ?? 'png';
                $filename = 'selfie_' . time() . '_' . Str::random(6) . '.' . $ext;
                $path = 'kyc/' . $user->id . '/' . $filename;
                Storage::put($path, $data);
                $user->kyc_selfie = $path;
            }
        }

        if (!empty($storedDocs)) {
            $user->kyc_documents = array_values($storedDocs);
        }

        $user->save();

        return back()->with('success', 'مدارک KYC با موفقیت ارسال شد. پس از بررسی، وضعیت احراز هویت تغییر خواهد کرد.');
    }

    /**
     * Serve current user's selfie image (protected)
     */
    public function selfie(Request $request)
    {
        $user = $request->user();
        if (empty($user->kyc_selfie) || !Storage::exists($user->kyc_selfie)) {
            abort(404);
        }

        $path = Storage::path($user->kyc_selfie);
        return response()->file($path);
    }

    public function adminSelfie(User $user)
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403);
        }

        if (empty($user->kyc_selfie) || !Storage::exists($user->kyc_selfie)) {
            abort(404);
        }

        return response()->file(Storage::path($user->kyc_selfie));
    }

    /**
     * Serve a specific document for the current user (protected)
     */
    public function document(Request $request, $filename)
    {
        $user = $request->user();
        $docs = $user->kyc_documents ?? [];
        // find requested file in user's stored docs (exact match or basename)
        $match = null;
        foreach ($docs as $doc) {
            if (basename($doc) === $filename || $doc === $filename) {
                $match = $doc;
                break;
            }
        }

        if (!$match || !Storage::exists($match)) {
            abort(404);
        }

        return response()->file(Storage::path($match));
    }

    public function adminDocument(User $user, $filename)
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403);
        }

        $docs = $user->kyc_documents ?? [];
        $match = null;
        foreach ($docs as $doc) {
            if (basename($doc) === $filename || $doc === $filename) {
                $match = $doc;
                break;
            }
        }

        if (!$match || !Storage::exists($match)) {
            abort(404);
        }

        return response()->file(Storage::path($match));
    }
}
