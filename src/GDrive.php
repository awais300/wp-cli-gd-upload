<?php

namespace AwaisWP\GDriveWPCLIPackage;

//defined( 'ABSPATH' ) || exit;

/**
 * Class GDrive
 * @package AwaisWP\Excluder\Addon\GDrive
 */

class GDrive {



	private $clientId     = null;
	private $clientSecret = null;
	private $redirectUri  = null;

	public $fileRequest;
	public $folderId;

	private $mimeType;
	private $fileName;
	private $path;
	private $client;

	public function __construct() {
		$this->client = (Token::get_instance())->getClient();
	}

	/**
	 * Set Google API. Get Token and init the upload process.
	 *
	 **/
	function initialize($path, $gd_folder_id) {
		$this->fileRequest = $path;
		$this->folderId    = $gd_folder_id;
		try {
			$this->display( 'Initializing uploading...' );
			$this->processFile();

		} catch ( \Exception $e ) {
			$message = $e->getMessage();
			$this->display( $message );
			$this->display( 'Exiting...' );
			exit();
		}
	}

	/**
	 * Process file and display the mime type.
	 *
	 **/
	public function processFile() {
		$fileRequest = $this->fileRequest;
		$this->display( "Process File: $fileRequest" );

		$path_parts     = pathinfo( $fileRequest );
		$this->path     = $path_parts['dirname'];
		$this->fileName = $path_parts['basename'];

		$finfo          = finfo_open( FILEINFO_MIME_TYPE );
		$this->mimeType = finfo_file( $finfo, $fileRequest );
		finfo_close( $finfo );

		$this->display( 'Mime type is: ' . $this->mimeType );
		$this->upload();
	}

	/**
	 * Upload the file in chunks.
	 * Uploading in chunks allows to upload a large file.
	 **/
	public function upload() {
		$client   = $this->client;
		$folderId = $this->folderId;
		$filePath = $this->fileRequest;

		$driveService = new \Google_Service_Drive( $client );

		if ( empty( $folderId ) ) {
			$fileMetadata = new \Google_Service_Drive_DriveFile(
				array(
					'name' => basename( $filePath ),
				)
			);
		} else {
			$fileMetadata = new \Google_Service_Drive_DriveFile(
				array(
					'name'    => basename( $filePath ),
					'parents' => array( $folderId ),
				)
			);
		}

		$chunkSizeBytes = 20 * 1024 * 1024; // 20MB chunk size (adjust as needed).
		$client->setDefer( true );

		$request = $driveService->files->create( $fileMetadata );
		$media   = new \Google_Http_MediaFileUpload(
			$client,
			$request,
			'application/octet-stream', // Set the appropriate MIME type for your file.
			null,
			true,
			$chunkSizeBytes
		);

		$media->setFileSize( filesize( $filePath ) );

		// Start uploading.
		$this->display( 'Uploading...: ' . $this->fileName );

		if ( $this->is_cli() ) {
			$progress = \WP_CLI\Utils\make_progress_bar( 'Uploading Progress', $this->get_total_ticks( $filePath, $chunkSizeBytes ) );
		}

		// Upload the various chunks. $status will be false until the process is complete.
		$status = false;
		$handle = fopen( $filePath, 'rb' );
		while ( ! $status && ! feof( $handle ) ) {
			$chunk  = fread( $handle, $chunkSizeBytes );
			$status = $media->nextChunk( $chunk );
			if ( $this->is_cli() ) {
				$progress->tick();
			}
		}

		// The final value of $status will be the data from the API for the object that has been uploaded.
		$result = false;
		if ( $status != false ) {
			$result = $status;
		}
		fclose( $handle );
		if ( $this->is_cli() ) {
			$progress->finish();
		}
		$this->display( 'File uploaded!' );

		// Reset to the client to execute requests immediately in the future.
		$client->setDefer( false );
		//dd($result);
	}

	/**
	 * Display message on browser.
	 * @param string $message
	 * @param bool $flush
	 **/
	public function display( $message = '', $flush = true ) {
		if ( $this->is_cli() ) {
			\WP_CLI::line( $message );
		} else {
			echo $message . '<br/>';
			if ( $flush === true ) {
				$this->flush_output();
			}
		}
	}

	/**
	 * Try to flush output buffer to browser.
	 **/
	public function flush_output() {
		wp_ob_end_flush_all();
		flush();
	}

	/**
	 * Get total chunks of file based on its chunck size.
	 **/
	public function get_total_ticks( $file, $chunk_size ) {
		$file_size = filesize( $file );
		$count     = $file_size / $chunk_size;
		return $count;
	}

	/**
	 * Check if you are CLI mode.
	 **/
	public function is_cli() {
		if ( php_sapi_name() === 'cli' ) {
			return true;
		} else {
			return false;
		}
	}
}
