<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

abstract class AdminController extends Controller
{
    protected string $module;

    protected array $columns = [];

    protected function listing(Builder $query): View
    {
        return view('admin.shared.index', [
            'module' => $this->module,
            'columns' => $this->columns,
            'records' => $query->paginate(15)->withQueryString(),
        ]);
    }

    protected function form(Model $record, array $extra = []): View
    {
        return view('admin.shared.form', array_merge($extra, [
            'module' => $this->module,
            'record' => $record,
        ]));
    }

    protected function saved(string $message): RedirectResponse
    {
        return to_route("admin.{$this->module}.index")
            ->with('success', $message);
    }

    protected function deleteRecord(
        Model $record,
        bool $inUse = false
    ): RedirectResponse {
        if ($inUse) {
            throw ValidationException::withMessages([
                'record' => 'This record is in use and cannot be deleted.',
            ]);
        }

        try {
            DB::transaction(fn () => $record->delete());
        } catch (QueryException $exception) {
            // Handle a foreign-key restriction if a reference was missed
            // or another request created one immediately before deletion.
            if (in_array(
                (string) $exception->getCode(),
                ['23000', '23503'],
                true
            )) {
                throw ValidationException::withMessages([
                    'record' => 'This record is linked to other data.',
                ]);
            }

            throw $exception;
        }

        return $this->saved('Record deleted.');
    }
}
