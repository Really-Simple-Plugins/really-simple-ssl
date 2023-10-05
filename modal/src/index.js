/** @jsx wp.element.createElement */
import {
    render, createRoot
} from '@wordpress/element';
import DeactivationModal from "./components/DeactivationModal/DeactivationModal";

document.addEventListener( 'DOMContentLoaded', () => {
    const container = document.getElementById( 'rsssl-modal-root' );
    if ( container ) {
        console.log("found container");
        if ( createRoot ) {
            createRoot( container ).render( <DeactivationModal/> );
        } else {
            render( <DeactivationModal/>, container );
        }
    }
});
/*
    * This event listener is used to open the modal window when the user clicks on the "Deactivate" link
 */
// function initEventListener() {
//     const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');
//     if (targetPluginLink) {
//         targetPluginLink.addEventListener('click', function(e) {
//             e.preventDefault();
//             window.showRssslModal();
//         });
//     }
// }