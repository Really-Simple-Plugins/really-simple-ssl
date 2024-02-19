import React, {useEffect, useState} from 'react';
import {
    Modal,
    MenuItem,
    SelectControl,
    Button,
    __experimentalConfirmDialog as ConfirmDialog
} from "@wordpress/components";
import {__} from "@wordpress/i18n";
import IpAddressInput from "../LimitLoginAttempts/IpAddressInput";
import FieldsData from "../FieldsData";
import WhiteListTableStore from "./WhiteListTableStore";

const TrustIpAddressModal = (props) => {
    const { inputRangeValidated, ipAddress, setIpAddress, maskError, dataLoaded, updateRow, resetRange} = WhiteListTableStore();
    const [rangeDisplay, setRangeDisplay] = useState(false);
    const [resetFlag, setResetFlag] = useState(false);
    const {showSavedSettingsNotice} = FieldsData();

    //we add a function to handle the range fill
    const handleRangeFill = () => {
        //we toggle the range display.
        setRangeDisplay(!rangeDisplay);
    }

    async function handleSubmit() {
        let status = props.status;
        // we check if statusSelected is not empty
        if (ipAddress && maskError === false) {
            await updateRow(ipAddress, status, props.dataActions).then((response) => {
                if (response.success) {
                    showSavedSettingsNotice(response.message);
                } else {
                    showSavedSettingsNotice(response.message, 'error');
                }
            });
            //we clear the input
            resetRange();
            //we close the modal
            props.onRequestClose();
        }
    }

    function handleCancel() {
        // Reset all local state
        setRangeDisplay(false);
        resetRange();

        // Close the modal
        props.onRequestClose();
    }
    if (!props.isOpen) {
        return null;
    }
    return (
        <Modal
            title={__("Add IP Address", "really-simple-ssl")}
            shouldCloseOnClickOutside={true}
            shouldCloseOnEsc={true}
            overlayClassName="rsssl-modal-overlay"
            className="rsssl-modal"
            onRequestClose={props.onRequestClose}
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
                        <div>
                            <IpAddressInput
                                label={__("IP Address", "really-simple-ssl")}
                                id="ip-address"
                                name="ip-address"
                                showSwitch={true}
                                value={ipAddress}
                                onChange={(e) => setIpAddress(e.target.value)}
                            />
                            <input
                                name={'rsssl_note_geo_trust_ip'}
                                type={'text'}
                                placeholder={__('Note <optional>', 'really-simple-ssl')}
                                style={{
                                    width: '100%',
                                    marginTop: '1em',
                                    padding: '0.25em',
                                }}
                            />
                        </div>
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
                            onClick={handleCancel}
                            style={{ marginRight: '10px' }}

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

export default TrustIpAddressModal;