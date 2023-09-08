document.addEventListener('DOMContentLoaded', function() {
    //the deactivation button
    const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
    window.showRssslModal = function() {
        const event = new Event('showRssslModalEvent');
        document.dispatchEvent(event);
    };

    if (targetPluginLink) {
        targetPluginLink.addEventListener('click', function(e) {
            e.preventDefault();
           window.showRssslModal();
           console.log('showRssslModalEvent');
        });
    }
});