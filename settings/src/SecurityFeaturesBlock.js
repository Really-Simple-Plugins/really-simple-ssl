import {
    Component,
} from '@wordpress/element';

import Placeholder from './Placeholder';
import Hyperlink from "./utils/Hyperlink";
import { __ } from '@wordpress/i18n';

class SecurityFeatureBullet extends Component {
    constructor() {
        super( ...arguments);

    }
    componentDidMount() {

    }

    render(){
        let field = this.props.field;
        let bulletClassName = field.value==1 ? 'rsssl-bullet rsssl-bullet-success' : 'rsssl-bullet rsssl-bullet-error';
        return (
            <div className="rsssl-new-feature">
                <div className={bulletClassName}></div>
                <div className="rssl-new-feature-label">
                    { field.value===1 && field.new_features_block.active}
                    { field.value!==1 && field.new_features_block.inactive}
                    { field.new_features_block.readmore.length>0 && <span>&nbsp;-&nbsp;<Hyperlink target="_blank" text={__("read more","really-simple-ssl")} url={field.new_features_block.readmore}/></span> }
                </div>
            </div>
        );
    }
}

class SecurityFeaturesBlock extends Component {
    constructor() {
        super( ...arguments);

    }
    componentDidMount() {

    }

    render(){

        if ( this.props.fields.length==0 ) {
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
            </div>

        );
    }
}
export default SecurityFeaturesBlock;