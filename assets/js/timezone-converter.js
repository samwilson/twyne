(function () {
    document.body.querySelectorAll('time').forEach(function (timeEl) {
        // Show the UTC time as the tooltip.
        timeEl.title = 'UTC time: ' + timeEl.innerText;
        // Convert to local browser time for actual display.
        const date = new Date(Date.parse(timeEl.dateTime));
        const options = {
            timeZoneName: 'short',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            weekday: 'long',
            hour: 'numeric',
            minute: 'numeric'
        };
        timeEl.innerText = date.toLocaleString([], options) + '.';
    });
}());
