import {
    SelectControl,
} from '@wordpress/components';
import * as rsssl_api from "../utils/api";
import {useRef} from "@wordpress/element";

const Host = (props) => {
    const disabled = useRef(false);

    const onChangeHandler = (fieldValue) => {
        let fields = props.fields;
        let field = props.field;
        field.value = fieldValue;
        fields[props.index]['value'] = fieldValue;

        //force update, and get new fields.
        disabled.current = true;
        let saveFields = [];
        props.handleNextButtonDisabled(true);
        saveFields.push(field);
        rsssl_api.setFields(saveFields).then(( response ) => {
            props.updateFields(response.fields);
            disabled.current = false;
            props.handleNextButtonDisabled(false);
        });
    }

    let fieldValue = props.field.value;
    let field = props.field;
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
              onChange={ ( fieldValue ) => onChangeHandler(fieldValue) }
              value= { fieldValue }
              options={ options }
              disabled={disabled.current}
          />
    )
}
export default Host;