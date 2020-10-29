<?php

return [
    'name' => 'SAIA Migrations',
    'migrations_namespace' => 'Saia\Pqr\migrations\lista',
    'table_name' => 'pqr_migrations',
    'column_name' => 'version',
    'column_length' => 14,
    'executed_at_column_name' => 'executed_at',
    'migrations_directory' => 'lista',
    'all_or_nothing' => true,
    'check_database_platform' => true
];
