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
    dataLoaded: false,
    setShowOnboardingModal: (showOnboardingModal) => {
        set(state => ({ showOnboardingModal }))
    },
    setOverrideSSL: (overrideSSL) => {
        set(state => ({ overrideSSL }))
    },
    setCurrentStepIndex: (currentStepIndex) => {
        const currentStep = get().steps[currentStepIndex];
        set(state => ({ currentStepIndex, currentStep }))
    },
    dismissModal: () => {
        let data={};
        data.dismiss = true;
        set((state) => ({showOnboardingModal: false}));
        rsssl_api.doAction('dismiss_modal', data).then(( response ) => { });
    },
    setShowOnBoardingModal: (showOnboardingModal) => set(state => ({ showOnboardingModal })),
    actionHandler: async (id, action, event) => {
        console.log("start action handler "+id+" "+action);
        event.preventDefault();
        const currentStepIndex = get().currentStepIndex;
        const itemIndex = get().steps[currentStepIndex].items.findIndex(item => {return item.id===id;});

        set(
            produce((state) => {
                let step = get().currentStep;
                let stepCopy = {...step};
                let itemsCopy = [...step.items];
                let itemCopy = {...step.items[itemIndex]};
                itemCopy.status = 'processing';
                itemCopy.current_action = action;
                itemsCopy[itemIndex] = itemCopy;
                stepCopy.items = itemsCopy;
                state.steps[currentStepIndex] = stepCopy;
                state.currentStep = state.steps[currentStepIndex];
            })
        )

        let next = await processAction(action, id);
        console.log("after first action");
        console.log(next);
        set(
            produce((state) => {
                let step = get().currentStep;
                let stepCopy = {...step};
                let itemsCopy = [...step.items];
                let itemCopy = {...step.items[itemIndex]};
                itemCopy.status = next.status;
                itemCopy.current_action = next.action;
                itemsCopy[itemIndex] = itemCopy;
                stepCopy.items = itemsCopy;
                state.steps[currentStepIndex] = stepCopy;
                state.currentStep = state.steps[currentStepIndex];
            })
        )

        if ( next.action!=='none' && next.action!=='completed') {
            next = await processAction(next.action, id);
            set(
                produce((state) => {
                    let step = get().currentStep;
                    let stepCopy = {...step};
                    let itemsCopy = [...step.items];
                    let itemCopy = {...step.items[itemIndex]};
                    itemCopy.status = next.status;
                    itemCopy.current_action = next.action;
                    itemsCopy[itemIndex] = itemCopy;
                    stepCopy.items = itemsCopy;
                    state.steps[currentStepIndex] = stepCopy;
                    state.currentStep = state.steps[currentStepIndex];
                })
            )

        }

    },
    getSteps: async (forceRefresh) => {
        set({
            dataLoaded: false,
        });
        const {steps, networkActivationStatus, certificateValid, networkProgress, networkwide, overrideSSL, error, sslEnabled} = await retrieveSteps(forceRefresh);
        //if ssl is already enabled, the server will send only one step. In that case we can skip the below.
        //it's only needed when SSL is activated just now, client side.
        let currentStepIndex = 0;
        if ( sslEnabled || ( networkwide && networkActivationStatus === 'completed') ) {
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
        });

        if (networkActivationStatus==='completed') {
            set( {networkProgress: 100} );
        }
    },
    refreshSSLStatus: (e) => {
        e.preventDefault();
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
                dataLoaded: true,
                error: error,
            });
        }, 1000) //add a delay, otherwise it's so fast the user may not trust it.
    },
    activateSSLNetworkWide: () => {
        rsssl_api.runTest('activate_ssl_networkwide' ).then( ( response ) => {
            if (response.success) {
                set({
                    networkProgress: response.progress,
                });
                if (response.progress>=100) {
                    const currentStepIndex = get().currentStepIndex;
                    const itemIndex = get().steps[currentStepIndex].items.findIndex(item => {return item.id==='ssl_enabled';});
                    set(
                        produce((state) => {
                            let step = get().currentStep;
                            let stepCopy = {...step};
                            let itemsCopy = [...step.items];
                            let itemCopy = {...step.items[itemIndex]};
                            itemCopy.status = 'success';
                            itemCopy.current_action = '';
                            itemsCopy[itemIndex] = itemCopy;
                            stepCopy.items = itemsCopy;
                            state.steps[currentStepIndex] = stepCopy;
                            state.currentStep = state.steps[currentStepIndex];
                        })
                    )
                }
            }
        });
}
}));

const retrieveSteps = (forceRefresh) => {
    return rsssl_api.getOnboarding(forceRefresh).then( ( response ) => {
        let steps = response.steps;
        let sslEnabled=  response.ssl_enabled;
        let networkActivationStatus=  response.network_activation_status;
        let certificateValid = response.certificate_valid;
        let networkProgress = response.network_progress;
        let networkwide = response.networkwide;
        let overrideSSL = response.ssl_detection_overridden;
        let error = response.error;
        return {steps, networkActivationStatus, certificateValid, networkProgress, networkwide, overrideSSL, error, sslEnabled};
    });
}

const processAction = (action, id) => {
    let data={};
    data.id = id;
    let next = {};
    console.log("process action " + action)
    return rsssl_api.doAction(action, data).then( async ( response ) => {
        console.log(response);
        if ( response.success ){
            next.action = response.next_action;
            next.status = 'success';
            return next;
        } else {
            next.action = 'failed';
            next.status = 'error';
            return next;
        }
    }).catch(error => {
        next.action = 'failed';
        next.status = 'error';
        return next;
    });
}

export default useOnboardingData;