<?php

namespace AwaisWP\GDriveWPCLIPackage;

/**
 * Class Command
 * @package AwaisWP\GDriveWPCLIPackage
 */

class Command extends \WP_CLI_Command{

	use ReadWrite;

	/**
	 * The GDrive instace.
	 *
	 * @var gdrive
	 */
	private $gdrive = null;

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
		$this->wp_cli_register_commands();
	}

	/**
	 * Register WP CLI command.
	 **/
	public function wp_cli_register_commands() {
		\WP_CLI::add_command( $this->command_name, array( $this, 'upload' ), $this->get_upload_command_synopsis() );

		 // Register the addkey sub-command
        \WP_CLI::add_command( "{$this->command_name} addkey", array( $this, 'add_key' ), $this->get_addkey_command_synopsis() );

         // Register the auth sub-command
        \WP_CLI::add_command( "{$this->command_name} auth", array( $this, 'authenticate' ), array(
            'shortdesc' => 'Authenticate with Google Drive using stored client ID and client secret.',
        ));
	}

	public function get_addkey_command_synopsis() {
		return array(
            'shortdesc' => 'Add Google Drive API client ID and client secret to a file as JSON.',
            'synopsis'  => array(
                array(
                    'type'        => 'assoc',
                    'name'        => 'client_id',
                    'description' => 'Google Drive API client ID.',
                    'optional'    => false,
                ),
                array(
                    'type'        => 'assoc',
                    'name'        => 'client_secret',
                    'description' => 'Google Drive API client secret.',
                    'optional'    => false,
                ),
            ),
        );
	}

	/**
	 * Get WP CLI arguments.
	 **/
	public function get_upload_command_synopsis() {
		return array(
			'shortdesc' => 'Upload file to a Google Drive.',
			'synopsis'  => array(
				array(
					'type'        => 'assoc',
					'name'        => 'file',
					'description' => 'Full path to the file',
					'optional'    => false,

				),
				array(
					'type'        => 'assoc',
					'name'        => 'gdrive_folder_id',
					'description' => 'Google Drive folder ID.',
					'optional'    => false,

				),
			),
		);
	}

	/**
	 * Start the upload process via WP CLI.
	 * @param  array $args
	 * @param  array $assoc_args
	 */
	public function upload( $args, $assoc_args ) {
		$path         = $assoc_args['file'];
		$gd_folder_id = $assoc_args['gdrive_folder_id'];

		if ( $this->validate_gd_upload_arguments( $path, $gd_folder_id ) === true ) {
			$gdrive              = GDrive::get_instance();
			$gdrive->initialize($path, $gd_folder_id);
		}
	}

	/**
	 * Validate WP CLI arguments.
	 * @param string $path.
	 * @param $gd_folder_id
	 * @return bool | Exit()
	 **/
	public function validate_gd_upload_arguments( $path = '', $gd_folder_id = '' ) {
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
	public function help_gd_upload() {
		\WP_CLI::line( 'Site root path is: ' . ABSPATH );
		\WP_CLI::line( "Usage: {$this->command_name} --file=paht/to/file --gdrive_folder_id=folder_id" );
		exit;
	}

	/**
     * Start the add key process via WP CLI.
     * @param  array $args
     * @param  array $assoc_args
     */
    public function add_key( $args, $assoc_args ) {
        $client_id     = $assoc_args['client_id'];
        $client_secret = $assoc_args['client_secret'];

        if ( $this->validate_key_arguments( $client_id, $client_secret ) === true ) {
            $keys = array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
            );

            // Encode the keys array as JSON
            $keys_json = json_encode( $keys );
            $this->save_keys(CREDENTIALS_PATH, $keys_json);

            \WP_CLI::success( 'Google Drive API client ID and client secret added successfully.' );
            \WP_CLI::line( 'Try below now.' );
            $this->help_gd_upload();
        }
    }

    /**
     * Start the authentication process via WP CLI.
     */
    public function authenticate() {
        $keys = $this->get_keys();

        if ( empty( $keys ) || empty($keys['client_id']) || empty($keys['client_secret'])) {
            \WP_CLI::error( 'Google Drive API client ID and client secret are not configured. Run "wp gd-upload addkey" to add them.' );
        }

       $client = (Token::get_instance())->getClient();
       \WP_CLI::success( 'Authenticated with Google Drive successfully.' );
    }

    /**
     * Validate WP CLI key arguments.
     * @param string $client_id
     * @param string $client_secret
     * @return bool | Exit()
     **/
    public function validate_key_arguments( $client_id = '', $client_secret = '' ) {
        if ( empty( $client_id ) || empty( $client_secret ) ) {
            \WP_CLI::error_multi_line( array( 'Please provide both the Google Drive API client ID and client secret.' ) );
            $this->help_add_key();
        }

        return true;
    }

    /**
     * Display add key command help in console.
     */
    public function help_add_key() {
        \WP_CLI::line( 'Usage: wp gd-upload addkey --client_id=YOUR_CLIENT_ID --client_secret=YOUR_CLIENT_SECRET' );
        exit;
    }

}