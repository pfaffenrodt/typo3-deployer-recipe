<?php
namespace Deployer;

use Deployer\Exception\ConfigurationException;

require 'recipe/common.php';
require 'recipe/rsync.php';
require 'typo3_composer.php';
require 'typo3_config.php';
require 'typo3_console.php';

// Unset env vars that affect build process
unset($_ENV['TYPO3_CONTEXT'], $_ENV['TYPO3_PATH_ROOT'], $_ENV['TYPO3_PATH_WEB'], $_ENV['TYPO3_PATH_COMPOSER_ROOT'], $_ENV['TYPO3_PATH_APP']);
putenv('TYPO3_CONTEXT');
putenv('TYPO3_PATH_ROOT');
putenv('TYPO3_PATH_WEB');
putenv('TYPO3_PATH_COMPOSER_ROOT');
putenv('TYPO3_PATH_APP');

// Determine the source path which will be rsynced to the server
set('source_path', function () {
    $sourcePath = '{{build_path}}/current';
    if (!has('build_path') && !Deployer::hasDefault('build_path')) {
        if (!file_exists(\getcwd() . '/deploy.php')) {
            throw new ConfigurationException('Could not determine path to deployment source directory ("source_path")', 1512317992);
        }
        $sourcePath = getcwd();
    }
    return $sourcePath;

});
/*
 * Local build and rsync strategy
 */
set('build_tasks', []);
task('build', function () {
    if (!has('build_path') && !Deployer::hasDefault('build_path')) {
        // No build path defined. Assuming source path to be the current directory, skipping build
        return;
    }
    // This code is copied from TaskCommand, as it seems to be the only option currently to get the target hosts
    $stage = input()->hasArgument('stage') ? input()->getArgument('stage') : null;
    $roles = input()->getOption('roles');
    $hosts = input()->getOption('hosts');
    if (!empty($hosts)) {
        $hosts = Deployer::get()->hostSelector->getByHostnames($hosts);
    } elseif (!empty($roles)) {
        $hosts = Deployer::get()->hostSelector->getByRoles($roles);
    } else {
        $hosts = Deployer::get()->hostSelector->getHosts($stage);
    }
    // Just select one host under the assumption that it does not make sense
    // to deploy different branches for the same hosts selection
    $hostBranch = current($hosts)->getConfig()->get('branch');
    $defaultBranch = get('branch');
    // Only change the branch, if we have differences
    if ($defaultBranch !== $hostBranch) {
        set('branch', $hostBranch);
    }
    set('deploy_path', '{{build_path}}');
    set('keep_releases', 1);
    invoke('deploy:prepare');
    invoke('deploy:release');
    invoke('deploy:update_code');
    invoke('deploy:vendors');
    foreach (get('build_tasks') as $task) {
        invoke($task);
    }
    invoke('deploy:symlink');
    invoke('cleanup');

})->local()->desc('Build project')->setPrivate();

task('transfer',  [
    'deploy:release',
    'rsync:warmup',
    'rsync',
    'deploy:shared',
])->desc('Transfer code to target hosts')->setPrivate();

task('release', [
    'deploy:symlink',
])->desc('Release code on target hosts')->setPrivate();

task('rsync')->setPrivate();
task('rsync:warmup')->setPrivate();
task('deploy:copy_dirs')->setPrivate();
task('deploy:clear_paths')->setPrivate();

add('rsync', [
    'exclude' => [
        '.DS_Store',
        '.gitignore',
        '/.env',
        '/{{typo3/root_dir}}/fileadmin',
        '/{{typo3/root_dir}}/typo3temp',
        '/{{typo3/root_dir}}/uploads',
        '/var/log',
    ],
    'flags' => 'r',
    'options' => [
        'times',
        'perms',
        'links',
        'delete',
        'delete-excluded',
    ],
    'timeout' => 360,
]);
set('rsync_src', '{{source_path}}');
set('rsync_dest','{{release_path}}');

/*
 * Main deploy task
 */
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'build',
    'transfer',
    'release',
    'cleanup',
])->desc('Deploy your project');
after('deploy', 'success');
before('transfer', 'deploy:lock');
after('release', 'deploy:unlock');
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

after('transfer', 'typo3:tasks');
after('release', 'typo3:flush:caches');
