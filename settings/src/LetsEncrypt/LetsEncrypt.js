import {useState, useEffect} from "@wordpress/element";
import * as rsssl_api from "../utils/api";
import sleeper from "../utils/sleeper";
import Directories from "./Directories";
import DnsVerification from "./DnsVerification";
import Generation from "./Generation";
import Activate from "./Activate";
import Installation from "./Installation";
import { __ } from '@wordpress/i18n';
import {useUpdateEffect} from 'react-use';
import Icon from "../utils/Icon";

const LetsEncrypt = (props) => {
    const [actionIndex, setActionIndex] = useState(0);
    const [id, setId] = useState(props.field.id);
    const [lastActionStatus, setLastActionStatus] = useState('');
    const [progress, setProgress] = useState(0);
    const [previousActionIndex, setPreviousActionIndex] = useState(-1);
    const [previousProgress, setPreviousProgress] = useState(0);

    let sleep=500;
    let maxAttempts=1;
    let rsssl_interval;

    useEffect(() => {
        props.handleNextButtonDisabled(true);
        runTest(0);
        rsssl_interval = setInterval(() => setProgress((progress) => progress + 0.2), 100);
       }, [])

    const restartTests = () => {
        //clear statuses to ensure the bullets are grey
        let actions = props.field.actions;
        for ( const action of actions) {
            action.status='inactive';
        }
        props.field.actions = actions;

        runTest(0);
        setActionIndex(0);
        setPreviousActionIndex(-1);
        setLastActionStatus('');
        setPreviousProgress(0);
        setProgress(0);
     }

    const getAction = (actionIndex) => {
        let newActions = props.field.actions;
        return newActions[actionIndex];
    }

    useUpdateEffect(()=> {
        let maxIndex = props.field.actions.length-1;
        if (actionIndex>previousActionIndex) {
            setPreviousActionIndex(actionIndex);
            setProgress( ( 100 / maxIndex ) * actionIndex );
        }
        setPreviousProgress(progress);

        //ensure that progress does not get to 100 when retries are still running
        let currentAction = getAction(actionIndex);
        if ( currentAction && currentAction.do==='retry' && currentAction.attemptCount>1 ){
            setProgress(90);
        }
        if (props.refreshTests){
            props.resetRefreshTests();
            restartTests();
        }
    })

    const adjustActionsForDNS = (actions) => {
        //find verification_type
        let verification_type = props.getFieldValue('verification_type');
        if ( !verification_type ) verification_type = 'dir';


        if ( verification_type==='dns' ) {
            //find bundle index
            let create_bundle_index = -1;
            actions.forEach(function(action, i) {
                if (action.action==="create_bundle_or_renew"){
                    create_bundle_index = i;
                }
            });
            if (create_bundle_index>0) {
                //overwrite create bundle action
                let newAction = {};
                newAction.action = 'verify_dns';
                newAction.description = __("Verifying DNS records...", "really-simple-ssl");
                newAction.attempts = 2;
                actions[create_bundle_index] = newAction;

                //add create bundle at end
                newAction = {};
                newAction.action = '"create_bundle_or_renew"';
                newAction.description = __("Generating SSL certificate...", "really-simple-ssl");
                newAction.attempts = 4;
                actions.push(newAction);
            }

        }

        return actions;
    }

    const processTestResult = (currentActionIndex) => {
         let action = getAction(currentActionIndex);
        setLastActionStatus(action.status);
        let maxIndex = props.field.actions.length-1;
        if (action.status==='success') {
            action.attemptCount = 0;
        } else {
            if (!Number.isInteger(action.attemptCount)) {
                action.attemptCount = 0;
            }
            action.attemptCount +=1;
        }
        //used for dns verification actions
        var event = new CustomEvent('rsssl_le_response', { detail: action });
        document.dispatchEvent(event);
        //if all tests are finished with success

        //finalize happens when halfway through our tests it's finished. We can skip all others.
        if ( action.do === 'finalize' ) {
            clearInterval(rsssl_interval);
            props.field.actions.forEach(function(action,i){
                if (i>currentActionIndex) {
                    action.hide=true;
                }
            });
            setActionIndex(maxIndex);
            props.handleNextButtonDisabled(false);
        } else if (action.do === 'continue' || action.do === 'skip' ) {
            //new action, so reset the attempts count
            action.attemptCount=1;
            //skip:  drop previous completely, skip to next.
            if ( action.do === 'skip' ) {
                action.hide = true;
            }
            //move to next action, but not if we're already on the max
            if ( maxIndex > currentActionIndex ) {
                setActionIndex(currentActionIndex+1);
                runTest(currentActionIndex+1);
            } else {
                setActionIndex(maxIndex);
                props.handleNextButtonDisabled(false);
                clearInterval(rsssl_interval);
            }
        } else if (action.do === 'retry' ) {
            if ( action.attemptCount >= maxAttempts ) {
                setActionIndex(maxIndex);
                clearInterval(rsssl_interval);
            } else {
                // clearInterval(rsssl_interval);
                runTest(currentActionIndex);
            }
        } else if ( action.do === 'stop' ){
            clearInterval(rsssl_interval);
        }

    }

    const runTest = (currentActionIndex ) => {

        if (props.field.id==='generation') {
            props.field.actions = adjustActionsForDNS(props.field.actions);
        }
        const startTime = new Date();
        let action = getAction(currentActionIndex);
        let test = action.action;
        maxAttempts = action.attempts;
        rsssl_api.runLetsEncryptTest(test, props.field.id ).then( ( response ) => {
                const endTime = new Date();
                let timeDiff = endTime - startTime; //in ms
                const elapsedTime = Math.round(timeDiff);
                let action = getAction(currentActionIndex);
                action.status = response.data.status ? response.data.status : 'inactive';
                action.hide = false;
                action.description = response.data.message;
                action.do = response.data.action;
                action.output = response.data.output ? response.data.output : false;
                sleep = 500;
                if (elapsedTime<1000) {
                   sleep = 1000-elapsedTime;
                }
            }).then(sleeper(sleep)).then(() => {
              processTestResult(currentActionIndex);
          });
    }

    const getStyles = () => {
        return Object.assign(
            {},
            {width: progress+"%"},
        );
    }

    let progressBarColor = lastActionStatus==='error' ? 'rsssl-orange' : '';
    if (!props.field.actions) {
        return (<></>);
    }
    // keep current action, before it is filtered. The actionindex doesn't match anymore after filtering
    let currentAction = props.field.actions[actionIndex];
    //filter out skipped actions
    let actions = props.field.actions.filter(action => action.hide !== true);
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

    return (
        <>
            <div className="rsssl-lets-encrypt-tests">
                <div className="rsssl-progress-bar"><div className="rsssl-progress"><div className={'rsssl-bar ' + progressBarColor} style={getStyles()}></div></div></div>
                <div className="rsssl_letsencrypt_container rsssl-progress-container field-group">
                    <ul>
                       {actions.map((action, i) =>
                              <li key={i}>
                                  <Icon name = {statuses[action.status].icon} color = {statuses[action.status].color} />
                                        {action.do==='retry' && action.attemptCount >=1 && <>{__("Attempt %s.", "really-simple-ssl").replace('%s', action.attemptCount)} </>}
                                        <span dangerouslySetInnerHTML={{__html:action.description}}></span>
                                    </li>

                            )
                        }
                    </ul>
                </div>
                {props.field.id === 'directories' && <Directories save={props.save} selectMenu={props.selectMenu} field={props.field} updateField={props.updateField} addHelp={props.addHelp} progress={progress} action={currentAction}/> }
                {props.field.id === 'dns-verification' && <DnsVerification save={props.save} selectMenu={props.selectMenu} field={props.field} updateField={props.updateField} addHelp={props.addHelp} progress={progress} action={currentAction}/> }
                {props.field.id === 'generation' && <Generation restartTests={restartTests} save={props.save} selectMenu={props.selectMenu} field={props.field} updateField={props.updateField} addHelp={props.addHelp} progress={progress} action={currentAction}/> }
                {props.field.id === 'installation' && <Installation restartTests={restartTests} save={props.save} selectMenu={props.selectMenu} field={props.field} updateField={props.updateField} addHelp={props.addHelp} progress={progress} action={currentAction}/> }
                {props.field.id === 'activate' && <Activate restartTests={restartTests} save={props.save} selectMainMenu={props.selectMainMenu} selectMenu={props.selectMenu} field={props.field} updateField={props.updateField} addHelp={props.addHelp} progress={progress} action={currentAction}/> }
            </div>
        </>
    )
}

export default LetsEncrypt;