/*
* The tooltip can't be included in the native toggleControl, so we have to build our own.
*/
import { useState, useEffect } from "@wordpress/element";
const CheckboxControl = (props) => {
    const [ isOpen, setIsOpen ] = useState( false );
    const [ ConfirmDialog, setConfirmDialog ] = useState( false );
    useEffect( () => {
        if (!ConfirmDialog) {
            import ('@wordpress/components').then(({default: __experimentalConfirmDialog}) => {
                setConfirmDialog(() => __experimentalConfirmDialog);
            });
        }
    }, []);

    const onChangeHandler = (e) => {
        //wordpress <6.0 does not have the confirmdialog component
        if (!ConfirmDialog) {
            executeAction();
            return;
        }

        if (props.field.warning && props.field.warning.length>0 && !props.field.value) {
            setIsOpen( true );
        } else {
            executeAction();
        }
    }

    const handleConfirm = async () => {
        setIsOpen( false );
        executeAction();
    };

    const handleCancel = () => {
        setIsOpen( false );
    };

    const executeAction = (e) => {
        let fieldValue = !props.field.value;
        props.onChangeHandler(fieldValue)
    }
    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            onChangeHandler(true);
        }
    }
    let field = props.field;
    let is_checked = field.value ? 'is-checked' : '';
    let is_disabled = props.disabled ? 'is-disabled' : '';

    return (
        <>
            {ConfirmDialog && <ConfirmDialog
                isOpen={ isOpen }
                onConfirm={ handleConfirm }
                onCancel={ handleCancel }
            >
                {field.warning}
            </ConfirmDialog> }
            <div className="components-base-control components-toggle-control">
                <div className="components-base-control__field">
                    <div data-wp-component="HStack" className="components-flex components-h-stack">
                        <span className={ "components-form-toggle "+is_checked + ' ' +is_disabled}>
                            <input
                                onKeyDown={(e) => handleKeyDown(e)}
                                checked={field.value}
                                className="components-form-toggle__input"
                                onChange={ ( e ) => onChangeHandler(e) }
                                id={field.id}
                                type="checkbox"
                                disabled={props.disabled}
                            />
                        <span className="components-form-toggle__track"></span>
                        <span className="components-form-toggle__thumb"></span>
                        </span>
                        <label htmlFor={field.id} className="components-toggle-control__label">{props.label}</label>
                    </div>
                </div>
            </div>
        </>
    );
}
export default CheckboxControl