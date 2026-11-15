<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'بيانات الاعتماد هذه غير متطابقة مع سجلاتنا.',
    'password' => 'كلمة المرور المقدمة غير صحيحة.',
    'throttle' => 'محاولات تسجيل دخول كثيرة جداً. يرجى المحاولة مرة أخرى بعد :seconds ثوانٍ.',
    'login_success' => 'تم تسجيل الدخول بنجاح.',
    'login_failed' => 'بيانات تسجيل الدخول غير صحيحة.',
    'logout_success' => 'تم تسجيل الخروج بنجاح.',
    'unauthenticated' => 'غير مسجل الدخول.',
    'forbidden' => 'غير مصرح لك بالوصول إلى هذا المورد.',
    'logout_failed' => 'أنت غير مسجل الدخول.',
    'email_not_verified' => 'من فضلك فعّل بريدك الإلكتروني قبل تسجيل الدخول.',
    'user_active' => [
        'labels' => [
            'INACTIVE' => 'غير نشط',
            'ACTIVE' => 'نشط',
            'PENDING_PASSWORD' => 'بانتظار تعيين كلمة المرور',
            'UNKNOWN' => 'غير معروف',
        ],
        'messages' => [
            'INACTIVE' => 'حسابك غير نشط، يرجى التواصل مع الإدارة.',
            'ACTIVE' => 'الحساب نشط ومفعّل.',
            'PENDING_PASSWORD' => 'يجب تعيين كلمة المرور الجديدة من رابط التفعيل المرسل إلى بريدك الإلكتروني قبل تسجيل الدخول.',
            'UNKNOWN' => 'حالة الحساب غير محددة.',
        ],
    ],
    'password_reset' => [
        'otp_sent_email' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.',
        'otp_sent_whatsapp' => 'تم إرسال رمز التحقق إلى رقم الواتساب الخاص بك.',
        'otp_verified' => 'تم التحقق من الرمز بنجاح.',
        'password_updated' => 'تم تحديث كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن.',
        'user_not_found' => 'لم نتمكن من العثور على حساب مطابق لهذه البيانات في هذه البوابة.',
        'throttled' => 'يرجى الانتظار :seconds ثانية قبل إعادة إرسال رمز التحقق.',
        'expired' => 'انتهت صلاحية رمز التحقق أو لم يتم طلبه، يرجى طلب رمز جديد.',
        'invalid_otp' => 'رمز التحقق غير صحيح.',
        'max_attempts' => 'تم تجاوز الحد الأقصى لمحاولات التحقق، يرجى طلب رمز جديد.',
        'invalid_reset_token' => 'رمز إعادة تعيين كلمة المرور غير صالح أو انتهت صلاحيته.',
        'whatsapp_delivery_failed' => 'تعذر إرسال رمز التحقق عبر واتساب حالياً، يرجى المحاولة لاحقاً.',
        'email_subject' => 'رمز التحقق لإعادة تعيين كلمة المرور - BaytFolio',
        'password_confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        'whatsapp_otp_message' => 'رمز التحقق الخاص بك لإعادة تعيين كلمة المرور في بيت فوليو هو: :otp (صالح لمدة :minutes دقائق). لا تشارك هذا الرمز مع أحد.',
    ],
];
