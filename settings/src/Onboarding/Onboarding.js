import {useState, useEffect} from "@wordpress/element";
import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';
import {useUpdateEffect} from 'react-use';
import Icon from "../utils/Icon";
import Placeholder from '../Placeholder/Placeholder';
import useMenu from "../Menu/MenuData";
import useFields from "../Settings/FieldsData";
import useOnboardingData from "./OnboardingData";

const Onboarding = (props) => {
    const { fetchFieldsData, updateField, saveFields, setChangedField} = useFields();
    const {
        dismissModal,
        updateActionForItem,
        actionHandler,
        getSteps,
        error,
        certificateValid,
        networkwide,
        dataLoaded,
        steps,
        currentStep,
        currentStepIndex,
        setCurrentStepIndex,
        overrideSSL,
        setOverrideSSL,
        networkActivationStatus,
        setNetworkActivationStatus,
        networkProgress,
        refreshSSLStatus,
        activateSSLNetworkWide
    } = useOnboardingData();
    const {setSelectedMainMenuItem, selectedMainMenuItem} = useMenu();

    useEffect( async () => {
        if (networkwide && networkActivationStatus==='main_site_activated') {
            await activateSSLNetworkWide();
        }
    }, [networkActivationStatus, networkProgress])

    useEffect( async () => {
        await getSteps(false);
    }, [])

    //ensure all fields are updated, and progress is retrieved again
    useEffect( async () => {
        if ( dataLoaded && currentStep.action === 'activate_setting' ){
            await fetchFieldsData('general');
        }
    }, [currentStep])

    const activateSSL = () => {
        rsssl_api.runTest('activate_ssl' ).then( async ( response ) => {
            setCurrentStepIndex(currentStepIndex+1);
            //change url to https, after final check
            if ( response.success ) {

                if ( response.site_url_changed ) {
                    window.location.reload();
                } else {
                    if ( networkwide ) {
                        setNetworkActivationStatus('main_site_activated');
                    }
                }
            }
        }).then( async () => { await fetchFieldsData(selectedMainMenuItem ) } );
    }

    const parseStepItems = (items) => {

        return items && items.map( (item, index) => {
            let { title, current_action, action, status, button, id } = item
            if (id==='ssl_enabled' && networkwide ) {
                if ( networkProgress>=100) {
                    status = 'success';
                    title = __( "SSL has been activated network wide", "really-simple-ssl" );
                } else {
                    status = 'processing';
                    title = __( "Processing activation of subsites networkwide", "really-simple-ssl" );
                }
            }
            const statuses = {
                'inactive': {
                    'icon': 'info',
                    'color': 'orange',
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
                'activate_setting': __('Activating...',"really-simple-ssl"),
                'activate': __('Activating...',"really-simple-ssl"),
                'install_plugin': __('Installing...',"really-simple-ssl"),
                'error': __('Failed',"really-simple-ssl"),
                'completed': __('Finished',"really-simple-ssl"),
            };

            let buttonTitle = '';
            if ( button ) {
                buttonTitle = button;
                if ( current_action!=='none' ) {
                    buttonTitle = currentActions[current_action];
                    if ( current_action==='failed' ) {
                        buttonTitle = currentActions['error'];
                    }
                }
            }
            let showLink = (button && button===buttonTitle);
            return (
                <li key={index} >
                    <Icon name = {statusIcon} color = {statusColor} />
                    {title}
                    {id==='ssl_enabled' && networkwide && networkActivationStatus==='main_site_activated' && <>
                        &nbsp;-&nbsp;
                        {networkProgress<100 && <>{__("working", "really-simple-ssl")}&nbsp;{networkProgress}%</>}
                        {networkProgress>=100 && __("completed", "really-simple-ssl") }
                        </>}
                    {button && <>&nbsp;-&nbsp;
                    {showLink && <Button isLink={true} onClick={(e) => actionHandler(id, action, e)}>{buttonTitle}</Button>}
                    {!showLink && <>{buttonTitle}</>}
                    </>}
                </li>
            )
        })
    }

    const goToDashboard = () => {
        if ( props.isModal ) dismissModal();
        setSelectedMainMenuItem('dashboard');
    }

    const goToLetsEncrypt = () => {
         if (props.isModal) dismissModal();
          window.location.href=rsssl_settings.letsencrypt_url;
    }

    const controlButtons = () => {
        let ActivateSSLText = networkwide ? __("Activate SSL networkwide", "really-simple-ssl") : __("Activate SSL", "really-simple-ssl");
        if ( currentStepIndex === 0 ) {
           return (
                <>
                <button disabled={!certificateValid && !overrideSSL} className="button button-primary" onClick={() => {activateSSL()}}>{ActivateSSLText}</button>
                { certificateValid && !rsssl_settings.pro_plugin_active && <a target="_blank" href={rsssl_settings.upgrade_link} className="button button-default" >{__("Improve Security with PRO", "really-simple-ssl")}</a>}
                {!certificateValid && <button className="button button-default" onClick={() => {goToLetsEncrypt()}}>{__("Install SSL", "really-simple-ssl")}</button>}
                {!certificateValid && <ToggleControl
                    label={__("Override SSL detection","really-simple-ssl")}
                    checked={overrideSSL}
                    onChange={(value) => {
                        setOverrideSSL(value);
                        let data = {};
                        data.overrideSSL = value;
                        rsssl_api.doAction('override_ssl_detection',data );
                   }}
                />}
                </>
            );
        }

        //for last step only
        if ( steps.length === currentStepIndex + 1 ) {
            return (
                <>
                    <button className="button button-primary" onClick={() => {goToDashboard()}}>{__('Go to Dashboard', 'really-simple-ssl')}</button>
                    <button className="button button-default" onClick={() => dismissModal()}>{__('Dismiss', 'really-simple-ssl')}</button>
                </>
            );
        }
    }

    if (error){
        return (
            <Placeholder lines="3" error={error}></Placeholder>
        )
    }
    let step = currentStep;
    return (
        <>
            { !dataLoaded && <>
                <ul>
                    <li><Icon name = "file-download" color = 'grey' />{__("Fetching next step...", "really-simple-ssl")}</li>
                </ul>
                <Placeholder lines="3" ></Placeholder></>}

            {
                dataLoaded &&
                    <div className="rsssl-modal-content-step">
                        {step.title && <h2 className="rsssl-modal-subtitle">{step.title}</h2>}
                        {step.subtitle && <div className="rsssl-modal-description">{step.subtitle}</div>}
                        <ul>
                            { parseStepItems(step.items) }
                        </ul>
                        { certificateValid && step.info_text && <div className="rsssl-modal-description" dangerouslySetInnerHTML={{__html: step.info_text}} /> }
                        { !certificateValid &&
                            <div className="rsssl-modal-description">
                               <a href="#" onClick={ (e) => refreshSSLStatus(e)}>
                                   { __("Refresh SSL status", "really-simple-ssl")}
                               </a>&nbsp;{__("The SSL detection method is not 100% accurate.", "really-simple-ssl")}&nbsp;
                               {__("If you’re certain an SSL certificate is present, and refresh SSL status does not work, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl")}
                            </div> }
                        <div className="rsssl-modal-content-step-footer">
                            {controlButtons()}
                        </div>
                    </div>
            }
        </>
    )
}

export default Onboarding;