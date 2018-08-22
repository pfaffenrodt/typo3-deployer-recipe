<?php
namespace Deployer;

require 'recipe/common.php';
require 'recipe/typo3.php';
require 'typo3_composer.php';
require 'typo3_config.php';
require 'typo3_console.php';

set('build_tasks', []);
task('build', function () {
    foreach (get('build_tasks') as $task) {
        invoke($task);
    }
})->desc('Build project');

after('deploy:vendors', 'build');
after('deploy:failed', 'deploy:unlock');

/**
 * Add TYPO3 tasks
 */
set('typo3_tasks', [
    'typo3:dump:settings',
    'typo3:create_default_folders',
    'typo3:update:databaseschema',
    'typo3:flush:caches',
    'typo3:setup:extensions',
    'typo3:flush:caches',
]);

task('typo3:tasks', function () {
    foreach (get('typo3_tasks') as $task) {
        invoke($task);
    }
})->desc('Migrate Typo3');

after('build', 'typo3:tasks');