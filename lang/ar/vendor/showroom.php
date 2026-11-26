<?php

return [
    'status' => [
        'DRAFT' => 'مسودة',
        'ACTIVE' => 'نشط',
        'SUSPENDED' => 'معلق',
        'TERMINATED' => 'موقوف',
    ],

    'contract_status' => [
        'DRAFT' => 'مسودة',
        'ACTIVE' => 'ساري',
        'EXPIRED' => 'منتهي',
        'TERMINATED' => 'ملغي',
    ],

    'verification_status' => [
        'PENDING' => 'قيد المراجعة',
        'APPROVED' => 'معتمد وموثق',
        'REJECTED' => 'مرفوض',
        'EXPIRED' => 'منتهي',
        'UNVERIFIED' => 'غير موثق',
    ],

    'messages' => [
        'created_success' => 'تم إنشاء وتجهيز المعرض بنجاح.',
        'updated_success' => 'تم تحديث بيانات المعرض بنجاح.',
        'contract_created' => 'تم إنشاء العقد وتحديده بنجاح.',
        'verification_submitted' => 'تم تقديم طلب توثيق المعرض بنجاح وهو قيد المراجعة.',
        'verification_approved' => 'تم اعتماد وتوثيق المعرض بنجاح.',
        'verification_rejected' => 'تم رفض طلب التوثيق.',
        'branch_created' => 'تم إضافة الفرع بنجاح.',
        'branch_updated' => 'تم تحديث بيانات الفرع بنجاح.',
        'branch_deleted' => 'تم حذف الفرع بنجاح.',
        'main_branch_set' => 'تم تعيين الفرع كفرع رئيسي بنجاح.',
    ],

    'errors' => [
        'not_found' => 'المعرض غير موجود.',
        'already_exists' => 'يوجد معرض مسجل مسبقاً لهذا الحساب.',
        'slug_taken' => 'المعرف الفريد (Slug) مستخدم بالفعل، يرجى اختيار معرف آخر.',
        'no_active_contract' => 'المعرض لا يمتلك عقداً سارياً في الوقت الحالي.',
        'room_limit_exceeded' => 'لقد تجاوزت الحد الأقصى المسموح به لعدد الغرف في باقتك الحالية.',
        'package_limit_exceeded' => 'لقد تجاوزت الحد الأقصى المسموح به لعدد الباقات في باقتك الحالية.',
        'pending_verification_exists' => 'يوجد طلب توثيق قيد المراجعة بالفعل لهذا المعرض.',
        'already_verified' => 'المعرض موثق بالفعل حالياً بالعلامة الزرقاء.',
        'branch_not_found' => 'الفرع غير موجود أو لا ينتمي لمعرضك.',
        'cannot_delete_only_branch' => 'لا يمكن حذف الفرع الوحيد للمعرض.',
    ],
    'attributes' => [
        'month' => 'مدة التوثيق (بالأشهر)',
    ],
];
