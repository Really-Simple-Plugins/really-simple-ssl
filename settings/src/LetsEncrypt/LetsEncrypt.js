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

const LetsEncrypt = (props) => {
    const {handleNextButtonDisabled, getFieldValue} = useFields();
    const {attemptCount, setAttemptCount, progress, setProgress, maxAttempts, setMaxAttempts, updateAction, refreshTests, setRefreshTests, updateActionProperty} = useLetsEncryptData();
    const sleep = useRef(1500);
    const intervalId = useRef(false);
    const lastActionStatus = useRef('');
    const actionIndex = useRef(0);
    const action = useRef(false);
    const previousActionIndex = useRef(-1);
    const startedTests = useRef([]);
    const actionsList = useRef([]);
    const maxIndex = useRef(1);

    useEffect(() => {
        reset();
   }, [props.field])

    const getActions = () => {
        let propActions = props.field.actions;
        if ( props.field.id==='generation' ) {
            propActions = adjustActionsForDNS(propActions);
        }

        maxIndex.current = propActions.length;
        return propActions;
    }

    useEffect(() => {
        if ( !action.current && actionsList.length>0){
            // action.current = actions[0];
            actionIndex.current = 0;
        }
    }, [actionsList.current])

    useEffect(() => {
        if ( actionIndex.current===0 ) {
            runTest();
        }
    }, [action.current])

    useEffect(() => {
        action.current = actionsList.current[actionIndex.current];
        console.log("set interval ")
        intervalId.current = setInterval(() => {
            console.log("update progress");
            setProgress((progress) => progress + 0.2)
        }, 100);
    }, [actionIndex.current])

    useEffect(() => {
        if ( actionIndex.current>previousActionIndex.current ) {
            previousActionIndex.current = actionIndex.current;
            setProgress( ( 100 / maxIndex.current ) * (actionIndex.current));
        }

        //ensure that progress does not get to 100 when retries are still running
        action.current = actionsList.current[actionIndex.current];
        if ( action.current && action.current.do==='retry' && attemptCount>1 ){
            setProgress(90);
        }

       }, [actionIndex.current, refreshTests ])


    useEffect(() => {
        if ( refreshTests ){
            setRefreshTests(false);
            reset();
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
        handleNextButtonDisabled(true);
        actionsList.current = getActions();
        actionsList.current.forEach(function(action,i){
            updateActionProperty(i, 'status', 'inactive');
        });
        startedTests.current = [];
        actionIndex.current = 0;
        action.current = false;
        previousActionIndex.current = -1;
        lastActionStatus.current = '';
        setProgress(0);
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
                let createBundleAction = actions[create_bundle_index];
                //overwrite create bundle action
                let newAction = {};
                newAction.action = 'verify_dns';
                newAction.description = __("Verifying DNS records...", "really-simple-ssl");
                newAction.attempts = 2;
                actions[create_bundle_index] = newAction;
                actions.push(createBundleAction);
            }
        }
        return actions;
    }

    const processTestResult = (action) => {
        console.log( "process test result" );
        console.log( action );
        lastActionStatus.current = action.status;
        if ( action.status==='success' ) {
            setAttemptCount(0);
        } else {
            if (!Number.isInteger(action.attemptCount)) {
                setAttemptCount(0);
            }
            setAttemptCount(attemptCount+1);
        }

        //used for dns verification actions
        let event = new CustomEvent('rsssl_le_response', { detail: action });
        document.dispatchEvent(event);
        //if all tests are finished with success

        //finalize happens when halfway through our tests it's finished. We can skip all others.
        if ( action.do === 'finalize' ) {
            clearInterval(intervalId.current);
            actionsList.current.forEach(function(action,i){
                if (i>actionIndex.current) {
                    updateActionProperty(i, 'hide', true);
                }
            });
            console.log("finalize, set index to max index +1 "+maxIndex.current);

            actionIndex.current = maxIndex.current+1;
            handleNextButtonDisabled(false);
        } else if ( action.do === 'continue' || action.do === 'skip' ) {
            //new action, so reset the attempts count
            setAttemptCount(1);
            //skip:  drop previous completely, skip to next.
            if ( action.do === 'skip' ) {
                actionsList.current[actionIndex.current]['hide'] = true;
            }
            //move to next action, but not if we're already on the max
            if ( maxIndex.current-1 > actionIndex.current) {
                let next =actionIndex.current+1;
                console.log("next, set index to "+ next);
                actionIndex.current = actionIndex.current+1;
                runTest();
            } else {
                console.log("max index "+maxIndex.current+" < or = "+actionIndex.current);
                console.log("set index to max index "+maxIndex.current);
                console.log("set button disabled to false ");
                handleNextButtonDisabled(false);
                actionIndex.current = actionIndex.current+1;

                clearInterval(intervalId.current);
            }
        } else if (action.do === 'retry' ) {
            if ( attemptCount >= maxAttempts ) {
                console.log("to many attempts, set index "+maxIndex.current);
                actionIndex.current = maxIndex.current;
                clearInterval(intervalId.current);
            } else {
                // clearInterval(intervalId.current);
                runTest();
            }
        } else if ( action.do === 'stop' ){
            console.log("stop, set index to max index  "+maxIndex.current);

            actionIndex.current = maxIndex.current;
            clearInterval(intervalId.current);
        }
    }

    const runTest = () => {

        let currentAction = {...actionsList.current[actionIndex.current]};
        if (!currentAction) return;
        let  test = currentAction.action;

        if (startedTests.current.includes(test)) {
            return;
        }
        startedTests.current.push(test);

        const startTime = new Date();
        setMaxAttempts( currentAction.attempts );
        rsssl_api.runLetsEncryptTest(test, props.field.id ).then( ( response ) => {
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
            action.current = currentAction;
            actionsList.current[actionIndex.current] = currentAction;
        }).then(sleeper(sleep.current)).then(() => {
            processTestResult(currentAction);
      });

    }

    const getStyles = () => {
        return Object.assign(
            {},
            {width: progress+"%"},
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

    let progressBarColor = lastActionStatus.current ==='error' ? 'rsssl-orange' : '';
    if ( !props.field.actions ) {
        return (<></>);
    }

    //filter out skipped actions
    let actionsOutput = actionsList.current.filter(action => action.hide !== true);

    return (
        <>
            <div className="rsssl-lets-encrypt-tests">
                <div className="rsssl-progress-bar"><div className="rsssl-progress"><div className={'rsssl-bar ' + progressBarColor} style={getStyles()}></div></div></div>
                <div className="rsssl_letsencrypt_container rsssl-progress-container field-group">
                    <ul>
                       {actionsOutput.map((action, i) =>
                              <li key={i}>
                                  <Icon name = {getStatusIcon(action)} color = {getStatusColor(action)} />
                                        {action.do==='retry' && attemptCount >=1 && <>{__("Attempt %s.", "really-simple-ssl").replace('%s', attemptCount)} </>}
                                        <span dangerouslySetInnerHTML={{__html:action.description}}></span>
                                    </li>

                            )
                        }
                    </ul>
                </div>
                {props.field.id === 'directories' && <Directories field={props.field} action={actionsList.current[actionIndex.current]}/> }
                {props.field.id === 'dns-verification' && <DnsVerification field={props.field} action={actionsList.current[actionIndex.current]}/> }
                {props.field.id === 'generation' && <Generation field={props.field} action={actionsList.current[actionIndex.current]}/> }
                {props.field.id === 'installation' && <Installation field={props.field} action={actionsList.current[actionIndex.current]}/> }
                {props.field.id === 'activate' && <Activate field={props.field} action={actionsList.current[actionIndex.current]}/> }
            </div>
        </>
    )
}

export default LetsEncrypt;