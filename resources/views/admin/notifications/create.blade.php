@extends('layouts.admin')
@section('title', 'Send notification')
@section('content')
<section class="admin-panel notification-composer">
    <span class="eyebrow">In-app delivery</span><h1>Send a notification</h1>
    <p>Messages appear in each recipient’s notification bell. This does not send email. Each selected user receives one notification, and the send is recorded for audit.</p>
    <form method="POST" action="{{ route('admin.notifications.store') }}" id="admin-notification-form">
        @csrf
        <input type="hidden" name="send_key" value="{{ $sendKey }}">
        <div class="form-row">
            <div><label for="audience">Audience</label><select id="audience" name="audience" required><option value="patients" @selected(old('audience')==='patients')>Patients</option><option value="doctors" @selected(old('audience')==='doctors')>Approved doctors</option><option value="all" @selected(old('audience')==='all')>Patients and approved doctors</option></select></div>
            <div><label for="internal_url">Optional internal destination</label><input id="internal_url" name="internal_url" value="{{ old('internal_url') }}" placeholder="/appointments" inputmode="url"><small>Use a site path beginning with /; external links are not accepted.</small></div>
        </div>
        <label for="recipient-search">Choose specific recipients (optional)</label><input id="recipient-search" type="search" placeholder="Filter recipient list by name or email">
        <div class="recipient-picker" aria-label="Recipient list">
            @foreach($recipients as $recipient)
                <label class="recipient-option" data-role="{{ $recipient->role }}"><input type="checkbox" name="user_ids[]" value="{{ $recipient->id }}" @checked(in_array($recipient->id, old('user_ids', [])))><span>{{ $recipient->name }} <small>{{ $recipient->email }} · {{ $recipient->role === 'parent' ? 'Patient' : 'Doctor' }}</small></span></label>
            @endforeach
        </div>
        <small>Select no one to send to the entire chosen audience. The list shows up to 250 eligible accounts.</small>
        <label for="title">Title</label><input id="title" name="title" maxlength="160" value="{{ old('title') }}" required>
        <label for="message">Message</label><textarea id="message" name="message" rows="5" maxlength="3000" required>{{ old('message') }}</textarea>
        <div class="notification-preview"><strong>Preview</strong><h3 data-preview-title>Notification title</h3><p data-preview-message>Your message will appear here.</p></div>
        <button class="btn btn-blue" type="submit">Review notification</button>
        <section class="notification-send-confirmation" id="send-confirmation" aria-labelledby="send-confirmation-title" tabindex="-1" hidden>
            <h2 id="send-confirmation-title">Confirm notification</h2>
            <p data-confirmation-summary></p>
            <div class="btn-row"><button class="btn btn-blue" type="submit" id="confirm-send">Send notification now</button><button class="btn btn-outline" type="button" id="edit-notification">Back to edit</button></div>
        </section>
    </form>
</section>
<script>
(() => {
 const form=document.getElementById('admin-notification-form'), audience=document.getElementById('audience'), search=document.getElementById('recipient-search');
 const filter=()=>document.querySelectorAll('.recipient-option').forEach(row=>{const role=row.dataset.role, roleOk=audience.value==='all'||(audience.value==='patients'&&role==='parent')||(audience.value==='doctors'&&role==='approved_doctor');const text=row.textContent.toLowerCase().includes(search.value.toLowerCase());row.hidden=!(roleOk&&text)});
 audience.addEventListener('change',filter);search.addEventListener('input',filter);filter();
 document.getElementById('title').addEventListener('input',e=>document.querySelector('[data-preview-title]').textContent=e.target.value||'Notification title');
 document.getElementById('message').addEventListener('input',e=>document.querySelector('[data-preview-message]').textContent=e.target.value||'Your message will appear here.');
 const confirmation=document.getElementById('send-confirmation');
 form.addEventListener('submit',e=>{if(form.dataset.confirmed==='yes')return;e.preventDefault();const checked=[...form.querySelectorAll('input[name="user_ids[]"]:checked')];const audienceLabel=audience.options[audience.selectedIndex].text;const choice=checked.length?`${checked.length} selected account(s): ${checked.map(input=>input.parentElement.textContent.trim()).join('; ')}`:`all eligible ${audienceLabel.toLowerCase()}`;confirmation.querySelector('[data-confirmation-summary]').textContent=`Send “${document.getElementById('title').value}” to ${choice}? This creates in-app notifications only.`;confirmation.hidden=false;confirmation.focus();document.getElementById('confirm-send').focus()});
 document.getElementById('confirm-send').addEventListener('click',()=>{form.dataset.confirmed='yes'});
 document.getElementById('edit-notification').addEventListener('click',()=>{confirmation.hidden=true;form.dataset.confirmed='';document.getElementById('title').focus()});
})();
</script>
@endsection
