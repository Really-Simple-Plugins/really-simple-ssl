import {
  Component,
} from '@wordpress/element';
import {Button} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

class SecurityFeaturesFooter extends Component {
  constructor() {
    super( ...arguments);
  }

  redirectToSettingsMenu(){
    this.props.selectMainMenu('settings');
  }

  render(){
    return (
        <Button variant="secondary" onClick={ (e) => this.redirectToSettingsMenu(e)}>{ __( 'Settings', 'really-simple-ssl' ) }</Button>
    );
  }
}
export default SecurityFeaturesFooter;