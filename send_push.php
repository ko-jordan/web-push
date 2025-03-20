<?php

namespace WpWebPush;

ini_set("log_errors", 1);
ini_set("error_log", "php-error.log");

require_once('permission.php');

Permission::check_authorization();

require_once("db_handler.php");
require_once("vendor/autoload.php");

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$webPush = Permission::get_web_push();

//get POST data
$title	= htmlspecialchars( $_POST['title'] );
$body	= htmlspecialchars( $_POST['body'] );
$url	= htmlspecialchars( $_POST['url'] );

$dbConnection = new DBConnection();

//insert notification into DB
$notificationId = $dbConnection->insert_notification( $title, $body, $url );

//throw error if 
Permission::cancel_if_unsuccessful( $notificationId );

//generate notification payload
$payload = "{\"title\": \"$title\", \"body\": \"$body\", \"url\": \"$url\"}";

//get subscriptions
$subscriptions = $dbConnection->get_subscriptions();

//count how many notifications get send.
$countSucessfulPushes = 0;
$countFailedPushes = 0;

//send notification to all subscribers
while( ( $sub = $subscriptions -> fetch_assoc() ) ) {

	//get subscription object
	$subscription = prepare_subscription( $sub );

	//TODO: check notification queuing
	//send notifications one at a time
	$report = $webPush->sendOneNotification( $subscription, $payload, [ 'TTL' => 3600 ] );

	//disable all subscriptions that didnt succeed
	if( !$report->isSuccess() && $report->isSubscriptionExpired() ) {

		$dbConnection->disable_subscription( $sub['endpoint'] );

		$countFailedPushes++;

	} else {
		$countSucessfulPushes++;
	}

	//insert report into DB
	$dbConnection->insert_report($sub['endpoint'], $notificationId, $report);
}

echo <<<EOM
	{"status": 200, "message": "Notification pushed.", "successful": $countSucessfulPushes, "failed": $countFailedPushes}
EOM;

/**
 * Build subscription JSON in order to instantiate and return Subscription object
 */
function prepare_subscription( $sub ) {

	$subscriptionJSON = "{\"endpoint\":\"{$sub['endpoint']}\",\"keys\":{$sub['auth_keys']}}";

	return Subscription::create( json_decode( $subscriptionJSON, true ) );
}
