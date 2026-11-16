@php
    $heading = match (true) {
        $message->email_type === 'booking_initiated' => 'Finish your appointment booking',
        $message->email_type === 'appointment_confirmed' => 'Your appointment is confirmed',
        $message->email_type === 'payment_succeeded' => 'Your payment is confirmed',
        $message->email_type === 'payment_failed' => 'Your payment needs attention',
        $message->email_type === 'payment_requires_review' => 'Your payment needs review',
        $message->email_type === 'meeting_link_added' => 'Your consultation link is ready',
        $message->email_type === 'prescription_published' => 'Your prescription is available',
        str_starts_with($message->email_type, 'payment_refunded') => 'Your refund is confirmed',
        str_contains($message->email_type, 'cancellation') => 'Appointment cancellation update',
        $message->email_type === 'appointment_rescheduled' => 'Your appointment time changed',
        $message->email_type === 'appointment_completed' => 'Your consultation is complete',
        $message->email_type === 'appointment_no_show' => 'Appointment status update',
        default => 'Appointment update',
    };
    $intro = match (true) {
        $message->email_type === 'booking_initiated' => 'Complete secure payment to continue your appointment booking.',
        $message->email_type === 'appointment_confirmed' => 'Your appointment has been confirmed. Keep this receipt for your records.',
        $message->email_type === 'payment_succeeded' => 'Safepay verified your payment. Your appointment details are below.',
        $message->email_type === 'payment_failed' => 'The payment was not completed. Review your appointment details and try again if the booking is still active.',
        $message->email_type === 'payment_requires_review' => 'Your payment was verified, and the booking is being reviewed by the Little Steps team.',
        $message->email_type === 'meeting_link_added' => 'Your doctor shared the details you need to join your online consultation.',
        $message->email_type === 'prescription_published' => 'Your doctor published a prescription for this appointment.',
        str_starts_with($message->email_type, 'payment_refunded') => 'A refund update was verified for this appointment.',
        default => 'There is an update to your Little Steps appointment. The latest details are below.',
    };
