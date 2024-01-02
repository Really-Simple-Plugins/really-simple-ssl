import React, {useEffect, useState} from 'react';
import {
    Modal,
    MenuItem,
    SelectControl,
    Button,
    __experimentalConfirmDialog as ConfirmDialog
} from "@wordpress/components";
import IpAddressDataTableStore   from "./IpAddressDataTableStore";
import {__} from "@wordpress/i18n";
import IpAddressInput from "./IpAddressInput";
import Cidr from "./Cidr";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";

const AddIpAddressModal = (props) => {
    const { inputRangeValidated, fetchCidrData, ipAddress, setIpAddress, maskError, dataLoaded, addRow, resetRange} = IpAddressDataTableStore();
    const [rangeDisplay, setRangeDisplay] = useState(false);
    const {fetchDynamicData} = EventLogDataTableStore();
    const [resetFlag, setResetFlag] = useState(false);
    //we add a function to handle the range fill
    const handleRangeFill = () => {
        //we toggle the range displayß
        setRangeDisplay(!rangeDisplay);
    }

    useEffect(() => {
        //we validate the range
        if (inputRangeValidated) {
            //we get the mask
            fetchCidrData('get_mask_from_range')
        }
    }, [inputRangeValidated]);

    function handleSubmit() {
        let status = props.status;
        // we check if statusSelected is not empty
        if (ipAddress && maskError === false) {
            addRow(ipAddress, status, props.dataActions);
            //we clear the input
            resetRange();
            //we close the modal
            props.onRequestClose();
            //we fetch the data again
            fetchDynamicData('event_log')
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
                        <div>
                            <IpAddressInput
                                label={__("IP Address", "really-simple-ssl")}
                                id="ip-address"
                                name="ip-address"
                                showSwitch={true}
                                value={ipAddress}
                                onChange={(e) => setIpAddress(e.target.value)}
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

export default AddIpAddressModal;