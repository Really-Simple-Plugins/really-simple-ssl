/** @jsx wp.element.createElement */
import { Modal, Button } from "@wordpress/components";
import {useEffect, useState} from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import './RssslModal.scss';

const RssslModal = ({title, content, list, confirmAction, confirmText, alternativeAction, alternativeText, alternativeClassName, isOpen, setOpen}) => {
    const [Icon, setIcon] = useState(null);

    alternativeClassName = alternativeClassName ? alternativeClassName : 'rsssl-warning';
    useEffect( () => {
        if (!Icon) {
            import ("../../../../settings/src/utils/Icon").then(({default: Icon}) => {
                setIcon(() => Icon);
            });
        }
    }, []);
    return (
        <>
            {isOpen && (
                <>
                    <Modal
                        className="rsssl-modal"
                        title={title}
                        onRequestClose={() => setOpen(false)}
                        open={isOpen}>
                        <div className="rsssl-modal-body">
                            {content && <p>{content}</p>}
                            {list && Icon && <ul>
                                {list.map((item, index) => <li key={index}><Icon name="circle-times" color="red"/>{item}</li>)}
                            </ul>}
                        </div>
                        <div className="rsssl-modal-footer">
                            <div>
                                <img className="rsssl-logo" src={rsssl_modal.plugin_url+"assets/img/really-simple-ssl-logo.svg"} alt="Really Simple SSL" />
                            </div>
                            <div>
                                <Button className='rsssl-modal-cancel' onClick={() => setOpen(false)}>{__("Cancel", "really-simple-ssl")}</Button>
                                {alternativeText && <Button className={alternativeClassName} onClick={() => alternativeAction()}>{alternativeText}</Button>}
                                {confirmText && <Button isPrimary onClick={()=> confirmAction() }>{confirmText}</Button>}
                            </div>

                        </div>
                    </Modal>
                </>


        )}
        </>
    );
}

export default RssslModal;