<?php
namespace Minishlink\WebPush;
require_once("vendor/autoload.php");

use Minishlink\WebPush\WebPush;
echo "<pre>";
var_dump(VAPID::createVapidKeys());
echo "</pre>";