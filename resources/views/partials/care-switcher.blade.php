<nav class="care-switcher" aria-label="Appointments and professional consultancy">
    <a href="{{ route('appointments.index') }}" @if($activeCarePage === 'appointments') aria-current="page" @endif>Appointments</a>
    <a href="{{ route('doctors.index') }}" @if($activeCarePage === 'consultancy') aria-current="page" @endif>Professional Consultancy</a>
</nav>
