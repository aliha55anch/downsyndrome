<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $title }}</title></head>
<body style="margin:0;padding:28px 12px;background:#f2f6f5;color:#203844;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:620px;margin:0 auto;background:#fff;border:1px solid #dbe5e8;border-radius:18px;overflow:hidden;">
        <tr><td style="padding:22px 28px;background:linear-gradient(110deg,#e6f3f1,#fff4dc);border-bottom:1px solid #e5edef;">
            @if(filled(config('mail.logo_url')) && str_starts_with(strtolower((string) config('mail.logo_url')), 'https://'))<img src="{{ config('mail.logo_url') }}" alt="{{ $brandName }}" style="display:block;max-width:170px;max-height:58px;margin-bottom:12px">@endif
            <p style="margin:0;color:#176a78;font-size:13px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase">{{ $brandName }} · Little Steps, together</p>
        </td></tr>
        <tr><td style="padding:28px;">
            <p style="display:inline-block;margin:0 0 14px;padding:7px 11px;border-radius:999px;background:#eaf3f4;color:#176a78;font-size:12px;font-weight:800">{{ $eyebrow ?? 'ACCOUNT NOTICE' }}</p>
            <h1 style="margin:0 0 10px;color:#17374b;font-size:25px;line-height:1.25">{{ $title }}</h1>
            <p style="margin:0 0 22px;color:#5a7078;font-size:15px;line-height:1.65">{{ $intro }}</p>
            <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 19px;border-radius:10px;background:{{ $actionColor ?? '#176a78' }};color:#fff;font-size:14px;font-weight:800;text-decoration:none">{{ $actionLabel }}</a>
            @if(!empty($expiryText))<p style="margin:18px 0 0;padding:13px 15px;border:1px solid #e5edef;border-radius:11px;background:#f8fbfb;color:#627780;font-size:13px;line-height:1.6">{{ $expiryText }}</p>@endif
            <p style="margin:20px 0 0;color:#718187;font-size:13px;line-height:1.6">{{ $safetyText ?? 'If you did not request this, you can safely ignore this email.' }}</p>
        </td></tr>
        <tr><td style="padding:16px 28px;border-top:1px solid #e5edef;color:#718187;font-size:12px;line-height:1.6">
            <strong style="color:#405a64">{{ $brandName }} support</strong>
            @if(config('mail.reply_to.address'))<br>Questions? Reply to this email or contact {{ config('mail.reply_to.address') }}.@endif
            <br>This is an automated account email. Keep your sign-in details private.
        </td></tr>
    </table>
</body>
</html>
