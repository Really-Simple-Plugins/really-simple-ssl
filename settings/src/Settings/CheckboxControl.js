import Icon from "../utils/Icon";
import Tooltip from '@mui/material/Tooltip';

/*
* The tooltip can't be included in the native toggleControl, so we have to build our own.
*/

const CheckboxControl = (props) => {
    const onChangeHandler = (e) => {
        let fieldValue = !props.field.value;
        props.onChangeHandler(fieldValue)
    }

    let field = props.field;
    let is_checked = field.value ? 'is-checked' : '';
    let tooltipColor = field.warning ? 'red': 'black';
    return (
        <>
            <div className="components-base-control components-toggle-control">
                <div className="components-base-control__field">
                    <div data-wp-component="HStack" className="components-flex components-h-stack">
                        <span className={ "components-form-toggle "+is_checked}>
                            <input
                            checked={field.value}
                            className="components-form-toggle__input"
                            onChange={ ( e ) => onChangeHandler(e) }
                            id={field.id}
                            type="checkbox"
                        />
                        <span className="components-form-toggle__track"></span>
                        <span className="components-form-toggle__thumb"></span>
                        </span>
                        {field.tooltip &&
                            <div className="rsssl-tooltip">
                                <Tooltip arrow title={field.tooltip} enterDelay={200}>
                                    <div><Icon name = "info-open" color = {tooltipColor} /></div>
                                </Tooltip>
                                <div></div>
                            </div>
                        }
                        <label htmlFor={field.id} className="components-toggle-control__label">{field.label}</label>
                    </div>
                </div>
            </div>
        </>
    );
}
export default CheckboxControl