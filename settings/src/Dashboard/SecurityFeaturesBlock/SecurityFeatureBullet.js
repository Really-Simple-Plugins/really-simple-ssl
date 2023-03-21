import Hyperlink from "../../utils/Hyperlink";
import { __ } from '@wordpress/i18n';
import Icon from '../../utils/Icon';
const SecurityFeatureBullet = (props) => {
    let field = props.field;
    let icon = [];
    icon['name'] = field.value == 1 ? 'circle-check' : 'circle-times';
    icon['color'] = field.value == 1 ? 'green' : 'red';
    return (
        <div className="rsssl-new-feature">
            <Icon name={icon.name} color={icon.color} />
            <div className="rsssl-new-feature-label">
                { field.value==1 && field.new_features_block.active}
                { field.value!=1 && field.new_features_block.readmore.length>0 && <Hyperlink target="_blank" text={field.new_features_block.inactive + ' - ' + __("%sRead more%s","really-simple-ssl")} url={field.new_features_block.readmore}/> }
            </div>
        </div>
    );
}
export default SecurityFeatureBullet;