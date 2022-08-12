import {
    Component,
} from '@wordpress/element';

import Placeholder from './Placeholder';

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
                <div className="rssl-new-feature-label">{field.label}</div>
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

        if ( this.props.fields && this.props.fields.length==0 ) {
            return (
                <Placeholder></Placeholder>
            );
        }

        let fields = this.props.fields;
        fields = fields.filter( field => field.new_features_block===true );
        return (
            <div>
                {fields.map((field, i) => <SecurityFeatureBullet key={i} index={i} field={field} fields={fields}/>)}
            </div>
        );
    }
}
export default SecurityFeaturesBlock;