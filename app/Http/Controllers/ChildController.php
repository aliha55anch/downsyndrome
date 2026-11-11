<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.children.index', ['children' => $request->user()->children()->latest()->get()]);
    }

    public function create(): View { return view('dashboard.children.form', ['child' => new Child]); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $request->user()->children()->create($data);
        return redirect()->route('dashboard.children.index')->with('status', 'Child profile added.');
    }

    public function edit(Request $request, Child $child): View
    {
        $this->authorize('update', $child);
        return view('dashboard.children.form', compact('child'));
    }

    public function update(Request $request, Child $child): RedirectResponse
    {
        $this->authorize('update', $child);
        $child->update($this->validated($request));
        return redirect()->route('dashboard.children.index')->with('status', 'Child profile updated.');
    }

    public function destroy(Request $request, Child $child): RedirectResponse
    {
        $this->authorize('delete', $child);
        $child->delete();
        return redirect()->route('dashboard.children.index')->with('status', 'Child profile deleted. Previous progress history was retained anonymously.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'pronouns' => ['nullable', 'string', 'max:80'],
            'communication_preferences' => ['nullable', 'string', 'max:2000'],
            'interests' => ['nullable', 'string', 'max:2000'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'support_preferences' => ['nullable', 'string', 'max:2000'],
            'learning_goals' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
