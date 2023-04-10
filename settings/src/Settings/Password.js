import useFields from "./FieldsData";

const Password = (props) => {
    const {updateField, setChangedField} = useFields();

    const onChangeHandler = (fieldValue) => {
        updateField( props.field.id, fieldValue );
        setChangedField( props.field.id, fieldValue );
    }

    /**
     * There is no "PasswordControl" in WordPress react yet, so we create our own license field.
     */
    return (
        <div className="components-base-control">
         <div className="components-base-control__field">
             <label
                 className="components-base-control__label"
                 htmlFor={props.field.id}>{props.field.label}</label>
             <input className="components-text-control__input"
                    type="password"
                    id={props.field.id}
                    value={props.field.value}
                    onChange={ ( e ) => onChangeHandler(e.target.value) }
             />
         </div>
        </div>
    );
}

export default Password;