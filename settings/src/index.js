import {
    render,
} from '@wordpress/element';
import Page from './Page';
import OnboardingModal from "./OnboardingModal";

/**
 * Initialize the whole thing
 */

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'really-simple-ssl' );
	if ( container ) {
		render(
			<>
				<Page/>
				<OnboardingModal/>
			</>,
			container
		);
	}
});




