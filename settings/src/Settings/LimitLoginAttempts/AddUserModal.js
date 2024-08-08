import { useState} from '@wordpress/element';
import {Modal, Button} from "@wordpress/components";
import UserDataTableStore from "./UserDataTableStore";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import {__} from "@wordpress/i18n";
import FieldsData from "../FieldsData";


const AddUserModal = (props) => {
    if (!props.isOpen) return null;

    const {updateRow} = UserDataTableStore();
    const {fetchDynamicData} = EventLogDataTableStore();
    const [user, setUser] = useState('');
    const {showSavedSettingsNotice} = FieldsData();

    async function handleSubmit() {
        let status = props.status;
        // we check if statusSelected is not empty
        if (user !== '') {
            await updateRow(user, status, props.dataActions).then((response) => {
               if(response.success) {
                   showSavedSettingsNotice(response.message);
               } else {
                     showSavedSettingsNotice(response.message, 'error');
               }
            });
            //we clear the input
            setUser('');
            await fetchDynamicData('event_log');
        }
        props.onRequestClose();
    }

    return (
        <Modal
            title={__("Add User", "really-simple-ssl")}
            shouldCloseOnClickOutside={true}
            shouldCloseOnEsc={true}
            overlayClassName="rsssl-modal-overlay"
            className="rsssl-modal"
            onRequestClose={props.onRequestClose}
        >
            <div className="modal-content">
                <div className="modal-body"
                     style={{
                         padding: "1em",
                     }}
                >
                    <div
                        style={{
                            width: "95%",
                            height: "100%",
                            padding: "10px",
                        }}
                    >
                        <p>
                            <label htmlFor="username"
                                   className="rsssl-label"
                            >{__("Username", "really-simple-ssl")}</label>
                            <input
                                type="text"
                                className="rsssl-input full"
                                id="username"
                                name="username"
                                onChange={(e) => setUser(e.target.value)}
                            />
                        </p>
                    </div>
                </div>
                <div className="modal-footer">
                    {/*//we add two buttons here for add row and cancel*/}
                    <div
                        className={'rsssl-grid-item-footer'}
                        style
                            ={{
                            display: 'flex',
                            justifyContent: 'flex-end',
                            alignItems: 'center',
                            padding: '1em',
                        }
                        }
                    >
                        <Button
                            isSecondary
                            onClick={props.onRequestClose}
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

export default AddUserModal;