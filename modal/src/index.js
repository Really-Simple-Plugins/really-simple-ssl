/** @jsx wp.element.createElement */
import {
    render, createRoot
} from '@wordpress/element';
import RssslModal from "./components/RssslModal";

document.addEventListener( 'DOMContentLoaded', () => {
    const root = wp.element.createRoot(document.getElementById('rsssl-modal-root'));
    root.render(<RssslModal />);
});


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
        });
    }
}