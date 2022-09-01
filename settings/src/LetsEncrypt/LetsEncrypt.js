import {useState, useEffect} from "@wordpress/element";
import { Button, ToggleControl } from '@wordpress/components';
import * as rsssl_api from "../utils/api";
import { __ } from '@wordpress/i18n';
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';

const LetsEncrypt = (props) => {
    const starttime = new Date();
    let endTime;
    const [actionIndex, setActionIndex] = useState(0);
    const [responseArray, setResponseArray] = useState([]);
    const elapsedTime = () => {
        endTime = new Date();
        var timeDiff = endTime - startTime; //in ms
        return Math.round(timeDiff);
    }

    useUpdateEffect(()=> {
//         // do componentDidUpdate logic
//         if ( networkProgress<100 && networkwide && networkActivationStatus==='main_site_activated' ){
//             rsssl_api.activateSSLNetworkwide().then((response) => {
//                if (response.data.success) {
//                     setNetworkProgress(response.data.progress);
//                     if (response.data.progress>=100) {
//                         updateActionForItem('ssl_enabled', '', 'success');
//                     }
//                 }
//             });
//
//         }
    })

    useEffect(() => {
        let actions = props.field.actions;
        let test = actions[actionIndex].action;
        let state = 'letsencrypt';//the state tells the lets encrypt module to load
        rsssl_api.runLetsEncryptTest(test).then( ( response ) => {
            console.log(response);
//             let elapsedTime = elapsedTime();
//             if (elapsedTime<1000) {
//                 rsssl_sleep(1000-elapsedTime);
//             }
//             responseArray[actionIndex] =
        });
    }, [])



    return (
        <>
            <div className="rsssl-progress-bar"><div className="rsssl-progress"><div className="rsssl-bar rsssl-orange" ></div></div></div>
            <div className="rsssl_letsencrypt_container rsssl-progress-container field-group">
                <ul>
                {props.field.actions.map((action, i) => <li key={i}><a className={"rsssl_action_"+action.action} >{action.description}</a></li>)}
                </ul>

            </div>
        </>
    )
}

export default LetsEncrypt;