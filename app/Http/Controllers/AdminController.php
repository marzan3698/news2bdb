<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => \App\Models\User::count(),
            'admins_count' => \App\Models\User::where('role', 'admin')->count(),
            'regular_users' => \App\Models\User::where('role', 'user')->count(),
        ];
        
        $users = \App\Models\User::orderBy('id', 'desc')->get();

        return view('admin.dashboard', compact('stats', 'users'));
    }

    public function getLatestLogs(Request $request)
    {
        $lastLogId = $request->query('last_log_id', 0);
        
        if ($lastLogId <= 0) {
            // For safety, if no ID passed, don't dump the whole table
            return response()->json(['logs' => []]);
        }

        $logs = \App\Models\GenerationLog::with('article')
                    ->where('id', '>', $lastLogId)
                    ->orderBy('id', 'asc')
                    ->get();
                    
        return response()->json(['logs' => $logs]);
    }
}
