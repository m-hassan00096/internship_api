<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'يجب قبول حقل :attribute.',
    'accepted_if' => 'يجب قبول حقل :attribute عندما يكون :other هو :value.',
    'active_url' => 'حقل :attribute لا يمثل رابطاً صحيحاً.',
    'after' => 'يجب أن يكون حقل :attribute تاريخاً لاحقاً للتاريخ :date.',
    'after_or_equal' => 'يجب أن يكون حقل :attribute تاريخاً لاحقاً أو مطابقاً للتاريخ :date.',
    'alpha' => 'يجب أن يحتوي حقل :attribute على حروف فقط.',
    'alpha_dash' => 'يجب أن يحتوي حقل :attribute على حروف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي حقل :attribute على حروف وأرقام فقط.',
    'any_of' => 'حقل :attribute غير صالح.',
    'array' => 'يجب أن يكون حقل :attribute مصفوفة.',
    'array_keys' => 'يجب أن يحتوي حقل :attribute على مدخلات للقيم التالية: :values.',
    'ascii' => 'يجب أن يحتوي حقل :attribute على رموز وأحرف أبجدية رقمية أحادية البايت فقط.',
    'base64' => 'يجب أن يكون حقل :attribute نصاً مشفراً بصيغة Base64 صالحة.',
    'before' => 'يجب أن يكون حقل :attribute تاريخاً سابقاً للتاريخ :date.',
    'before_or_equal' => 'يجب أن يكون حقل :attribute تاريخاً سابقاً أو مطابقاً للتاريخ :date.',
    'between' => [
        'array' => 'يجب أن يحتوي حقل :attribute على عدد من العناصر بين :min و :max.',
        'file' => 'يجب أن يكون حجم ملف :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يتراوح عدد حروف حقل :attribute بين :min و :max حرفاً.',
    ],
    'boolean' => 'يجب أن تكون قيمة حقل :attribute إما true أو false.',
    'can' => 'حقل :attribute يحتوي على قيمة غير مصرح بها.',
    'confirmed' => 'تأكيد حقل :attribute غير متطابق.',
    'contains' => 'حقل :attribute يفتقر إلى قيمة مطلوبة.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'حقل :attribute ليس تاريخاً صحيحاً.',
    'date_equals' => 'يجب أن يكون حقل :attribute تاريخاً مساوياً للتاريخ :date.',
    'date_format' => 'حقل :attribute لا يتوافق مع الصيغة :format.',
    'decimal' => 'يجب أن يحتوي حقل :attribute على :decimal منازل عشرية.',
    'declined' => 'يجب رفض حقل :attribute.',
    'declined_if' => 'يجب رفض حقل :attribute عندما يكون :other هو :value.',
    'different' => 'يجب أن يكون حقل :attribute مختلفاً عن :other.',
    'digits' => 'يجب أن يحتوي حقل :attribute على :digits أرقام.',
    'digits_between' => 'يجب أن يحتوي حقل :attribute على أرقام بين :min و :max رقم.',
    'dimensions' => 'أبعاد الصورة في حقل :attribute غير صالحة.',
    'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
    'doesnt_end_with' => 'يجب ألا ينتهي حقل :attribute بأحد القيم التالية: :values.',
    'doesnt_start_with' => 'يجب ألا يبدأ حقل :attribute بأحد القيم التالية: :values.',
    'email' => 'يجب أن يكون حقل :attribute عنوان بريد إلكتروني صحيحاً.',
    'ends_with' => 'يجب أن ينتهي حقل :attribute بأحد القيم التالية: :values.',
    'enum' => 'القيمة المحددة في حقل :attribute غير صالحة.',
    'exists' => 'القيمة المحددة في حقل :attribute غير موجودة.',
    'extensions' => 'يجب أن يحتوي حقل :attribute على أحد الامتدادات التالية: :values.',
    'file' => 'يجب أن يكون حقل :attribute ملفاً.',
    'filled' => 'حقل :attribute إجباري ولا يمكن تركه فارغاً.',
    'gt' => [
        'array' => 'يجب أن يحتوي حقل :attribute على أكثر من :value عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يكون عدد حروف حقل :attribute أكثر من :value أحرف.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :value عناصر أو أكثر.',
        'file' => 'يجب أن يكون حجم ملف :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من أو تساوي :value.',
        'string' => 'يجب أن يكون عدد حروف حقل :attribute مساوياً أو أكثر من :value أحرف.',
    ],
    'hex_color' => 'يجب أن يكون حقل :attribute لوناً سداسياً عشرياً صالحاً.',
    'image' => 'يجب أن يكون حقل :attribute صورة.',
    'in' => 'القيمة المحددة في حقل :attribute غير صالحة.',
    'in_array' => 'حقل :attribute غير موجود في :other.',
    'integer' => 'يجب أن يكون حقل :attribute عدداً صحيحاً.',
    'ip' => 'يجب أن يكون حقل :attribute عنوان IP صحيحاً.',
    'ipv4' => 'يجب أن يكون حقل :attribute عنوان IPv4 صحيحاً.',
    'ipv6' => 'يجب أن يكون حقل :attribute عنوان IPv6 صحيحاً.',
    'json' => 'يجب أن يكون حقل :attribute نصاً من نوع JSON صحيحاً.',
    'list' => 'يجب أن يكون حقل :attribute قائمة.',
    'lowercase' => 'يجب أن يكون حقل :attribute بحروف صغيرة.',
    'lt' => [
        'array' => 'يجب أن يحتوي حقل :attribute على أقل من :value عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من :value.',
        'string' => 'يجب أن يكون عدد حروف حقل :attribute أقل من :value أحرف.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :value عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من أو تساوي :value.',
        'string' => 'يجب أن يكون عدد حروف حقل :attribute مساوياً أو أقل من :value أحرف.',
    ],
    'mac_address' => 'يجب أن يكون حقل :attribute عنوان MAC صحيحاً.',
    'max' => [
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يتعدى حجم ملف :attribute :max كيلوبايت.',
        'numeric' => 'يجب ألا تكون قيمة :attribute أكبر من :max.',
        'string' => 'يجب ألا يتجاوز عدد حروف حقل :attribute :max حرفاً.',
    ],
    'max_digits' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max أرقام.',
    'mimes' => 'يجب أن يكون ملفاً من نوع: :values.',
    'mimetypes' => 'يجب أن يكون ملفاً من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي حقل :attribute على الأقل على :min عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute على الأقل :min كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute على الأقل :min.',
        'string' => 'يجب أن يحتوي حقل :attribute على الأقل على :min حروف.',
    ],
    'min_digits' => 'يجب أن يحتوي حقل :attribute على الأقل على :min أرقام.',
    'missing' => 'يجب أن يكون حقل :attribute مفقوداً.',
    'missing_if' => 'يجب أن يكون حقل :attribute مفقوداً عندما يكون :other هو :value.',
    'missing_unless' => 'يجب أن يكون حقل :attribute مفقوداً ما لم يكن :other هو :value.',
    'missing_with' => 'يجب أن يكون حقل :attribute مفقوداً عند وجود :values.',
    'missing_with_all' => 'يجب أن يكون حقل :attribute مفقوداً عند وجود :values.',
    'multiple_of' => 'يجب أن يكون حقل :attribute من مضاعفات :value.',
    'not_in' => 'القيمة المحددة في حقل :attribute غير صالحة.',
    'not_regex' => 'صيغة حقل :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون حقل :attribute رقماً.',
    'password' => [
        'letters' => 'يجب أن يحتوي حقل :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن يحتوي حقل :attribute على حرف كبير وآخر صغير على الأقل.',
        'numbers' => 'يجب أن يحتوي حقل :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن يحتوي حقل :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'القيمة المحددة في حقل :attribute ظهرت في تسريب بيانات. يرجى اختيار قيمة مختلفة.',
    ],
    'present' => 'يجب تقديم حقل :attribute.',
    'present_if' => 'يجب تقديم حقل :attribute عندما يكون :other هو :value.',
    'present_unless' => 'يجب تقديم حقل :attribute ما لم يكن :other هو :value.',
    'present_with' => 'يجب تقديم حقل :attribute عند وجود :values.',
    'present_with_all' => 'يجب تقديم حقل :attribute عند وجود :values.',
    'prohibited' => 'حقل :attribute محظور.',
    'prohibited_if' => 'حقل :attribute محظور عندما يكون :other هو :value.',
    'prohibited_unless' => 'حقل :attribute محظور ما لم يكن :other في :values.',
    'prohibits' => 'حقل :attribute يحظر وجود :other.',
    'regex' => 'صيغة حقل :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي حقل :attribute على مدخلات للقيم: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عند قبول :other.',
    'required_if_declined' => 'حقل :attribute مطلوب عند رفض :other.',
    'required_unless' => 'حقل :attribute مطلوب ما لم يكن :other في :values.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_with_all' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_without' => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'حقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same' => 'يجب أن يتطابق حقل :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي حقل :attribute على :size عناصر.',
        'file' => 'يجب أن يكون حجم ملف :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute مساوية لـ :size.',
        'string' => 'يجب أن يحتوي حقل :attribute على :size حروف بالضبط.',
    ],
    'starts_with' => 'يجب أن يبدأ حقل :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون حقل :attribute نصاً.',
    'timezone' => 'يجب أن يكون حقل :attribute نطاقاً زمنياً صحيحاً.',
    'unique' => 'قيمة حقل :attribute مستخدمة من قبل.',
    'uploaded' => 'فشل في تحميل حقل :attribute.',
    'uppercase' => 'يجب أن يكون حقل :attribute بحروف كبيرة.',
    'url' => 'يجب أن يكون حقل :attribute رابطاً صحيحاً.',
    'ulid' => 'يجب أن يكون حقل :attribute معرف ULID صحيحاً.',
    'uuid' => 'يجب أن يكون حقل :attribute معرف UUID صحيحاً.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'login' => 'البريد الإلكتروني أو الهاتف',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'phone' => 'رقم الهاتف',
        'avatar' => 'الصورة الشخصية',
        'role' => 'الدور',
        'name' => 'الاسم',
    ],

];
