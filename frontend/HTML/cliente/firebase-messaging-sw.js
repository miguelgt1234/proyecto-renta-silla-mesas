importScripts('https://www.gstatic.com/firebasejs/10.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "tu valor real",
    projectId: "sistemarenta-489401",
    messagingSenderId: "tu valor real",
    appId: "tu valor real"
});

const messaging = firebase.messaging();