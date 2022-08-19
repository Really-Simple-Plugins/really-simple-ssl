import {
    Component,
} from '@wordpress/element';
import {
    Button,
} from '@wordpress/components';
import SecurityFeatureBullet from './SecurityFeatureBullet';
import Placeholder from '../../Placeholder/Placeholder';
import Hyperlink from "../../utils/Hyperlink";
import { __ } from '@wordpress/i18n';

class SecurityFeaturesBlock extends Component {
    constructor() {
        super( ...arguments);

    }
    componentDidMount() {

    }

    redirectToSettingsMenu(){
        this.props.selectMainMenu('settings');
    }

    render(){

        if ( this.props.fields && this.props.fields.length==0 ) {
            return (
                <Placeholder></Placeholder>
            );
        }

        let fields = this.props.fields;
        fields = fields.filter( field => field.new_features_block );
        return (
            <div>
                {fields.map((field, i) => <SecurityFeatureBullet key={i} index={i} field={field} fields={fields}/>)}
                <div className="rsssl-new-feature-desc">
                    <p>{__("Upgrade your security in a few clicks!","realy-simple-ssl")}</p>
                    <p>{__("Check out the", "really-simple-ssl")}&nbsp;<Hyperlink target="_blank" text={__("Documentation","really-simple-ssl")} url="https://really-simple-ssl.com/hardening"/>&nbsp;
                    {__("or the", "really-simple-ssl")}&nbsp;
                        <Hyperlink target="_blank" text={__("WordPress forum","really-simple-ssl")} url="https://wordpress.org/plugins/really-simple-ssl"/></p>
                </div>
                <Button variant="secondary" onClick={ (e) => this.redirectToSettingsMenu(e)}>{ __( 'Settings', 'really-simple-ssl' ) }</Button>
            </div>

        );
    }
}
export default SecurityFeaturesBlock;