import {
    render,
} from '@wordpress/element';
import Page from './Page';
import MultiStepModal from "./MultiStepModal";

/**
 * Initialize the whole thing
 */

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'really-simple-ssl' );
	if ( container ) {
		render(
			<>
				<Page/>
				<MultiStepModal/>
			</>,
			container
		);
	}
});




