<?php
namespace Deployer;
/*
 * Global config
 */

// Disallow statistics
set('allow_anonymous_stats', false);

/*
 * TYPO3-specific config
 */
set('shared_dirs', [
    '{{typo3/root_dir}}/fileadmin',
    '{{typo3/root_dir}}/uploads',
    '{{typo3/root_dir}}/typo3temp/assets',
    '{{typo3/root_dir}}/typo3temp/var/locks',
    'var/log',
]);

set('shared_files',
    [
        'conf/host.yml',
        '{{typo3/public_dir}}/.htaccess'
    ]
);

// Writeable directories
set('writable_dirs', [
    '{{typo3/root_dir}}/typo3temp/var/Cache',
    // These folders do not need to be made writeable on each deploy
    // but it is useful to make them writable on first deploy, so we keep them here
    '{{typo3/root_dir}}/fileadmin',
    '{{typo3/root_dir}}/uploads',
    '{{typo3/root_dir}}/typo3temp/assets',
    '{{typo3/root_dir}}/typo3temp/var/locks',
]);

// These are server specific and should be set in the main deployment description
// See https://deployer.org/docs/flow#deploy:writable
//after('deploy:shared', 'deploy:writable');
//set('writable_mode', 'chmod');
//set('writable_chmod_recursive', true);
//set('writable_use_sudo', false);
//set('writable_chmod_mode', 'g+w');
