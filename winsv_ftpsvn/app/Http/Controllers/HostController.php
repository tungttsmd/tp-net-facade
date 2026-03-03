<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HostNameFileService;

class HostController extends Controller
{
    public function names()
    {
        return response()->json(
            HostNameFileService::all()
        );
    }

    public function rename(Request $request, string $hostId)
    {
        $request->validate([
            'name' => 'required|string|max:64'
        ]);

        HostNameFileService::set($hostId, $request->name);

        return response()->json(['ok' => true]);
    }
}
