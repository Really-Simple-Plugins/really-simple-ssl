import {useState, useEffect} from "@wordpress/element";
import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "./utils/api";

const OnboardingModal = () => {
    const [show, setShow] = useState(false);
    const [steps, setSteps] = useState([]);
    const [overrideSSL, setOverrideSSL] = useState(false);
    const [activateSSLDisabled, setActivateSSLDisabled] = useState(true);

    useEffect(() => {
        rsssl_api.getOnboarding().then( ( response ) => {
            let steps = response.data.steps;
            steps[0].visible = true;
            setSteps(steps);
            setShow(!response.data.dismissed);
        });
    }, [])

    const dismissModal = () => {
        localStorage.setItem("showModal", false);
        setShow(!show)
    }

    const activateSSL = () => {
        let sslUrl = window.location.href.replace("http://", "https://");
        rsssl_api.activateSSL().then((response) => {
            steps[0].visible = false;
            steps[1].visible = true;
            setActivateSSLDisabled(true);
            //change url to https, after final check
            if (response.data.success) {
                window.location.href=url;
            }
            setSteps(steps);
        });
    }

    const itemButtonHandler = (status, pluginSlug) => {
//         if(status === "inactive") {
//             rsssl_api.installPlugin(pluginSlug).then((response) => {
//                 console.log(response)
//             }).catch((err) => { console.log(err) })
//         }
//
//         if(status === "warning") {
//             rsssl_api.activateRecommendedPlugin(pluginSlug).then((response) => {
//                 console.log(response)
//             })
//         }
    }

    const parseStepItems = (items) => {
        return items.map((item, index) => {
            const { title, status, help, button, plugin_slug: pluginSlug } = item
            const statuses = {
                'inactive': 'rsssl-inactive',
                'warning': 'rsssl-warning',
                'error': 'rsssl-error',
                'active': 'rsssl-success'
            };
            return (
                <li key={index} className={statuses[status]}>
                    {title} {button && <> - <Button isLink={true} onClick={() => itemButtonHandler(status, pluginSlug)}>{button.title}</Button></>}
                </li>
            )
        })
    }

    const parseStepButtons = (buttons) => {
        return buttons.map((button) => {
            const {title, variant, disabled, type, href, target, action} = button;
            const buttonTypes = {
                'button': <Button
                    variant={variant}
                    disabled={disabled && activateSSLDisabled}
                    onClick={() => {
                        if(action === "dismiss") {
                            dismissModal();
                        }
                        if(action === "activate_ssl") {
                            activateSSL();
                        }
                    }}>
                    {title}
                </Button>,
                'link': <Button variant={variant} href={href} disabled={disabled} isLink={true} target={target}>{title}</Button>,
                'checkbox': <ToggleControl
                    label={title}
                    disabled={disabled}
                    checked={overrideSSL}
                    onChange={(value) => {
                        rsssl_api.overrideSSLDetection(value).then((response) => {
                            setOverrideSSL(value)
                            setActivateSSLDisabled(!value)
                        });
                    }}
                />
            };

            return buttonTypes[type];
        })
    }

    const css = `
        #rsssl-message li {
            position: relative;
            padding-left: 15px;
        }
        #rsssl-message li:before {
            position: absolute;
            left: 0;
            color: #fff;
            height: 10px;
            width: 10px;
            border-radius:50%;
            content: '';
            position: absolute;
            margin-top: 4px;
        }
        #rsssl-message li.rsssl-inactive:before {
            background-color: #ABB0B8;
        }
        #rsssl-message li.rsssl-warning:before {
            background-color: #f8be2e;
        }
        #rsssl-message li.rsssl-error:before {
            background-color: #D7263D;
        }
        #rsssl-message li.rsssl-success:before {
            background-color: #61ce70;
        }
    `

    const renderSteps = () => {
        return (
            <>
                {
                    steps.map((step, index) => {
                        const {title, subtitle, items, info_text: infoText, buttons, visible} = step;
                        console.log("step data "+index);
                        console.log(visible);
                        return (
                            <div className="rsssl-modal-content-step" key={index} style={{ display: visible ? 'block' : 'none' }}>
                                {steps.length > 1 && <div>{index + 1}/{steps.length}</div>}
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
                <style>{css}</style>
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

export default OnboardingModal;