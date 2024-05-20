<?php

namespace AwaisWP\GDriveWPCLIPackage;

//defined( 'ABSPATH' ) || exit;

/**
 * Class Command
 * @package AwaisWP\GDriveWPCLIPackage
 */

class Command extends \WP_CLI_Command{

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
		//$this->gdrive = GDrive::get_instance();
	}

	/**
	 * Register WP CLI command.
	 **/
	public function wp_cli_register_commands() {
		\WP_CLI::add_command( $this->command_name, array( $this, 'upload' ), $this->get_command_arguments() );
	}

	/**
	 * Get WP CLI arguments.
	 **/
	public function get_command_arguments() {
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

		if ( $this->validate_arguments( $path, $gd_folder_id ) === true ) {
			$gdrive              = $this->gdrive;
			$gdrive->fileRequest = $path;
			$gdrive->folderId    = $gd_folder_id;
			$gdrive->initialize();
		}
	}

	/**
	 * Validate WP CLI arguments.
	 * @param string $path.
	 * @param $gd_folder_id
	 * @return bool | Exit()
	 **/
	public function validate_arguments( $path = '', $gd_folder_id = '' ) {
		if ( ! is_file( $path ) || ! file_exists( $path ) ) {
			\WP_CLI::error_multi_line( array( 'Please provide the correct path of file.' ) );
			$this->help();
		}

		if ( empty( $gd_folder_id ) || is_bool( $gd_folder_id ) ) {
			\WP_CLI::error_multi_line( array( 'Please provide the Google Drive folder ID.' ) );
			$this->help();
		}

		return true;
	}

	/**
	 * Display general help in console.
	 */
	public function help() {
		\WP_CLI::line( 'Site root path is: ' . ABSPATH );
		\WP_CLI::line( "Usage: {$this->command_name} --file=paht/to/file --gdrive_folder_id=folder_id" );
		exit;
	}
}