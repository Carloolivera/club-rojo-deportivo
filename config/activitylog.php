<?php

use Spatie\Activitylog\Models\Activity;

return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),
    'delete_records_older_than_days' => 365,
    'default_log_name' => 'default',
    'activity_model' => Activity::class,
    'subject_returns_soft_deleted_models' => false,
    'table_name' => env('ACTIVITY_LOG_TABLE', 'activity_log'),
    'database_connection' => env('ACTIVITY_LOG_DB_CONNECTION', null),
];
