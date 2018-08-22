<?php
namespace Deployer;

/**
 * This file contain helpers that extract config from composer.json
 */

// Fetch composer.json and store it in array for later use
set('composer_config', function () {
    $composerJsonPath = parse('{{release_path}}/composer.json');
    if (!file_exists($composerJsonPath)) {
        // If we don't find a composer.json file, we assume the root dir to be the release path
        return null;
    }
    return \json_decode(\file_get_contents($composerJsonPath), true);
});

// Extract bin-dir from composer config
set('composer_config/bin-dir', function() {
    $binDir = '{{release_path}}/vendor/bin';
    $composerConfig = get('composer_config');
    if (isset($composerConfig['config']['bin-dir'])) {
        $binDir = '{{release_path}}/' . $composerConfig['config']['bin-dir'];
    }
    return $binDir;
});

// Extract TYPO3 root dir from composer config
set('typo3/root_dir', function () {
    // If no config is provided, we assume the root dir to be the release path
    $typo3RootDir = '.';
    $composerConfig = get('composer_config');
    if (isset($composerConfig['extra']['typo3/cms']['web-dir'])) {
        $typo3RootDir = $composerConfig['extra']['typo3/cms']['web-dir'];
    }
    if (isset($composerConfig['extra']['typo3/cms']['root-dir'])) {
        $typo3RootDir = $composerConfig['extra']['typo3/cms']['root-dir'];
    }
    return $typo3RootDir;
});

// Extract TYPO3 public directory from composer config
set('typo3/public_dir', function () {
    $composerConfig = get('composer_config');
    if (!isset($composerConfig['extra']['typo3/cms']['web-dir'])) {
        // If no config is provided, we assume the web dir to be the release path
        return '.';
    }
    return $composerConfig['extra']['typo3/cms']['web-dir'];
});