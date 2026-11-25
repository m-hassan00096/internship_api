<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق لإعادة تعيين كلمة المرور - BaytFolio</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f7f7f9; color: #333; margin: 0; padding: 40px 20px; direction: rtl; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; }
        .title { color: #1a1a1a; font-size: 24px; font-weight: 700; margin-bottom: 10px; }
        .content { font-size: 16px; line-height: 1.8; color: #555; }
        .otp-box { background-color: #f3f6fc; border: 1px solid #d5e1fb; border-radius: 10px; padding: 20px; text-align: center; margin: 30px 0; letter-spacing: 8px; font-size: 32px; font-weight: 700; color: #111827; direction: ltr; }
        .footer { font-size: 13px; color: #888; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">التحقق من إعادة تعيين كلمة المرور</h1>
        </div>
        <div class="content">
            <p>مرحباً،</p>
            <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في منصة <strong>BaytFolio</strong>.</p>
            <p>يرجى إدخال رمز التحقق المكوّن من 6 أرقام التالي لإتمام العملية:</p>
            <div class="otp-box">{{ $otp }}</div>
            <p style="font-size: 14px; color: #777;">تنتهي صلاحية هذا الرمز خلال {{ $expiresInMinutes }} دقائق لحماية حسابك.</p>
            <p style="font-size: 14px; color: #777;">إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذه الرسالة بأمان.</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} BaytFolio. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>

