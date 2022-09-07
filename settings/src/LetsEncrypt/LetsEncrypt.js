import {useState, useEffect} from "@wordpress/element";
import * as rsssl_api from "../utils/api";
import sleeper from "../utils/sleeper";
import Directories from "./Directories";
import DnsVerification from "./DnsVerification";
import { __ } from '@wordpress/i18n';
import {useUpdateEffect} from 'react-use';

const LetsEncrypt = (props) => {
    const [actionIndex, setActionIndex] = useState(0);
    const [id, setId] = useState(props.field.id);
    const [lastActionStatus, setLastActionStatus] = useState('');
    const [progress, setProgress] = useState(0);
    const [previousActionIndex, setPreviousActionIndex] = useState(-1);
    const [previousProgress, setPreviousProgress] = useState(0);

    let sleep=100;
    let maxAttempts=1;
    let rsssl_interval;

    useEffect(() => {
        props.handleNextButtonDisabled(true);
        runTest(0, progress);
        rsssl_interval = setInterval(() => setProgress((progress) => progress + 0.2), 100);
       }, [])

    const stop_progress = ( status ) => {
        clearInterval(rsssl_interval);
    }

    const getAction = (actionIndex) => {
        let newActions = props.field.actions;
        return newActions[actionIndex];
    }

    useUpdateEffect(()=> {
        if (actionIndex>previousActionIndex) {
            setPreviousActionIndex(actionIndex);
            setProgress( ((100 / props.field.actions.length) * actionIndex) );
        }
        setPreviousProgress(progress);

        //ensure that progress does not get to 100 when retries are still running
        let currentAction = getAction(actionIndex);
        if (currentAction && currentAction.do==='retry' && currentAction.attemptCount>1 ){
            setProgress(90);
        }
    })

    const adjustActionsForDNS = (actions) => {
        //find verification_type
        let verification_type='dir';
        for (const fieldItem of props.fields){
            if (fieldItem.id === 'verification_type' ){
                verification_type = fieldItem.value;
            }
        }

        if (verification_type==='dns') {
            //add new actions for the generation step


        //     $index = array_search( 'generation', array_column( $steps['lets-encrypt'], 'id' ) );
        //     $index ++;
        //     $steps['lets-encrypt'][ $index ]['actions'] = array (
        //         array(
        //             'description' => __("Verifying DNS records...", "really-simple-ssl"),
        //         'action'=> 'verify_dns',
        //         'attempts' => 2,
        //         'speed' => 'slow',
        // ),
        //     array(
        //         'description' => __("Generating SSL certificate...", "really-simple-ssl"),
        //         'action'=> 'create_bundle_or_renew',
        //         'attempts' => 4,
        //         'speed' => 'slow',
        // )
        // );
        }

        return actions;
    }

    const processTestResult = (currentActionIndex) => {
         let action = getAction(currentActionIndex);
        setLastActionStatus(action.status);
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

        if ( action.do === 'finalize' ) {
            //finalize
            setActionIndex(actions.length);

        } else if (action.do === 'continue' || action.do === 'skip' ) {
            //new action, so reset the attempts count
            action.attemptCount=1;
            //skip:  drop previous completely, skip to next.
            if ( action.do === 'skip' ) {
                action.hide = true;
            }
            //move to next action
            setActionIndex(currentActionIndex+1);
            if ( currentActionIndex+1===props.field.actions.length ) {
                props.handleNextButtonDisabled(false);
                setActionIndex(props.field.actions.length);
                clearInterval(rsssl_interval);
            } else {
                runTest(currentActionIndex+1);
            }
        } else if (action.do === 'retry' ) {
            if ( action.attemptCount >= maxAttempts ) {
                setActionIndex(props.field.actions.length);
                clearInterval(rsssl_interval);
            } else {
                // clearInterval(rsssl_interval);
                runTest(currentActionIndex);
            }
        } else if ( action.do === 'stop' ){
            clearInterval(rsssl_interval);
        }

    }

    const runTest = (currentActionIndex) => {
        console.log(props.field);
        if (props.field.id==='generation') {
            props.field.actions = adjustActionsForDNS(props.field.actions);
        }
        const startTime = new Date();
        let action = getAction(currentActionIndex);
        let test = action.action;
        console.log("run test "+test);
        maxAttempts = action.attempts;
        rsssl_api.runLetsEncryptTest(test, props.field.id ).then( ( response ) => {
                const endTime = new Date();
                let timeDiff = endTime - startTime; //in ms
                const elapsedTime = Math.round(timeDiff);
                let action = getAction(currentActionIndex);
                action.status = response.data.status;
                action.description = response.data.message;
                action.do = response.data.action;
                action.output = response.data.output ? response.data.output : false;
                sleep = 100;
                if (elapsedTime<600) {
                    //sleep = 600-elapsedTime;
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
    return (
        <>
            <div className="rsssl-lets-encrypt-tests">
                <div className="rsssl-progress-bar"><div className="rsssl-progress"><div className={'rsssl-bar ' + progressBarColor} style={getStyles()}></div></div></div>
                <div className="rsssl_letsencrypt_container rsssl-progress-container field-group">
                    <ul>
`                       {props.field.actions.map((action, i) =>
                            <li key={i} className={"rsssl_action_"+action.action+" rsssl-"+action.status} >
                                {action.do==='retry' && action.attemptCount >=1 && <>{__("Attempt %s.", "really-simple-ssl").replace('%s', action.attemptCount)} </>}
                                <span dangerouslySetInnerHTML={{__html:action.description}}></span>
                            </li>)
                        }
                    </ul>
                </div>
                {props.field.id === 'directories' && <Directories save={props.save} selectMenu={props.selectMenu} field={props.field} updateField={props.updateField} addHelp={props.addHelp} progress={progress} action={props.field.actions[actionIndex]}/> }
                {props.field.id === 'dns-verification' && <DnsVerification save={props.save} selectMenu={props.selectMenu} field={props.field} updateField={props.updateField} addHelp={props.addHelp} progress={progress} action={props.field.actions[actionIndex]}/> }
            </div>
        </>
    )
}

export default LetsEncrypt;