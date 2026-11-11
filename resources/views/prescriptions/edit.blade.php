@extends('layouts.doctor')
@section('title', 'Prescription draft')
@section('heading', 'Digital prescription')
@section('doctor-content')
<section class="doctor-panel">
    <p>For {{ $appointment->patient->name }} · {{ $appointment->localStartsAt()->format('D, M j, Y · g:i A') }} · Appointment LS-APT-{{ $appointment->id }}</p>
    <p>Drafts are private to you. Publishing makes this prescription available only to the patient, treating doctor, and authorized administrators.</p>
    <form method="POST" action="{{ route('doctor.appointments.prescription.save', $appointment) }}" id="prescription-form">@csrf
        <label for="diagnosis">Diagnosis</label><textarea id="diagnosis" name="diagnosis" maxlength="5000">{{ old('diagnosis', $prescription->diagnosis) }}</textarea>
        <h3>Medicines</h3><div id="medicine-list">
        @foreach(old('medicines', $prescription->medicines ?? []) as $index => $medicine)
            <fieldset class="doctor-panel medicine-row"><legend>Medicine</legend><label>Name<input name="medicines[{{ $index }}][name]" maxlength="200" value="{{ $medicine['name'] ?? '' }}"></label><label>Dosage<input name="medicines[{{ $index }}][dosage]" maxlength="200" value="{{ $medicine['dosage'] ?? '' }}"></label><label>Frequency<input name="medicines[{{ $index }}][frequency]" maxlength="200" value="{{ $medicine['frequency'] ?? '' }}"></label><label>Duration<input name="medicines[{{ $index }}][duration]" maxlength="200" value="{{ $medicine['duration'] ?? '' }}"></label></fieldset>
        @endforeach</div>
        <button type="button" class="btn btn-outline btn-small" id="add-medicine">Add medicine</button>
        <label for="instructions">Instructions</label><textarea id="instructions" name="instructions" maxlength="5000">{{ old('instructions', $prescription->instructions) }}</textarea>
        <label for="notes">Additional notes</label><textarea id="notes" name="notes" maxlength="5000">{{ old('notes', $prescription->notes) }}</textarea>
        <label for="follow_up">Follow-up recommendation</label><textarea id="follow_up" name="follow_up" maxlength="2000">{{ old('follow_up', $prescription->follow_up) }}</textarea>
        <div class="btn-row"><button class="btn btn-outline" type="submit">Save draft</button><button class="btn btn-blue" type="submit" formaction="{{ route('doctor.appointments.prescription.publish', $appointment) }}" formmethod="POST" onclick="return confirm('Publish this prescription to the patient?')">Publish prescription</button></div>
    </form>
</section>
<script>(()=>{const list=document.getElementById('medicine-list'),add=document.getElementById('add-medicine');add.addEventListener('click',()=>{const i=list.querySelectorAll('.medicine-row').length;const fieldset=document.createElement('fieldset');fieldset.className='doctor-panel medicine-row';fieldset.innerHTML=`<legend>Medicine</legend><label>Name<input name="medicines[${i}][name]" maxlength="200"></label><label>Dosage<input name="medicines[${i}][dosage]" maxlength="200"></label><label>Frequency<input name="medicines[${i}][frequency]" maxlength="200"></label><label>Duration<input name="medicines[${i}][duration]" maxlength="200"></label>`;list.appendChild(fieldset)})})();</script>
@endsection

