import {
    SelectControl,
} from '@wordpress/components';
import {useRef} from "@wordpress/element";
import useFields from "./FieldsData";

const Host = (props) => {
    const {updateField, setChangedField, saveFields, handleNextButtonDisabled} = useFields();
    const disabled = useRef(false);

    const onChangeHandler = async (fieldValue) => {
        let field = props.field;
        //force update, and get new fields.
        handleNextButtonDisabled(true);
        disabled.current = true;
        updateField(field.id, fieldValue);
        setChangedField(field.id, fieldValue);

        await saveFields(true, false);

        handleNextButtonDisabled(false);
        disabled.current = false;
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