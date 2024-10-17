import {useState, useEffect, useRef} from '@wordpress/element';
import Icon from "../../utils/Icon";
import {
    Modal,
    Button,
    TextControl
} from "@wordpress/components";
import {__} from "@wordpress/i18n";
import FieldsData from "../FieldsData";
import UserAgentStore from "./UserAgentStore";

const UserAgentModal = (props) => {
    const {note, setNote, user_agent, setUserAgent, dataLoaded, addRow, fetchData} = UserAgentStore();
    const {showSavedSettingsNotice} = FieldsData();
    const userAgentInputRef = useRef(null);
    const noteInputRef = useRef(null);

    async function handleSubmit() {
        // we check if statusSelected is not empty
        if (user_agent.length) {
            await addRow(user_agent, note).then((response) => {
                console.log(response);
                if (response.success) {
                    showSavedSettingsNotice(response.message);
                    fetchData('rsssl_user_agent_list');
                } else {
                    showSavedSettingsNotice(response.message, 'error');
                }
            });
            //we clear the input
            resetValues();
            //we close the modal
            props.onRequestClose();
        }
    }

    function resetValues() {
        setUserAgent('');
        setNote('');
    }

    function handleCancel() {
        resetValues();
        // Close the modal
        props.onRequestClose();
    }

    function handleKeyPress(event) {
        console.log('i pressed a key' + event.key);
        if (event.key === 'Enter') {
            handleSubmit();
        }
    }

    useEffect(() => {
        if (userAgentInputRef.current) {
            userAgentInputRef.current.addEventListener('keypress', handleKeyPress);
        }
        if (noteInputRef.current) {
            noteInputRef.current.addEventListener('keypress', handleKeyPress);
        }

        // cleanup event listeners
        return () => {
            if (userAgentInputRef.current) {
                userAgentInputRef.current.removeEventListener('keypress', handleKeyPress);
            }
            if (noteInputRef.current) {
                noteInputRef.current.removeEventListener('keypress', handleKeyPress);
            }
        }
    }, []);

    if (!props.isOpen) {
        return null;
    }

    return (
        <Modal
            title={__("Block User-Agent", "really-simple-ssl")}
            shouldCloseOnClickOutside={true}
            shouldCloseOnEsc={true}
            overlayClassName="rsssl-modal-overlay"
            className="rsssl-modal"
            onRequestClose={props.onRequestClose}
            onKeyPress={handleKeyPress}
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
                                htmlFor={'user_agent'}
                                className={'rsssl-label'}
                            >{__('User-Agent', 'really-simple-ssl')}</label>
                            <input
                                id={'user_agent'}
                                type={'text'}
                                value={user_agent}
                                name={'user_agent'}
                                onChange={(e) => setUserAgent(e.target.value)}
                                style={{
                                    width: '100%',
                                }}
                                ref={userAgentInputRef}
                            />
                        </div>
                        <div>
                            <label
                                htmlFor={'note'}
                                className={'rsssl-label'}
                            >{__('Notes', 'really-simple-ssl')}</label>
                            <input
                                name={'note'}
                                id={'note'}
                                type={'text'}
                                value={note}
                                onChange={(e) => setNote(e.target.value)}
                                style={{
                                    width: '100%',
                                }}
                                ref={noteInputRef}
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
                            onClick={handleSubmit}
                        >
                            {__("Add", "really-simple-ssl")}
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    )
}

export default UserAgentModal;