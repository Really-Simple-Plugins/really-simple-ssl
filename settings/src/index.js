import {
    render,
} from '@wordpress/element';
import Page from './Page';

/**
 * Initialize
 */

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'really-simple-ssl' );
	if ( container ) {
		render(
			<>
				<Page/>
			</>,
			container
		);
	}
});




