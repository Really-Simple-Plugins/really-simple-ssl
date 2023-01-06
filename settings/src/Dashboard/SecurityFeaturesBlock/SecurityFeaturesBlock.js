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

    render(){

        if ( this.props.fields && this.props.fields.length==0 ) {
            return (
                <Placeholder></Placeholder>
            );
        }

        let fields = this.props.fields;
        fields = fields.filter( field => field.new_features_block );
        return (
            <>
                <div className={'rsssl-new-features'}>
                    {fields.map((field, i) => <SecurityFeatureBullet key={i} index={i} field={field} fields={fields}/>)}

                </div>
                <div className="rsssl-new-feature-desc">
                    <p>{__("Improve WordPress security.", "really-simple-ssl")}&nbsp;
                        <Hyperlink target="_blank" text={__("Check our %sdocumentation%s","really-simple-ssl")} url="https://really-simple-ssl.com/instructions/about-hardening-features"/>&nbsp;
                        <Hyperlink target="_blank" text={__("or use the %sWordPress forum%s.","really-simple-ssl")} url="https://wordpress.org/support/plugin/really-simple-ssl/"/>
                    </p>
                </div>
            </>
        );
    }
}
export default SecurityFeaturesBlock;