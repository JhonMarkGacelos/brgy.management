<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('postedBy')
            ->orderByDesc('created_at')
            ->paginate(10);

        $stats = [
            'published' => Announcement::where('status', 'Published')->count(),
            'draft'     => Announcement::where('status', 'Draft')->count(),
            'archived'  => Announcement::where('status', 'Archived')->count(),
            'this_month'=> Announcement::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return view('announcements.index', compact('announcements', 'stats'));
    }

    public function create()
    {
        return view('announcements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'category' => 'required|string',
            'audience' => 'nullable|in:All Residents,Senior Citizens,PWD,4Ps,Voters',
        ]);

        Announcement::create([
            'title'        => $request->title,
            'content'      => $request->content,
            'category'     => $request->category,
            'audience'     => $request->audience ?? 'All Residents',
            'status'       => $request->status ?? 'Published',
            'published_at' => $request->published_at ?? now(),
            'expires_at'   => $request->expires_at,
            'posted_by'    => Auth::id(),
        ]);

        $route = Auth::user()->role === 'staff' ? 'staff.announcements.index' : 'announcements.index';
        return redirect()->to(route($route))->with('success', 'Announcement posted successfully.');
    }

    public function show(string $id)
    {
        $announcement = Announcement::with('postedBy')->findOrFail($id);
        return view('announcements.show', compact('announcement'));
    }

    public function edit(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        return view('announcements.create', compact('announcement'));
    }

    public function update(Request $request, string $id)
    {
        $announcement = Announcement::findOrFail($id);

        $announcement->update([
            'title'        => $request->title,
            'content'      => $request->content,
            'category'     => $request->category,
            'audience'     => $request->audience ?? $announcement->audience,
            'status'       => $request->status ?? $announcement->status,
            'published_at' => $request->published_at ?? $announcement->published_at,
            'expires_at'   => $request->expires_at ?? $announcement->expires_at,
        ]);

        $route = Auth::user()->role === 'staff' ? 'staff.announcements.index' : 'announcements.index';
        return redirect()->to(route($route))->with('success', 'Announcement updated.');
    }

    public function destroy(string $id)
    {
        Announcement::findOrFail($id)->delete();
        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }
}
