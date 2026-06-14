<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\IncomeSource;
use App\Models\Resident;
use App\Services\ClassificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResidentController extends Controller
{
    private function isStaff(): bool
    {
        return Auth::user()->role === 'staff';
    }

    private function view(string $name, array $data = []): \Illuminate\View\View
    {
        $prefix = $this->isStaff() ? 'staff.' : '';
        return view($prefix . $name, $data);
    }

    private function route(string $name, mixed $params = []): string
    {
        $prefix = $this->isStaff() ? 'staff.' : '';
        return route($prefix . $name, $params);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $query = Household::with(['head', 'residents'])->withCount('residents');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('purok', 'like', "%{$search}%")
                  ->orWhere('street', 'like', "%{$search}%")
                  ->orWhere('house_no', 'like', "%{$search}%")
                  ->orWhereHas('head', fn($r) => $r->where('first_name', 'like', "%{$search}%")
                                                    ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        if ($purok = $request->purok) {
            $query->where('purok', $purok);
        }

        if ($sector = $request->sector) {
            $sectorMap = [
                '4Ps'           => 'is_4ps',
                'Senior Citizen'=> 'is_senior_citizen',
                'PWD'           => 'is_pwd',
                'Solo Parent'   => 'is_solo_parent',
                'Pregnant'      => 'is_pregnant',
            ];
            if ($col = $sectorMap[$sector] ?? null) {
                $query->whereHas('residents', fn($r) => $r->where($col, true));
            }
        }

        if ($classification = $request->classification) {
            $query->where('classification', $classification);
        }

        $households      = $query->orderByDesc('created_at')->paginate(10)->withQueryString();
        $totalResidents  = Resident::count();
        $totalHouseholds = Household::count();
        $seniorCitizens  = Resident::where('is_senior_citizen', true)->count();
        $pwdMembers      = Resident::where('is_pwd', true)->count();
        $puroks          = Household::distinct()->orderBy('purok')->pluck('purok');

        $classificationCounts = Household::whereNotNull('classification')
            ->selectRaw('classification, count(*) as total')
            ->groupBy('classification')
            ->pluck('total', 'classification');

        return view('residents.index', compact(
            'households', 'totalResidents', 'totalHouseholds',
            'seniorCitizens', 'pwdMembers', 'puroks', 'classificationCounts'
        ));
    }

    public function create()
    {
        return view('residents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'purok'                               => 'required|string',
            'families.*.head.first_name'          => 'required|string|max:100',
            'families.*.head.last_name'           => 'required|string|max:100',
            'families.*.head.date_of_birth'       => 'required|date',
            'families.*.head.gender'              => 'required|string',
            'families.*.head.civil_status'        => 'required|string',
            'families.*.members.*.first_name'     => 'required|string|max:100',
            'families.*.members.*.last_name'      => 'required|string|max:100',
            'families.*.members.*.date_of_birth'  => 'required|date',
            'families.*.members.*.gender'         => 'required|string',
            'families.*.members.*.relationship'   => 'required|string',
        ], [
            'families.*.head.date_of_birth.required'      => 'Date of birth is required for the head of family.',
            'families.*.head.civil_status.required'       => 'Civil status is required for the head of family.',
            'families.*.members.*.first_name.required'    => 'First name is required for all members.',
            'families.*.members.*.last_name.required'     => 'Last name is required for all members.',
            'families.*.members.*.date_of_birth.required' => 'Date of birth is required for all members.',
            'families.*.members.*.gender.required'        => 'Gender is required for all members.',
            'families.*.members.*.relationship.required'  => 'Relationship is required for all members.',
        ]);

        foreach ($request->input('families', []) as $familyData) {
            $head = $familyData['head'] ?? [];
            if (empty($head['first_name'])) continue;

            $household = Household::create([
                'house_no' => $request->house_no,
                'street'   => $request->street,
                'purok'    => $request->purok,
            ]);

            $household->residents()->create([
                'first_name'           => $head['first_name'],
                'middle_name'          => $head['middle_name'] ?? null,
                'last_name'            => $head['last_name'],
                'date_of_birth'        => $head['date_of_birth'] ?? null,
                'age'                  => $head['age'] ?? null,
                'gender'               => $head['gender'],
                'civil_status'         => $head['civil_status'] ?? null,
                'nationality'          => 'Filipino',
                'relationship_to_head' => 'Head',
                'is_head'              => true,
                'contact_number'       => $head['contact_number'] ?? null,
                'employment_status'    => $head['employment_status'] ?? null,
                'monthly_income'       => $head['monthly_income'] ?? null,
                'is_4ps'               => !empty($head['is_4ps']),
                'is_senior_citizen'    => !empty($head['is_senior_citizen']),
                'is_pwd'               => !empty($head['is_pwd']),
                'is_solo_parent'       => !empty($head['is_solo_parent']),
                'is_voter'             => !empty($head['is_voter']),
                'is_indigent'          => !empty($head['is_indigent']),
                'is_pregnant'          => !empty($head['is_pregnant']),
                'pregnant_due_date'    => !empty($head['is_pregnant']) ? ($head['pregnant_due_date'] ?: null) : null,
            ]);

            foreach ($familyData['members'] ?? [] as $member) {
                if (empty($member['first_name']) && empty($member['last_name'])) continue;
                $household->residents()->create([
                    'first_name'           => $member['first_name'] ?? '',
                    'middle_name'          => $member['middle_name'] ?? null,
                    'last_name'            => $member['last_name'] ?? '',
                    'date_of_birth'        => $member['date_of_birth'] ?? null,
                    'age'                  => $member['age'] ?? null,
                    'gender'               => $member['gender'] ?? null,
                    'nationality'          => 'Filipino',
                    'relationship_to_head' => $member['relationship'] ?? null,
                    'is_head'              => false,
                    'employment_status'    => $member['employment_status'] ?? null,
                    'monthly_income'       => $member['monthly_income'] ?? null,
                    'is_4ps'               => !empty($member['is_4ps']),
                    'is_senior_citizen'    => !empty($member['is_senior_citizen']),
                    'is_pwd'               => !empty($member['is_pwd']),
                    'is_solo_parent'       => !empty($member['is_solo_parent']),
                    'is_voter'             => !empty($member['is_voter']),
                    'is_indigent'          => !empty($member['is_indigent']),
                    'is_pregnant'          => !empty($member['is_pregnant']),
                    'pregnant_due_date'    => !empty($member['is_pregnant']) ? ($member['pregnant_due_date'] ?: null) : null,
                ]);
            }

            $this->recomputeClassification($household);
        }

        return redirect()->to($this->route('residents.index'))
            ->with('success', 'Household registered successfully.');
    }

    public function show(string $id)
    {
        $household = Household::with(['residents' => fn($q) => $q->orderByDesc('is_head'), 'incomeSources'])->findOrFail($id);
        return view('residents.show', compact('household'));
    }

    public function edit(string $id)
    {
        $household = Household::with(['head', 'residents', 'incomeSources'])->findOrFail($id);
        return view('residents.create', compact('household'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'purok'                              => 'required|string',
            'families.0.head.first_name'         => 'required|string|max:100',
            'families.0.head.last_name'          => 'required|string|max:100',
            'families.0.head.date_of_birth'      => 'required|date',
            'families.0.head.gender'             => 'required|string',
            'families.0.head.civil_status'       => 'required|string',
            'families.0.members.*.first_name'    => 'required|string|max:100',
            'families.0.members.*.last_name'     => 'required|string|max:100',
            'families.0.members.*.date_of_birth' => 'required|date',
            'families.0.members.*.gender'        => 'required|string',
            'families.0.members.*.relationship'  => 'required|string',
        ], [
            'families.0.head.date_of_birth.required'      => 'Date of birth is required.',
            'families.0.head.civil_status.required'       => 'Civil status is required.',
            'families.0.members.*.first_name.required'    => 'First name is required for all members.',
            'families.0.members.*.last_name.required'     => 'Last name is required for all members.',
            'families.0.members.*.date_of_birth.required' => 'Date of birth is required for all members.',
            'families.0.members.*.gender.required'        => 'Gender is required for all members.',
            'families.0.members.*.relationship.required'  => 'Relationship is required for all members.',
        ]);

        $household = Household::with('residents')->findOrFail($id);

        $household->update([
            'house_no' => $request->house_no,
            'street'   => $request->street,
            'purok'    => $request->purok,
        ]);

        $familyData  = $request->input('families.0', []);
        $headData    = $familyData['head'] ?? [];
        $membersData = $familyData['members'] ?? [];

        // Update head of household
        $head = $household->residents()->where('is_head', true)->first();
        if ($head && !empty($headData['first_name'])) {
            $head->update([
                'first_name'           => $headData['first_name'],
                'middle_name'          => $headData['middle_name'] ?? null,
                'last_name'            => $headData['last_name'],
                'date_of_birth'        => $headData['date_of_birth'] ?: null,
                'age'                  => $headData['age'] ?? null,
                'gender'               => $headData['gender'],
                'civil_status'         => $headData['civil_status'] ?? null,
                'contact_number'       => $headData['contact_number'] ?? null,
                'employment_status'    => $headData['employment_status'] ?? null,
                'monthly_income'       => $headData['monthly_income'] ?? null,
                'is_4ps'               => !empty($headData['is_4ps']),
                'is_senior_citizen'    => !empty($headData['is_senior_citizen']),
                'is_pwd'               => !empty($headData['is_pwd']),
                'is_solo_parent'       => !empty($headData['is_solo_parent']),
                'is_voter'             => !empty($headData['is_voter']),
                'is_indigent'          => !empty($headData['is_indigent']),
                'is_pregnant'          => !empty($headData['is_pregnant']),
                'pregnant_due_date'    => !empty($headData['is_pregnant']) ? ($headData['pregnant_due_date'] ?: null) : null,
            ]);
        }

        // Replace members (delete old, insert new)
        $household->residents()->where('is_head', false)->delete();
        foreach ($membersData as $member) {
            if (empty($member['first_name']) && empty($member['last_name'])) continue;
            $household->residents()->create([
                'first_name'           => $member['first_name'] ?? '',
                'middle_name'          => $member['middle_name'] ?? null,
                'last_name'            => $member['last_name'] ?? '',
                'date_of_birth'        => $member['date_of_birth'] ?: null,
                'age'                  => $member['age'] ?? null,
                'gender'               => $member['gender'] ?? null,
                'nationality'          => 'Filipino',
                'relationship_to_head' => $member['relationship'] ?? null,
                'is_head'              => false,
                'monthly_income'       => $member['monthly_income'] ?? null,
                'is_4ps'               => !empty($member['is_4ps']),
                'is_senior_citizen'    => !empty($member['is_senior_citizen']),
                'is_pwd'               => !empty($member['is_pwd']),
                'is_solo_parent'       => !empty($member['is_solo_parent']),
                'is_voter'             => !empty($member['is_voter']),
                'is_indigent'          => !empty($member['is_indigent']),
                'is_pregnant'          => !empty($member['is_pregnant']),
                'pregnant_due_date'    => !empty($member['is_pregnant']) ? ($member['pregnant_due_date'] ?: null) : null,
            ]);
        }

        $this->recomputeClassification($household);

        return redirect()->to($this->route('residents.show', $household->id))
            ->with('success', 'Household updated successfully.');
    }

    private function recomputeClassification(Household $household): void
    {
        $household->load(['residents', 'incomeSources']);
        $result = \App\Services\ClassificationService::classify($household);
        $household->update([
            'classification'  => $result['classification'],
            'welfare_score'   => $result['final_score'],
            'per_capita_income'=> $result['per_capita'],
        ]);
    }

    public function destroy(string $id)
    {
        Household::findOrFail($id)->delete();
        return redirect()->to($this->route('residents.index'))
            ->with('success', 'Household deleted successfully.');
    }

    public function updateMember(Request $request, string $householdId, string $memberId)
    {
        $member = Resident::where('household_id', $householdId)->findOrFail($memberId);

        $member->update([
            'first_name'           => $request->first_name,
            'middle_name'          => $request->middle_name,
            'last_name'            => $request->last_name,
            'date_of_birth'        => $request->date_of_birth ?: null,
            'age'                  => $request->age,
            'gender'               => $request->gender,
            'civil_status'         => $request->civil_status,
            'relationship_to_head' => $request->relationship,
            'contact_number'       => $request->contact_number,
            'employment_status'    => $request->employment_status,
            'monthly_income'       => $request->monthly_income,
            'is_4ps'               => $request->boolean('is_4ps'),
            'is_senior_citizen'    => $request->boolean('is_senior_citizen'),
            'is_pwd'               => $request->boolean('is_pwd'),
            'is_solo_parent'       => $request->boolean('is_solo_parent'),
            'is_voter'             => $request->boolean('is_voter'),
            'is_indigent'          => $request->boolean('is_indigent'),
            'is_pregnant'          => $request->boolean('is_pregnant'),
            'pregnant_due_date'    => $request->boolean('is_pregnant') ? ($request->pregnant_due_date ?: null) : null,
        ]);

        return redirect()->to($this->route('residents.show', $householdId))
            ->with('success', $member->full_name . ' updated successfully.');
    }

    public function destroyMember(string $householdId, string $memberId)
    {
        $member = Resident::where('household_id', $householdId)->where('is_head', false)->findOrFail($memberId);
        $member->delete();

        return redirect()->to($this->route('residents.show', $householdId))
            ->with('success', 'Member removed successfully.');
    }
}
