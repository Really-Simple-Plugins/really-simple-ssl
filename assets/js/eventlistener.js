console.log('eventlistener.js is loaded');
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEventListener);
} else {
    initEventListener();
}

window.showRssslModal = function() {
    const event = new Event('showRssslModalEvent');
    document.dispatchEvent(event);
};

/*
    * This event listener is used to open the modal window when the user clicks on the "Deactivate" link
 */function initEventListener() {
    const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
    if (targetPluginLink) {
        targetPluginLink.addEventListener('click', function(e) {
            e.preventDefault();
            window.showRssslModal();
            console.log('showRssslModalEvent');
        });
    }
}