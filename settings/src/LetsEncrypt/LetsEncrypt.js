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
    const {fields, handleNextButtonDisabled, getFieldValue} = useFields();
    const {maxIndex, attemptCount, setAttemptCount, actionIndex, setActionIndex, progress, setProgress, maxAttempts, setMaxAttempts, action, setAction, actions, setActions, refreshTests, setRefreshTests, updateActionProperty} = useLetsEncryptData();
    const sleep = useRef(1500);
    const intervalId = useRef(false);
    const lastActionStatus = useRef('');
    const previousActionIndex = useRef(-1);

    useEffect(() => {
        handleNextButtonDisabled(true);
        setActions(props.field.actions);
        if ( props.field.id==='generation' ) {
            setActions(adjustActionsForDNS(actions));
        }
   }, [])

    useEffect(() => {
        if ( !action && actions.length>0){
            console.log("no action, set first")
            console.log(actions);
            setAction(actions[0]);
            setActionIndex(0);
        }
    }, [actions])

    useEffect(() => {
        console.log("action update triggered");
        console.log(action);
        console.log("actionIndex");
        console.log(actionIndex);
        console.log("maxIndex");
        console.log(maxIndex);
        if ( actionIndex<actions.length-1 ) {
            console.log("run test as it")
            runTest();
        }

        intervalId.current = setInterval(() => setProgress((progress) => progress + 0.2), 100);
    }, [action])

    useEffect(() => {
        if (actionIndex>previousActionIndex.current) {
            previousActionIndex.current = actionIndex;
            setProgress( ( 100 / maxIndex ) * actionIndex);
        }

        //ensure that progress does not get to 100 when retries are still running
        setAction(actions[actionIndex+1]);
        if ( action && action.do==='retry' && attemptCount>1 ){
            setProgress(90);
        }

       }, [actionIndex, refreshTests ])

    // useEffect(() => {
    //     if ( refreshTests ){
    //         setRefreshTests(false);
    //         restartTests();
    //     }
    // }, [refreshTests ])

    const restartTests = () => {
        // //clear statuses to ensure the bullets are grey
        // // actions.forEach(function (action, i) {
        // //     updateActionProperty(i, 'status', 'inactive');
        // // });
        // setActions(props.field.actions);
        // setActionIndex(0);
        // previousActionIndex.current =-1;
        // lastActionStatus.current = '';
        // setProgress(0);
        // runTest();
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
        lastActionStatus.current = action.status;
        if (action.status==='success') {
            setAttemptCount(0);
        } else {
            if (!Number.isInteger(action.attemptCount)) {
                setAttemptCount(0);
            }
            setAttemptCount(attemptCount+1);
        }

        //used for dns verification actions
        var event = new CustomEvent('rsssl_le_response', { detail: action });
        document.dispatchEvent(event);
        //if all tests are finished with success

        //finalize happens when halfway through our tests it's finished. We can skip all others.
        if ( action.do === 'finalize' ) {
            clearInterval(intervalId.current);
            actions.forEach(function(action,i){
                if (i>actionIndex) {
                    updateActionProperty(i, 'hide', true);
                }
            });
            console.log("finalize, set index to "+maxIndex);

            setActionIndex(maxIndex);
            handleNextButtonDisabled(false);
        } else if ( action.do === 'continue' || action.do === 'skip' ) {
            //new action, so reset the attempts count
            setAttemptCount(1);
            //skip:  drop previous completely, skip to next.
            if ( action.do === 'skip' ) {
                updateActionProperty(actionIndex, 'hide', true);
            }
            //move to next action, but not if we're already on the max
            if ( maxIndex > actionIndex) {
                console.log("next, set index to "+maxIndex);

                setActionIndex(actionIndex+1);
                runTest();
            } else {
                console.log("set index to "+maxIndex);
                setActionIndex(maxIndex);
                handleNextButtonDisabled(false);
                clearInterval(intervalId.current);
            }
        } else if (action.do === 'retry' ) {
            if ( attemptCount >= maxAttempts ) {
                console.log("to many attempts, set index "+maxIndex);
                setActionIndex(maxIndex);
                clearInterval(intervalId.current);
            } else {
                // clearInterval(intervalId.current);
                console.log("still attempts left, try again ");
                console.log("attemptCount")
                console.log(attemptCount)
                console.log("maxIndex")
                console.log(maxIndex)
                runTest();
            }
        } else if ( action.do === 'stop' ){
            clearInterval(intervalId.current);
        }
    }

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

    const runTest = () => {
        if (!action) return;
        let  test = action.action;
        const startTime = new Date();
        setMaxAttempts( action.attempts );
        rsssl_api.runLetsEncryptTest(test, props.field.id ).then( ( response ) => {
            const endTime = new Date();
            let timeDiff = endTime - startTime; //in ms
            const elapsedTime = Math.round(timeDiff);
            action.status = response.status ? response.status : 'inactive';
            action.hide = false;
            action.description = response.message;
            action.do = response.action;
            action.output = response.output ? response.output : false;
            sleep.current = 500;
            if (elapsedTime<1500) {
               sleep.current = 1500-elapsedTime;
            }
        }).then(sleeper(sleep.current)).then(() => {
            processTestResult(action);
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
    let actionsOutput = actions.filter(action => action.hide !== true);

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
                {props.field.id === 'directories' && <Directories field={props.field} /> }
                {props.field.id === 'dns-verification' && <DnsVerification field={props.field} /> }
                {props.field.id === 'generation' && <Generation field={props.field} /> }
                {props.field.id === 'installation' && <Installation field={props.field} /> }
                {props.field.id === 'activate' && <Activate field={props.field} /> }
            </div>
        </>
    )
}

export default LetsEncrypt;