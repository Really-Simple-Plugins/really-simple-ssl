import {useEffect, useRef} from "@wordpress/element";
import * as rsssl_api from "../utils/api";
import sleeper from "../utils/sleeper";
import Directories from "./Directories";
import DnsVerification from "./DnsVerification";
import Generation from "./Generation";
import Activate from "./Activate";
import Installation from "./Installation";
import { __ } from '@wordpress/i18n';
import Icon from "../utils/Icon";
import useFields from "../Settings/FieldsData";
import useLetsEncryptData from "./letsEncryptData";
import DOMPurify from "dompurify";

const LetsEncrypt = ({field}) => {
    const {handleNextButtonDisabled, getFieldValue} = useFields();
    const {setSwitchButtonDisabled, actionsList, setActionsList, setActionsListItem, setActionsListProperty, actionIndex, setActionIndex, attemptCount, setAttemptCount, progress, setProgress, refreshTests, setRefreshTests} = useLetsEncryptData();
    const sleep = useRef(1000);
    const intervalId = useRef(false);
    const previousActionIndex = useRef(-1);
    const maxIndex = useRef(1);
    const refProgress = useRef(0);
    const lastAction = useRef({});

    useEffect(() => {
        reset();
   }, [field.id])

    useEffect(() => {
        setSwitchButtonDisabled(true);
    }, []);

    const getActions = () => {
        let propActions = field.actions;
        if ( field.id==='generation' ) {
            propActions = adjustActionsForDNS(propActions);
        }

        maxIndex.current = propActions.length;
        return propActions;
    }

    useEffect(() => {

        handleNextButtonDisabled(false);
        if ( actionsList.length>0 && actionIndex===-1){
            setActionIndex(0);
            runTest(0, 0);
        }
        return () => {
            // Perform any cleanup logic here if needed
            // For example, you can cancel any ongoing asynchronous tasks or subscriptions
        };
    }, [actionsList])

    useEffect(() => {
    }, [actionIndex, maxIndex.current]);

    const startInterval = () => {
        intervalId.current = setInterval(() => {
            if (refProgress.current<100) {
                setProgress(refProgress.current + 0.2);
            }
        }, 100);
    }

    useEffect(() => {
        previousActionIndex.current = actionIndex;
        setProgress( ( 100 / maxIndex.current ) * (actionIndex));

        //ensure that progress does not get to 100 when retries are still running
        let currentAction = actionsList[actionIndex];
        if ( currentAction && currentAction.do==='retry' && attemptCount>1 ){
            setProgress(90);
        }

       }, [actionIndex ])

    useEffect (() => {
        refProgress.current = progress;
    },[progress])

    useEffect(() => {
        if ( refreshTests ){
            setRefreshTests(false);
            reset();
            actionsList.forEach(function(action,i){
                setActionsListProperty(i, 'status', 'inactive');
            });
        }
    }, [refreshTests ])

    const statuses = {
        'inactive': {
            'icon': 'circle-times',
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
    };

    const reset = () => {
        setSwitchButtonDisabled(true);
        // handleNextButtonDisabled(true);
        setActionsList(getActions());
        setProgress(0);
        refProgress.current = 0;
        setActionIndex(-1);
        previousActionIndex.current = -1;
     }

    const adjustActionsForDNS = (actions) => {
        //find verification_type
        let verification_type = getFieldValue('verification_type');
        if ( !verification_type ) verification_type = 'dir';

        if ( verification_type==='dns' ) {
            //check if dns verification already is added
            let dnsVerificationAdded = false;
            actions.forEach(function(action, i) {
                if (action.action==="verify_dns"){
                    dnsVerificationAdded = true;
                }
            });

            //find bundle index
            let create_bundle_index = -1;
            actions.forEach(function(action, i) {
                if (action.action==="create_bundle_or_renew"){
                    create_bundle_index = i;
                }
            });

            if (!dnsVerificationAdded && create_bundle_index>0) {
                //store create bundle action
                let actionsCopy = [...actions];
                let createBundleAction = actionsCopy[create_bundle_index];
                //overwrite create bundle action
                let newAction = {};
                newAction.action = 'verify_dns';
                newAction.description = __("Verifying DNS records...", "really-simple-ssl");
                newAction.attempts = 2;
                actionsCopy[create_bundle_index] = newAction;
                actionsCopy.push(createBundleAction);
                actions = actionsCopy;
            }
        }
        return actions;
    }

    const processTestResult = async (action, newActionIndex) => {
        // clearInterval(intervalId.current);

        if ( action.status==='success' ) {
            setAttemptCount(0);
        } else {
            if (!Number.isInteger(action.attemptCount)) {
                setAttemptCount(0);
            }
            //ensure attemptCount is an integer
            setAttemptCount( parseInt(attemptCount) + 1 );
        }

        //used for dns verification actions
        let event = new CustomEvent('rsssl_le_response', { detail: action });
        document.dispatchEvent(event);
        //if all tests are finished with success
        //finalize happens when halfway through our tests it's finished. We can skip all others.
        if ( action.do === 'finalize' ) {
            actionsList.forEach(function(action,i){
                if (i>newActionIndex) {
                    setActionsListProperty(i, 'hide', true);
                }
            });
            setActionIndex(maxIndex.current+1);
            setSwitchButtonDisabled(false);
            // handleNextButtonDisabled(false);
        } else if ( action.do === 'continue' || action.do === 'skip' ) {
            //new action, so reset the attempts count
            setAttemptCount(1);
            //skip:  drop previous completely, skip to next.
            if ( action.do === 'skip' ) {
                setActionsListProperty(newActionIndex, 'hide', true);
            }
            //move to next action, but not if we're already on the max
            if ( maxIndex.current-1 > newActionIndex) {
                setActionIndex(newActionIndex+1);
                await runTest(newActionIndex+1);
            } else {
                setActionIndex(newActionIndex+1);
                setSwitchButtonDisabled(false);
                // handleNextButtonDisabled(false);
            }
        } else if (action.do === 'retry' ) {
            if ( attemptCount >= action.attempts ) {
                setSwitchButtonDisabled(false);
                setActionIndex(maxIndex.current);
            } else {
                setSwitchButtonDisabled(false);
                setActionIndex(newActionIndex);
                await runTest(newActionIndex);
            }
        } else if ( action.do === 'stop' ){
            setSwitchButtonDisabled(false);
            setActionIndex(maxIndex.current);
        }
    }

    const runTest = async (newActionIndex) => {
        let currentAction = {...actionsList[newActionIndex]};
        if (!currentAction) return;
        let  test = currentAction.action;
        const startTime = new Date();
        await rsssl_api.runLetsEncryptTest(test, field.id ).then( ( response ) => {
            const endTime = new Date();
            let timeDiff = endTime - startTime; //in ms
            const elapsedTime = Math.round(timeDiff);
            currentAction.status = response.status ? response.status : 'inactive';
            currentAction.hide = false;
            currentAction.description = response.message;
            currentAction.do = response.action;
            currentAction.output = response.output ? response.output : false;
            sleep.current = 500;
            if (elapsedTime<1500) {
               sleep.current = 1500-elapsedTime;
            }
            setActionsListItem(newActionIndex, currentAction);
        }).then(sleeper(sleep.current)).then( () => {
            processTestResult(currentAction, newActionIndex);
      });
    }

    const getStyles = (newProgress) => {
        return Object.assign(
            {},
            {width: newProgress+"%"},
        );
    }

    const getStatusIcon = (action) => {
        if (!statuses.hasOwnProperty(action.status)) {
            return statuses['inactive'].icon;
        }
        return statuses[action.status].icon
    }

    const getStatusColor = (action) => {
        if (!statuses.hasOwnProperty(action.status)) {
            return statuses['inactive'].color;
        }
        return statuses[action.status].color;
    }

    if ( !field.actions ) {
        return (<></>);
    }

    let progressCopy = progress;
    if (maxIndex.current === actionIndex+1 ){
        progressCopy = 100;
    }

    //filter out skipped actions
    let actionsOutput = actionsList.filter(action => action.hide !== true);
    //ensure the sub components have an action to look at, also if the action has been dropped after last test.
    let action = actionsList[actionIndex];
    if (action){
        lastAction.current = action;
    } else {
        action = lastAction.current;
    }
    let progressBarColor = action.status==='error' ? 'rsssl-orange' : '';
    return (
        <>
            <div className="rsssl-lets-encrypt-tests">
                <div className="rsssl-progress-bar"><div className="rsssl-progress"><div className={'rsssl-bar ' + progressBarColor} style={getStyles(progressCopy)}></div></div></div>
                <div className="rsssl_letsencrypt_container rsssl-progress-container field-group">
                    <ul>
                       {actionsOutput.map((action, i) =>
                              <li key={"action-"+i}>
                                  <Icon name = {getStatusIcon(action)} color = {getStatusColor(action)} />
                                        {action.do==='retry' && attemptCount >=1 && <>{__("Attempt %s.", "really-simple-ssl").replace('%s', attemptCount)} </>}
                                        &nbsp;
                                        <span dangerouslySetInnerHTML={{__html: DOMPurify.sanitize(action.description) }}></span> {/* nosemgrep: react-dangerouslysetinnerhtml */}
                                    </li>

                            )
                        }
                    </ul>
                </div>
                {field.id === 'directories' && <Directories field={field} action={action}/> }
                {field.id === 'dns-verification' && <DnsVerification field={field} action={action}/> }
                {field.id === 'generation' && <Generation field={field} action={action}/> }
                {field.id === 'installation' && <Installation field={field} action={action}/> }
                {field.id === 'activate' && <Activate field={field} action={action}/> }
            </div>
        </>
    )
}

export default LetsEncrypt;