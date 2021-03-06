<?php
/**
 * Все значения можно переопределить в неподверсионном файле config.local.php
 */
$config = [
    'db'          => [
        'host'     => 'localhost',
        'database' => 'ruranobe',
        'user'     => 'ruranobe',
        'password' => 'ruranobe',
    ],
    'key'         => '123',
    'public_key'  => '123',
    'ga_tid'      => '123',
    'folder'      => '123',
    'repo'        => '123',
    'repo_prefix' => '123'
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, include __DIR__ . '/config.local.php');
}
