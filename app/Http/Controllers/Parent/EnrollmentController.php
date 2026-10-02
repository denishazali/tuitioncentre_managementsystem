<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Subject;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class EnrollmentController extends Controller
{
    public function index()
    {
        return view('parent.enrollments.index', [
            'subjects' => Subject::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'packages' => $this->availablePackages()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['individual', 'bundle'])],
            'package_id' => [
                'required_if:type,bundle',
                'nullable',
                'integer',
                'prohibited_if:type,individual',
            ],
        ]);

        $children = $request->user()
            ->children()
            ->orderBy('name')
            ->get();
        
        if ($children->isEmpty()) {
            return to_route('parent.children.index')
                ->withErrors([
                    'student_id' => 'Register or link a child before enrolling.',
            ]);
        }

        $package = $data['type'] === 'bundle'
            ? $this->availablePackages()->findOrFail($data['package_id'])
            : null;
    
        $subjects = $package
        ? $package->subjects
        : Subject::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

        return view('parent.enrollments.create', [
            'children' => $children,
            'type' => $data['type'],
            'package' => $package,
            'subjects' => $subjects,
        ]);
    }

    public function preview(Request $request)
    {
        $selection = $this->selection($request);
        $quoteToken = (string) Str::uuid();

        $request->session()->put('enrollment_quote', [
            'token' => $quoteToken,
            'parent_id' => $request->user()->id,
            'input' => $selection['input'],
            'subject_ids' => $selection['subjects']
            ->pluck('id')->sort()->values()->all(),
            'total_cents' => $selection['total_cents'],
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return view('parent.enrollments.preview', [
            ...$selection,
            'quoteToken' => $quoteToken,
        ]);
    }

    public function store(Request $request)
    {
        $quote = $request->session()->get('enrollment_quote');
        $token = $request->input('quote_token');

        if(
            ! is_array($quote)
            || ! is_string($token)
            || ! hash_equals($quote['token'], $token)
            || (int) $quote['parent_id'] !== (int) $request->user()->id
            || $quote['expires_at'] <= now()->timestamp
        ) {
            return to_route('parent.enrollments.index')
                ->withErrors([
                    'enrollment' => 'Your review has expired or is invalid. '
                        .'Please review your selection again.',
                ]);
        }

        try {
            $enrollment = DB::transaction(function () use ($request, $quote) {
                // Serialize submissions for this child and prevent reassignment
                // while the enrollment is being saved.
                $child = $request->user()
                    ->children()
                    ->lockForUpdate()
                    ->find($quote['input']['student_id']);

                if (! $child) {
                    throw ValidationException::withMessages([
                        'student_id' => 'This child is no longer linked to your account.',
                    ]);
                }

                // Revalidate only the selection stored by the server.
                $selectionRequest = Request::create(
                    '/',
                    'POST',
                    $quote['input']
                );

                $selectionRequest->setUserResolver(
                    fn () => $request->user()
                );

                $selection = $this->selection($selectionRequest);

                $subjectIds = $selection['subjects']
                    ->pluck('id')->sort()->values()->all();

                if (
                    $selection['total_cents'] !== $quote['total_cents']
                    || $subjectIds !== $quote['subject_ids']
                ) {
                    throw ValidationException::withMessages([
                        'enrollment' => 'The fee or included subjects have changed. '
                            .'Please review your selection again.',
                    ]);
                }

                $enrollment = Enrollment::create([
                    'student_id' => $child->id,
                    'parent_id' => $request->user()->id,
                    'package_id' => $selection['package']?->id,
                    'type' => $selection['type'],
                    'status' => 'pending_payment',
                    'total_amount' => $selection['total'],
                    'submitted_at' => now(),
                ]);

                $enrollment->subjects()->attach(
                    $selection['subjects']->mapWithKeys(
                        fn (Subject $subject) => [
                            $subject->id => [
                                'price_at_enrollment' => $subject->price,
                            ],
                        ]
                    )->all()
                );

                $enrollment->payment()->create([
                    'payment_method' => 'counter',
                    'payment_status' => 'pending',
                    'amount' => $selection['total'],
                ]);

                return $enrollment;
            }, 3);
        } catch (ValidationException $exception) {
            $request->session()->forget('enrollment_quote');

            return to_route('parent.enrollments.index')
                ->withErrors($exception->errors());
        }

        $request->session()->forget('enrollment_quote');

        return to_route('parent.enrollments.history')
            ->with(
                'success',
                "Enrollment #{$enrollment->id} submitted. Please pay at the centre counter. "
            );
    }

    public function history(Request $request)
    {
        return view('parent.enrollments.history', [
            'enrollments' => Enrollment::query()
            ->where('parent_id', $request->user()->id)
            ->with(['student', 'subjects', 'package', 'payment'])
            ->latest()
            ->paginate(15),
        ]);
    }

    public function selection(Request $request): array
    {
        $data = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')
                ->where('parent_id', $request->user()->id),
            ],
            'type' => ['required', Rule::in(['individual', 'bundle'])],
            'package_id' => [
                'required_if:type,bundle',
                'nullable',
                'integer',
                'prohibited_if:type,individual',
            ],
            'subject_ids' => [
                'required_if:type,individual',
                'array',
                'max:100',
            ],
            'subject_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        // Resolve through the parent relationship as an ownership check.
        $child = $request->user()
            ->children()
            ->findOrFail($data['student_id']);

        $selectedIds = collect($data['subject_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        
        $package = null;

        if ($data['type'] === 'bundle') {
            $package = $this->availablePackages()
                ->find($data['package_id']);

            if(! $package) {
                throw ValidationException::withMessages([
                    'package_id' => 'This package is no longer available.',
                ]);
            }

            $compulsory = $package->subjects->filter(
                fn (Subject $subject) => (bool) $subject->pivot->is_compulsory
            );

            $electives = $package->subjects->reject(
                fn (Subject $subject) => (bool) $subject->pivot->is_compulsory
            );

            // Submitted subjects must be electives belonging to this package.
            if ($selectedIds->diff($electives->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'subject_ids' => 'Choose electives from this package only.',
                ]);
            }

            if ($selectedIds->count() > $package->elective_subject_limit) {
                throw ValidationException::withMessages([
                    'subject_ids' => 'You exceeded this package’s elective limit.',
                ]);
            }

            // Compulsory subjects are included by the server.
            $selectedSubjects = $compulsory
                ->merge($electives->whereIn('id', $selectedIds->all()))
                ->values();

            if ($selectedSubjects->isEmpty()) {
                throw ValidationException::withMessages([
                    'subject_ids' => 'Selected at least one subject.',
                ]);
            }

            $totalCents = $this->toCents($package->price);
        } else {
            $selectedSubjects = Subject::query()
                ->where('is_active', true)
                ->whereIn('id', $selectedIds->all())
                ->orderBy('name')
                ->get();

            if (
                $selectedIds->isEmpty()
                || $selectedSubjects->count() !== $selectedIds->count()
            ) {
                throw ValidationException::withMessages([
                    'subject_ids' => 'Choose at least one available subject.',
                ]);
            }

            $totalCents = $selectedSubjects->sum(
                fn (Subject $subject) => $this->toCents($subject->price)
            );
        }

        if ($totalCents < 0 || $totalCents > 9999999999) {
            throw ValidationException::withMessages([
                'enrollment' => 'The calculated amount is outside the allowed range.',
            ]);
        }

        // Check overlaps for this student, including enrollments 
        // originally submitted by another parent.
        $duplicate = Enrollment::query()
            ->where('student_id', $child->id)
            ->whereIn('status', [
                'draft',
                'pending_payment',
                'pending_approval',
                'approved',
            ])
            ->whereHas('subjects', function ($query) use ($selectedSubjects) {
                $query->whereIn('subjects.id', $selectedSubjects->modelKeys());
            })
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'enrollment' => 'This child already has an active enrollment '
                    .'containing one or more selected subjects.',
            ]);
        }

        return [
            'child' => $child,
            'type' => $data['type'],
            'package' => $package,
            'subjects' => $selectedSubjects, 
            'total' => number_format($totalCents / 100, 2, '.', ''),
            'total_cents' => $totalCents,
            'input' => $data,
        ];
    }

    private function availablePackages(): Builder
    {
        return Package::query()
            ->where('is_active', true)
            // Hide packages without any usable subjects.
            ->whereHas('subjects', function (Builder $query) {
                $query->where('subjects.is_active', true);
            })
            // Do not offer an incomplete package.
            ->whereDoesntHave('subjects', function (Builder $query) {
                $query->where('package_subject.is_compulsory', true)
                    ->where('subjects.is_active', false);
            })
            ->with(['subjects' => function ($query) {
                $query->where('subjects.is_active', true)
                    ->orderBy('subjects.name');
            }]);
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) 
        + (int) str_pad($fraction, 2, '0');
    }
}
