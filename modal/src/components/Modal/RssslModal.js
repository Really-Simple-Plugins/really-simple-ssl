/** @jsx wp.element.createElement */
const { Modal, Button } = wp.components;
const { useState, useEffect } = wp.element;
const { __ } = wp.i18n;
import './RssslModal.scss';

const RssslModal = ({title, content, cancelBtnTxt, confirmBtnTxt, onConfirm, isOpen, setOpen}) => {
    const handleOpen = () => {

    }

    return (
        <>
            {isOpen && (
                    <div className="rsssl-modal">
                        <Modal
                            title={title}
                            onRequestClose={() => setOpen(false)}
                            open={isOpen}>
                            <div className="rsssl-modal-body">
                                <p>My Modal Content</p>
                            </div>
                            <div className="rsssl-modal-footer">
                                <div>
                                    <img className="rsssl-logo" src={rsssl_modal.plugin_url+"assets/img/really-simple-ssl-logo.svg"} alt="Really Simple SSL" />
                                </div>
                                <div>
                                    <Button isPrimary onClick={() => setOpen(false)}>{__("Cancel", "really-simple-ssl")}</Button>
                                </div>

                            </div>
                        </Modal>
                    </div>


        )}
            </>
    );
}

export default RssslModal;