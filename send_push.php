<?php
namespace WpWebPush;

ini_set("log_errors", 1);
ini_set("error_log", "logs/php-error.log");

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
$team	= htmlspecialchars( $_POST['team'] ?? '' );
$transfermarkt = htmlspecialchars( $_POST['transfermarkt'] ?? '');

/**
 * Setting global variables
 */
//dbConnection
$dbConnection 			= new DBConnection();
//id of notification that will be inserted in DB
$notificationId 		= null;
//count how many notifications get send.
$countSucessfulPushes 	= 0;
$countFailedPushes 		= 0;

//insert notification into DB
$notificationId = $dbConnection->insert_notification( $title, $body, $url, $team, $transfermarkt );

//throw error if 
Permission::cancel_if_unsuccessful( $notificationId );

//generate notification payload
$payload = "{\"title\": \"$title\", \"body\": \"$body\", \"url\": \"$url\"}";

//get subscriptions
$subs = $dbConnection->get_subscriptions( $team, $transfermarkt );


//send notification to all subscribers
while( ( $sub = $subs -> fetch_assoc() ) ) {

	//get subscription object
	$subscription = prepare_subscription( $sub );

	//enqueue for flushing later
	$report = $webPush->queueNotification( $subscription, $payload );
}



$webPush->flushPooled('WpWebPush\prepareReport');

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

function prepareReport( $report ) {

	global 	$notificationId, 
			$countSucessfulPushes, 
			$countFailedPushes, 
			$dbConnection;

	//disable all subscriptions that didnt succeed
	if( !$report->isSuccess() ) {

		$dbConnection->disable_subscription( $report->getEndpoint() );

		$countFailedPushes++;

	} else {

		$countSucessfulPushes++;
	}

	//insert report into DB
	$dbConnection->insert_report($report->getEndpoint(), $notificationId, $report);
}