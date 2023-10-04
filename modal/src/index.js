/** @jsx wp.element.createElement */

import DeactivationModal from "./components/DeactivationModal/DeactivationModal";

document.addEventListener( 'DOMContentLoaded', () => {
    const root = wp.element.createRoot(document.getElementById('rsssl-modal-root'));
    root.render(<DeactivationModal />);
});
/*
    * This event listener is used to open the modal window when the user clicks on the "Deactivate" link
 */
function initEventListener() {
    const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
    if (targetPluginLink) {
        targetPluginLink.addEventListener('click', function(e) {
            e.preventDefault();
            window.showRssslModal();
        });
    }
}