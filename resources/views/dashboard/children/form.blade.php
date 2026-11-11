@extends('layouts.dashboard')
@section('heading', $child->exists ? 'Edit child profile' : 'Add a child')
@section('dashboard-content')
<form method="POST" action="{{ $child->exists ? route('dashboard.children.update', $child) : route('dashboard.children.store') }}" class="form-box child-profile-form">
    @csrf
    @if($child->exists) @method('PUT') @endif

    <section class="dashboard-panel">
        <span class="eyebrow">Basic information</span>
        <div class="form-row">
            <div><label for="child-name">Child name</label><input id="child-name" name="name" value="{{ old('name', $child->name) }}" autocomplete="off" required></div>
            <div><label for="child-dob">Date of birth</label><input id="child-dob" type="date" name="date_of_birth" value="{{ old('date_of_birth', $child->date_of_birth?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required><small class="hint">Used to calculate age and suggest suitable activities.</small></div>
        </div>
        <label for="child-pronouns">Pronouns <span class="hint">Optional</span></label>
        <input id="child-pronouns" name="pronouns" value="{{ old('pronouns', $child->pronouns) }}" maxlength="80" placeholder="For example, she/her or he/him">
    </section>

    <section class="dashboard-panel">
        <span class="eyebrow">How your child communicates and learns</span>
        <label for="communication-preferences">Communication preferences</label>
        <textarea id="communication-preferences" name="communication_preferences" rows="3" maxlength="2000" placeholder="Helpful ways to communicate, such as gestures, pictures, signs, devices, or extra response time">{{ old('communication_preferences', $child->communication_preferences) }}</textarea>

        <div class="form-row">
            <div><label for="child-interests">Interests and favorite activities</label><textarea id="child-interests" name="interests" rows="3" maxlength="2000" placeholder="Favorite toys, games, music, topics, or activities">{{ old('interests', $child->interests) }}</textarea></div>
            <div><label for="child-strengths">Strengths</label><textarea id="child-strengths" name="strengths" rows="3" maxlength="2000" placeholder="Things your child enjoys or does well">{{ old('strengths', $child->strengths) }}</textarea></div>
        </div>

        <label for="support-preferences">Support and sensory preferences</label>
        <textarea id="support-preferences" name="support_preferences" rows="3" maxlength="2000" placeholder="Helpful routines, preferred instructions, or things that make activities more comfortable">{{ old('support_preferences', $child->support_preferences) }}</textarea>

        <label for="learning-goals">Learning goals</label>
        <textarea id="learning-goals" name="learning_goals" rows="3" maxlength="2000" placeholder="Skills or activities your family would like to practice">{{ old('learning_goals', $child->learning_goals) }}</textarea>
    </section>

    <button class="btn btn-blue" type="submit">Save child profile</button>
    <a class="btn btn-outline" href="{{ route('dashboard.children.index') }}">Cancel</a>
</form>
@endsection
