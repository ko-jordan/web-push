<?php

namespace WpWebPush;

ini_set("log_errors", 1);
ini_set("error_log", "logs/php-error.log");


require_once('db_handler.php');

$dbConnection = new DBConnection();

//get subscription from POST data
$subscription = json_decode(file_get_contents('php://input'), true);

header('Content-Type: application/json');

$domain = $_SERVER['SERVER_NAME'];

header("Access-Control-Allow-Origin: $domain");
header("Access-Control-Request-Methods: POST, PUT, DELETE");
header("Access-Control-Max-Age: 86400");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
     // create a new subscription entry in database (endpoint is unique)

    $success = $dbConnection->insert_subscription( $subscription );

    $message = $success ? "Du bist für Push-Notifications angemeldet!" : "Anmelden hat nicht geklappt..";

    $success = $success ? 'true' : 'false';

    echo <<<EOM
{"status": 200, "success": $success, "message": "$message"}
EOM;
        break;
    case 'PUT':
        // update the key and token of subscription corresponding to the endpoint
        echo <<<EOM
{"status": 200, "success": true, "message": "Anmeldung aktualisiert!"}
EOM;
        break;
    case 'DELETE':
        // delete the subscription corresponding to the endpoint
    $success = $dbConnection->disable_subscription( $subscription['endpoint'] );

    $message = $success ? "Anmeldung gelöscht!" : "Anmeldung konnte nicht gelöscht werden.";

    $success = $success ? 'true' : 'false';

        echo <<<EOM
{"status": 200, "success": $success, "message": "$message"}
EOM;
        break;
    default:
    	header('HTTP/1. 403 Forbidden');
        echo <<<EOM
{"status": 403, "error": "Access denied!"}
EOM;
        return;
}
