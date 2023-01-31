const Password = (props) => {
    const onChangeHandler = (fieldValue) => {
        props.fields[props.index]['value'] = fieldValue;
        props.saveChangedFields( props.field.id )
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