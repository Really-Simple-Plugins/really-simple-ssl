import {useState, useEffect} from "@wordpress/element";
import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "./utils/api";

const MultiStepModal = () => {
    const [show, setShow] = useState(true);
    const [steps, setSteps] = useState([]);
    const [overrideSSL, setOverrideSSL] = useState(false);
    const [activateSSL, setActivateSSL] = useState(true);

    useEffect(() => {
        const showModal = localStorage.getItem("showModal");
        const modalShow = showModal ? (showModal === 'true') : true;
        setShow(modalShow)

        if(modalShow) {
            rsssl_api.getOnboarding().then( ( response ) => {
                setSteps(response.data.steps)
            });
        }
    }, [])

    const dismissModal = () => {
        localStorage.setItem("showModal", false);
        setShow(!show)
    }

    const parseStepItems = (items) => {
        return items.map((item, index) => {
            const { title, status, help } = item
            const statuses = {
                'warning': 'rsssl-warning',
                'error': 'rsssl-error',
                'active': 'rsssl-success'
            };
            return (
                <li key={index} className={statuses[status]}>{title}</li>
            )
        })
    }

    const parseStepButtons = (buttons) => {
        return buttons.map((button) => {
            const {title, variant, disabled, type, href} = button;
            const buttonTypes = {
                'button': <Button variant={variant} disabled={activateSSL}>{title}</Button>,
                'link': <Button variant={variant} href={href} disabled={disabled} isLink={true}>{title}</Button>,
                'checkbox': <ToggleControl
                    label={title}
                    disabled={disabled}
                    checked={overrideSSL}
                    onChange={(value) => {
                        setActivateSSL(!value)
                        setOverrideSSL(value)

                        rsssl_api.overrideSSLDetection(value).then((response) => {
                            console.log(response)
                        });
                    }}
                />
            };

            return buttonTypes[type];
        })
    }

    const renderSteps = () => {
        return (
            <>
                {
                    steps.map((step, index) => {
                        const {title, subtitle, items, info_text: infoText, buttons} = step;
                        return (
                            <div className="rsssl-modal-content-step" key={index}>
                                {title && <div className="rsssl-modal-subtitle">{title}</div>}
                                {subtitle && <div className="rsssl-modal-description">{subtitle}</div>}
                                <ul>
                                    { parseStepItems(items) }
                                </ul>
                                { infoText && <div className="rsssl-modal-description" dangerouslySetInnerHTML={{__html: infoText}} /> }


                                <div className="rsssl-modal-content-step-footer">
                                    {parseStepButtons(buttons)}
                                </div>
                            </div>
                        )
                    })
                }
            </>
        )
    }

    return (
        <>
            { (steps.length > 0 && show) && <>
                <div className="rsssl-modal-backdrop" onClick={ dismissModal }>&nbsp;</div>
                <div className="rsssl-modal">
                    <div className="rsssl-modal-header">
                        <h2 className="modal-title">
                            Really Simple SSL
                        </h2>
                        <button type="button" className="rsssl-modal-close" data-dismiss="modal" aria-label="Close" onClick={ dismissModal }>
                            <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" height="24" >
                                <path fill="#000000" d="M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"/>
                            </svg>
                        </button>
                    </div>

                    <div className="rsssl-modal-content" id="rsssl-message">
                        {renderSteps()}
                    </div>

                    <div className="rssl-modal-footer"/>
                </div>
            </> }
        </>
    )
}

export default MultiStepModal;