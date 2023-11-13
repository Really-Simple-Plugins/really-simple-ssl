import { useEffect, useState } from "@wordpress/element";
import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';
import Icon from "../utils/Icon";
import Placeholder from '../Placeholder/Placeholder';
import useFields from "../Settings/FieldsData";
import useProgress from "../Dashboard/Progress/ProgressData";
import useOnboardingData from "./OnboardingData";
import useRiskData from "../Settings/RiskConfiguration/RiskData";
import OnboardingControls from "./OnboardingControls";

const Onboarding = ({isModal}) => {
    const { fetchFieldsData, getFieldValue} = useFields();
    const { getProgressData} = useProgress();
    const [hardeningEnabled, setHardeningEnabled] = useState(false);
    const [vulnerabilityDetectionEnabled, setVulnerabilityDetectionEnabled] = useState(false);
    const {
        fetchFirstRun, fetchVulnerabilities
    } = useRiskData();
    const {
        actionHandler,
        getSteps,
        error,
        certificateValid,
        networkwide,
        sslEnabled,
        dataLoaded,
        processing,
        currentStep,
        currentStepIndex,
        setCurrentStepIndex,
        overrideSSL,
        setOverrideSSL,
        networkActivationStatus,
        networkProgress,
        refreshSSLStatus,
        activateSSLNetworkWide,
        email,
        setEmail,
        includeTips,
        setIncludeTips,
    } = useOnboardingData();
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
            'icon': 'loading',
            'color': 'black',
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

    useEffect( () => {
        if (currentStep && currentStep.items) {
            let hardeningItem = currentStep.items.find((item) => {
                return item.id === 'hardening';
            })
            if (hardeningItem) {
                setHardeningEnabled(hardeningItem.status === 'success');
            }
            let vulnerabilityDetection = currentStep.items.find((item) => {
                return item.id === 'vulnerability_detection';
            })
            if (vulnerabilityDetection) {
                setVulnerabilityDetectionEnabled(vulnerabilityDetection.status === 'success');
            }
        }
    }, [currentStep]);

    //ensure all fields are updated, and progress is retrieved again
    useEffect( () => {
        const runUpdate = async () => {
            //in currentStep.items, find item with id 'hardening'
            //if it has status 'completed' fetchFieldsData again.
            if ( hardeningEnabled ) {
                await fetchFieldsData('hardening');
                await getProgressData();
            }

            if (vulnerabilityDetectionEnabled) {
                await fetchFieldsData('vulnerabilities');
                await fetchFirstRun();
                await fetchVulnerabilities();
                await getProgressData();
            }

            if ( sslEnabled ) {
                await getProgressData();
            }
        }
        runUpdate();
    }, [hardeningEnabled, vulnerabilityDetectionEnabled, sslEnabled])

    const parseStepItems = (items) => {
        return items && items.map( (item, index) => {
            let { title, description, current_action, action, status, button, id } = item

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
                </li>
            )
        })
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
            { !dataLoaded &&
                <>
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
                    <div className={ processingClass }>
                        <ul>
                            { parseStepItems(step.items) }
                        </ul>
                        { currentStep.id === 'email'&&
                            <>
                                <div>
                                    <input type="email" value={email} placeholder={__("Your email address", "really-simple-ssl")} onChange={(e) => setEmail(e.target.value)} />
                                </div>
                                <div>
                                <label>
                                    <input onChange={ (e) => setIncludeTips(e.target.checked)} type="checkbox" checked={includeTips} />{__("Include 6 Tips & Tricks to get started with Really Simple SSL.","really-simple-ssl")}&nbsp;<a href="https://really-simple-ssl.com/legal/privacy-statement/" target="_blank">{__("Privacy Statement", "really-simple-ssl")}</a>
                                </label>
                                </div>
                            </>
                        }
                        { certificateValid && step.info_text && <div className="rsssl-modal-description" dangerouslySetInnerHTML={{__html: step.info_text}} /> }
                        { currentStepIndex===0 && !certificateValid &&
                            <>
                                <div className="rsssl-modal-description">
                                   <a href="#" onClick={ (e) => refreshSSLStatus(e)}>
                                       { __("Refresh SSL status", "really-simple-ssl")}
                                   </a>.&nbsp;{__("The SSL detection method is not 100% accurate.", "really-simple-ssl")}&nbsp;
                                   {__("If you’re certain an SSL certificate is present, and refresh SSL status does not work, please check “Override SSL detection” to continue activating SSL.", "really-simple-ssl")}
                                </div>
                                <ToggleControl className="rsssl-override-detection-toggle"
                                    label={__("Override SSL detection","really-simple-ssl")}
                                    checked={overrideSSL}
                                    onChange={(value) => {
                                        setOverrideSSL(value);
                                        let data = {};
                                        data.overrideSSL = value;
                                        rsssl_api.doAction('override_ssl_detection',data );
                                    }}
                                />
                                { !isModal &&
                                    <OnboardingControls isModal={isModal}/>
                                }
                            </>
                        }

                    </div>
            }
        </>
    )
}

export default Onboarding;