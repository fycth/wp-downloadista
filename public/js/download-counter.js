document.addEventListener('DOMContentLoaded', function () {
    var startButton = document.getElementById('startCounterButton');
    var counterDiv = document.getElementById('downloadista-counter');
    var utcExpiration = counterDiv.getAttribute('data-expiration');
    var checkInterval;
    startButton.addEventListener('click', function () {
        if (counterDiv) {
            counterDiv.style.display = 'block'; // Show the counter div
            startButton.style.display = 'none'; // Hide the button

            if (!isLinkExpired()) {
                var url = counterDiv.getAttribute('data-url');
                var counter = parseInt(downloadistaSettings.counterValue, 10);
                var interval = setInterval(function () {
                    if (counter === 0) {
                        counterDiv.innerHTML = '<span>' + downloadistaSettings.urlText + '</span><a target="_blank" href="' + url + '">' + downloadistaSettings.urlName + '</a>';
                        clearInterval(interval);
                        startExpirationCheck();
                    } else {
                        counterDiv.innerHTML = downloadistaSettings.counterPreText + counter + downloadistaSettings.counterPostText;
                        counter--;
                    }
                }, 1000);
            }
        }
    });

    function startExpirationCheck() {
        checkInterval = setInterval(isLinkExpired, 1000);
    }

    function isLinkExpired() {
        var nowUtc = new Date().toISOString().slice(0, 19) + 'Z'; // Get current UTC time in ISO format
        if (nowUtc >= utcExpiration) {
            clearInterval(checkInterval);
            counterDiv.innerHTML = downloadistaSettings.linkExpiredMessage;
            return true;
        }
        return false
    }
});
