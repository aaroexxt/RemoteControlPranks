var s = document.createElement('script');
s.src = chrome.extension.getURL('fingerprint2.js');
(document.head||document.documentElement).appendChild(s);
s.onload = function() {
    s.parentNode.removeChild(s);
    var sc = document.createElement('script');
    sc.src = chrome.extension.getURL('inject.js');
    (document.head||document.documentElement).appendChild(sc);
    sc.onload = function() {
        sc.parentNode.removeChild(sc);
    };
};