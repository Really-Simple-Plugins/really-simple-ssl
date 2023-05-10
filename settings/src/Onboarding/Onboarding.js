import { useEffect} from "@wordpress/element";
import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';
import Icon from "../utils/Icon";
import Placeholder from '../Placeholder/Placeholder';
import useMenu from "../Menu/MenuData";
import useFields from "../Settings/FieldsData";
import useProgress from "../Dashboard/Progress/ProgressData";
import useOnboardingData from "./OnboardingData";
import useRiskData from "../Settings/RiskConfiguration/RiskData";

const Onboarding = (props) => {
    const { fetchFieldsData, updateField, updateFieldsData, getFieldValue} = useFields();
    const { getProgressData} = useProgress();
    const {
        fetchVulnerabilities
    } = useRiskData();
    const {
        dismissModal,
        actionHandler,
        getSteps,
        error,
        certificateValid,
        networkwide,
        sslEnabled,
        dataLoaded,
        processing,
        setProcessing,
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
        activateSSLNetworkWide,
        email,
        setEmail,
        saveEmail,
        includeTips,
        setIncludeTips,
        sendTestEmail,
        setSendTestEmail
    } = useOnboardingData();
    const {setSelectedMainMenuItem, selectedMainMenuItem} = useMenu();
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

    useEffect( () => {
        if (networkwide && networkActivationStatus==='main_site_activated') {
            activateSSLNetworkWide();
        }
    }, [networkActivationStatus, networkProgress])

    useEffect( () => {
        const run = async () => {
            await getSteps(false);
            if ( dataLoaded && sslEnabled && currentStepIndex===0) {
                setCurrentStepIndex(1)
            }

            if (getFieldValue('notifications_email_address') !== '' && email==='') {
                setEmail(getFieldValue('notifications_email_address'))
            }
        }
        run();
    }, [])

    //ensure all fields are updated, and progress is retrieved again
    useEffect( () => {
        const runUpdate = async () => {
            //in currentStep.items, find item with id 'hardening'
            //if it has status 'completed' fetchFieldsData again.
            if (currentStep && currentStep.items) {
                let hardeningItem = currentStep.items.find((item) => {
                    return item.id === 'hardening';
                })
                if (hardeningItem && hardeningItem.status === 'success') {
                    await fetchFieldsData('hardening');
                    await getProgressData();
                    await fetchVulnerabilities();
                }
            }
        }
        runUpdate();
    }, [currentStep])

    const activateSSL = () => {
        setProcessing(true);
        rsssl_api.runTest('activate_ssl' ).then( async ( response ) => {
            setProcessing(false);
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
        }).then( async () => {
            await getProgressData();
            await fetchFieldsData(selectedMainMenuItem )
        } );
    }

    const parseStepItems = (items) => {
        return items && items.map( (item, index) => {
            let { title, description, current_action, action, status, button, id, read_more } = item

            if ( id==='ssl_enabled' && networkwide ) {
                if ( networkProgress>=100) {
                    status = 'success';
                    title = __( "SSL has been activated network wide", "really-simple-ssl" );
                } else {
                    status = 'processing';
                    title = __( "Processing activation of subsites networkwide", "really-simple-ssl" );
                }
            }
            const statusIcon = item.status!=='success' && item.is_plugin && item.current_action === 'none' ? 'empty' : statuses[status].icon;
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
            let showAsPlugin = item.status!=='success' && item.is_plugin && item.current_action === 'none';
            let isPluginClass = showAsPlugin ? 'rsssl-is-plugin' : '';
            title = showAsPlugin ? <b>{title}</b> : title;
            return (
                <li key={"pluginItem-"+index} className={isPluginClass}>
                    <Icon name = {statusIcon} color = {statusColor} />
                    {title}{description && <>&nbsp;-&nbsp;{description}</>}
                    {id==='ssl_enabled' && networkwide && networkActivationStatus==='main_site_activated' && <>
                        &nbsp;-&nbsp;
                        {networkProgress<100 && <>{__("working", "really-simple-ssl")}&nbsp;{networkProgress}%</>}
                        {networkProgress>=100 && __("completed", "really-simple-ssl") }
                        </>}
                    {button && <>&nbsp;-&nbsp;
                        {showLink && <Button isLink={true} onClick={(e) => actionHandler(id, action, e)}>{buttonTitle}</Button>}
                        {!showLink && <>{buttonTitle}</>}
                    </>}
                    {showAsPlugin && read_more && <a target="_blank" href={read_more} className="button button-default rsssl-read-more">{__("Read More", "really-simple-ssl")}</a>}
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

    const saveEmailAndUpdateFields = async () => {
        await saveEmail();

        updateField('send_notifications_email', true );
        updateField('notifications_email_address', email );
        updateFieldsData();
    }

    const controlButtons = () => {

        let ActivateSSLText = networkwide ? __("Activate SSL networkwide", "really-simple-ssl") : __("Activate SSL", "really-simple-ssl");
        if ( currentStepIndex === 0 ) {
           return (
                <>
                    <button disabled={processing || (!certificateValid && !overrideSSL) } className="button button-primary" onClick={() => {activateSSL()}}>{ActivateSSLText}</button>
                    { certificateValid && !rsssl_settings.pro_plugin_active && <a target="_blank" href={rsssl_settings.upgrade_link} className="button button-default" >{__("Improve Security with PRO", "really-simple-ssl")}</a>}
                    { !certificateValid && <button className="button button-default" onClick={() => {goToLetsEncrypt()}}>{__("Install SSL", "really-simple-ssl")}</button>}
                    { !certificateValid && <ToggleControl
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

        if (currentStepIndex>0 && currentStepIndex<steps.length-1) {
            return (
                <>
                    <button disabled={processing} className="button button-primary" onClick={() => saveEmailAndUpdateFields()}>{__('Save and continue', 'really-simple-ssl')}</button>
                    <button disabled={processing} className="button button-default" onClick={() => {setCurrentStepIndex(currentStepIndex+1)}}>{__('Skip', 'really-simple-ssl')}</button>
                </>
            );
        }

        //for last step only
        if ( steps.length-1 === currentStepIndex ) {
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
    let processingClass = processing ? 'rsssl-processing' : '';
    return (
        <>
            { !dataLoaded && <>
                <div className="rsssl-onboarding-placeholder">
                    <ul>
                        <li><Icon name = "file-download" color = 'grey' />{__("Fetching next step...", "really-simple-ssl")}</li>
                    </ul>
                    <Placeholder lines="3" ></Placeholder>
                </div>
            </>
            }
            {
                dataLoaded &&
                    <div className={ "rsssl-modal-content-step "+processingClass }>
                        <ul>
                            { parseStepItems(step.items) }
                        </ul>
                        { currentStep.id === 'email'&&
                            <>
                                <div>
                                    <input type="email" value={email} placeholder={__("Your email address", "really-simple-ssl")} onChange={(e) => setEmail(e.target.value)} />
                                </div><div>
                                <label><input onChange={ (e) => setIncludeTips(e.target.checked)} type="checkbox" checked={includeTips} />{__("Include 6 Tips & Tricks to get started with Really Simple SSL.","really-simple-ssl")}&nbsp;<a href="https://really-simple-ssl.com/legal/privacy-statement/" target="_blank">{__("Privacy Statement", "really-simple-ssl")}</a></label>
                                </div><div>
                                    <label><input onChange={ (e) => setSendTestEmail(e.target.checked)} type="checkbox" checked={sendTestEmail} />{__("Send a notification test email - Notification emails are sent from your server.","really-simple-ssl")}</label>
                                </div>
                            </>

                        }
                        { certificateValid && step.info_text && <div className="rsssl-modal-description" dangerouslySetInnerHTML={{__html: step.info_text}} /> }
                        { currentStepIndex===0 && !certificateValid &&
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