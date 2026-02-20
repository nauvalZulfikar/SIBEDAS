<?php

namespace App\Http\Controllers;

use App\Models\PbgTask;
use App\Models\PbgTaskAttachment;
use Illuminate\Http\Request;

class PbgTaskAttachmentsController extends Controller
{
    public function show(string $id, Request $request){
        try{
            $title = $request->get('type') == "berita-acara" ? "Berita Acara" : "Bukti Bayar";
            $data = PbgTaskAttachment::findOrFail($id);
            $pbg = PbgTask::findOrFail($data->pbg_task_id);
            return view('pbg-task-attachment.show', compact('data', 'pbg', 'title'));
        }catch(\Exception $e){
            return view('pages.404');
        }
    }
}
