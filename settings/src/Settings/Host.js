import {
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import {
    Component,
} from '@wordpress/element';


class Host extends Component {
    constructor() {
        super( ...arguments );
        this.disabled = false
    }

    onChangeHandler(fieldValue) {
        let fields = this.props.fields;
        let field = this.props.field;
        field.value = fieldValue;
        fields[this.props.index]['value'] = fieldValue;

        //force update, and get new fields.
        this.disabled = true;
        let saveFields = [];
        this.props.handleNextButtonDisabled(true);
        saveFields.push(field);
        rsssl_api.setFields(saveFields).then(( response ) => {
            this.props.updateFields(response.data.fields);
            this.disabled = false;
            this.props.handleNextButtonDisabled(false);
        });
    }


    render(){
        let fieldValue = this.props.field.value;
        let field = this.props.field;
        let options = [];
        if ( field.options ) {
            for (var key in field.options) {
                if (field.options.hasOwnProperty(key)) {
                    let item = {};
                    item.label = field.options[key];
                    item.value = key;
                    options.push(item);
                }
            }
        }
        return (
              <SelectControl
                  label={ field.label }
                  onChange={ ( fieldValue ) => this.onChangeHandler(fieldValue) }
                  value= { fieldValue }
                  options={ options }
                  disabled={this.disabled}
              />
        )


    }
}

export default Host;