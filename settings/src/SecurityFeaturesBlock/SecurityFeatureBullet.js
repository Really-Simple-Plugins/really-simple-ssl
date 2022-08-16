import {
    Component,
} from '@wordpress/element';
import Hyperlink from "../utils/Hyperlink";
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
                    { field.value==1 && field.new_features_block.active}
                    { field.value!=1 && field.new_features_block.inactive}
                    { field.value!=1 && field.new_features_block.readmore.length>0 && <span>&nbsp;-&nbsp;<Hyperlink target="_blank" text={__("read more","really-simple-ssl")} url={field.new_features_block.readmore}/></span> }
                </div>
            </div>
        );
    }
}
export default SecurityFeatureBullet;