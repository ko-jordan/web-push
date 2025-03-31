<?php

namespace WpWebPush;

require_once("permission.php");

class DBConnection extends \mysqli{


	protected const DB_CREDENTIALS_URI = './keys/db_credentials.txt';
	/**
	 * Return a mysqli Object with the credentials provided in file
	 */
	function __construct() {
    
		$credentials = $this -> get_credentials();
		try{
		    parent::__construct(
		    	$credentials['host'] ?? '127.0.0.1', 
		    	$credentials['user'] ?? 'root', 
		    	$credentials['password'] ?? '', 
		    	$credentials['db'] ?? 'push_notifications'
		    );

		}catch(Exception $e ) {
			Permission::exit_with_error(500, "Unable to connect to DB. '$e'", "Server not set up correctly");
		}
	}




	/**
	 * Get credentials from file.
	 */
	function get_credentials() {

		$pathToCredentials = self::DB_CREDENTIALS_URI;

		if( !file_exists( $pathToCredentials ) ) {

			Permission::exit_with_error(500, "DB Credentials not set up", "Server not set up correctly");
		}

		$credentialsContents = file_get_contents(self::DB_CREDENTIALS_URI);

		$credentials = [];

		foreach( ['user', 'password', 'db', 'host'] as $attr ) {

			$regex = '/^'. preg_quote( $attr ) . ':\s+(.*)[$\r]/m';

			preg_match( $regex, $credentialsContents, $match );

			if( !empty( $match ) && !empty( $match[1] ) ){

				$credentials[$attr] = $match[1];
			} else {

				$credentials[$attr] = '';
			}
		}

		return $credentials;
	}



	/**
	 *  Get all active(!) subscriptions 
	 */
	function get_subscriptions( $team =false ) {

		$condition = empty( $team ) ? "1" : "team = '$team'";

		$query = "SELECT * FROM subscriptions WHERE active AND $condition";

		return $this->query( $query );
	}



	/**
	 * Insert if subscription does not exist yet.
	 * If it does, set it to be active.
	 */
	function insert_subscription( $subscription ) {

		$endpoint = $subscription['endpoint'] ?? false;

		if( empty ( $endpoint ) ) {

			return false;
		}

		$auth_keys = json_encode( $subscription['keys'] ?? [] );

		$team = $subscription['team'] ?? false;

		$entry = $this -> get_entry('subscriptions', compact( 'endpoint' ) )->fetch_assoc();

		if( empty( $entry ) ){
		

			return $this->insert( 'subscriptions', compact( 'endpoint', 'auth_keys', 'team' ) );

		} else {

			if( $entry['active'] === false ) {

				return $this->update_entry( 'subscriptions', compact( 'endpoint' ), ['active' => 1, 'team' => $team]);
			}
		}
	}



	/**
	 * Insert notification.
	 */
	function insert_notification( $title, $body, $url, $team = '' ) {

		$result =  $this->insert('notifications', compact('title', 'body', 'url', 'team') );

		Permission:cancel_if_unsuccessful( $result );

		return $result;
	}



	/**
	 * Insert report.
	 */
	function insert_report( $endpoint, $notification_id, $report ) {

		$subscriptionResult = $this->get_entry( 'subscriptions', compact('endpoint') );

		if( ( $subscription = $subscriptionResult->fetch_assoc() ) ) {

			$subscription_id = $subscription['id'];

		} else {
			error_log("NOT FOUND!");
			return false;
		}

		$message = $report->getReason();

		$status = $report->isSuccess() ? 'success' : 'error';

		return $this->insert( 'reports', compact( 'notification_id', 'subscription_id', 'status', 'message' ) );
	}



	/**
	 * Generic insert function
	 */
	function insert( $table, $fields ) {

		$columns = implode( ',', array_keys( $fields ) );

		$values = array_reduce( array_values( $fields ), function( $carry, $field ){

			return $carry .= is_int( $field ) ? "$field," : "'$field',";
		},''); 

		$values = rtrim($values, ',');

		$query = "INSERT INTO $table ($columns) VALUES ($values);";

		$result = $this -> query( $query );

		if( $result ) {
			return $this->insert_id;
		}else{
			return false;
		}
	}

	/**
	 * Disable a subscription
	 */
	function disable_subscription( $endpoint ) {

		return $this->update_entry('subscriptions', compact('endpoint'), ['active' => 0]);
	}


	/**
	 * Generic get entry
	 */
	function get_entry( $table, $idPairs ) {

		$condition = $this -> _getComparison( $idPairs );

		$query = "SELECT * FROM $table where $condition";

		return $this -> query( $query );
	}



	/**
	 * Generic update function
	 */
	function update_entry( $table, $idPairs, $updates) {

		$condition = $this -> _getComparison( $idPairs );

		$update = $this -> _getComparison( $updates, ',' );

		$query = "UPDATE $table SET $update WHERE $condition";

		return $this -> query( $query );
	}



	/**
	 * get comparisons based on associative array.
	 * Works for conditions or SET clauses in update statements.
	 */
	function _getComparison( $idPairs, $glue = " AND " ) {

		$conditionArr = []; 

		foreach( $idPairs as $field => $value ) {

			$conditionArr[] = is_int($value) 
				? "$field=$value" : "$field='$value'";
		}

		return implode( $glue, $conditionArr );
	}

}
