import {
    render, createRoot
} from '@wordpress/element';
import Page from './Page';

/**
 * Initialize
 */

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'really-simple-ssl' );
	if ( container ) {
		if ( createRoot ) {
			createRoot( container ).render( <Page/> );
		} else {
			render( <Page/>, container );
		}
	}
});

/*
* Some oldschool stuff
*/

document.addEventListener('click', e => {
    if ( e.target.closest('#ssl-labs-check-button') ) {
        document.querySelector('.rsssl-ssllabs').classList.add('rsssl-block-highlight');
    }
});

