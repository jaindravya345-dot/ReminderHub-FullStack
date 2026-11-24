
self.addEventListener("push", function(event) {
    let data = {
        title: "ReminderHub",
        body: "You have a reminder due!"
    };

    if (event.data) {
        data = event.data.json();
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: "assets/img/icon.png",
            badge: "assets/img/icon.png"
        })
    );
});

self.addEventListener("notificationclick", function(event) {
    event.notification.close();

    event.waitUntil(
        clients.openWindow("./index.php")
    );
});
