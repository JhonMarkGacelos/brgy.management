@extends('layouts.app')
@section('title', 'User Accounts')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">User Accounts</h2>
        <p class="text-sm text-gray-500">Manage admin, staff, and resident user accounts</p>
    </div>
    <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors"
            style="background-color:#1a4731;"
            onmouseover="this.style.backgroundColor='#2d6a4f'"
            onmouseout="this.style.backgroundColor='#1a4731'">
        <i class="fa-solid fa-user-plus text-xs"></i> Add User
    </button>
</div>

{{-- Stats --}}
@php
    $roleCounts = $users->groupBy('role')->map->count();
@endphp
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    @php
        $roleCards = [
            ['role'=>'admin',    'label'=>'Admins',    'icon'=>'fa-shield-halved',  'bg'=>'bg-red-50',    'text'=>'text-red-600'],
            ['role'=>'staff',    'label'=>'Staff',     'icon'=>'fa-id-badge',       'bg'=>'bg-amber-50',  'text'=>'text-amber-600'],
            ['role'=>'resident', 'label'=>'Residents', 'icon'=>'fa-user',           'bg'=>'bg-sky-50',    'text'=>'text-sky-600'],
        ];
    @endphp
    @foreach($roleCards as $rc)
    <div class="rounded-2xl bg-white border border-gray-100 p-5 shadow-sm flex items-center gap-4">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $rc['bg'] }} {{ $rc['text'] }} text-lg">
            <i class="fa-solid {{ $rc['icon'] }}"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900">{{ $roleCounts[$rc['role']] ?? 0 }}</p>
            <p class="text-xs text-gray-400 font-medium">{{ $rc['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead style="background-color:#1a4731;">
                <tr class="text-left text-xs text-green-100 uppercase">
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Joined</th>
                    <th class="px-5 py-3 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $roleBadge = [
                        'admin'    => 'bg-red-100 text-red-700',
                        'staff'    => 'bg-amber-100 text-amber-700',
                        'resident' => 'bg-sky-100 text-sky-700',
                    ];
                @endphp
                @forelse($users as $user)
                <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white text-xs font-bold"
                                 style="background-color:#1a4731;">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <span class="font-medium text-gray-800">{{ $user->name }}</span>
                            @if($user->id === auth()->id())
                                <span class="text-[10px] font-semibold bg-green-100 text-green-700 rounded-full px-1.5 py-0.5">You</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-500">{{ $user->email }}</td>
                    <td class="px-5 py-3.5">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleBadge[$user->role] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-400">{{ $user->created_at->format('M d, Y') }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick="openEdit({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->role }}')"
                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors">
                                <i class="fa-solid fa-pen-to-square text-[10px]"></i> Edit
                            </button>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user->id) }}"
                                  onsubmit="return confirm('Delete user {{ addslashes($user->name) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold bg-red-50 text-red-500 hover:bg-red-100 transition-colors">
                                    <i class="fa-solid fa-trash text-[10px]"></i> Delete
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center text-gray-400">
                        <i class="fa-solid fa-users text-3xl mb-2 block"></i>
                        No user accounts found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── CREATE MODAL ── --}}
<div id="modal-create" class="hidden fixed inset-0 z-50 md:left-[220px] flex items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100" style="background-color:#1a4731;">
            <p class="text-sm font-semibold text-white"><i class="fa-solid fa-user-plus mr-2"></i>Add New User</p>
            <button onclick="document.getElementById('modal-create').classList.add('hidden')"
                    class="text-white/60 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('users.store') }}" class="p-5 space-y-4">
            @csrf
            @if($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                <ul class="text-xs text-red-600 space-y-1 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Full Name</label>
                <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Maria Santos"
                       class="w-full rounded-xl border {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }} px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email Address</label>
                <input type="email" name="email" required value="{{ old('email') }}" placeholder="e.g. maria@example.com"
                       class="w-full rounded-xl border {{ $errors->has('email') ? 'border-red-400' : 'border-gray-200' }} px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Role</label>
                <select name="role" required
                        class="w-full rounded-xl border {{ $errors->has('role') ? 'border-red-400' : 'border-gray-200' }} px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                    <option value="">Select role…</option>
                    <option value="admin"    {{ old('role') === 'admin'    ? 'selected' : '' }}>Admin</option>
                    <option value="staff"    {{ old('role') === 'staff'    ? 'selected' : '' }}>Staff / Clerk</option>
                    <option value="resident" {{ old('role') === 'resident' ? 'selected' : '' }}>Resident</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Password</label>
                <input type="password" name="password" required minlength="8" placeholder="Min. 8 characters"
                       class="w-full rounded-xl border {{ $errors->has('password') ? 'border-red-400' : 'border-gray-200' }} px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="document.getElementById('modal-create').classList.add('hidden')"
                        class="flex-1 rounded-xl border border-gray-200 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── EDIT MODAL ── --}}
<div id="modal-edit" class="hidden fixed inset-0 z-50 md:left-[220px] flex items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100" style="background-color:#1a4731;">
            <p class="text-sm font-semibold text-white"><i class="fa-solid fa-pen-to-square mr-2"></i>Edit User</p>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')"
                    class="text-white/60 hover:text-white transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form method="POST" id="edit-form" class="p-5 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Full Name</label>
                <input type="text" name="name" id="edit-name" required
                       class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email Address</label>
                <input type="email" name="email" id="edit-email" required
                       class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Role</label>
                <select name="role" id="edit-role" required
                        class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
                    <option value="admin">Admin</option>
                    <option value="staff">Staff / Clerk</option>
                    <option value="resident">Resident</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">New Password <span class="text-gray-400 font-normal">(leave blank to keep current)</span></label>
                <input type="password" name="password" minlength="8" placeholder="Leave blank to keep current"
                       class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirm New Password</label>
                <input type="password" name="password_confirmation"
                       class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
                        class="flex-1 rounded-xl border border-gray-200 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 rounded-xl py-2.5 text-sm font-semibold text-white transition-colors"
                        style="background-color:#1a4731;"
                        onmouseover="this.style.backgroundColor='#2d6a4f'"
                        onmouseout="this.style.backgroundColor='#1a4731'">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Re-open create modal if validation failed --}}
@if($errors->any())
<script>document.getElementById('modal-create').classList.remove('hidden');</script>
@endif

<script>
function openEdit(id, name, email, role) {
    document.getElementById('edit-form').action = '/users/' + id;
    document.getElementById('edit-name').value  = name;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-role').value  = role;
    document.getElementById('modal-edit').classList.remove('hidden');
}
</script>

@endsection
