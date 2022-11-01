import {useState, useEffect} from "@wordpress/element";
import * as rsssl_api from "../utils/api";
import Onboarding from "./Onboarding";
import update from 'immutability-helper';
import {useUpdateEffect} from 'react-use';
import Placeholder from '../Placeholder/Placeholder';
import { __ } from '@wordpress/i18n';
import Icon from "../utils/Icon";
const OnboardingModal = (props) => {
    const [modalLoaded, setModalLoaded] = useState(false);
    useEffect(() => {
        if ( !modalLoaded ) {
            rsssl_api.runTest('get_modal_status' ).then( ( response ) => {
                setModalLoaded(true);
                props.setShowOnBoardingModal(!response.data.dismissed);
            });
        }
    });

    useUpdateEffect(()=> {
        if (props.showOnBoardingModal===true) {
            let data={};
            data.dismiss = false;
            rsssl_api.runTest('dismiss_modal', 'refresh', data).then(( response ) => {});
        }
    });

    const dismissModal = () => {
        let data={};
        data.dismiss = true;
        props.setShowOnBoardingModal(false)
        rsssl_api.runTest('dismiss_modal', 'refresh', data).then(( response ) => { });
    }
    return (
        <>
            { (props.showOnBoardingModal) && <>
                <div className="rsssl-modal-backdrop">&nbsp;</div>
                <div className="rsssl-modal rsssl-onboarding">
                    <div className="rsssl-modal-header">
                      <img className="rsssl-logo"
                           src={rsssl_settings.plugin_url + 'assets/img/really-simple-ssl-logo.svg'}
                           alt="Really Simple SSL logo"/>
                        <button type="button" className="rsssl-modal-close" data-dismiss="modal" aria-label="Close" onClick={ dismissModal }>
                            <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" height="24" >
                                <path fill="#000000" d="M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"/>
                            </svg>
                        </button>
                    </div>

                    <div className="rsssl-modal-content">
                        { !props.isAPILoaded &&
                             <>
                                <ul>
                                    <li><Icon name = "file-download" color = 'orange' />{__("Please wait while we detect your setup", "really-simple-ssl")}</li>
                                </ul>
                                <Placeholder lines="10"></Placeholder>
                            </>
                         }
                        { props.isAPILoaded &&
                            <Onboarding
                                getFields={props.getFields}
                                updateField={props.updateField}
                                selectMainMenu={props.selectMainMenu}
                                isModal={true}
                                dismissModal={dismissModal}/>
                                }
                    </div>

                    <div className="rssl-modal-footer"/>
                </div>
            </> }
        </>
    )
}

export default OnboardingModal;