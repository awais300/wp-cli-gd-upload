<?php
namespace AwaisWP\GDriveWPCLIPackage;

trait ReadWrite{
	protected function save_keys($data) {
		if (file_put_contents(CREDENTIALS_PATH, $data) === false) {
			    $error = error_get_last();
			    \WP_CLI::error('Could not save keys. Reason: ' . $error);
			}
	}

	/**
     * Read keys from JSON file and convert to array
     */
    protected function get_keys() {
        $file_path = CREDENTIALS_PATH;

        if ( ! file_exists( $file_path ) ) {
            \WP_CLI::error( 'Keys file does not exist. Run "wp gd-upload addkey" to add them.' );
        }

        $keys_json = file_get_contents( $file_path );
        $keys = json_decode( $keys_json, true );

        return $keys;
    }
}

?>