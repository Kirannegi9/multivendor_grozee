importScripts("https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js");
importScripts("https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js");

firebase.initializeApp({
  databaseURL: "https://ammart-8885e-default-rtdb.firebaseio.com",
  apiKey: "AIzaSyB_irKkbzCs35Dpth-F6efzGbwlPrb_dlY",
    authDomain: "vrindavanshringar-cfb17.firebaseapp.com",
    projectId: "vrindavanshringar-cfb17",
    storageBucket: "vrindavanshringar-cfb17.appspot.com",
    messagingSenderId: "178875540396",
    appId: "1:178875540396:web:530b32acb3b7db682c5009",
    measurementId: "G-8P07187LGV"
});

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler(function (payload) {
    const promiseChain = clients
        .matchAll({
            type: "window",
            includeUncontrolled: true
        })
        .then(windowClients => {
            for (let i = 0; i < windowClients.length; i++) {
                const windowClient = windowClients[i];
                windowClient.postMessage(payload);
            }
        })
        .then(() => {
            const title = payload.notification.title;
            const options = {
                body: payload.notification.score
              };
            return registration.showNotification(title, options);
        });
    return promiseChain;
});
self.addEventListener('notificationclick', function (event) {
    console.log('notification received: ', event)
});