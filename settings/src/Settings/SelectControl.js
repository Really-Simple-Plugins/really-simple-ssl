import DOMPurify from "dompurify";
import {useEffect, useRef} from '@wordpress/element';
import hoverTooltip from '../utils/hoverTooltip';

const SelectControl = (props) => {

    const selectRef = useRef(null);

    const disabledPropIsArray = Array.isArray(props.disabled);
    let disabledOptionsArray = (disabledPropIsArray ? props.disabled : false);
    let disabledSelectPropBoolean = (disabledPropIsArray === false && props.disabled);
    let disabledSelectViaFieldConfig = (props.field.disabled === true);

    let selectDisabled = (
        disabledSelectViaFieldConfig
        || disabledSelectPropBoolean
    );

    let tooltipText = '';
    let emptyValues = [undefined, null, ''];

    if (selectDisabled
        && props.field.hasOwnProperty('disabledTooltipHoverText')
        && !emptyValues.includes(props.field.disabledTooltipHoverText)
    ) {
        tooltipText = props.field.disabledTooltipHoverText;
    }

    hoverTooltip(
        selectRef,
        (selectDisabled && (tooltipText !== '')),
        tooltipText
    );

    // Add effect to disable the select element when the selectDisabled state changes
    useEffect(() => {
        if (selectRef.current) {
            selectRef.current.disabled = selectDisabled;
        }
    }, [disabledSelectViaFieldConfig, selectDisabled]);

    return (
        <>
            <div className="components-base-control">
                <div className="components-base-control__field">
                    <div data-wp-component="HStack" className="components-flex components-select-control">
                        <label htmlFor={props.field.id} className="components-toggle-control__label"
                               style={props.style && props.style.label ? props.style.label : undefined}>{props.label}</label>
                        <select
                            ref={selectRef}
                            disabled={selectDisabled}
                            value={props.value}
                            onChange={(e) => props.onChangeHandler(e.target.value)}
                            style={props.style && props.style.select ? props.style.select : undefined}
                        >
                            {props.options.map((option, i) => (
                                <option
                                    key={'option-' + i}
                                    value={option.value}
                                    disabled={disabledOptionsArray && disabledOptionsArray.includes(option.value)}
                                >
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>
            {props.field.comment && (
                <div className="rsssl-comment" dangerouslySetInnerHTML={{__html: DOMPurify.sanitize(props.field.comment) }} ></div>
                /* nosemgrep: react-dangerouslysetinnerhtml */
            )}
        </>
    );
}

export default SelectControl;