@endphp
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $heading }}</title></head>
<body style="margin:0;padding:28px 12px;background:#f2f6f5;color:#203844;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:620px;margin:0 auto;background:#fff;border:1px solid #dbe5e8;border-radius:18px;overflow:hidden;">
        <tr><td style="padding:22px 28px;background:linear-gradient(110deg,#e6f3f1,#fff4dc);border-bottom:1px solid #e5edef;">
            @if(!empty($logoUrl))<img src="{{ $logoUrl }}" alt="{{ $brandName }}" style="display:block;max-width:170px;max-height:58px;margin-bottom:12px">@endif
            <p style="margin:0;color:#176a78;font-size:13px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase">{{ $brandName }} · Appointment care</p>
        </td></tr>
        <tr><td style="padding:28px;">
            <p style="display:inline-block;margin:0 0 14px;padding:7px 11px;border-radius:999px;background:#eaf3f4;color:#176a78;font-size:12px;font-weight:800">{{ ucwords(str_replace('_', ' ', $message->email_type)) }}</p>
            <h1 style="margin:0 0 10px;color:#17374b;font-size:25px;line-height:1.25">{{ $heading }}</h1>
            <p style="margin:0 0 22px;color:#5a7078;font-size:15px;line-height:1.65">Hello {{ $message->recipient->first_name ?: $message->recipient->name }}, {{ $intro }}</p>

            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;border:1px solid #dbe8e8;border-radius:13px;background:#f8fbfb;">
                <tr><td style="padding:15px 17px;border-bottom:1px solid #e3ecec;">
                    <p style="margin:0 0 4px;color:#627780;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase">Appointment reference</p>
                    <p style="margin:0;color:#176a78;font-size:20px;font-weight:800;letter-spacing:.4px">{{ $snapshot['appointment_reference'] ?? '' }}</p>
                </td></tr>
                <tr><td style="padding:14px 17px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-size:14px;line-height:1.6">
                        <tr><td style="padding:5px 0;color:#627780">Patient</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['patient_name'] ?? 'Patient' }}</td></tr>
                        <tr><td style="padding:5px 0;color:#627780">Professional</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['professional_title'] ?? '' }} {{ $snapshot['doctor_name'] ?? 'Doctor' }}</td></tr>
                        @if(!empty($snapshot['doctor_specialty']))<tr><td style="padding:5px 0;color:#627780">Specialty</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['doctor_specialty'] }}</td></tr>@endif
                        <tr><td style="padding:5px 0;color:#627780">When</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['starts_at'] ?? '' }} {{ !empty($snapshot['timezone']) ? '('.$snapshot['timezone'].')' : '' }}</td></tr>
                        <tr><td style="padding:5px 0;color:#627780">Consultation</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ ucwords(str_replace('_', ' ', $snapshot['consultation_type'] ?? '')) }}</td></tr>
                        <tr><td style="padding:5px 0;color:#627780">Appointment status</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ ucwords(str_replace('_', ' ', $snapshot['appointment_status'] ?? '')) }}</td></tr>
                        <tr><td style="padding:5px 0;color:#627780">Payment status</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ ucwords(str_replace('_', ' ', $snapshot['payment_status'] ?? 'not_required')) }}</td></tr>
                        @if(($snapshot['amount'] ?? '0.00') !== '0.00')<tr><td style="padding:5px 0;color:#627780">Amount</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['currency'] ?? '' }} {{ $snapshot['amount'] }}</td></tr>@endif
                        @if(($snapshot['refunded_amount'] ?? '0.00') !== '0.00')<tr><td style="padding:5px 0;color:#627780">Verified refund</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['currency'] ?? '' }} {{ $snapshot['refunded_amount'] }}</td></tr>@endif
                    </table>
                </td></tr>
            </table>

            @if(!empty($snapshot['payment_reference']))<p style="margin:0 0 16px;color:#627780;font-size:12px">Safepay reference: <strong>{{ $snapshot['payment_reference'] }}</strong></p>@endif
            @if($message->recipient->hasRole(\App\Models\User::ROLE_APPROVED_DOCTOR) && !empty($snapshot['patient_email']))<p style="margin:0 0 16px;color:#627780;font-size:13px">Patient contact: {{ $snapshot['patient_email'] }}</p>@endif
            @if(in_array($message->email_type, ['appointment_cancelled','cancellation_requested','cancellation_approved','cancellation_rejected'], true) && !empty($snapshot['cancellation_reason']))<p style="margin:0 0 16px;padding:12px 14px;border-radius:10px;background:#fff7ed;color:#7c4a19;font-size:13px;line-height:1.55"><strong>Cancellation note:</strong> {{ $snapshot['cancellation_reason'] }}</p>@endif
            @if(!empty($snapshot['meeting_link']))<a href="{{ $snapshot['meeting_link'] }}" style="display:inline-block;margin:0 8px 10px 0;padding:12px 18px;border-radius:10px;background:#176a78;color:#fff;font-size:14px;font-weight:800;text-decoration:none">Join consultation</a>@endif
            <a href="{{ $snapshot['appointment_url'] ?? url('/') }}" style="display:inline-block;margin:0 0 10px;padding:12px 18px;border:1px solid #cbdde2;border-radius:10px;background:#fff;color:#176a78;font-size:14px;font-weight:800;text-decoration:none">View appointment</a>
            <p style="margin:12px 0 0;color:#718187;font-size:12px;line-height:1.6">This message contains appointment details only. Little Steps never includes payment card credentials in email.</p>
        </td></tr>
        <tr><td style="padding:16px 28px;border-top:1px solid #e5edef;color:#718187;font-size:12px;line-height:1.6">
            <strong style="color:#405a64">{{ $brandName }} support</strong>
            @if(config('mail.reply_to.address'))<br>Questions? Reply to this email or contact {{ config('mail.reply_to.address') }}.@endif
        </td></tr>
    </table>
</body>
</html>
