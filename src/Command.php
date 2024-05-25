<?php

namespace AwaisWP\GDriveWPCLIPackage;

/**
 * Class Command
 * @package AwaisWP\GDriveWPCLIPackage
 */

class Command extends \WP_CLI_Command{

	use ReadWrite;

	/**
	 * The command name
	 *
	 * @var gdrive
	 */
	private $command_name = 'gd-upload';

	/**
	 * Construct the class.
	 */
	public function __construct() {
		//$this->wp_cli_register_commands();
	}

	/**
	 * Register WP CLI command.
	 **/
	/*public function wp_cli_register_commands() {
		//\WP_CLI::add_command( $this->command_name, array( $this, 'upload' ) );
	}*/

	/**
     * Upload file to a Google Drive.
     * 
     * ## OPTIONS
     * 
     * --file=<file>
     * : Full path to the file. This is a required argument.
     * 
     * --gdrive_folder_id=<gdrive_folder_id>
     * : Google Drive folder ID. This is a required argument.
     * 
     * ## EXAMPLES
     * 
     *     wp gd-upload upload --file=/path/to/file --gdrive_folder_id=folder_id
     * 
     * @when after_wp_load
     */
    public function upload( $args, $assoc_args ) {
        $path = $assoc_args['file'];
        $gd_folder_id = $assoc_args['gdrive_folder_id'];

        if ( $this->validate_gd_upload_arguments( $path, $gd_folder_id ) === true ) {
            $gdrive = GDrive::get_instance();
            $gdrive->initialize($path, $gd_folder_id);
        }
    }

    /**
     * Add Google Drive API client ID and client secret.
     * 
     * ## OPTIONS
     * 
     * --client_id=<client_id>
     * : Google Drive API client ID. This is a required argument.
     * 
     * --client_secret=<client_secret>
     * : Google Drive API client secret. This is a required argument.
     * 
     * ## EXAMPLES
     * 
     *     wp gd-upload addkey --client_id=YOUR_CLIENT_ID --client_secret=YOUR_CLIENT_SECRET
     * 
     * @when after_wp_load
     */
    public function addkey( $args, $assoc_args ) {
        $client_id = $assoc_args['client_id'];
        $client_secret = $assoc_args['client_secret'];

        if ( $this->validate_key_arguments( $client_id, $client_secret ) === true ) {
            $keys = array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
            );

            // Encode the keys array as JSON
            $keys_json = json_encode( $keys );
            $this->save_keys($keys_json);

            \WP_CLI::success( 'Google Drive API client ID and client secret added successfully.' );
            \WP_CLI::line( 'Try below now.' );
            $this->help_gd_upload();
        }
    }

    /**
     * Authenticate with Google Drive using stored client ID and client secret.
     * 
     * ## EXAMPLES
     * 
     *     wp gd-upload auth
     * 
     * @when after_wp_load
     */
    public function auth() {
        $keys = $this->get_keys();

        if ( empty( $keys ) || empty($keys['client_id']) || empty($keys['client_secret'])) {
            \WP_CLI::error( 'Google Drive API client ID and client secret are not configured. Run "wp gd-upload addkey" to add them.' );
        }

        $client = (Token::get_instance())->getClient();
        \WP_CLI::success( 'Authenticated with Google Drive successfully.' );
    }


	/**
     * Validate WP CLI arguments.
     * @param string $path
     * @param string $gd_folder_id
     * @return bool | Exit()
     */
    private function validate_gd_upload_arguments( $path = '', $gd_folder_id = '' ) {
        if ( ! is_file( $path ) || ! file_exists( $path ) ) {
            \WP_CLI::error_multi_line( array( 'Please provide the correct path of file.' ) );
            $this->help_gd_upload();
        }

        if ( empty( $gd_folder_id ) || is_bool( $gd_folder_id ) ) {
            \WP_CLI::error_multi_line( array( 'Please provide the Google Drive folder ID.' ) );
            $this->help_gd_upload();
        }

        return true;
    }

    /**
     * Display general help in console.
     */
    private function help_gd_upload() {
        \WP_CLI::line( 'Site root path is: ' . ABSPATH );
        \WP_CLI::line( "Usage: {$this->command_name} --file=path/to/file --gdrive_folder_id=folder_id" );
        exit;
    }

    /**
     * Validate WP CLI key arguments.
     * @param string $client_id
     * @param string $client_secret
     * @return bool | Exit()
     */
    private function validate_key_arguments( $client_id = '', $client_secret = '' ) {
        if ( empty( $client_id ) || empty( $client_secret ) ) {
            \WP_CLI::error_multi_line( array( 'Please provide both the Google Drive API client ID and client secret.' ) );
            $this->help_addkey();
        }

        return true;
    }

    /**
     * Display add key command help in console.
     */
    private function help_addkey() {
        \WP_CLI::line( 'Usage: wp gd-upload addkey --client_id=YOUR_CLIENT_ID --client_secret=YOUR_CLIENT_SECRET' );
        exit;
    }

}