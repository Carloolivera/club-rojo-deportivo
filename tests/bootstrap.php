<?php

// Register worktree app path in the autoloader so tests can find classes
// that exist in the worktree but not in the main project directory.
$loader = require __DIR__ . '/../vendor/autoload.php';

$worktreeApp = realpath(__DIR__ . '/../app');
if ($worktreeApp) {
    $loader->addPsr4('App\\', $worktreeApp, true);
}

// Force Laravel to use the worktree as the base path so that routes, views,
// and other resources are loaded from the worktree, not the main project.
$_ENV['APP_BASE_PATH'] = realpath(__DIR__ . '/..');
