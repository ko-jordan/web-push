self.addEventListener("push", event => {
	// {title: "hi", body: "text", url: "/mypage"}
	const notification = event.data.json();
	event.waitUntil(self.registration.showNotification(notification.title, {
		body: notification.body,
		icon: "../dam2025/dam25-logo-96.png",
		data: {notifURL: notification.url}
	}));
	console.log(notification);
})

self.addEventListener("notificationclick", event => {
	event.waitUntil(clients.openWindow(event.notification.data.notifURL));
});