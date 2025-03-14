<?php
require_once("vendor/autoload.php");

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
$auth = [
	'VAPID' => [
		'subject' => 'mailto: me@me.me',
		'publicKey' => file_get_contents('./keys/public_key.txt'),
		'privateKey' => file_get_contents('./keys/private_key.txt'),


	]
];

$webPush = new WebPush( $auth );

$subscriptionsJSON = [
		'{"endpoint":"https://wns2-sg2p.notify.windows.com/w/?token=BQYAAADLTanvObQth4Lrl%2bqgSOdlN5epXAXMH9miN2eJpzo8CjKqecpc3E7xu0ktrdPgOYjOcnCE4%2bNtP9bgLPySre%2bJWkj9kBU3zEwkEGfGCDypG7svvwHizK4Q46AAD4jDUjn8xWBy9LQ6CT9alFYzzgxc8rFAlEIoa1Cb8e3zMbcIzpPqZ4EytkIZQkU2%2fwpK2TZ8IKKcW5vqmZwtRV8ug6c1nn2qaLaq6gyEi2CRQh5DRSvSTHmnG8EiCQHuf8mMnd%2foKk9KDOI%2bETNVC3kzca3r4SDZg1cpgbZQgotpXmC1pGqjSR19WCBqpDwocWm6PvA%3d","expirationTime":null,"keys":{"p256dh":"BNtHpnFxO1gcluWgGxqX083B7WcgaeWX08HENDONOFp7kG44WfUuZwq0fVoC1OXArJg0JAh9bBtZ8pr4kyZjfuU","auth":"VOJ7dXGSpSAGx3k2H_1jNA"}}',
		'{"endpoint":"https://fcm.googleapis.com/fcm/send/dF9iC5i2QUg:APA91bFwF_bC_3AFcZykiHYDNpte7TYj_JHHTPMxr_rPF2CydRg47FVYJS_xcgUZbUQXGMX9PxRQUdOxH491rUU15yvIRTvn7OBs7AQCEwbvCF0Lpj6TTz-Jqtg40ADVfsd8UrZJ1fkf","expirationTime":null,"keys":{"p256dh":"BLA7YzaEh4pPaQMUHegX99chRzdUya4JImQOGc74I-AuQJIrRJZGQ4cz2t4pZndohi7JXFFjL8-f7T73q5XzEn4","auth":"BxAWnatYGOddczw-H-3tDw"}}'
];
foreach($subscriptionsJSON as $subscrptionJSON ) {

	$subscription = Subscription::create( json_decode( $subscrptionJSON, true ) );

	$payload = '{"title": "hi from PHP", "body": "it\'s working from PHP", "url": "http://localhost/dam2025/"}';

	$resolved = $webPush->sendOneNotification( $subscription, $payload, [ 'TTL' => 100 ] );

	var_dump( $resolved );

}
