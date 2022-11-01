import {
  Component,
} from '@wordpress/element';
import {Button} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

class SecurityFeaturesFooter extends Component {
  constructor() {
    super( ...arguments);
  }

  render(){
    return (
            <a className="button button-default" href="#settings">{ __( 'Settings', 'really-simple-ssl' ) }</a>
    );
  }
}
export default SecurityFeaturesFooter;