/** @jsx wp.element.createElement */
import { Modal, Button } from "@wordpress/components";
import {useEffect, useState} from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import './RssslModal.scss';
import ErrorBoundary from "../../../../settings/src/utils/ErrorBoundary";

const RssslModal = ({
                        title,
                        subTitle,
                        buttons,
                        content,
                        list,
                        confirmAction,
                        confirmText,
                        alternativeAction,
                        alternativeText,
                        alternativeClassName,
                        isOpen,
                        setOpen,
                        className,
                        footer,
                    }) => {
    const [Icon, setIcon] = useState(null);
    let pluginUrl = typeof rsssl_modal !== 'undefined' ? rsssl_modal.plugin_url : rsssl_settings.plugin_url;
    alternativeClassName = alternativeClassName ? alternativeClassName : 'rsssl-warning';
    useEffect( () => {
        if (!Icon) {
            import ("../../../../settings/src/utils/Icon").then(({default: Icon}) => {
                setIcon(() => Icon);
            });
        }


    }, []);
    let modalCustomClass = className ? ' '+className : ""
    return (
        <>
            {isOpen && (
                <>
                    <ErrorBoundary fallback={"Error loading modal"}>
                        <Modal
                        className={"rsssl-modal"+modalCustomClass}
                        shouldCloseOnClickOutside={false}
                        shouldCloseOnEsc={false}
                        title={title}
                        onRequestClose={() => setOpen(false)}
                        open={isOpen}>
                        <div className="rsssl-modal-body">
                            {subTitle && <p>{subTitle}</p>}
                            {content && <>{content}</>}
                            {list && Icon && <ul>
                                {list.map((item, index) => <li key={index}><Icon name={item.icon} color={item.color}/>{item.text}</li>)}
                            </ul>}
                        </div>
                        <div className="rsssl-modal-footer">
                            {!footer && <div className="rsssl-modal-footer-image">
                                <img className="rsssl-logo" src={pluginUrl+"assets/img/really-simple-ssl-logo.svg"} alt="Really Simple SSL" />
                            </div>}
                            { footer && <div className="rsssl-modal-footer-feedback">
                                {footer}
                            </div>}
                            <div className="rsssl-modal-footer-buttons">
                                <Button onClick={() => setOpen(false)}>{__("Cancel", "really-simple-ssl")}</Button>
                                { buttons && <>{buttons}</>}
                                { !buttons && <>
                                        {alternativeText && <Button className={alternativeClassName} onClick={() => alternativeAction()}>{alternativeText}</Button>}
                                        {confirmText && <Button isPrimary onClick={()=> confirmAction() }>{confirmText}</Button>}
                                    </>
                                }
                            </div>

                        </div>
                    </Modal>
                    </ErrorBoundary>
                </>


        )}
        </>
    );
}

export default RssslModal;