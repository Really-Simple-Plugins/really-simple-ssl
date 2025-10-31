import {
    render, createRoot
} from '@wordpress/element';
import DeactivationModal from "./components/DeactivationModal/DeactivationModal";

const initModal = () => {
    const container = document.getElementById( 'rsssl-modal-root' );
    if ( container ) {
        if ( createRoot ) {
            createRoot( container ).render( <DeactivationModal/> );
        } else {
            render( <DeactivationModal/>, container );
        }
    }
};

// Handle both cases - DOM already loaded OR still loading
if ( document.readyState === 'loading' ) {
    // DOM hasn't loaded yet
    document.addEventListener( 'DOMContentLoaded', initModal );
} else {
    // DOM is already ready
    initModal();
}