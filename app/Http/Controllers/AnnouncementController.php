<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $filterCategory = $request->category;

        $query = Announcement::with('postedBy')->orderByDesc('created_at');
        if ($filterCategory && $filterCategory !== 'All') {
            $query->where('category', $filterCategory);
        }
        $announcements = $query->paginate(10)->withQueryString();

        $stats = [
            'published' => Announcement::where('status', 'Published')->count(),
            'draft'     => Announcement::where('status', 'Draft')->count(),
            'archived'  => Announcement::where('status', 'Archived')->count(),
            'this_month'=> Announcement::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return view('announcements.index', compact('announcements', 'stats', 'filterCategory'));
    }

    public function create()
    {
        return view('announcements.create');
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());

        $status       = $request->status ?? 'Published';
        $announcement = Announcement::create([
            'title'        => $request->title,
            'content'      => $request->content,
            'category'     => $request->category,
            'audience'     => $request->audience ?? 'All Residents',
            'status'       => $status,
            'published_at' => $request->published_at ?? now(),
            'expires_at'   => $request->expires_at,
            'posted_by'    => Auth::id(),
        ]);

        // Email the audience now if it's live; a later publish date is emailed by announcements:send-scheduled
        $announcement->emailAudienceIfDue();

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
        $request->validate($this->rules());

        $newStatus = $request->status ?? $announcement->status;

        $announcement->update([
            'title'        => $request->title,
            'content'      => $request->content,
            'category'     => $request->category,
            'audience'     => $request->audience ?? $announcement->audience,
            'status'       => $newStatus,
            'published_at' => $request->published_at ?? $announcement->published_at,
            'expires_at'   => $request->expires_at ?? $announcement->expires_at,
        ]);

        // Emails once, the first time the announcement is live (re-saves never resend)
        $announcement->emailAudienceIfDue();

        $route = Auth::user()->role === 'staff' ? 'staff.announcements.index' : 'announcements.index';
        return redirect()->to(route($route))->with('success', 'Announcement updated.');
    }

    public function destroy(string $id)
    {
        Announcement::findOrFail($id)->delete();

        $route = Auth::user()->role === 'staff' ? 'staff.announcements.index' : 'announcements.index';
        return redirect()->route($route)->with('success', 'Announcement deleted.');
    }

    private function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'category'     => 'required|string|max:100',
            'audience'     => 'nullable|in:' . implode(',', Announcement::AUDIENCES),
            'status'       => 'nullable|in:Draft,Published,Archived',
            'published_at' => 'nullable|date',
            'expires_at'   => 'nullable|date',
        ];
    }
}
