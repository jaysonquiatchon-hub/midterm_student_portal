<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('admin.departments.index', [
            'departments' => Department::query()->withCount('programs')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validatedData($request) + ['status' => 'active']);

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validatedData($request, $department));

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    public function archive(Department $department): RedirectResponse
    {
        $department->update(['status' => 'archived']);

        return redirect()->route('departments.index')->with('success', 'Department archived.');
    }

    private function validatedData(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($department)],
            'name' => ['required', 'string', 'max:255'],
        ]);
    }
}
