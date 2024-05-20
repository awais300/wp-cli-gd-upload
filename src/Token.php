<?php

namespace AwaisWP\GDriveWPCLIPackage;

//defined('ABSPATH') || exit;

/**
 * Class Token
 * @package AwaisWP\GDriveWPCLIPackage
 */

class Token extends Singleton
{
	public function getClient()
	{
		$client = new \Google_Client();
		$client->setApplicationName('Google Drive API');
		$client->setScopes(SCOPES);
		$client->setAuthConfig(CREDENTIALS_PATH);
		$client->setAccessType('offline');
		$client->setPrompt('select_account consent');

		// Load previously authorized token from a file, if it exists.
		if (file_exists(TOKEN_PATH)) {
			$accessToken = json_decode(file_get_contents(TOKEN_PATH), true);
			$client->setAccessToken($accessToken);
		}

		// If there is no previous token or it's expired, get a new one.
		if ($client->isAccessTokenExpired()) {
			if ($client->getRefreshToken()) {
				$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			} else {
				// Request authorization from the user.
				$authUrl = $client->createAuthUrl();
				printf("Open the following link in your browser:\n%s\n", $authUrl);
				print 'Enter verification code: ';
				$authCode = trim(fgets(STDIN));

				// Exchange authorization code for an access token.
				$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
				$client->setAccessToken($accessToken);

				// Check to see if there was an error.
				if (array_key_exists('error', $accessToken)) {
					throw new Exception(join(', ', $accessToken));
				}
			}
			// Save the token to a file.
			if (!file_exists(dirname(TOKEN_PATH))) {
				mkdir(dirname(TOKEN_PATH), 0700, true);
			}
			file_put_contents(TOKEN_PATH, json_encode($client->getAccessToken()));
		}
		return $client;
	}
}
