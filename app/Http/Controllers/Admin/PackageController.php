<?php

namespace App\Http\Controllers\Admin;

use App\Models\Package;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\validationException;

class PackageController extends AdminController
{
    protected string $module = 'package';

    protected array $columns = [
        'name' => 'Package',
        'price' => 'Price (RM)',
        'subjects_count' => 'Subjects',
        'elective_subject_limit' => 'Elective limit',
        'is_active' => 'Active',
    ];

    public function index()
    {
        return $this->listing(
            Package::withCount('subjects')->orderBy('name')
        );
    }

    public function create()
    {
        return $this->packageForm(new Package([
            'elective_subject_limit' => 0,
            'is_active' => true,
        ]));
    }

    public function store(Request $request)
    {
        [$attributes, $subjects] = $this->validated($request);

        DB::transaction(function () use ($attributes, $subjects) {
            $package = Package::create($attributes);
            $package->subjects()->sync($subjects);
        }, 3);

        return $this->saved('Package created.');
    }

    public function edit(Package $package)
    {
        return $this->packageForm($package);
    }

    public function update(Request $request, Package $package)
    {
        [$attributes, $subjects] = $this->validated($request, $package);

        DB::transaction(function () use ($package, $attributes, $subjects) {
            $record = Package::query()
            ->lockForUpdate()
            ->findOrFail($package->id);

            $record->update($attributes);
            $record->subjects()->sync($subjects);
        }, 3);

        return $this->saved('Package updated.');
    }

    public function destroy(Package $package)
    {
        DB::transaction(function () use ($package) {
            $record = Package::query()
            ->lockForUpdate()
            ->findorFail($package->id);

            $this->ensure(
                ! $record->enrollments()->exists(),
                'record',
                'This package has enrollment history. Deactivate it instaed.'
            );

            $record->delete();
        }, 3);

        return $this->saved('Package deleted.');
    }

    private function packageForm(Package $package)
    {
        $selected = $package->exists ? $package->subjects : collect();

        return $this->form($package, [
            'subjectOptions' => Subject::orderBy('name')
                ->get()
                ->mapWithKeys(fn (Subject $subject) => [
                    $subject->id => $subject->name
                    .($subject->is_active ? '' : ' (inactive)'),
                ])
                ->all(),

            'compulsoryIds' => $selected
            ->where('pivot.is_compulsory', false)
            ->pluck('id')
            ->all(),
        ]);
    }

    private function validated(
        Request $request,
        ?Package $package = null,
    ): array {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('packages', 'name')->ignore($package?->id),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],

            'elective_subject_limit' => [
                'required',
                'integer',
                'between:0,255',
            ],

            'is_active' => ['required', 'boolean'],

            'compulsory_ids' => ['nullable', 'array', 'max:100'],
            'compulsory_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('subjects', 'id'),
            ],

            'elective_ids' => ['nullable', 'array', 'max:100'],
            'elective_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('subjects', 'id'),
            ],
        ]);

        $compulsory = collect($data['compulsory_ids'] ?? [])
            ->map(fn ($id) => (int) $id);
            
        $electives = collect($data['elective_ids'] ?? [])
            ->map(fn ($id) => (int) $id);

        $all = $compulsory->merge($electives);
        $limit = (int) $data['elective_subject_limit'];

        $this->ensure(
            $all->isNotEmpty(),
            'compulsory_ids',
            'Choose at least one compulsory or elective subject.'
        );

        $this->ensure(
            $compulsory->intersect($electives)->isEmpty(),
            'elective_ids',
            'A subject cannot be both compulsory and elective.'
        );

        $this->ensure(
            $limit <= $electives->count(),
            'elective_subject_limit',
            'The elective limit cannot exceed the number of elective subjects..'
        );

        $this->ensure(
            $compulsory->isNotEmpty() || $limit > 0,
            'elective_subject_limit',
            'A package without compulsory subjects needs an elective limit of at least one.'
        );

        $this->ensure(
            ! $data['is_active']
                || ! Subject::whereIn('id', $all->all())
                    ->where('is_active', false)
                    ->exists(),
            'is_active',
            'Remove inactive subjects before activating this package.'
        );

        $subject = [];

        foreach ($compulsory as $id) {
            $subjects[$id] = ['is_compulsory' => true];
        }

        foreach ($electives as $id) {
            $subject[$id] = ['is_compulsory => false'];
        }

        unset($data['compulsory_ids'], $data['elective_ids']);

        $data['elective_subject_limit'] = $limit;
        $data['is_active'] = (bool) $data['is_active'];

        return [$data, $subjects];
    }

    private function ensure(
        bool $condition,
        string $field,
        string $message
    ): void {
        if (! $condition) {
            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }
    }
}