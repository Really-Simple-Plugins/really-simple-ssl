/** @jsx wp.element.createElement */
import { Modal, Button } from "@wordpress/components";
import {useEffect, useState} from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import './RssslModal.scss';
import ErrorBoundary from "../../../../settings/src/utils/ErrorBoundary";
// import useLicense from "../../../../settings/src/Settings/License/LicenseData";

const RssslModal = ({title, subTitle, currentStep, buttons, content, list, confirmAction, confirmText, alternativeAction, alternativeText, alternativeClassName, isOpen, setOpen, className}) => {
    const [Icon, setIcon] = useState(null);
    // const {licenseStatus} = useLicense();

    let pluginUrl = typeof rsssl_modal !== 'undefined' ? rsssl_modal.plugin_url : rsssl_settings.plugin_url;
    alternativeClassName = alternativeClassName ? alternativeClassName : 'rsssl-warning';
    useEffect( () => {
        if (!Icon) {
            import ("../../../../settings/src/utils/Icon").then(({default: Icon}) => {
                setIcon(() => Icon);
            });
        }
    })

    const handleLicenseClick = () => {
        setOpen(false);
    };

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
                                {subTitle && (
                                    <p
                                        dangerouslySetInnerHTML={{
                                            __html: subTitle
                                        }}
                                    />
                                )}
                                {content && <>{content}</>}
                                {list && Icon && <ul>
                                    {list.map((item, index) => <li key={index}><Icon name={item.icon} color={item.color}/>{item.text}</li>)}
                                </ul>}
                            </div>
                            <div className="rsssl-modal-footer">
                                <div className="rsssl-modal-footer-image">
                                    <img className="rsssl-logo" src={pluginUrl+"assets/img/really-simple-security-logo.svg"} alt="Really Simple Security" />
                                </div>
                                <div className="rsssl-modal-footer-buttons">
                                    {/*# If is last step of onboarding (pro), and license not valid*/}
                                    {/*{ rsssl_settings.pro_plugin_active && licenseStatus !== 'valid' && currentStep.id === 'pro' && (*/}
                                    {/*    <p className={"rsssl-activate-license-text"}>*/}
                                    {/*        {__("Please", "really-simple-ssl")  + " "}*/}
                                    {/*        <a href="#settings/license" onClick={handleLicenseClick}>*/}
                                    {/*            {__('activate your license key', 'really-simple-ssl')}*/}
                                    {/*        </a>*/}
                                    {/*        {" " + __("to enable Pro features", "really-simple-ssl")}*/}
                                    {/*    </p>*/}
                                    {/*)}*/}
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