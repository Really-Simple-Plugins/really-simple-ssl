/** @jsx wp.element.createElement */
const { Modal, Button } = wp.components;
const { useState, useEffect } = wp.element;
const { __ } = wp.i18n;
import './RssslModal.scss';

function RssslModal() {
    const [isOpen, setOpen] = useState(false);
    const handleOpen = () => {

    }

    useEffect(() => {
        const showModalListener = () => {
            setOpen(true);
        };

        document.addEventListener('showRssslModalEvent', showModalListener);

        // Cleanup the listener on component unmount
        return () => {
            document.removeEventListener('showRssslModalEvent', showModalListener);
        };
    }, [isOpen]); // Add isOpen as a dependency

    return (
        <>
            {isOpen && (
                    <Modal
                        className="rsssl-modal"
                        title={__("Are you sure?", "really-simple-ssl")}
                        onRequestClose={() => setOpen(false)}
                        open={handleOpen()}>
                        <div className="rsssl-modal-body">
                            <p>My Modal Content</p>
                        </div>
                        <div className="rsssl-modal-footer">
                            <img className="rsssl-logo" src={rsssl_modal.plugin_url+"assets/img/really-simple-ssl-logo.svg"} alt="Really Simple SSL" />

                            <Button isPrimary onClick={() => setOpen(false)}>{__("Cancel", "really-simple-ssl")}</Button>
                        </div>
                    </Modal>

        )}
            </>
    );
}

export default RssslModal;