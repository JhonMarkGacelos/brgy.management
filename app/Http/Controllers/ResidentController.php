<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\IncomeSource;
use App\Models\Resident;
use App\Services\ClassificationService;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResidentController extends Controller
{
    private const ID_DOC_RULE          = 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120';
    private const ID_DOC_REQUIRED_RULE = 'required|image|mimes:jpg,jpeg,png,webp|max:5120';

    /**
     * Upload/keep/clear a PWD or Solo Parent ID document for one resident.
     * Returns ['url' => ?string, 'public_id' => ?string] or ['error' => string] on upload failure.
     */
    private function resolveIdDocument(Request $request, string $fileKey, bool $wants, ?string $existingUrl, ?string $existingPublicId, string $folder, string $label): array
    {
        $url      = $existingUrl;
        $publicId = $existingPublicId;

        if ($request->hasFile($fileKey)) {
            try {
                if ($publicId) {
                    app(CloudinaryService::class)->delete($publicId);
                }
                $uploaded = app(CloudinaryService::class)->uploadIdPhoto($request->file($fileKey), $folder);
                $url      = $uploaded['url'];
                $publicId = $uploaded['public_id'];
            } catch (\Throwable $e) {
                return ['error' => "{$label} upload failed. Please try again or contact barangay staff."];
            }
        }

        if (!$wants) {
            $url      = null;
            $publicId = null;
        }

        return ['url' => $url, 'public_id' => $publicId];
    }

    private function isSeniorPerson(array $person): bool
    {
        return !empty($person['date_of_birth'])
            && \Carbon\Carbon::parse($person['date_of_birth'])->age >= Resident::SENIOR_AGE;
    }

    /** A Senior (OSCA) ID photo is required for DSWD Social Pension beneficiaries. */
    private function wantsSeniorId(array $person): bool
    {
        return ($person['pension'] ?? null) === 'social' && $this->isSeniorPerson($person);
    }

    private function pensionAmountRule(array $person): string
    {
        return in_array($person['pension'] ?? null, ['social', 'other'], true) && $this->isSeniorPerson($person)
            ? 'required|numeric|min:0' : 'nullable|numeric|min:0';
    }

    private function isStaff(): bool
    {
        return Auth::user()->role === 'staff';
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
                'Social Pension'=> 'is_social_pensioner',
                'PWD'           => 'is_pwd',
                'Solo Parent'   => 'is_solo_parent',
                'Pregnant'      => 'is_pregnant',
            ];
            if ($sector === 'Pregnant') {
                $query->whereHas('residents', fn($r) => $r->currentlyPregnant());
            } elseif ($col = $sectorMap[$sector] ?? null) {
                $query->whereHas('residents', fn($r) => $r->where($col, true));
            }
        }

        if ($classification = $request->classification) {
            $query->where('classification', $classification);
        }

        if ($request->boolean('review')) {
            $query->needsReview();
        }

        if ($psaStatus = $request->psa_status) {
            $psaStatus === 'below'
                ? $query->whereIn('psa_status', ClassificationService::PSA_POOR)
                : $query->where('psa_status', $psaStatus);
        }

        if ($employmentStatus = $request->employment_status) {
            $query->whereHas('residents', fn($r) => $r->where('employment_status', $employmentStatus));
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

    public function roster(Request $request)
    {
        $query = Resident::with('household');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($purok = $request->purok) {
            $query->whereHas('household', fn($q) => $q->where('purok', $purok));
        }

        if ($gender = $request->gender) {
            $query->where('gender', $gender);
        }

        if ($status = $request->status) {
            $query->where('status', $status);
        }

        if ($employmentStatus = $request->employment_status) {
            $query->where('employment_status', $employmentStatus);
        }

        if ($sector = $request->sector) {
            $sectorMap = [
                '4Ps'            => 'is_4ps',
                'Senior Citizen' => 'is_senior_citizen',
                'Social Pension' => 'is_social_pensioner',
                'PWD'            => 'is_pwd',
                'Solo Parent'    => 'is_solo_parent',
                'Voter'          => 'is_voter',
                'Indigent'       => 'is_indigent',
                'Pregnant'       => 'is_pregnant',
            ];
            if ($sector === 'Social Pension Candidates') {
                $query->socialPensionCandidates();
            } elseif ($sector === 'Pregnant') {
                $query->currentlyPregnant();
            } elseif ($col = $sectorMap[$sector] ?? null) {
                $query->where($col, true);
            }
        }

        $residents = $query->orderBy('last_name')->orderBy('first_name')->get();
        $puroks    = Household::distinct()->orderBy('purok')->pluck('purok');

        return view('residents.roster', compact('residents', 'puroks'));
    }

    public function create()
    {
        return view('residents.create');
    }

    public function store(Request $request)
    {
        $rules = [
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
            'families.*.head.pension'             => 'nullable|in:none,social,other',
            'families.*.members.*.pension'        => 'nullable|in:none,social,other',
        ];

        foreach ($request->input('families', []) as $fi => $familyData) {
            $head = $familyData['head'] ?? [];
            $rules["families.$fi.head.pwd_id_document"]         = !empty($head['is_pwd']) ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
            $rules["families.$fi.head.solo_parent_id_document"] = !empty($head['is_solo_parent']) ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
            $rules["families.$fi.head.fourps_id_document"]      = !empty($head['is_4ps']) ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
            $rules["families.$fi.head.senior_id_document"]      = $this->wantsSeniorId($head) ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
            $rules["families.$fi.head.pension_amount"]          = $this->pensionAmountRule($head);

            foreach ($familyData['members'] ?? [] as $mi => $member) {
                $rules["families.$fi.members.$mi.pwd_id_document"]         = !empty($member['is_pwd']) ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
                $rules["families.$fi.members.$mi.solo_parent_id_document"] = !empty($member['is_solo_parent']) ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
                $rules["families.$fi.members.$mi.senior_id_document"]      = $this->wantsSeniorId($member) ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
                $rules["families.$fi.members.$mi.pension_amount"]          = $this->pensionAmountRule($member);
            }
        }

        $request->validate($rules, [
            'families.*.head.date_of_birth.required'      => 'Date of birth is required for the head of family.',
            'families.*.head.civil_status.required'       => 'Civil status is required for the head of family.',
            'families.*.members.*.first_name.required'    => 'First name is required for all members.',
            'families.*.members.*.last_name.required'     => 'Last name is required for all members.',
            'families.*.members.*.date_of_birth.required' => 'Date of birth is required for all members.',
            'families.*.members.*.gender.required'        => 'Gender is required for all members.',
            'families.*.members.*.relationship.required'  => 'Relationship is required for all members.',
            'families.*.head.pwd_id_document.required'              => 'A PWD ID photo is required for the head of family when tagged as PWD.',
            'families.*.head.solo_parent_id_document.required'      => 'A Solo Parent ID photo is required for the head of family when tagged as Solo Parent.',
            'families.*.head.fourps_id_document.required'           => 'A 4Ps ID photo is required for the head of family when tagged as 4Ps.',
            'families.*.members.*.pwd_id_document.required'         => 'A PWD ID photo is required for this member.',
            'families.*.members.*.solo_parent_id_document.required' => 'A Solo Parent ID photo is required for this member.',
            'families.*.head.senior_id_document.required'           => 'A Senior Citizen (OSCA) ID photo is required for DSWD Social Pension beneficiaries.',
            'families.*.members.*.senior_id_document.required'      => 'A Senior Citizen (OSCA) ID photo is required for DSWD Social Pension beneficiaries.',
            'families.*.head.pension_amount.required'               => 'Enter the monthly pension amount.',
            'families.*.members.*.pension_amount.required'          => 'Enter the monthly pension amount.',
        ]);

        // All families are saved together: a failed upload or insert must not leave an empty household behind.
        DB::beginTransaction();
        try {
            foreach ($request->input('families', []) as $fi => $familyData) {
                $head = $familyData['head'] ?? [];
                if (empty($head['first_name'])) continue;

                $headPwd = $this->resolveIdDocument($request, "families.$fi.head.pwd_id_document", !empty($head['is_pwd']), null, null, 'PWD IDs', 'PWD ID');
                if (isset($headPwd['error'])) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(["families.$fi.head.pwd_id_document" => $headPwd['error']]);
                }
                $headSp = $this->resolveIdDocument($request, "families.$fi.head.solo_parent_id_document", !empty($head['is_solo_parent']), null, null, 'Solo Parent IDs', 'Solo Parent ID');
                if (isset($headSp['error'])) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(["families.$fi.head.solo_parent_id_document" => $headSp['error']]);
                }
                $head4ps = $this->resolveIdDocument($request, "families.$fi.head.fourps_id_document", !empty($head['is_4ps']), null, null, '4Ps IDs', '4Ps ID');
                if (isset($head4ps['error'])) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(["families.$fi.head.fourps_id_document" => $head4ps['error']]);
                }
                $headSenior = $this->resolveIdDocument($request, "families.$fi.head.senior_id_document", $this->wantsSeniorId($head), null, null, 'Senior IDs', 'Senior Citizen ID');
                if (isset($headSenior['error'])) {
                    DB::rollBack();
                    return back()->withInput()->withErrors(["families.$fi.head.senior_id_document" => $headSenior['error']]);
                }

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
                    'email'                => $head['email'] ?? null,
                    'employment_status'    => $head['employment_status'] ?? null,
                    'monthly_income'       => $head['monthly_income'] ?? null,
                    'is_4ps'               => !empty($head['is_4ps']),
                    'is_senior_citizen'    => !empty($head['is_senior_citizen']),
                    ...Resident::pensionFields($head['pension'] ?? null, $head['pension_amount'] ?? null),
                    'senior_id_url'        => $headSenior['url'],
                    'senior_id_public_id'  => $headSenior['public_id'],
                    'is_pwd'               => !empty($head['is_pwd']),
                    'is_solo_parent'       => !empty($head['is_solo_parent']),
                    'is_voter'             => !empty($head['is_voter']),
                    'is_indigent'          => !empty($head['is_indigent']),
                    'is_pregnant'          => !empty($head['is_pregnant']),
                    'pregnant_due_date'    => !empty($head['is_pregnant']) ? ($head['pregnant_due_date'] ?: null) : null,
                    'pwd_id_url'               => $headPwd['url'],
                    'pwd_id_public_id'         => $headPwd['public_id'],
                    'solo_parent_id_url'       => $headSp['url'],
                    'solo_parent_id_public_id' => $headSp['public_id'],
                    'fourps_id_url'            => $head4ps['url'],
                    'fourps_id_public_id'      => $head4ps['public_id'],
                ]);

                foreach ($familyData['members'] ?? [] as $mi => $member) {
                    if (empty($member['first_name']) && empty($member['last_name'])) continue;

                    $mPwd = $this->resolveIdDocument($request, "families.$fi.members.$mi.pwd_id_document", !empty($member['is_pwd']), null, null, 'PWD IDs', 'PWD ID');
                    if (isset($mPwd['error'])) {
                        DB::rollBack();
                    return back()->withInput()->withErrors(["families.$fi.members.$mi.pwd_id_document" => $mPwd['error']]);
                    }
                    $mSp = $this->resolveIdDocument($request, "families.$fi.members.$mi.solo_parent_id_document", !empty($member['is_solo_parent']), null, null, 'Solo Parent IDs', 'Solo Parent ID');
                    if (isset($mSp['error'])) {
                        DB::rollBack();
                    return back()->withInput()->withErrors(["families.$fi.members.$mi.solo_parent_id_document" => $mSp['error']]);
                    }
                    $mSenior = $this->resolveIdDocument($request, "families.$fi.members.$mi.senior_id_document", $this->wantsSeniorId($member), null, null, 'Senior IDs', 'Senior Citizen ID');
                    if (isset($mSenior['error'])) {
                        DB::rollBack();
                    return back()->withInput()->withErrors(["families.$fi.members.$mi.senior_id_document" => $mSenior['error']]);
                    }

                    $household->residents()->create([
                        'first_name'           => $member['first_name'] ?? '',
                        'middle_name'          => $member['middle_name'] ?? null,
                        'last_name'            => $member['last_name'] ?? '',
                        'date_of_birth'        => $member['date_of_birth'] ?? null,
                        'age'                  => $member['age'] ?? null,
                        'gender'               => $member['gender'] ?? null,
                        'civil_status'         => $member['civil_status'] ?? null,
                        'nationality'          => 'Filipino',
                        'relationship_to_head' => $member['relationship'] ?? null,
                        'is_head'              => false,
                        'employment_status'    => $member['employment_status'] ?? null,
                        'monthly_income'       => $member['monthly_income'] ?? null,
                        'email'                => $member['email'] ?? null,
                        'is_4ps'               => !empty($member['is_4ps']),
                        'is_senior_citizen'    => !empty($member['is_senior_citizen']),
                        ...Resident::pensionFields($member['pension'] ?? null, $member['pension_amount'] ?? null),
                        'senior_id_url'        => $mSenior['url'],
                        'senior_id_public_id'  => $mSenior['public_id'],
                        'is_pwd'               => !empty($member['is_pwd']),
                        'is_solo_parent'       => !empty($member['is_solo_parent']),
                        'is_voter'             => !empty($member['is_voter']),
                        'is_indigent'          => !empty($member['is_indigent']),
                        'is_pregnant'          => !empty($member['is_pregnant']),
                        'pregnant_due_date'    => !empty($member['is_pregnant']) ? ($member['pregnant_due_date'] ?: null) : null,
                        'pwd_id_url'               => $mPwd['url'],
                        'pwd_id_public_id'         => $mPwd['public_id'],
                        'solo_parent_id_url'       => $mSp['url'],
                        'solo_parent_id_public_id' => $mSp['public_id'],
                    ]);
                }

                ClassificationService::refresh($household);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
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
        $headIn    = $request->input('families.0.head', []);
        $membersIn = $request->input('families.0.members', []);

        // Which ID photos are already on file comes from the database, not from the form.
        $household     = Household::with('residents')->findOrFail($id);
        $currentHead   = $household->residents->firstWhere('is_head', true);
        $currentMember = fn (array $m) => !empty($m['id'])
            ? $household->residents->where('is_head', false)->firstWhere('id', (int) $m['id'])
            : null;

        $rules = [
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
            'families.0.head.pension'            => 'nullable|in:none,social,other',
            'families.0.members.*.pension'       => 'nullable|in:none,social,other',
        ];

        $rules['families.0.head.pwd_id_document'] = (!empty($headIn['is_pwd']) && empty($currentHead?->pwd_id_url))
            ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
        $rules['families.0.head.solo_parent_id_document'] = (!empty($headIn['is_solo_parent']) && empty($currentHead?->solo_parent_id_url))
            ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
        $rules['families.0.head.fourps_id_document'] = (!empty($headIn['is_4ps']) && empty($currentHead?->fourps_id_url))
            ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
        $rules['families.0.head.senior_id_document'] = ($this->wantsSeniorId($headIn) && empty($currentHead?->senior_id_url))
            ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
        $rules['families.0.head.pension_amount'] = $this->pensionAmountRule($headIn);

        foreach ($membersIn as $mi => $member) {
            $rules["families.0.members.$mi.pwd_id_document"] = (!empty($member['is_pwd']) && empty($currentMember($member)?->pwd_id_url))
                ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
            $rules["families.0.members.$mi.solo_parent_id_document"] = (!empty($member['is_solo_parent']) && empty($currentMember($member)?->solo_parent_id_url))
                ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
            $rules["families.0.members.$mi.senior_id_document"] = ($this->wantsSeniorId($member) && empty($currentMember($member)?->senior_id_url))
                ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE;
            $rules["families.0.members.$mi.pension_amount"] = $this->pensionAmountRule($member);
        }

        $request->validate($rules, [
            'families.0.head.date_of_birth.required'      => 'Date of birth is required.',
            'families.0.head.civil_status.required'       => 'Civil status is required.',
            'families.0.members.*.first_name.required'    => 'First name is required for all members.',
            'families.0.members.*.last_name.required'     => 'Last name is required for all members.',
            'families.0.members.*.date_of_birth.required' => 'Date of birth is required for all members.',
            'families.0.members.*.gender.required'        => 'Gender is required for all members.',
            'families.0.members.*.relationship.required'  => 'Relationship is required for all members.',
            'families.0.head.pwd_id_document.required'              => 'A PWD ID photo is required for the head of family when tagged as PWD.',
            'families.0.head.solo_parent_id_document.required'      => 'A Solo Parent ID photo is required for the head of family when tagged as Solo Parent.',
            'families.0.head.fourps_id_document.required'           => 'A 4Ps ID photo is required for the head of family when tagged as 4Ps.',
            'families.0.members.*.pwd_id_document.required'         => 'A PWD ID photo is required for this member.',
            'families.0.members.*.solo_parent_id_document.required' => 'A Solo Parent ID photo is required for this member.',
            'families.0.head.senior_id_document.required'           => 'A Senior Citizen (OSCA) ID photo is required for DSWD Social Pension beneficiaries.',
            'families.0.members.*.senior_id_document.required'      => 'A Senior Citizen (OSCA) ID photo is required for DSWD Social Pension beneficiaries.',
            'families.0.head.pension_amount.required'               => 'Enter the monthly pension amount.',
            'families.0.members.*.pension_amount.required'          => 'Enter the monthly pension amount.',
        ]);

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
            $headPwd = $this->resolveIdDocument(
                $request, 'families.0.head.pwd_id_document', !empty($headData['is_pwd']),
                $currentHead?->pwd_id_url, $currentHead?->pwd_id_public_id,
                'PWD IDs', 'PWD ID'
            );
            if (isset($headPwd['error'])) {
                return back()->withInput()->withErrors(['families.0.head.pwd_id_document' => $headPwd['error']]);
            }
            $headSp = $this->resolveIdDocument(
                $request, 'families.0.head.solo_parent_id_document', !empty($headData['is_solo_parent']),
                $currentHead?->solo_parent_id_url, $currentHead?->solo_parent_id_public_id,
                'Solo Parent IDs', 'Solo Parent ID'
            );
            if (isset($headSp['error'])) {
                return back()->withInput()->withErrors(['families.0.head.solo_parent_id_document' => $headSp['error']]);
            }
            $head4ps = $this->resolveIdDocument(
                $request, 'families.0.head.fourps_id_document', !empty($headData['is_4ps']),
                $currentHead?->fourps_id_url, $currentHead?->fourps_id_public_id,
                '4Ps IDs', '4Ps ID'
            );
            if (isset($head4ps['error'])) {
                return back()->withInput()->withErrors(['families.0.head.fourps_id_document' => $head4ps['error']]);
            }
            $headSenior = $this->resolveIdDocument(
                $request, 'families.0.head.senior_id_document', $this->wantsSeniorId($headData),
                $currentHead?->senior_id_url, $currentHead?->senior_id_public_id,
                'Senior IDs', 'Senior Citizen ID'
            );
            if (isset($headSenior['error'])) {
                return back()->withInput()->withErrors(['families.0.head.senior_id_document' => $headSenior['error']]);
            }

            $head->update([
                'first_name'           => $headData['first_name'],
                'middle_name'          => $headData['middle_name'] ?? null,
                'last_name'            => $headData['last_name'],
                'date_of_birth'        => $headData['date_of_birth'] ?: null,
                'age'                  => $headData['age'] ?? null,
                'gender'               => $headData['gender'],
                'civil_status'         => $headData['civil_status'] ?? null,
                'contact_number'       => $headData['contact_number'] ?? null,
                'email'                => $headData['email'] ?? null,
                'employment_status'    => $headData['employment_status'] ?? null,
                'monthly_income'       => $headData['monthly_income'] ?? null,
                'is_4ps'               => !empty($headData['is_4ps']),
                'is_senior_citizen'    => !empty($headData['is_senior_citizen']),
                ...Resident::pensionFields($headData['pension'] ?? null, $headData['pension_amount'] ?? null),
                'senior_id_url'        => $headSenior['url'],
                'senior_id_public_id'  => $headSenior['public_id'],
                'is_pwd'               => !empty($headData['is_pwd']),
                'is_solo_parent'       => !empty($headData['is_solo_parent']),
                'is_voter'             => !empty($headData['is_voter']),
                'is_indigent'          => !empty($headData['is_indigent']),
                'is_pregnant'          => !empty($headData['is_pregnant']),
                'pregnant_due_date'    => !empty($headData['is_pregnant']) ? ($headData['pregnant_due_date'] ?: null) : null,
                'pwd_id_url'               => $headPwd['url'],
                'pwd_id_public_id'         => $headPwd['public_id'],
                'solo_parent_id_url'       => $headSp['url'],
                'solo_parent_id_public_id' => $headSp['public_id'],
                'fourps_id_url'            => $head4ps['url'],
                'fourps_id_public_id'      => $head4ps['public_id'],
            ]);
        }

        // Members are updated in place (matched by id) so their document requests, blotter links, status
        // and history survive an edit. All uploads run first, so a failed upload changes nothing.
        $memberRows = [];
        foreach ($membersData as $mi => $member) {
            if (empty($member['first_name']) && empty($member['last_name'])) continue;

            $mPwd = $this->resolveIdDocument(
                $request, "families.0.members.$mi.pwd_id_document", !empty($member['is_pwd']),
                $currentMember($member)?->pwd_id_url, $currentMember($member)?->pwd_id_public_id,
                'PWD IDs', 'PWD ID'
            );
            if (isset($mPwd['error'])) {
                return back()->withInput()->withErrors(["families.0.members.$mi.pwd_id_document" => $mPwd['error']]);
            }
            $mSp = $this->resolveIdDocument(
                $request, "families.0.members.$mi.solo_parent_id_document", !empty($member['is_solo_parent']),
                $currentMember($member)?->solo_parent_id_url, $currentMember($member)?->solo_parent_id_public_id,
                'Solo Parent IDs', 'Solo Parent ID'
            );
            if (isset($mSp['error'])) {
                return back()->withInput()->withErrors(["families.0.members.$mi.solo_parent_id_document" => $mSp['error']]);
            }
            $mSenior = $this->resolveIdDocument(
                $request, "families.0.members.$mi.senior_id_document", $this->wantsSeniorId($member),
                $currentMember($member)?->senior_id_url, $currentMember($member)?->senior_id_public_id,
                'Senior IDs', 'Senior Citizen ID'
            );
            if (isset($mSenior['error'])) {
                return back()->withInput()->withErrors(["families.0.members.$mi.senior_id_document" => $mSenior['error']]);
            }

            $memberRows[] = [
                'id'    => !empty($member['id']) ? (int) $member['id'] : null,
                'attrs' => [
                    'first_name'           => $member['first_name'] ?? '',
                    'middle_name'          => $member['middle_name'] ?? null,
                    'last_name'            => $member['last_name'] ?? '',
                    'date_of_birth'        => $member['date_of_birth'] ?: null,
                    'age'                  => $member['age'] ?? null,
                    'gender'               => $member['gender'] ?? null,
                    'civil_status'         => $member['civil_status'] ?? null,
                    'relationship_to_head' => $member['relationship'] ?? null,
                    'is_head'              => false,
                    'employment_status'    => $member['employment_status'] ?? null,
                    'monthly_income'       => $member['monthly_income'] ?? null,
                    'email'                => $member['email'] ?? null,
                    'is_4ps'               => !empty($member['is_4ps']),
                    'is_senior_citizen'    => !empty($member['is_senior_citizen']),
                    ...Resident::pensionFields($member['pension'] ?? null, $member['pension_amount'] ?? null),
                    'senior_id_url'        => $mSenior['url'],
                    'senior_id_public_id'  => $mSenior['public_id'],
                    'is_pwd'               => !empty($member['is_pwd']),
                    'is_solo_parent'       => !empty($member['is_solo_parent']),
                    'is_voter'             => !empty($member['is_voter']),
                    'is_indigent'          => !empty($member['is_indigent']),
                    'is_pregnant'          => !empty($member['is_pregnant']),
                    'pregnant_due_date'    => !empty($member['is_pregnant']) ? ($member['pregnant_due_date'] ?: null) : null,
                    'pwd_id_url'               => $mPwd['url'],
                    'pwd_id_public_id'         => $mPwd['public_id'],
                    'solo_parent_id_url'       => $mSp['url'],
                    'solo_parent_id_public_id' => $mSp['public_id'],
                ],
            ];
        }

        $keepIds = [];
        foreach ($memberRows as $row) {
            $existing = $row['id']
                ? $household->residents()->where('is_head', false)->find($row['id'])
                : null;
            if ($existing) {
                $existing->update($row['attrs']);
                $keepIds[] = $existing->id;
            } else {
                $keepIds[] = $household->residents()->create($row['attrs'] + ['nationality' => 'Filipino'])->id;
            }
        }
        // Members removed from the form
        $household->residents()->where('is_head', false)->whereNotIn('id', $keepIds)->delete();

        ClassificationService::refresh($household);

        return redirect()->to($this->route('residents.show', $household->id))
            ->with('success', 'Household updated successfully.');
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

        $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'gender'          => 'required|in:Male,Female',
            'pwd_id_document' => ($request->boolean('is_pwd') && !$member->pwd_id_url)
                ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE,
            'solo_parent_id_document' => ($request->boolean('is_solo_parent') && !$member->solo_parent_id_url)
                ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE,
            'fourps_id_document' => ($request->boolean('is_4ps') && !$member->fourps_id_url)
                ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE,
            'pension' => 'nullable|in:none,social,other',
            'pension_amount' => $this->pensionAmountRule($request->only('pension', 'date_of_birth')),
            'senior_id_document' => ($this->wantsSeniorId($request->only('pension', 'date_of_birth')) && !$member->senior_id_url)
                ? self::ID_DOC_REQUIRED_RULE : self::ID_DOC_RULE,
        ], [
            'pwd_id_document.required'         => 'A PWD ID photo is required for this member.',
            'solo_parent_id_document.required' => 'A Solo Parent ID photo is required for this member.',
            'fourps_id_document.required'      => 'A 4Ps ID photo is required for the head of family when tagged as 4Ps.',
            'senior_id_document.required'      => 'A Senior Citizen (OSCA) ID photo is required for DSWD Social Pension beneficiaries.',
            'pension_amount.required'          => 'Enter the monthly pension amount.',
        ]);

        $pwd = $this->resolveIdDocument(
            $request, 'pwd_id_document', $request->boolean('is_pwd'),
            $member->pwd_id_url, $member->pwd_id_public_id, 'PWD IDs', 'PWD ID'
        );
        if (isset($pwd['error'])) {
            return back()->withErrors(['pwd_id_document' => $pwd['error']]);
        }
        $sp = $this->resolveIdDocument(
            $request, 'solo_parent_id_document', $request->boolean('is_solo_parent'),
            $member->solo_parent_id_url, $member->solo_parent_id_public_id, 'Solo Parent IDs', 'Solo Parent ID'
        );
        if (isset($sp['error'])) {
            return back()->withErrors(['solo_parent_id_document' => $sp['error']]);
        }
        $fourPs = $this->resolveIdDocument(
            $request, 'fourps_id_document', $request->boolean('is_4ps'),
            $member->fourps_id_url, $member->fourps_id_public_id, '4Ps IDs', '4Ps ID'
        );
        if (isset($fourPs['error'])) {
            return back()->withErrors(['fourps_id_document' => $fourPs['error']]);
        }
        $senior = $this->resolveIdDocument(
            $request, 'senior_id_document', $this->wantsSeniorId($request->only('pension', 'date_of_birth')),
            $member->senior_id_url, $member->senior_id_public_id, 'Senior IDs', 'Senior Citizen ID'
        );
        if (isset($senior['error'])) {
            return back()->withErrors(['senior_id_document' => $senior['error']]);
        }

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
            'email'                => $request->email,
            'employment_status'    => $request->employment_status,
            'monthly_income'       => $request->monthly_income,
            'is_4ps'               => $request->boolean('is_4ps'),
            'is_senior_citizen'    => $request->boolean('is_senior_citizen'),
            ...Resident::pensionFields($request->input('pension'), $request->input('pension_amount')),
            'senior_id_url'            => $senior['url'],
            'senior_id_public_id'      => $senior['public_id'],
            'is_pwd'               => $request->boolean('is_pwd'),
            'is_solo_parent'       => $request->boolean('is_solo_parent'),
            'is_voter'             => $request->boolean('is_voter'),
            'is_indigent'          => $request->boolean('is_indigent'),
            'is_pregnant'          => $request->boolean('is_pregnant'),
            'pregnant_due_date'    => $request->boolean('is_pregnant') ? ($request->pregnant_due_date ?: null) : null,
            'pwd_id_url'               => $pwd['url'],
            'pwd_id_public_id'         => $pwd['public_id'],
            'solo_parent_id_url'       => $sp['url'],
            'solo_parent_id_public_id' => $sp['public_id'],
            'fourps_id_url'            => $fourPs['url'],
            'fourps_id_public_id'      => $fourPs['public_id'],
        ]);

        ClassificationService::refresh($member->household);

        return redirect()->to($this->route('residents.show', $householdId))
            ->with('success', $member->full_name . ' updated successfully.');
    }

    public function destroyMember(string $householdId, string $memberId)
    {
        $member = Resident::where('household_id', $householdId)->where('is_head', false)->findOrFail($memberId);
        $member->delete();

        ClassificationService::refresh(Household::findOrFail($householdId));

        return redirect()->to($this->route('residents.show', $householdId))
            ->with('success', 'Member removed successfully.');
    }
}
