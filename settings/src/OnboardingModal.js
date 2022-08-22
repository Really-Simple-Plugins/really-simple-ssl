import {useState, useEffect} from "@wordpress/element";
import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "./utils/api";
import { __ } from '@wordpress/i18n';

const OnboardingModal = () => {
    const [show, setShow] = useState(false);
    const [steps, setSteps] = useState([]);
    const [overrideSSL, setOverrideSSL] = useState(false);
    const [sslActivated, setsslActivated] = useState(false);
    const [activateSSLDisabled, setActivateSSLDisabled] = useState(true);
    const [currentActionId, setcurrentActionId] = useState('none');
    const [currentAction, setcurrentAction] = useState('none');
    const [currentStatus, setcurrentStatus] = useState('warning');

    useEffect(() => {
        rsssl_api.getOnboarding().then( ( response ) => {
            let steps = response.data.steps;
            steps[0].visible = true;
            setsslActivated(response.data.ssl_enabled);
            setSteps(steps);
            setShow(!response.data.dismissed);
        });
    }, [])

    const dismissModal = () => {
        let data={};
        data.id='dismiss_onboarding_modal';
        data.action='dismiss';
        data.type='';
        rsssl_api.onboardingActions(data).then(( response ) => {
            setShow(false)
        });
    }

    const activateSSL = () => {
        console.log("clicked activate SSL");
        let sslUrl = window.location.href.replace("http://", "https://");
        rsssl_api.activateSSL().then((response) => {
            steps[0].visible = false;
            steps[1].visible = true;
            //change url to https, after final check
            if (response.data.success) {
                //we need to ensure that the rest url is with the new protocol
                rsssl_settings.site_url = rsssl_settings.site_url.replace("http://", "https://");
                window.location.href=sslUrl;
            }
            setSteps(steps);
            setsslActivated(response.data.success);
            window.location.reload();
        });
    }

    const updateActionForItem = (findItem, newAction) => {
        steps.forEach(function(step, i) {
            step.items.forEach(function(item, j) {
                if (item.id===findItem){
                   steps[i].items[j].action=newAction;
                }
            });
        });
        setcurrentActionId(findItem);
        setcurrentAction(newAction);
        setSteps(steps);
    }

    const itemButtonHandler = (id, type, action) => {
        let data={};
        data.action = action;
        data.id = id;
        data.type = type;
        updateActionForItem(id, action);
        rsssl_api.onboardingActions(data).then( ( response ) => {

            if ( response.data.success ){
                let nextAction = response.data.next_action;
                updateActionForItem(id, nextAction );
                if (nextAction!=='none') {
                    data.action = nextAction;
                    updateActionForItem(id, nextAction );
                    rsssl_api.onboardingActions(data).then( ( response ) => {
                        if ( response.data.success ){
                            updateActionForItem(id, response.data.next_action );
                            setcurrentStatus('success');
                        } else {
                            updateActionForItem(id, 'failed' );
                            setcurrentStatus('error');
                        }
                    }).catch(error => {
                        setcurrentStatus('error');
                    })
                } else {
                    updateActionForItem(id, 'failed' );
                    setcurrentStatus('success');
                }
            } else {
                setcurrentStatus('error');
            }
        }).catch(error => {
            setcurrentStatus('error');
        });
    }

    const parseStepItems = (items) => {
        return items.map((item, index) => {
            let { title, action, status, help, button, id, type } = item
            const statuses = {
                'warning': 'rsssl-warning',
                'error': 'rsssl-error',
                'success': 'rsssl-success'
            };

            let buttonTitle = '';
            if ( button ) {
                buttonTitle = button.title;
                if (currentActionId===id) {
                    status = currentStatus;
                    if ( currentAction!=='none' ) {
                        const currentActions = {
                            'activate_plugin': __('activating...',"really-simple-ssl"),
                            'install_plugin': __('installing...',"really-simple-ssl"),
                            'error': __('failed',"really-simple-ssl"),
                            'completed': __('finished',"really-simple-ssl"),
                        };
                        buttonTitle = currentActions[currentAction];
                        if (status==='error') {
                            buttonTitle = currentActions['error'];
                        }
                    }
                }
            }
            return (
                <li key={index} className={statuses[status]}>
                    {title} {button && <> - <Button isLink={true} onClick={() => itemButtonHandler(id, type, action)}>{buttonTitle}</Button>
                    {currentAction!=='none' && currentAction!=='completed' && status!=='error' && currentActionId===id &&
                        <div className="rsssl-loader">
                            <div className="rect1" key="1"></div>
                            <div className="rect2" key="2"></div>
                            <div className="rect3" key="3"></div>
                            <div className="rect4" key="4"></div>
                            <div className="rect5" key="5"></div>
                        </div>}
                    </>}
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

    const renderSteps = () => {
        return (
            <>
                {
                    steps.map((step, index) => {
                        const {title, subtitle, items, info_text: infoText, buttons, visible} = step;
                        return (
                            <div className="rsssl-modal-content-step" key={index} style={{ display: visible ? 'block' : 'none' }}>
                                {steps.length > 1 && <div className="rsssl-step-feedback">{index + 1}/{steps.length}</div>}
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
                <div className="rsssl-modal rsssl-onboarding">
                    <div className="rsssl-modal-header">
                      <img className="rsssl-logo"
                           src={rsssl_settings.plugin_url + 'assets/img/really-simple-ssl-logo.svg'}
                           alt="Really Simple SSL logo"/>
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