<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width">
</head>
<body>
	<h1>Subscribe to Push Notifications</h1>
	<button id="subscribe" onclick="enableNotif()">Subscribe!</button>
	<input id="message" value="" placeholder="paste message here"/>
	<button id="send">Send push</button>
</body>
<script>navigator.serviceWorker.register("sw.js").then(ev=>console.log("sw registered", ev)).catch(er=>console.log("sw not registered",er));</script>
<script src="app.js" type="text/javascript"></script>
</html>