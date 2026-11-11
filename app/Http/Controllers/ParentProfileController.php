<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ParentProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('dashboard.profile', [
            'user' => $request->user()->load(['parentProfile', 'children']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:female,male,non_binary,prefer_not_to_say'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:500'],
            'children' => ['nullable', 'array', 'max:10'],
            'children.*.id' => ['nullable', 'integer'],
            'children.*.name' => ['required', 'string', 'max:255'],
            'children.*.date_of_birth' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($request, $validated, $user): void {
            $user->forceFill([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => $validated['first_name'].' '.$validated['last_name'],
            ])->save();

            $profile = $user->parentProfile()->firstOrCreate([]);
            $profile->fill([
                'contact' => $validated['contact'] ?? null,
                'gender' => $validated['gender'] ?? null,
            ]);

            if ($request->hasFile('profile_picture')) {
                if ($profile->profile_picture_path) {
                    Storage::disk('local')->delete($profile->profile_picture_path);
                }
                $profile->profile_picture_path = $request->file('profile_picture')->store('profiles', 'local');
            }

            $profile->save();

            foreach ($validated['children'] ?? [] as $childData) {
                if (! empty($childData['id'])) {
                    $child = $user->children()->findOrFail($childData['id']);
                    $child->update([
                        'name' => $childData['name'],
                        'date_of_birth' => $childData['date_of_birth'],
                    ]);
                } else {
                    $user->children()->create([
                        'name' => $childData['name'],
                        'date_of_birth' => $childData['date_of_birth'],
                    ]);
                }
            }
        });

        return back()->with('status', 'Your profile was saved.');
    }

    public function picture(Request $request): mixed
    {
        $path = $request->user()->parentProfile?->profile_picture_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }
}
