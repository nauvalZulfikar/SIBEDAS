<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PbgTaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PbgTaskAttachmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $pbg_task_id)
    {
        try{
            $request->validate([
                'file' => 'required|file|mimes:jpg,png,pdf|max:5120',
                'pbg_type' => 'string'
            ]);

            $attachment = PbgTaskAttachment::create([
                'pbg_task_id' => $pbg_task_id,
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_path' => '', // empty path initially
                'pbg_type' => $request->pbg_type == 'bukti_bayar' ? 'bukti_bayar' : 'berita_acara'
            ]);

            $file = $request->file('file');
            $path = $file->store("uploads/pbg-tasks/{$pbg_task_id}/{$attachment->id}", "public");

            $attachment->update([
                'file_path' => $path,
            ]);

            return response()->json([
                'message' => 'File uploaded successfully.',
                'attachment' => [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_url' => Storage::url($attachment->file_path),
                    'pbg_type' => $attachment->pbg_type
                ] 
            ]);
        }catch(\Exception $e){
            \Log::error($e->getMessage());
            return response()->json([
                "success" => false,
                "message" => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function download(string $id)
    {
        try {
            $data = PbgTaskAttachment::findOrFail($id);
            $filePath = $data->file_path; // already relative to 'public' disk

            if (!Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    "success" => false,
                    "message" => "File not found on server"
                ], Response::HTTP_NOT_FOUND);
            }

            return Storage::disk('public')->download($filePath, $data->file_name);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}
