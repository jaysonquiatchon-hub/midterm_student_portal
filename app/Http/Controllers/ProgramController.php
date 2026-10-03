<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('admin.programs.index', [
            'programs' => Program::query()->withCount('courses')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.programs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Program::create($this->validatedData($request) + ['status' => 'active']);

        return redirect()->route('programs.index')->with('success', 'Program created.');
    }

    public function edit(Program $program): View
    {
        return view('admin.programs.edit', [
            'program' => $program,
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $program->update($this->validatedData($request, $program));

        return redirect()->route('programs.index')->with('success', 'Program updated.');
    }

    public function archive(Program $program): RedirectResponse
    {
        $program->update(['status' => 'archived']);

        return redirect()->route('programs.index')->with('success', 'Program archived.');
    }

    private function validatedData(Request $request, ?Program $program = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('programs', 'code')->ignore($program)],
            'name' => ['required', 'string', 'max:255'],
        ]);
    }
}
