import {
    render, createRoot
} from '@wordpress/element';
import DeactivationModal from "./components/DeactivationModal/DeactivationModal";
document.addEventListener( 'DOMContentLoaded', () => {

    const container = document.getElementById( 'rsssl-modal-root' );
    if ( container ) {
        if ( createRoot ) {
            createRoot( container ).render( <DeactivationModal/> );
        } else {
            render( <DeactivationModal/>, container );
        }
    }
});