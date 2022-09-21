import {useState, useEffect} from "@wordpress/element";
import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';
import Icon from "../utils/Icon";
import Placeholder from '../Placeholder/Placeholder';

const Onboarding = (props) => {
    const [steps, setSteps] = useState([]);
    const [overrideSSL, setOverrideSSL] = useState(false);
    const [certificateValid, setCertificateValid] = useState(false);
    const [sslActivated, setsslActivated] = useState(false);
    const [activateSSLDisabled, setActivateSSLDisabled] = useState(true);
    const [stepsChanged, setStepsChanged] = useState('');
    const [networkwide, setNetworkwide] = useState(false);
    const [networkActivationStatus, setNetworkActivationStatus] = useState(false);
    const [networkProgress, setNetworkProgress] = useState(0);

    useUpdateEffect(()=> {
        // do componentDidUpdate logic
        if ( networkProgress<100 && networkwide && networkActivationStatus==='main_site_activated' ){
            rsssl_api.runTest('activate_ssl_networkwide' ).then( ( response ) => {
               if (response.data.success) {
                    setNetworkProgress(response.data.progress);
                    if (response.data.progress>=100) {
                        updateActionForItem('ssl_enabled', '', 'success');
                    }
                }
            });
        }
    })

    useEffect(() => {
        updateOnBoardingData(false);
    }, [])

    const updateOnBoardingData = (forceRefresh) => {
        rsssl_api.getOnboarding(forceRefresh).then( ( response ) => {
            let steps = response.data.steps;
            setNetworkwide(response.data.networkwide);
            setOverrideSSL(response.data.ssl_detection_overridden);
            setActivateSSLDisabled(!response.data.ssl_detection_overridden);
            setCertificateValid(response.data.certificate_valid);
            setsslActivated(response.data.ssl_enabled);
            steps[0].visible = true;
            if (response.data.ssl_enabled) {
                steps[0].visible = false;
                steps[1].visible = true;
            }
            setNetworkActivationStatus(response.data.network_activation_status);
            setSteps(steps);
            setStepsChanged('initial');
        });
    }

    const refreshSSLStatus = (e) => {
        e.preventDefault();
        steps.forEach(function(step, i) {
            if (step.id==='activate_ssl') {
                step.items.forEach(function(item, j){
                    if (item.status==='error') {
                        steps[i].items[j].status = 'processing';
                        steps[i].items[j].title = __("Re-checking SSL certificate, please wait...","really-simple-ssl");
                    }
                });
            }
        });

        setSteps(steps);
        setStepsChanged(true);
        setTimeout(function(){
            updateOnBoardingData(true)
        }, 1000)
    }

    const activateSSL = () => {
        let sslUrl = window.location.href.replace("http://", "https://");
        rsssl_api.runTest('activate_ssl' ).then( ( response ) => {
            steps[0].visible = false;
            steps[1].visible = true;
            //change url to https, after final check
            //if ( response.data.success ) {
                setSteps(steps);
                setsslActivated(response.data.success);
                if (response.data.site_url_changed) {
                    window.location.reload();
                } else if (networkwide) {
                    setNetworkActivationStatus('main_site_activated');
                }
            //}
        });
    }

    const updateActionForItem = (findItem, newAction, newStatus) => {
        let stepsCopy = steps;
        stepsCopy.forEach(function(step, i) {
            stepsCopy[i].items.forEach(function(item, j) {
                if (item.id===findItem){
                  let itemCopy = stepsCopy[i].items[j];
                  itemCopy.current_action = newAction;
                  if (newStatus) {
                       itemCopy.status=newStatus;
                  }
                  stepsCopy[i].items[j] = itemCopy;
                }
            });
        });
        setSteps(stepsCopy);
        setStepsChanged(findItem+newAction+newStatus);
    }

    const itemButtonHandler = (id, type, action) => {
        let data={};
        data.action = action;
        data.id = id;
        data.type = type;
        updateActionForItem(id, action, false);
        rsssl_api.onboardingActions(data).then( ( response ) => {
            if ( response.data.success ){
                let nextAction = response.data.next_action;
                if (nextAction!=='none') {
                    data.action = nextAction;
                    updateActionForItem(id, nextAction, false);
                    rsssl_api.onboardingActions(data).then( ( response ) => {
                        if ( response.data.success ){
                            updateActionForItem(id, 'completed', 'success' );
                        } else {
                            updateActionForItem(id, 'failed', 'error' );
                        }
                    }).catch(error => {
                        updateActionForItem(id, 'failed', 'error' );
                    })
                } else {
                    updateActionForItem(id, 'completed', 'success' );
                }
            } else {
                updateActionForItem(id, 'failed', 'error' );
            }
        }).catch(error => {
            updateActionForItem(id, 'failed', 'error' );
        });
    }

    const parseStepItems = (items) => {
        return items.map((item, index) => {

            let { title, current_action, action, status, help, button, id, type, percentage } = item
            const statuses = {
                'inactive': {
                    'icon': 'bullet',
                    'color': 'grey',
                },
                'warning': {
                    'icon': 'circle-times',
                    'color': 'orange',
                },
                'error': {
                    'icon': 'circle-times',
                    'color': 'red',
                },
                'success': {
                    'icon': 'circle-check',
                    'color': 'green',
                },
                'processing': {
                    'icon': 'file-download',
                    'color': 'red',
                },
            };
            const statusIcon = statuses[status].icon;
            const statusColor = statuses[status].color;

            const currentActions = {
                'activate': __('Activating...',"really-simple-ssl"),
                'install_plugin': __('Installing...',"really-simple-ssl"),
                'error': __('Failed',"really-simple-ssl"),
                'completed': __('Finished',"really-simple-ssl"),
            };


            let buttonTitle = '';
            if ( button ) {
                buttonTitle = button.title;
                if ( current_action!=='none' ) {
                    buttonTitle = currentActions[current_action];
                    if (current_action==='failed') {
                        buttonTitle = currentActions['error'];
                    }
                }
            }
            let isLink = (button && button.title===buttonTitle);

            return (
                <li key={index} >
                    <Icon name = {statusIcon} color = {statusColor} />
                    {title}
                    {percentage && networkActivationStatus==='main_site_activated' && <>
                        &nbsp;-&nbsp;
                        {networkProgress<100 && <>{__("working", "really-simple-ssl")}&nbsp;{networkProgress}%</>}
                        {networkProgress>=100 && __("completed", "really-simple-ssl") }
                        </>}
                    {button && <>&nbsp;-&nbsp;
                    {isLink && <Button isLink={true} onClick={() => itemButtonHandler(id, type, action)}>{buttonTitle}</Button>}
                    {!isLink && <>{buttonTitle}</>}
                    {current_action==='activate' || current_action==='install_plugin' &&
                        <div className="rsssl-loader">
                            <div className="rect1" key="1"></div>
                            <div className="rect2" key="2"></div>
                            <div className="rect3" key="3"></div>
                            <div className="rect4" key="4"></div>
                            <div className="rect5" key="5"></div>
                        </div>
                    }
                    </>}
                </li>
            )
        })
    }

    const goToDashboard = () => {
        if (props.isModal) props.dismissModal();
        props.selectMainMenu('dashboard');
    }

    const goToLetsEncrypt = () => {
         if (props.isModal) props.dismissModal();
        window.location.href=rsssl_settings.letsencrypt_url;
    }

    const controlButtons = () => {
        let ActivateSSLText = networkwide ? __("Activate SSL networkwide", "really-simple-ssl") : __("Activate SSL", "really-simple-ssl");
        if (steps[0].visible) {
           return (
                <>
                <button disabled={!certificateValid && !overrideSSL} className="button button-primary" onClick={() => {activateSSL()}}>{ActivateSSLText}</button>
                {certificateValid && !rsssl_settings.pro_plugin_active && <a target="_blank" href={rsssl_settings.upgrade_link} className="button button-default" >{__("Improve Security with PRO", "really-simple-ssl")}</a>}
                {!certificateValid && <button className="button button-default" onClick={() => {goToLetsEncrypt()}}>{__("Install SSL", "really-simple-ssl")}</button>}
                {!certificateValid && <ToggleControl
                    label={__("Override SSL detection","really-simple-ssl")}
                    checked={overrideSSL}
                    onChange={(value) => {
                        setOverrideSSL(value);
                        let data = {};
                        data.overrideSSL = value;
                        rsssl_api.doAction('override_ssl_detection',data ).then( ( response ) => {
                            setActivateSSLDisabled(!value)
                        });
                       }}
                />}
                </>
            );
        }

        if (steps[1].visible){
            return (
                <>
                <button className="button button-primary" onClick={() => {goToDashboard()}}>{__('Go to Dashboard', 'really-simple-ssl')}</button>
                <button className="button button-default" onClick={() => {props.dismissModal()}}>{__('Dismiss', 'really-simple-ssl')}</button>
                </>
            );

        }
    }


    return (
        <>
            {!stepsChanged && <Placeholder lines="12"></Placeholder>}
            {
                stepsChanged && steps.map((step, index) => {
                    const {title, subtitle, items, info_text: infoText, visible} = step;
                    return (
                        <div className="rsssl-modal-content-step" key={index} style={{ display: visible ? 'block' : 'none' }}>
                            {title && <h2 className="rsssl-modal-subtitle">{title}</h2>}
                            {subtitle && <div className="rsssl-modal-description">{subtitle}</div>}
                            <ul>
                                { parseStepItems(items) }
                            </ul>
                            { certificateValid && infoText && <div className="rsssl-modal-description" dangerouslySetInnerHTML={{__html: infoText}} /> }
                            { !certificateValid &&
                                <div className="rsssl-modal-description">
                                   <a href="#" onClick={ (e) => refreshSSLStatus(e)}>
                                       { __("Refresh SSL status", "really-simple-ssl")}
                                   </a>&nbsp;{__("The SSL detection method is not 100% accurate.", "really-simple-ssl")}.&nbsp;
                                   {__("If you’re certain an SSL certificate is present, and refresh SSL status does not work, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl")}
                                </div> }
                            <div className="rsssl-modal-content-step-footer">
                                {controlButtons()}
                            </div>

                        </div>
                    )
                })
            }
        </>
    )
}

export default Onboarding;