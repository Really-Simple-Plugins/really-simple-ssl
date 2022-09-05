import {
    Component,
} from '@wordpress/element';
import Hyperlink from "../../utils/Hyperlink";
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
        console.log(field.new_features_block);
        return (
            <div className="rsssl-new-feature">
                <div className={bulletClassName}></div>
                <div className="rssl-new-feature-label">
                    { field.value==1 && field.new_features_block.active}
                    { field.value!=1 && field.new_features_block.readmore.length>0 && <Hyperlink target="_blank" text={field.new_features_block.inactive + ' - ' + __("%sRead more%s","really-simple-ssl")} url={field.new_features_block.readmore}/> }
                </div>
            </div>
        );
    }
}
export default SecurityFeatureBullet;