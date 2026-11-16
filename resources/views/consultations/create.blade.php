@extends('layouts.public')
@section('title', 'Request a consultation')
@section('body_class', 'consultancy-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/consultancy.css') }}">
    <style>
        .consult-request-page { max-width: 760px; margin-inline: auto; }
        .consult-request-page [hidden] { display: none !important; }
        .consult-request-page .pc-results-head { margin-bottom: 1.25rem; }
        .consult-request-doctor, .consult-request-form { padding: clamp(1.15rem, 3vw, 1.7rem); margin-bottom: 1rem; }
        .consult-request-doctor p { margin: .35rem 0; }
        .consult-request-form .pc-filters__grid { grid-template-columns: minmax(0,1fr); gap: 1.1rem; }
        .consult-request-form .pc-field { min-width: 0; }
        .consult-request-form .pc-field label { margin-bottom: .45rem; }
        .consult-request-form .pc-field input, .consult-request-form .pc-field select, .consult-request-form .pc-field textarea { width: 100%; }
        .consult-request-payment { padding: 1rem 1.1rem; border: 1px solid #d8e6e8; border-radius: 14px; background: #f7fbfb; }
        .consult-request-payment h2 { margin: 0 0 .35rem; color: #17374b; font-size: 1.08rem; }
        .consult-request-payment > p { margin: 0 0 .8rem; color: #627780; line-height: 1.55; }
        .consult-request-payment-options { display: grid; gap: .65rem; }
        .consult-request-payment-option { display: flex; align-items: flex-start; gap: .65rem; padding: .8rem .9rem; border: 1px solid #dbe5e8; border-radius: 11px; background: #fff; cursor: pointer; }
        .consult-request-payment-option input { width: 18px; height: 18px; min-height: 18px; flex: 0 0 auto; margin: .15rem 0 0; padding: 0; }
        .consult-request-payment-option span { display: grid; gap: .2rem; }
        .consult-request-payment-option small { color: #627780; line-height: 1.45; }
        .consult-request-secure-note { display: flex; gap: .6rem; margin: .75rem 0 0 !important; padding: .7rem .8rem; border-radius: 10px; background: #eef7f4; color: #35694f !important; font-size: .9rem; }
        .consult-request-error { margin: .4rem 0 0; color: #b42318; font-size: .9rem; }
        .consult-request-page .pc-filters__actions { justify-content: flex-start; }
        @media(max-width: 560px) { .consult-request-page .pc-filters__actions { align-items: stretch; flex-direction: column; } }
    </style>
@endpush

@section('content')
<section class="pc-directory">
    <div class="container consult-request-page">
        <div class="pc-results-head">
            <div>
                <span class="pc-kicker">Consultation request</span>
                <h1>Request a consultation</h1>
                <p>Send your preferred consultation type and date to {{ $profile->professional_title }} {{ $profile->user->name }}.</p>
            </div>
        </div>

        <section class="pc-doctor-card consult-request-doctor" aria-label="Doctor and consultation fee">
            <p><strong>Doctor:</strong> {{ $profile->professional_title }} {{ $profile->user->name }}</p>
            <p><strong>Specialty:</strong> {{ $profile->specialties->pluck('name')->join(', ') ?: $profile->specialty }}</p>
            <p><strong>Consultation fee:</strong> <span id="consultation-fee-display">
                @if($fee > 0)
                    {{ config('app.currency', 'PKR') }} {{ number_format($fee, 2) }}
                @else
                    No fee has been set. The doctor may confirm the fee before the consultation.
                @endif
            </span></p>
        </section>

        <form method="POST" action="{{ route('consultations.store', $profile) }}" class="pc-filters consult-request-form">
            @csrf
            <div class="pc-filters__grid">
                <div class="pc-field">
                    <label for="consultation_type">Consultation type</label>
                    <select id="consultation_type" name="consultation_type" required>
                        <option value="general" @selected(old('consultation_type')==='general')>General</option>
                        <option value="online" @selected(old('consultation_type')==='online')>Online</option>
                        <option value="in_person" @selected(old('consultation_type')==='in_person')>In person</option>
                    </select>
                    @error('consultation_type')<p class="consult-request-error">{{ $message }}</p>@enderror
                </div>

                <div class="pc-field">
                    <label for="request_date">Preferred request date</label>
                    <input id="request_date" type="date" name="request_date" value="{{ old('request_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                    @error('request_date')<p class="consult-request-error">{{ $message }}</p>@enderror
                </div>

                <section class="consult-request-payment" aria-labelledby="payment-heading">
                    <h2 id="payment-heading">Payment</h2>
                    @if($fee > 0)
                        <p id="payment-guidance">Online consultations require payment now. In-person consultations can be paid now by card or later in person.</p>
                    @else
                        <p id="payment-guidance">No payment is due now because a consultation fee has not been set.</p>
                    @endif
                    <div class="consult-request-payment-options">
                        <label class="consult-request-payment-option" id="pay-now-option">
                            <input type="radio" name="payment_choice" value="pay_now" @checked(old('payment_choice', 'pay_now') === 'pay_now') required>
                            <span><strong>Pay now by card</strong><small>Continue to Safepay secure checkout to enter the required card details.</small></span>
                        </label>
                        <label class="consult-request-payment-option" id="pay-later-option">
                            <input type="radio" name="payment_choice" value="pay_later" @checked(old('payment_choice') === 'pay_later')>
                            <span><strong>Pay later in person</strong><small>Available for in-person consultations only.</small></span>
                        </label>
                    </div>
                    <p class="consult-request-secure-note" id="card-details-note"><span aria-hidden="true">🔒</span><span>Card details are entered on Safepay’s secure checkout page. They are required there and are not stored on this site.</span></p>
                    @error('payment_choice')<p class="consult-request-error" role="alert">{{ $message }}</p>@enderror
                    @error('payment')<p class="consult-request-error" role="alert">{{ $message }}</p>@enderror
                </section>

                <div class="pc-field">
                    <label for="patient_notes">Notes for the doctor <span class="hint">Optional</span></label>
                    <textarea id="patient_notes" name="patient_notes" rows="4" maxlength="2000" placeholder="Briefly describe your concern or preferred time...">{{ old('patient_notes') }}</textarea>
                    @error('patient_notes')<p class="consult-request-error">{{ $message }}</p>@enderror
                </div>

                <div class="pc-filters__actions">
                    <button class="pc-cta pc-cta--primary" id="submit-consultation-request" type="submit">Continue to secure payment</button>
                    <a class="pc-clear" href="{{ route('doctors.show', $profile) }}">Back to profile</a>
                </div>
            </div>
        </form>
    </div>
</section>
<script>
(() => {
    const type = document.getElementById('consultation_type');
    const payLater = document.getElementById('pay-later-option');
    const payLaterInput = payLater.querySelector('input');
    const payNowInput = document.querySelector('input[name="payment_choice"][value="pay_now"]');
    const guidance = document.getElementById('payment-guidance');
    const cardNote = document.getElementById('card-details-note');
    const submit = document.getElementById('submit-consultation-request');
    const fees = {{ Illuminate\Support\Js::from($fees) }};
    const feeDisplay = document.getElementById('consultation-fee-display');

    function updatePaymentOptions() {
        const isInPerson = type.value === 'in_person';
        const fee = Number(fees[type.value] || 0);
        const onlineFeeMissing = type.value === 'online' && fee <= 0;
        payLater.hidden = !isInPerson || fee <= 0;
        payLaterInput.disabled = !isInPerson || fee <= 0;
        if (payLaterInput.disabled && payLaterInput.checked) payNowInput.checked = true;
        feeDisplay.textContent = fee > 0
            ? '{{ config('app.currency', 'PKR') }} ' + fee.toFixed(2)
            : 'No fee has been set. The doctor may confirm the fee before the consultation.';
        submit.disabled = onlineFeeMissing;
        if (onlineFeeMissing) {
            guidance.textContent = 'Online requests require a published fee before payment can be collected. Choose in person or contact the doctor.';
            cardNote.hidden = true;
            submit.textContent = 'Online payment unavailable';
        } else if (fee <= 0) {
            guidance.textContent = 'No payment is due now because a consultation fee has not been set.';
            cardNote.hidden = true;
            submit.textContent = 'Submit consultation request';
        } else if (isInPerson) {
            guidance.textContent = 'Pay now by card or choose to pay later in person.';
            cardNote.hidden = !payNowInput.checked;
            submit.textContent = payNowInput.checked ? 'Continue to secure payment' : 'Submit consultation request';
        } else {
            guidance.textContent = 'Online consultations require payment now by card.';
            cardNote.hidden = false;
            submit.textContent = 'Continue to secure payment';
        }
    }

    type.addEventListener('change', updatePaymentOptions);
    document.querySelectorAll('input[name="payment_choice"]').forEach(input => input.addEventListener('change', updatePaymentOptions));
    updatePaymentOptions();
})();
</script>
@endsection
