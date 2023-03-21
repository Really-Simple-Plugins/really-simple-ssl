import { __ } from '@wordpress/i18n';
const SecurityFeaturesFooter = () => {
   return (
            <a className="button button-default" href="#settings">{ __( 'Settings', 'really-simple-ssl' ) }</a>
    );
}
export default SecurityFeaturesFooter;