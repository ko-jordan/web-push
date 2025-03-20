<?php

namespace WpWebPush;

use Minishlink\WebPush\WebPush;


/**
 * Auth Class for checking Authorization and Authentication
 */
class Permission {

	/**
	 *  URIs to AUTH files
	 */
	protected const AUTH_BEARER_URI = './keys/http_auth_bearer.txt';
	protected const VAPID_PUBLIC_KEY_URI = './keys/public_key.txt';
	protected const VAPID_PRIVATE_KEY_URI = './keys/private_key.txt';

	/**
	 * Check if Bearer token sent with request matches the
	 * stored Bearer token on the server.
	 */
	public static function check_authorization() {

		//if file is missing authorization is not set up correctly
		if( !file_exists( self::AUTH_BEARER_URI ) ) {

			self::exit_with_error(
				500,
				"Authorization not set up properly! File is missing.",
				"Authorization not set up properly!",
			);
		}

		$authTokenServer = file_get_contents('./keys/http_auth_bearer.txt');

		$allHeaders = getallheaders();

		//remove 'Bearer' keyword in the Authorization header
		$authTokenRequest = preg_replace(
			'/Bearer\s+/', 
			'', 
			$allHeaders['Authorization'] ?? '' );

		//if tokens do not match, the request is not authorized
		if( $authTokenServer !== $authTokenRequest ) {
			
			self::exit_with_error(
				403,
				"auth mismatch. server !== request ($authTokenRequest)",
				"Error Processing the request.",
				"Access denied!"
			);
		}
	}

	/**
	 * Check if VAPID keys are stored correctly. Exit if not set up.
	 */
	public static function check_vapid_setup() {

		if( !file_exists(self::VAPID_PUBLIC_KEY_URI) || !file_exists(self::VAPID_PRIVATE_KEY_URI) ) {

			self::exit_with_error(
				500,
				"VAPID not set up properly! public and/or private key missing.",
				"VAPID not set up properly!",
			);
		}
	}

	/**
	 * VAPID keys need to be stored in DB.
	 */
	public static function get_vapid() {

		self::check_vapid_setup();
		
		return [
			'VAPID' => [
				'subject' => 'mailto: moin@kojordan.com',
				'publicKey' => file_get_contents('./keys/public_key.txt'),
				'privateKey' => file_get_contents('./keys/private_key.txt')
			]
		];
	}

	/**
	 * Try to instantiate WebPush Object. Exit if it fails. 
	 */
	public static function get_web_push() {
		
		try{

			$vapid = self::get_vapid;

			return new WebPush( $vapid );

		} catch(Exception $err ) {

			self::exit_with_error(
				500,
				"Error creating WebPush Object: '$err'",
				"Error setting up web-push with VAPID!",
			);
		}
	}

	/**
	 * Cancel processing request if param is not true.
	 */
	public static function cancel_if_unsuccessful( $success ) {
		
		if( empty( $success ) ) {

			self::exit_with_error(
				422,
				"Error Processing the request.",
				"Error Processing the request.",
			);
		}
	}

	public static function exit_with_error( $code, $privateMessage, $publicMessage ) {

		$errors = [
			403 => 'Forbidden',
			422 => 'Unprocessable Entity',
			500 => 'Internal Server Error'
		];

		if( array_key_exists($code, $errors) ) {
			$code = 500;
		}

		error_log($privateMessage);

		header("HTTP/1.0 $code {$errors[$code]}");

		die("{\"status\": $code, \"error\": \"$publicMessage\"}");
	}
}