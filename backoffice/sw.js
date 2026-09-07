self.addEventListener('push', function(event) {
    if (event.data) {
        var data = event.data.json();
        var title = data.title || 'มีการแจ้งเตือนใหม่';
        var options = {
            body: data.body || 'คลิกเพื่อดูรายละเอียด',
            icon: '/am/backoffice/assets/images/am-group-logo.png', // เปลี่ยน path ให้ตรงกับโลโก้
            badge: '/am/backoffice/assets/images/am-group-logo.png',
            vibrate: [200, 100, 200, 100, 200, 100, 200],
            data: {
                url: data.url ? ('/am/backoffice/main/' + data.url) : '/am/backoffice/main/'
            }
        };
        event.waitUntil(self.registration.showNotification(title, options));
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    if (event.notification.data && event.notification.data.url) {
        event.waitUntil(clients.openWindow(event.notification.data.url));
    }
});
