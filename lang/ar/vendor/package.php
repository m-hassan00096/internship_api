<?php

return [
    'status' => [
        'DRAFT' => 'مسودة',
        'PUBLISHED' => 'منشورة',
        'ARCHIVED' => 'مؤرشفة',
    ],
    'messages' => [
        'created' => 'تم إنشاء الباقة بنجاح.',
        'updated' => 'تم تحديث بيانات الباقة بنجاح.',
        'deleted' => 'تم حذف الباقة بنجاح.',
        'status_changed' => 'تم تغيير حالة الباقة بنجاح.',
    ],
    'attributes' => [
        'name' => 'اسم الباقة',
        'sku' => 'كود الباقة (SKU)',
        'description' => 'وصف الباقة',
        'image' => 'صورة الباقة',
        'image_path' => 'صورة الباقة',
        'price' => 'سعر الباقة',
        'status' => 'حالة الباقة',
        'current_step' => 'الخطوة الحالية',
        'published_at' => 'تاريخ النشر',
        'rooms' => 'الغرف المشمولة',
    ],
    'errors' => [
        'not_found' => 'الباقة غير موجودة أو لا تتبع لمعرضك.',
        'invalid_rooms' => 'واحدة أو أكثر من الغرف المحددة لا تتبع لمعرضك.',
        'empty_rooms_cannot_publish' => 'لا يمكن نشر باقة لا تحتوي على أي غرف.',
    ],
];
