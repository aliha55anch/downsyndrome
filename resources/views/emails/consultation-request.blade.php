<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Consultation request receipt</title></head>
<body style="margin:0;padding:28px 12px;background:#f2f6f5;color:#203844;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:620px;margin:0 auto;background:#fff;border:1px solid #dbe5e8;border-radius:18px;overflow:hidden;">
        <tr><td style="padding:22px 28px;background:linear-gradient(110deg,#e6f3f1,#fff4dc);border-bottom:1px solid #e5edef;">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $brandName }}" style="display:block;max-width:170px;max-height:58px;margin-bottom:12px">@endif
            <p style="margin:0;color:#176a78;font-size:13px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase">{{ $brandName }} · Consultation care</p>
        </td></tr>
        <tr><td style="padding:28px;">
            @if($message->email_type === 'consultation_payment_succeeded')
                <p style="display:inline-block;margin:0 0 14px;padding:7px 11px;border-radius:999px;background:#eaf6ed;color:#28734b;font-size:12px;font-weight:800">PAYMENT VERIFIED</p>
                <h1 style="margin:0 0 10px;color:#17374b;font-size:25px;line-height:1.25">Your payment receipt</h1>
                <p style="margin:0 0 22px;color:#5a7078;font-size:15px;line-height:1.65">Safepay confirmed payment for your consultation request. Your request is still subject to the doctor’s review.</p>
            @else
                <p style="display:inline-block;margin:0 0 14px;padding:7px 11px;border-radius:999px;background:#eaf3f4;color:#176a78;font-size:12px;font-weight:800">REQUEST RECEIVED</p>
                <h1 style="margin:0 0 10px;color:#17374b;font-size:25px;line-height:1.25">Your consultation receipt</h1>
                <p style="margin:0 0 22px;color:#5a7078;font-size:15px;line-height:1.65">We’ve received the consultation request. The doctor will review it and follow up with you.</p>
            @endif

            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;border:1px solid #dbe8e8;border-radius:13px;background:#f8fbfb;">
                <tr><td style="padding:15px 17px;border-bottom:1px solid #e3ecec;">
                    <p style="margin:0 0 4px;color:#627780;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase">Unique reference</p>
                    <p style="margin:0;color:#176a78;font-size:20px;font-weight:800;letter-spacing:.4px">{{ $snapshot['reference'] }}</p>
                </td></tr>
                <tr><td style="padding:14px 17px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-size:14px;line-height:1.6">
                        <tr><td style="padding:5px 0;color:#627780">Patient</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['patient_name'] }}</td></tr>
                        <tr><td style="padding:5px 0;color:#627780">Professional</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['professional_title'] }} {{ $snapshot['doctor_name'] }}</td></tr>
                        @if($snapshot['specialty'])<tr><td style="padding:5px 0;color:#627780">Specialty</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['specialty'] }}</td></tr>@endif
                        <tr><td style="padding:5px 0;color:#627780">Consultation</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ ucwords(str_replace('_',' ', $snapshot['consultation_type'])) }}</td></tr>
                        <tr><td style="padding:5px 0;color:#627780">Preferred date</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['request_date'] }}</td></tr>
                        <tr><td style="padding:5px 0;color:#627780">Fee</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['currency'] }} {{ $snapshot['fee'] }}</td></tr>
                        <tr><td style="padding:5px 0;color:#627780">Payment</td><td style="padding:5px 0;text-align:right;font-weight:700">
                            @if($snapshot['payment_method'] === 'pay_later') Pay later in person
                            @elseif($snapshot['payment_method'] === 'not_required') No payment due now
                            @else {{ $snapshot['payment_status'] === 'paid' ? 'Card payment verified' : 'Card payment pending verification' }} @endif
                        </td></tr>
                        @if($snapshot['paid_at'])<tr><td style="padding:5px 0;color:#627780">Paid at</td><td style="padding:5px 0;text-align:right;font-weight:700">{{ $snapshot['paid_at'] }}</td></tr>@endif
                    </table>
                </td></tr>
            </table>

            @if($snapshot['recipient_role'] === 'patient')
                <a href="{{ $snapshot['patient_url'] }}" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#176a78;color:#fff;font-size:14px;font-weight:800;text-decoration:none">View my requests</a>
            @else
                <a href="{{ $snapshot['doctor_url'] }}" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#176a78;color:#fff;font-size:14px;font-weight:800;text-decoration:none">View professional profile</a>
            @endif
            <p style="margin:22px 0 0;color:#718187;font-size:12px;line-height:1.6">Keep this reference for your records. This receipt confirms the request and payment status shown above; it does not confirm an appointment time.</p>
        </td></tr>
        <tr><td style="padding:16px 28px;border-top:1px solid #e5edef;color:#718187;font-size:12px;line-height:1.6">
            <strong style="color:#405a64">{{ $brandName }} support</strong>
            @if(config('mail.reply_to.address'))<br>Questions? Reply to this email or contact {{ config('mail.reply_to.address') }}.@endif
            <br>This email does not contain or store card details.
        </td></tr>
    </table>
</body>
</html>
