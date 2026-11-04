<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث بخصوص طلب التسجيل</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 40px 20px;
            direction: rtl;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            padding: 36px 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .header h1 {
            color: #0f172a;
            font-size: 22px;
            margin-top: 10px;
        }
        .content {
            font-size: 15px;
            line-height: 1.8;
            color: #334155;
        }
        .reason-box {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
            color: #991b1b;
        }
        .reason-title {
            font-weight: bold;
            margin-bottom: 6px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>منصة BaytFolio</h1>
        </div>
        <div class="content">
            <p>مرحباً <strong>{{ $vendorName }}</strong>،</p>
            <p>نشكركم على اهتمامكم بالانضمام إلى منصة BaytFolio وتقديم طلب تسجيل معرضكم.</p>
            <p>نأسف لإبلاغكم بأنه بعد مراجعة بيانات الطلب من قبل فريق العمل، لم نتمكن من قبول الطلب في الوقت الحالي للأسباب التالية:</p>

            <div class="reason-box">
                <div class="reason-title">سبب عدم القبول:</div>
                <div>{{ $reason }}</div>
            </div>

            <p>يمكنكم استيفاء البيانات والمستندات المطلوبة وإعادة تقديم طلب جديد عبر المنصة في أي وقت.</p>
            <p>نتمنى لكم دوام التوفيق والنجاح.</p>
        </div>
        <div class="footer">
            <p>فريق منصة BaytFolio &copy; {{ date('Y') }}</p>
        </div>
    </div>
</body>
</html>

