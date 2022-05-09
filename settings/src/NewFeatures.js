import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';

import {
    Placeholder,
} from '@wordpress/components';
import * as rsssl_api from "./utils/api";
import Field from "./fields";

class NewFeatures extends Component {
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
        console.log("fields in new features");
        fields = fields.filter( field => field.new_features_block===true );
        console.log( fields);

        return (
            <div>
                NEW FEATURES
                {/*{fields.map((field, i) => <Field key={i} index={i} field={field} fields={this.props.fields}/>)}*/}
                {fields.map((field, i) => <Field key={i} index={i} highLightField="" highLightedField={this.props.highLightField} saveChangedFields={this.props.saveChangedFields} field={field} fields={fields}/>)}

            </div>
        );
    }
}
export default NewFeatures;