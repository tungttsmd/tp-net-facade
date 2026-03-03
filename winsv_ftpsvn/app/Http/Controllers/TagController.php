<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class TagController extends Controller
{
    private string $file = 'tags.master.json';

    public function index()
    {
        if (!Storage::exists($this->file)) {
            Storage::put($this->file, '{}');
        }
        return response()->json(
            json_decode(Storage::get('tags.master.json'), true)
        );
    }

    public function store(Request $r)
    {
        $tags = json_decode(Storage::get($this->file), true);
        $tags[$r->name] = [
            'color' => $r->color,
            'text' => $r->text
        ];
        Storage::put($this->file, json_encode($tags, JSON_PRETTY_PRINT));
        return response()->json(['ok' => true]);
    }

    public function update(Request $r, string $name)
    {
        $tags = json_decode(Storage::get($this->file), true);
        $tags[$name] = $r->only(['color', 'text']);
        Storage::put($this->file, json_encode($tags, JSON_PRETTY_PRINT));
        return response()->json(['ok' => true]);
    }

    public function destroy(string $name)
    {
        $tags = json_decode(Storage::get($this->file), true);
        unset($tags[$name]);
        Storage::put($this->file, json_encode($tags, JSON_PRETTY_PRINT));
        return response()->json(['ok' => true]);
    }
}
