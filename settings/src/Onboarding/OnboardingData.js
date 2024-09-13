import {create} from 'zustand';
import {produce} from 'immer';

import * as rsssl_api from "../utils/api";
import {__} from "@wordpress/i18n";
const useOnboardingData = create(( set, get ) => ({
    steps: [],
    currentStepIndex: 0,
    currentStep: {},
    error: false,
    networkProgress: 0,
    networkActivationStatus: '',
    certificateValid: '',
    networkwide: false,
    sslEnabled: false,
    overrideSSL: false,
    showOnboardingModal: false,
    modalStatusLoaded: false,
    dataLoaded: false,
    processing: false,
    email: '',
    includeTips:false,
    sendTestEmail:true,
    overrideSSLDetection:false,
    footerStatus: '',
    setFooterStatus: (footerStatus) => {
        set({footerStatus:footerStatus})
    },
    setIncludeTips: (includeTips) => {
        set(state => ({ includeTips }))
    },
    setSendTestEmail: (sendTestEmail) => {
        set(state => ({ sendTestEmail }))
    },
    setEmail: (email) => {
        set(state => ({ email }))
    },
    setShowOnboardingModal: (showOnboardingModal) => {
        set(state => ({ showOnboardingModal }))
    },
    setProcessing: (processing) => {
        set(state => ({ processing }))
    },
    setCurrentStepIndex: (currentStepIndex) => {
        const currentStep = get().steps[currentStepIndex];
        set(state => ({ currentStepIndex, currentStep }))
    },
    dismissModal: async (dismiss) => {
        let data={};
        data.dismiss = dismiss;
        //dismiss is opposite of showOnboardingModal, so we check the inverse.
        set(() => ({showOnboardingModal: !dismiss}));
        await rsssl_api.doAction('dismiss_modal', data);
    },
    setOverrideSSL: async (override) => {
        set({overrideSSL: override});
        let data = {
            overrideSSL: override,
        };
        await rsssl_api.doAction('override_ssl_detection',data );
    },
    activateSSL: () => {
        set((state) => ({processing:true}));
        rsssl_api.runTest('activate_ssl' ).then( async ( response ) => {
            set((state) => ({processing:false}));
            get().setCurrentStepIndex( get().currentStepIndex+1 );
            //change url to https, after final check
            if ( response.success ) {
                if ( response.site_url_changed ) {
                    window.location.reload();
                } else {
                    if ( get().networkwide ) {
                        set(state => ({ networkActivationStatus:'main_site_activated' }))
                    }
                }

                set({ sslEnabled: true})
            }
        });
    },
    saveEmail:() => {
        get().setFooterStatus( __("Updating email preferences..", "really-simple-ssl") );
        let data={};
        data.email = get().email;
        data.includeTips = get().includeTips;
        data.sendTestEmail = get().sendTestEmail;
        set((state) => ({processing:true}));
        rsssl_api.doAction('update_email', data).then(( response ) => {
            set((state) => ({processing:false}));
            get().setFooterStatus('' );
        });
    },
    updateItemStatus: (stepId, id, action, status, activated) => {
        const index = get().steps.findIndex(item => { return item.id===stepId; });
        const itemIndex = get().steps[index].items.findIndex(item => {return item.id===id;});
        set(
            produce((state) => {
                if (typeof action !== 'undefined') state.steps[index].items[itemIndex].action = action;
                if (typeof status !== 'undefined') state.steps[index].items[itemIndex].status = status;
                if (typeof activated !== 'undefined') state.steps[index].items[itemIndex].activated = activated;
            })
        )
        let currentStep = get().steps[get().currentStepIndex];
        set(
            produce((state) => {
                    state.currentStep = currentStep;
                }
            ))
    },
    fetchOnboardingModalStatus: async () => {
        rsssl_api.doAction('get_modal_status').then((response) => {
            set({
                showOnboardingModal: !response.dismissed,
                modalStatusLoaded: true,
            })
        });
    },
    setShowOnBoardingModal: (showOnboardingModal) => set(state => ({ showOnboardingModal })),
    pluginInstaller: async (id, action, title) => {
        if ( !action ) {
            return;
        }

        set(() => ({processing:true}));
        get().updateItemStatus('plugins', id, action, 'processing');
        get().setFooterStatus(__("Installing %d...", "really-simple-ssl").replace("%d", title));

        let nextAction = await processAction(action, id);
        get().updateItemStatus('plugins', id, nextAction);

        if ( nextAction!=='none' && nextAction!=='completed') {
            get().setFooterStatus(__("Activating %d...", "really-simple-ssl").replace("%d", title));
            nextAction = await processAction(nextAction, id);
            get().updateItemStatus('plugins', id, nextAction);
        } else {
            get().setFooterStatus('');
        }
        set((state) => ({processing:false}));
    },
    getSteps: async (forceRefresh) => {
        const {steps, networkActivationStatus, certificateValid, networkProgress, networkwide, overrideSSL, error, sslEnabled, upgradedFromFree} = await retrieveSteps(forceRefresh);
        //if ssl is already enabled, the server will send only one step. In that case we can skip the below.
        //it's only needed when SSL is activated just now, client side.
        let currentStepIndex = 0;

        if ( ! upgradedFromFree && ( sslEnabled || (networkwide && networkActivationStatus === 'completed' ) ) ) {
            currentStepIndex = 1;
        }

        set({
            steps: steps,
            currentStepIndex:currentStepIndex,
            currentStep: steps[currentStepIndex],
            networkActivationStatus: networkActivationStatus,
            certificateValid: certificateValid,
            networkProgress: networkProgress,
            networkwide: networkwide,
            overrideSSL: overrideSSL,
            sslEnabled: sslEnabled,
            dataLoaded: true,
            error:error,
            // licenseField: licenseField,
        });

        if (networkActivationStatus==='completed') {
            set( {networkProgress: 100} );
        }
    },
    refreshSSLStatus: (e) => {
        e.preventDefault();
        set( {processing: true} );
        set(
            produce((state) => {
                const stepIndex = state.steps.findIndex(step => {
                    return step.id==='activate_ssl';
                });
                const step = state.steps[stepIndex];
                step.items.forEach(function(item, j){
                    if (item.status==='error') {
                        step.items[j].status = 'processing';
                        step.items[j].title = __("Re-checking SSL certificate, please wait...","really-simple-ssl");
                    }
                });
                state.steps[stepIndex] = step;
            })
        )

        setTimeout(async function () {
            const {
                steps,
                certificateValid,
                error,
            } = await retrieveSteps(true);
            set({
                steps: steps,
                certificateValid: certificateValid,
                processing: false,
                error: error,
            });
        }, 1000) //add a delay, otherwise it's so fast the user may not trust it.
    },
    activateSSLNetworkWide: () => {
        let progress = get().networkProgress;
        if (typeof progress !== 'undefined') {
            get().setFooterStatus(__("%d% of subsites activated.").replace('%d', progress));
        }
        if (get().networkProgress>=100) {
            set({
                sslEnabled: true,
                networkActivationStatus:'completed'
            });
            return;
        }
        set( () => ({processing: true}));
        rsssl_api.runTest('activate_ssl_networkwide' ).then( ( response ) => {
            if (response.success) {
                set({
                    networkProgress: response.progress,
                    processing:false,
                });
                get().setFooterStatus(__("%d% of subsites activated.").replace('%d', response.progress));

                if (response.progress>=100) {
                    get().setFooterStatus('');
                    set({
                        sslEnabled: true,
                        networkActivationStatus:'completed'
                    });
                }
            }
        });
    }
}));

const retrieveSteps = (forceRefresh) => {
    let data={};
    data.forceRefresh = forceRefresh;
    return rsssl_api.doAction('onboarding_data', data).then( ( response ) => {
        let steps = response.steps;
        let sslEnabled=  response.ssl_enabled;
        let networkActivationStatus=  response.network_activation_status;
        let certificateValid = response.certificate_valid;
        let networkProgress = response.network_progress;
        let networkwide = response.networkwide;
        let overrideSSL = response.ssl_detection_overridden;
        let error = response.error;
        let upgradedFromFree = response.rsssl_upgraded_from_free;
        return {steps, networkActivationStatus, certificateValid, networkProgress, networkwide, overrideSSL, error, sslEnabled, upgradedFromFree};
    });
}

const processAction = async (action, id) => {
    let data={};
    data.id = id;
    return await rsssl_api.doAction(action, data).then( async ( response ) => {
        if ( response.success ){
            return response.next_action;
        } else {
            return 'failed';
        }
    }).catch(error => {
        return 'failed';
    });
}

export default useOnboardingData;