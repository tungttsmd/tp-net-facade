<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HostTagController extends Controller
{
    private string $file = 'tags.list.json';

    /** GET /api/host/tags */
    public function index()
    {
        if (!Storage::exists($this->file)) {
            Storage::put($this->file, '{}');
        }

        return response()->json(
            json_decode(Storage::get($this->file), true)
        );
    }

    /** POST /api/host/{id}/tags */
    public function save(Request $req, string $id)
    {
        if (!Storage::exists($this->file)) {
            Storage::put($this->file, '{}');
        }

        $data = json_decode(Storage::get($this->file), true);

        $data[$id] = array_values(
            array_unique($req->input('tags', []))
        );

        Storage::put(
            $this->file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return response()->json(['ok' => true]);
    }
}
