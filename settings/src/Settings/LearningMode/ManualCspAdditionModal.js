import {useEffect, useMemo, useRef} from '@wordpress/element';
import {Modal, Button} from "@wordpress/components";
import SelectControl from "../SelectControl.js"
import {__} from "@wordpress/i18n";
import FieldsData from "../FieldsData";
import ManualCspAddition from "./ManualCspAddition";
import UseLearningMode from "./LearningModeData";

const ManualCspAdditionModal = (props) => {

    const cspUriRef = useRef(null);
    const {manualAdditionProcessing, directive, setCspUri, setDirective, addManualCspEntry} = ManualCspAddition();
    const {fetchLearningModeData} = UseLearningMode();
    const {showSavedSettingsNotice} = FieldsData();
    const directiveOptions = useMemo(() => getModalSelectOptions(), []);

    async function submitManualCspAddition() {
        if (!cspUriRef.current || !cspUriRef.current.value) {
            return showSavedSettingsNotice(__('Something went wrong while saving the manual CSP entry.'), 'error');
        }

        if (!cspUriRef.current.value.length || !directive.length) {
            return showSavedSettingsNotice(__('Please enter both the "URI" and the "Directive" before saving.'), 'error');
        }

        await addManualCspEntry(cspUriRef.current.value, directive).then((response) => {
            if (response.success === false) {
                return showSavedSettingsNotice(response.message, 'error');
            }

            showSavedSettingsNotice(response.message);

            // Re-render table to show new addition
            fetchLearningModeData(props.parentId);

            clearManualCspAdditionModalFields();
            return closeManualCspAdditionModal();
        });
    }

    function clearManualCspAdditionModalFields() {
        setCspUri('');
        setDirective('');

        if (cspUriRef.current) {
            cspUriRef.current.value = '';
        }
    }

    /**
     * Map the value of the directives passed via the props to the value and
     * label of the options in the dropdown. We do this because the label in
     * RSSSL()->headers->directives contains excessive information
     *
     * @returns {{value: *, label: *}[]}
     */
    function getModalSelectOptions()
    {
        return Object.keys(props.directives).map(key => ({
            value: props.directives[key],
            label: props.directives[key]
        }));
    }

    /**
     * Method should be called when a user clicked the cancel button
     */
    function handleCancel() {
        clearManualCspAdditionModalFields();
        closeManualCspAdditionModal();
    }

    /**
     * Method can be used to submit the manual CSP entry with the "Enter" key
     */
    function submitWithEnter(event) {
        if (event.key === 'Enter') {
            submitManualCspAddition();
        }
    }

    /**
     * When modal is used the closing-callback for the modal can be bind to a
     * method by using the "onRequestClose" property.
     * @see settings/src/Settings/LearningMode/LearningMode.js
     */
    function closeManualCspAdditionModal()
    {
        props.onRequestClose();
    }

    /**
     * Set default value to the first value in props.directives.
     */
    useEffect(() => {
        if (!directive) {
            setDirective(props.directives[0]);
        }
    }, []);

    /**
     * Don't render when the modal isn't open.
     */
    if (!props.isOpen) {
        return null;
    }

    return (
        <Modal
            title={__("Add Entry", "really-simple-ssl")}
            shouldCloseOnClickOutside={true}
            shouldCloseOnEsc={true}
            overlayClassName="rsssl-modal-overlay"
            className="rsssl-modal"
            onRequestClose={closeManualCspAdditionModal}
        >
            <div className="modal-content">
                <div className="modal-body"
                     style={{
                         padding: "0.5em",
                     }}
                >
                    <div
                        style={{
                            width: "95%",
                            height: "100%",
                            padding: "10px",
                        }}
                    >
                        <div style={{position: 'relative'}}>
                            <label
                                htmlFor={'cspUri'}
                                className={'rsssl-label'}
                            >{__('URI', 'really-simple-ssl')}</label>
                            <input
                                id={'cspUri'}
                                type={'text'}
                                name={'cspUri'}
                                ref={cspUriRef}
                                onKeyDown={submitWithEnter}
                                style={{
                                    width: '100%',
                                }}
                                disabled={manualAdditionProcessing}
                            />
                        </div>
                        <div style={{marginTop: '10px'}}>
                            <SelectControl
                                field={
                                    {
                                        // workaround for working with SelectControl
                                        id: 'directive',
                                    }
                                }
                                id={'directive'}
                                label={__('Directive', 'really-simple-ssl')}
                                name={'directive'}
                                value={directive}
                                onChangeHandler={(value) => setDirective(value)}
                                options={directiveOptions}
                                style={{
                                    label: {
                                        display: 'block',
                                    },
                                    select: {
                                        maxWidth: 'unset',
                                        width: '100%',
                                    }
                                }}
                                disabled={manualAdditionProcessing}
                            />
                        </div>
                    </div>
                </div>
                <div className="modal-footer">
                    <div
                        className={'rsssl-grid-item-footer'}
                        style={{
                            display: 'flex',
                            justifyContent: 'flex-end',
                            alignItems: 'center',
                            padding: '1em',
                        }}
                    >
                        <Button
                            isSecondary
                            onClick={handleCancel}
                            style={{marginRight: '10px'}}
                        >
                            {__("Cancel", "really-simple-ssl")}
                        </Button>
                        <Button
                            isPrimary
                            onClick={submitManualCspAddition}
                        >
                            {__("Add", "really-simple-ssl")}
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    )
}

export default ManualCspAdditionModal;