<?php

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

$autoload = 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

define('CREDENTIALS_PATH', __DIR__ . '/credentials.json');
define('TOKEN_PATH', __DIR__ . '/token.json');
define('SCOPES', implode(' ', [
    \Google_Service_Drive::DRIVE_FILE
]));

// Register the main command with WP-CLI
//\WP_CLI::add_command('gd-upload', '\AwaisWP\GDriveWPCLIPackage\Command');

new \AwaisWP\GDriveWPCLIPackage\Command();