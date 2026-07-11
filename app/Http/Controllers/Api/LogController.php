<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoryLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index()
    {
        return response()->json(HistoryLog::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'actor' => 'required|string',
            'action' => 'required|string',
            'details' => 'required|string',
        ]);

        $log = HistoryLog::create($validated);
        return response()->json($log, 201);
    }
}
