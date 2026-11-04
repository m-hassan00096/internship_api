<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم اعتماد طلب انضمامك - BaytFolio</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f7f7f9; color: #333; margin: 0; padding: 40px 20px; direction: rtl; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; }
        .title { color: #1a1a1a; font-size: 24px; font-weight: 700; margin-bottom: 10px; }
        .content { font-size: 16px; line-height: 1.8; color: #555; }
        .btn-wrapper { text-align: center; margin: 35px 0; }
        .btn { display: inline-block; background-color: #1a56db; color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 600; }
        .footer { font-size: 13px; color: #888; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">تهانينا! تم اعتماد طلب انضمامكم 🎉</h1>
        </div>
        <div class="content">
            <p>مرحباً <strong>{{ $vendorName }}</strong>،</p>
            <p>يسعدنا إبلاغكم بأنه قد تمت مراجعة واعتماد طلب انضمام معرضكم بنجاح إلى منصة <strong>BaytFolio</strong>.</p>
            <p>للبدء في استخدام لوحة التحكم الخاصة بمعرضكم وإدارة المنتجات والموظفين، يُرجى الضغط على الزر التالي لإنشاء كلمة المرور وتفعيل الحساب:</p>
            <div class="btn-wrapper">
                <a href="{{ $setupUrl }}" class="btn">تفعيل الحساب وإنشاء كلمة المرور</a>
            </div>
            <p style="font-size: 14px; color: #777;">ملاحظة: هذا الرابط آمن ومخصص لكم فقط، وتصل صلاحيته إلى 48 ساعة.</p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} BaytFolio. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>

