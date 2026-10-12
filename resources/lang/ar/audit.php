<?php

return [
    'resource' => [
        'label' => 'سجل التدقيق',
        'plural_label' => 'سجلات التدقيق',
        'navigation_group' => 'التدقيق',
    ],

    'columns' => [
        'time' => 'الوقت',
        'event' => 'الحدث',
        'subject' => 'الموضوع',
        'actor' => 'المستخدم',
        'guard' => 'الحارس',
        'batch' => 'الدُفعة',
        'ip' => 'عنوان IP',
        'url' => 'الرابط',
    ],

    'sections' => [
        'overview' => 'نظرة عامة',
        'changes' => 'التغييرات',
        'context' => 'السياق',
        'http_requests' => 'طلبات HTTP الصادرة',
    ],

    'fields' => [
        'before' => 'قبل',
        'after' => 'بعد',
        'method' => 'الطريقة',
        'status_code' => 'رمز الحالة',
        'duration' => 'المدة',
        'failed' => 'فشل',
    ],

    'tabs' => [
        'all' => 'الكل',
        'created' => 'إنشاء',
        'updated' => 'تعديل',
        'deleted' => 'حذف',
        'routes' => 'المسارات',
    ],

    'actions' => [
        'revert' => 'تراجع',
        'revert_this_change' => 'التراجع عن هذا التغيير',
        'prune_selected' => 'حذف المحدد',
    ],

    'widgets' => [
        'total_audits' => 'إجمالي التدقيقات',
        'created' => 'إنشاء',
        'updated' => 'تعديل',
        'deleted' => 'حذف',
        'route_hits' => 'طلبات المسارات',
        'distinct_actors' => 'المستخدمين الفريدين',
        'total_actors' => 'إجمالي المستخدمين',
        'most_active' => 'الأكثر نشاطاً',
        'active_today' => 'النشطون اليوم',
        'system_actions' => 'إجراءات النظام',
        'no_actor' => 'بدون مستخدم',
        'audits_count' => ':count تدقيق',
        'recent_activity' => 'آخر نشاط التدقيق',
        'command_performance' => 'أداء الأوامر',
        'duration_ms' => 'المدة (مللي ثانية)',
        'audit_impact' => 'تأثير التدقيق',
    ],

    'rollback' => [
        'changes_reverted' => 'سيتم التراجع عن التغييرات التالية:',
        'attribute' => 'الخاصية',
        'current' => 'الحالي',
        'restoring_to' => 'الاستعادة إلى',
        'no_changes' => 'لم يتم العثور على تغييرات.',
        'event' => 'الحدث',
    ],

    'actor' => [
        'system' => 'النظام',
    ],

    'relation' => [
        'title' => 'السجل',
    ],

    'filters' => [
        'model' => 'الموديل',
        'actor_type' => 'نوع المستخدم',
        'actor_id' => 'رقم المستخدم',
        'from' => 'من',
        'until' => 'إلى',
        'batch_id' => 'رقم الدُفعة',
        'only_rollbackable' => 'القابل للتراجع فقط',
    ],

    'status' => [
        'ok' => 'نجاح',
        'failed' => 'فشل',
    ],
];
