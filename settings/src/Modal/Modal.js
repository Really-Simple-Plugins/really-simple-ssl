import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import Icon from "../utils/Icon";
import useModal from "./ModalData";
import {useState} from '@wordpress/element';

const Modal = (props) => {
    const {handleModal, modalData, setModalData, showModal, setIgnoredItemId, setFixedItemId, item} = useModal();
    const [buttonsDisabled, setButtonsDisabled] = useState(false);

    const dismissModal = () => {
        handleModal(false, null, null);
    }

    const handleFix = (e, type) => {
        //set to disabled
        let action = modalData.action;
        setButtonsDisabled(true);
        rsssl_api.runTest(action, 'refresh', modalData ).then( ( response ) => {
            let data = {...modalData};
            data.description = response.msg;
            data.subtitle = '';
            setModalData(data);
            setButtonsDisabled(false);
            if (response.success) {
                if (type==='ignore' && item !==false ) {
                    setIgnoredItemId(item.id);
                } else {
                    setFixedItemId(item.id);
                }
                handleModal(false, null);
            }
        });
    }

    if (!showModal) {
        return (<></>);
    }

    let disabled = buttonsDisabled ? 'disabled' : '';
    let description = modalData.description;
    if ( !Array.isArray(description) ) {
        description = [description];
    }

    return (
        <div>
            <div className="rsssl-modal-backdrop" onClick={ (e) => dismissModal(e) }>&nbsp;</div>
            <div className="rsssl-modal" id="{id}">
                <div className="rsssl-modal-header">
                    <h2 className="modal-title">
                        {modalData.title}
                    </h2>
                    <button type="button" className="rsssl-modal-close" data-dismiss="modal" aria-label="Close" onClick={ (e) => dismissModal(e) }>
                        <Icon name='times' />
                    </button>
                </div>
                <div className="rsssl-modal-content">
                    { modalData.subtitle && <div className="rsssl-modal-subtitle">{modalData.subtitle}</div>}
                    { Array.isArray(description) && description.map((s, i) => <div key={"modalDescription-"+i} className="rsssl-modal-description">{s}</div>) }
                </div>
                <div className="rsssl-modal-footer">
                    { modalData.edit && <a href={modalData.edit} target="_blank" rel="noopener noreferrer" className="button button-secondary">{__("Edit", "really-simple-ssl")}</a>}
                    { modalData.help && <a href={modalData.help} target="_blank" rel="noopener noreferrer" className="button rsssl-button-help">{__("Help", "really-simple-ssl")}</a>}
                    { (!modalData.ignored && modalData.action==='ignore_url') && <button disabled={disabled} className="button button-primary" onClick={ (e) => handleFix(e, 'ignore') }>{ __("Ignore", "really-simple-ssl")}</button>}
                    { modalData.action!=='ignore_url' &&  <button disabled={disabled} className="button button-primary" onClick={ (e) => handleFix(e, 'fix') }>{__("Fix", "really-simple-ssl")}</button> }
                </div>
            </div>
        </div>
    )
}

export default Modal;