import { __ } from '@wordpress/i18n';
import useFields from "./FieldsData";
const ChangeStatus = (props) => {
    const {fields, updateField, getFieldValue, getField, setChangedField, highLightField, saveFields} = useFields();

    const onChangeHandler = (enabled, clickedItem, type ) => {
        let field=props.field;
        enabled = enabled==1 ? 0 : 1;
        if (typeof field.value === 'object') {
            field.value = Object.values(field.value);
        }
        //find this item in the field list
        for (const item of field.value){
            if (item.id === clickedItem.id) {
                item[type] = enabled;
            }
            delete item.valueControl;
            delete item.statusControl;
            delete item.deleteControl;
        }
        //the updateItemId allows us to update one specific item in a field set.
        field.updateItemId = clickedItem.id;

        setChangedField(field.id, field.value);
        updateField(field.id, field.value);
        saveFields(true, false);
    }

    let statusClass = props.item.status==1 ? 'button button-primary rsssl-status-allowed' : 'button button-default rsssl-status-revoked';
    let label = props.item.status==1 ? __("Revoke", "really-simple-ssl") : __("Allow", "really-simple-ssl");
    return (
        <button onClick={ () => onChangeHandler( props.item.status, props.item, 'status' ) } className={statusClass}>{label}</button>
    )
}
export default ChangeStatus