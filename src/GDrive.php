<?php

namespace AwaisWP\GDriveWPCLIPackage;

use WP_CLI;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Google_Http_MediaFileUpload;

/**
 * Class GDrive
 * @package AwaisWP\GDriveWPCLIPackage
 */
class GDrive extends Singleton
{
    /**
     * @var string The path of the file to be uploaded.
     */
    public $file_request;

    /**
     * @var string The Google Drive folder ID where the file will be uploaded.
     */
    public $folder_id;

    /**
     * @var string The MIME type of the file to be uploaded.
     */
    private $mime_type;

    /**
     * @var string The name of the file to be uploaded.
     */
    private $file_name;

    /**
     * @var string The directory path of the file to be uploaded.
     */
    private $path;

    /**
     * @var \Google_Client The Google Client instance.
     */
    private $client;

    /**
     * Construct the class
     */
    public function __construct()
    {
        $this->client = (Token::get_instance())->getClient();
    }

    /**
     * Set Google API, get token, and initialize the upload process.
     *
     * @param string $path The path of the file to be uploaded.
     * @param string $gd_folder_id The Google Drive folder ID where the file will be uploaded.
     */
    function initialize($path, $gd_folder_id)
    {
        $this->file_request = $path;
        $this->folder_id    = $gd_folder_id;
        try {
            $this->display('Initializing uploading...');
            $this->process_file();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $this->display($message);
            $this->display('Exiting...');
            exit();
        }
    }

    /**
     * Process the file and display its MIME type.
     */
    public function process_file()
    {
        $file_request = $this->file_request;
        $this->display("Process File: $file_request");

        $path_parts     = pathinfo($file_request);
        $this->path     = $path_parts['dirname'];
        $this->file_name = $path_parts['basename'];

        $finfo          = finfo_open(FILEINFO_MIME_TYPE);
        $this->mime_type = finfo_file($finfo, $file_request);
        finfo_close($finfo);

        $this->display('Mime type is: ' . $this->mime_type);
        $this->upload();
    }

    /**
     * Upload the file in chunks. Uploading in chunks allows for uploading a large file.
     */
    public function upload()
    {
        $client   = $this->client;
        $folder_id = $this->folder_id;
        $file_path = $this->file_request;

        $drive_service = new Google_Service_Drive($client);

        if (empty($folder_id)) {
            $fileMetadata = new Google_Service_Drive_DriveFile(
                array(
                    'name' => basename($file_path),
                )
            );
        } else {
            $fileMetadata = new Google_Service_Drive_DriveFile(
                array(
                    'name'    => basename($file_path),
                    'parents' => array($folder_id),
                )
            );
        }

        $chunk_size_bytes = 20 * 1024 * 1024; // 20MB chunk size (adjust as needed).
        $client->setDefer(true);

        $request = $drive_service->files->create($fileMetadata);
        $media   = new Google_Http_MediaFileUpload(
            $client,
            $request,
            'application/octet-stream', // Set the appropriate MIME type for your file.
            null,
            true,
            $chunk_size_bytes
        );

        $media->setFileSize(filesize($file_path));

        // Start uploading.
        $this->display('Uploading...: ' . $this->file_name);

        if ($this->is_cli()) {
            $progress = \WP_CLI\Utils\make_progress_bar('Uploading Progress', $this->get_total_ticks($file_path, $chunk_size_bytes));
        }

        // Upload the various chunks. $status will be false until the process is complete.
        $status = false;
        $handle = fopen($file_path, 'rb');
        while (!$status && !feof($handle)) {
            $chunk  = fread($handle, $chunk_size_bytes);
            $status = $media->nextChunk($chunk);
            if ($this->is_cli()) {
                $progress->tick();
            }
        }

        // The final value of $status will be the data from the API for the object that has been uploaded.
        $result = false;
        if ($status != false) {
            $result = $status;
        }
        fclose($handle);
        if ($this->is_cli()) {
            $progress->finish();
        }
        $this->display('File uploaded!');

        // Reset to the client to execute requests immediately in the future.
        $client->setDefer(false);
    }

    /**
     * Display a message to the user.
     *
     * @param string $message The message to display.
     * @param bool $flush Whether to flush the output buffer.
     */
    public function display($message = '', $flush = true)
    {
        if ($this->is_cli()) {
            WP_CLI::line($message);
        } else {
            echo $message . '<br/>';
            if ($flush === true) {
                $this->flush_output();
            }
        }
    }

    /**
     * Try to flush the output buffer to the browser.
     */
    public function flush_output()
    {
        wp_ob_end_flush_all();
        flush();
    }

    /**
     * Get the total number of chunks required to upload the file.
     *
     * @param string $file The path to the file.
     * @param int $chunk_size The size of each chunk in bytes.
     * @return int The total number of chunks.
     */
    public function get_total_ticks($file, $chunk_size)
    {
        $file_size = filesize($file);
        $count     = $file_size / $chunk_size;
        return $count;
    }

    /**
     * Check if the script is running in CLI mode.
     *
     * @return bool True if running in CLI mode, false otherwise.
     */
    public function is_cli()
    {
        return php_sapi_name() === 'cli';
    }
}
