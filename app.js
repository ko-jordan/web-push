console.log("why me");
function enableNotif () {
	Notification.requestPermission().then(permission =>{
		if (permission === 'granted') {
			//get service worker
			navigator.serviceWorker.ready.then(sw => {
				sw.pushManager.subscribe({
					userVisibleOnly: true,
					applicationServerKey: `BFL_U4wuGRox4cGysRkAKkfkedHInfG7mAmicrCCqwx8kSd8m-5aHuPFgPYEOh7g1I1Q7QdPA1OcFxMEwRn0Dkw`
				}).then(subscription => {
					console.log(JSON.stringify(subscription));
				})
			})
		}
	})
}