<?php

namespace AwaisWP\GDriveWPCLIPackage;

//defined( 'ABSPATH' ) || exit;

/**
 * Class Init
 * @package AwaisWP\GDriveWPCLIPackage
 */
require_once '../vendor/autoload.php';

define('CREDENTIALS_PATH', __DIR__ . '/credentials.json');
define('TOKEN_PATH', __DIR__ . '/token.json');
define('SCOPES', implode(' ', [
    \Google_Service_Drive::DRIVE_FILE
]));


new Command();