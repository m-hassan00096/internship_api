<?php

return [
    'status' => [
        'PENDING' => 'بانتظار المراجعة',
        'APPROVED' => 'معتمد ومنشور',
        'REJECTED' => 'مرفوض',
        'HIDDEN' => 'مخفي',
    ],
    'target_types' => [
        'room' => 'غرفة',
        'showroom' => 'معرض',
    ],
    'target_badges' => [
        'room' => 'غرفة (Room)',
        'showroom' => 'معرض (Showroom)',
    ],
    'sku_label' => 'الرمز المرجعي: SKU-:sku',
    'verified_purchase' => 'مشتري حقيقي وموثق',
    'operational_note_title' => 'قاعدة الاحتساب للمنصة',
    'operational_notes' => [
        'pending' => 'هذا التقييم بحالة (بانتظار المراجعة) ولن يُحتسب في متوسط التقييم العام (Average Rating) حتى يتم اعتماده ونشره.',
        'approved' => 'هذا التقييم (معتمد ومنشور) حالياً ويؤثر مباشرة في متوسط التقييم العام للغرفة والمعرض.',
        'rejected' => 'هذا التقييم (مرفوض) ومحجوب عن المنصة ولا يُحتسب في أي متوسط أو إحصائية عامة.',
        'hidden' => 'هذا التقييم (مخفي) حالياً بقرار إداري ولا يؤثر في متوسط التقييم العام للمنصة.',
    ],
    'sentiments' => [
        'positive' => 'تقييم إيجابي (:percentage%)',
        'neutral' => 'تقييم متوسط (:percentage%)',
        'negative' => 'تقييم سلبي (:percentage%)',
    ],
    'kpis' => [
        'total' => 'إجمالي التقييمات',
        'pending' => 'بانتظار المراجعة',
        'average_rating' => 'متوسط التقييم العام',
        'rejected_or_hidden' => 'مرفوضة ومخفية',
    ],
    'messages' => [
        'fetched' => 'تم استرجاع قائمة التقييمات بنجاح.',
        'detail_fetched' => 'تم استرجاع تفاصيل التقييم بنجاح.',
        'status_updated' => 'تم تحديث حالة التقييم بنجاح.',
        'approved' => 'تم اعتماد ونشر التقييم بنجاح.',
        'hidden' => 'تم إخفاء التقييم بنجاح.',
        'rejected' => 'تم رفض التقييم وحجبه بنجاح.',
    ],
    'attributes' => [
        'status' => 'حالة التقييم',
        'reject_reason' => 'سبب الرفض',
    ],
    'errors' => [
        'not_found' => 'التقييم المطلوب غير موجود.',
        'reject_reason_required' => 'يجب إدخال سبب الرفض عند رفض التقييم.',
        'status_invalid' => 'حالة التقييم المحددة غير صالحة.',
        'status_required' => 'حقل حالة التقييم مطلوب.',
    ],
];
