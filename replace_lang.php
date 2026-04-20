<?php

$translate = [
    "'البيانات الأساسية'" => "__('messages.basic_info')",
    "'الاسم الكامل'" => "__('messages.full_name')",
    "'اسم التاجر'" => "__('messages.merchant_name')",
    "'رقم الهاتف'" => "__('messages.phone')",
    "'رقم الرخصة'" => "__('messages.license_number')",
    "'كلمة المرور'" => "__('messages.password')",
    "'الحالة'" => "__('messages.status')",
    "'نشط'" => "__('messages.active')",
    "'غير نشط'" => "__('messages.inactive')",
    "'موقوف'" => "__('messages.suspended')",
    "'موثق'" => "__('messages.verified')",
    "'الرصيد'" => "__('messages.balance')",
    "'تاريخ التسجيل'" => "__('messages.registration_date')",
    "'حالة التحقق'" => "__('messages.verification_status')",
    "'تفعيل'" => "__('messages.activate')",
    "'إيقاف'" => "__('messages.deactivate')",
    "'ربط جهاز'" => "__('messages.link_device')",
    "'الرقم التسلسلي للجهاز'" => "__('messages.device_serial')",
    "'المحفظة'" => "__('messages.wallet')",
    "'العملة'" => "__('messages.currency')",
    "'حالة المحفظة'" => "__('messages.wallet_status')",
    "'الأجهزة المرتبطة'" => "__('messages.linked_devices')",
    "'الرقم التسلسلي'" => "__('messages.serial_number')",
    "'بيانات التحقق (KYC)'" => "__('messages.kyc_data')",
    "'نوع الهوية'" => "__('messages.id_type')",
    "'رقم الهوية'" => "__('messages.id_number')",
    "'الجنسية'" => "__('messages.nationality')",
    "'العنوان'" => "__('messages.address')",
    "'تاريخ الميلاد'" => "__('messages.dob')",
    "'مقبول'" => "__('messages.approved')",
    "'مرفوض'" => "__('messages.rejected')",
    "'قيد الانتظار'" => "__('messages.pending_verification')",
    "'صورة الهوية الأمامية'" => "__('messages.id_front_image')",
    "'صورة الهوية الخلفية'" => "__('messages.id_back_image')",
    "'#'" => "__('messages.id')",
];

$files = [
    'app/Filament/AgentPanel/Resources/UserResource.php',
    'app/Filament/AgentPanel/Resources/MerchantResource.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Replace static Arabic labels with methods
    $content = preg_replace("/protected static string \| \\\\UnitEnum \| null \\\$navigationGroup = 'إدارة المستخدمين';/", "", $content);
    $content = preg_replace("/protected static \?string \\\$navigationLabel = 'العملاء';/", "", $content);
    $content = preg_replace("/protected static \?string \\\$modelLabel = 'عميل';/", "", $content);
    $content = preg_replace("/protected static \?string \\\$pluralModelLabel = 'العملاء';/", "", $content);
    
    $content = preg_replace("/protected static \?string \\\$navigationLabel = 'التجار';/", "", $content);
    $content = preg_replace("/protected static \?string \\\$modelLabel = 'تاجر';/", "", $content);
    $content = preg_replace("/protected static \?string \\\$pluralModelLabel = 'التجار';/", "", $content);

    if (strpos($file, 'UserResource') !== false) {
        $methods = "    public static function getNavigationGroup(): ?string\n    {\n        return __('messages.user_management');\n    }\n\n    public static function getNavigationLabel(): string\n    {\n        return __('messages.clients');\n    }\n\n    public static function getModelLabel(): string\n    {\n        return __('messages.client');\n    }\n\n    public static function getPluralModelLabel(): string\n    {\n        return __('messages.clients');\n    }\n";
    } else {
        $methods = "    public static function getNavigationGroup(): ?string\n    {\n        return __('messages.user_management');\n    }\n\n    public static function getNavigationLabel(): string\n    {\n        return __('messages.merchants');\n    }\n\n    public static function getModelLabel(): string\n    {\n        return __('messages.merchant');\n    }\n\n    public static function getPluralModelLabel(): string\n    {\n        return __('messages.merchants');\n    }\n";
    }

    if (strpos($content, 'getNavigationGroup') === false) {
        $content = str_replace('public static function form', $methods . "\n    public static function form", $content);
    }

    foreach ($translate as $ar => $en) {
        $content = str_replace($ar, $en, $content);
    }
    
    file_put_contents($file, $content);
    echo "Updated $file\n";
}
