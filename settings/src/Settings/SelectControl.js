/*
* The native selectControl doesn't allow disabling per option.
*/

const SelectControl = (props) => {
    let field = props.field;
    let selectDisabled = !Array.isArray(props.disabled) && props.disabled;
    let optionsDisabled = Array.isArray(props.disabled) ? props.disabled : false;
    return (
        <>
            <div className="components-base-control">
                <div className="components-base-control__field">
                    <div data-wp-component="HStack" className="components-flex components-select-control">
                        <label htmlFor={field.id} className="components-toggle-control__label">{props.label}</label>
                        <select disabled={selectDisabled} value={props.value} onChange={(e) => props.onChangeHandler(e.target.value)}>
                            {props.options.map((option,i) => <option key={i} value={option.value} disabled={optionsDisabled && optionsDisabled.includes(option.value)}>{option.label}</option>) }
                        </select>
                    </div>
                </div>
            </div>
            {field.comment && <div className="rsssl-comment" dangerouslySetInnerHTML={{__html:field.comment}}></div>}
        </>
    );
}
export default SelectControl