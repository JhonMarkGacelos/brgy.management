<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Resident;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

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
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'category' => 'required|string',
            'audience' => 'nullable|in:All Residents,Senior Citizens,PWD,4Ps,Voters',
        ]);

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

        // Email all residents with an email address when published
        if ($status === 'Published') {
            $this->notifyResidents($announcement);
        }

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

        $wasPublished = $announcement->status === 'Published';
        $newStatus    = $request->status ?? $announcement->status;

        $announcement->update([
            'title'        => $request->title,
            'content'      => $request->content,
            'category'     => $request->category,
            'audience'     => $request->audience ?? $announcement->audience,
            'status'       => $newStatus,
            'published_at' => $request->published_at ?? $announcement->published_at,
            'expires_at'   => $request->expires_at ?? $announcement->expires_at,
        ]);

        // Email residents only when status first changes to Published (not on re-saves)
        if (!$wasPublished && $newStatus === 'Published') {
            $this->notifyResidents($announcement);
        }

        $route = Auth::user()->role === 'staff' ? 'staff.announcements.index' : 'announcements.index';
        return redirect()->to(route($route))->with('success', 'Announcement updated.');
    }

    public function destroy(string $id)
    {
        Announcement::findOrFail($id)->delete();
        return redirect()->route('announcements.index')->with('success', 'Announcement deleted.');
    }

    private function notifyResidents(Announcement $announcement): void
    {
        $emails = Resident::whereNotNull('email')
            ->where('email', '!=', '')
            ->where('status', 'Active')
            ->pluck('email')
            ->unique()
            ->values();

        // Residents with a portal login get mail + a bell notification; others get mail only
        $portalUsers = User::where('role', 'resident')
            ->whereIn('email', $emails)
            ->get()
            ->keyBy('email');

        foreach ($emails as $email) {
            if ($user = $portalUsers->get($email)) {
                $user->notify(new AnnouncementPublished($announcement));
            } else {
                Notification::route('mail', $email)
                    ->notify(new AnnouncementPublished($announcement));
            }
        }
    }
}
