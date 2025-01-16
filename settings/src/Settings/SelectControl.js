// src/components/SelectControl.js
import {useRef, useEffect} from '@wordpress/element';
import DOMPurify from "dompurify";
import hoverTooltip from "../utils/hoverTooltip";
import {__} from '@wordpress/i18n';

const SelectControl = (props) => {
    let field = props.field;
    // Track the original disabled state from PHP separately
    let originalDisabled = field.disabled;
    // Handle conditional disable state
    let conditionalDisabled = !Array.isArray(props.disabled) && props.disabled;
    // Combine them for the actual select disabled state
    let selectDisabled = originalDisabled || conditionalDisabled;

    let optionsDisabled = Array.isArray(props.disabled) ? props.disabled : false;

    const selectRef = useRef(null);
    const tooltipText = __("404 errors detected on your home page. 404 blocking is unavailable, to prevent blocking of legitimate visitors. It is strongly recommended to resolve these errors.", "really-simple-ssl");

    // Pass originalDisabled instead of selectDisabled - we want to show the tooltip
    // when the field is disabled due to the PHP condition
    hoverTooltip(selectRef, originalDisabled, tooltipText);

    // Add effect to disable the select element when the selectDisabled state changes
    useEffect(() => {
        if (selectRef.current) {
            selectRef.current.disabled = selectDisabled;
        }
    }, [field.disabled, selectDisabled]);

    return (
        <>
            <div className="components-base-control">
                <div className="components-base-control__field">
                    <div data-wp-component="HStack" className="components-flex components-select-control">
                        <label htmlFor={field.id} className="components-toggle-control__label">{props.label}</label>
                        <select
                            ref={selectRef}
                            className={field.id}
                            disabled={selectDisabled}
                            value={props.value}
                            onChange={(e) => props.onChangeHandler(e.target.value)}
                        >
                            {props.options.map((option, i) => (
                                <option
                                    key={'option-'+i}
                                    value={option.value}
                                    disabled={optionsDisabled && optionsDisabled.includes(option.value)}
                                >
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>
            {field.comment && (
                <div
                    className="rsssl-comment"
                    dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(field.comment) }}
                ></div>
            )}
        </>
    );
};

export default SelectControl;
