import Icon from "../utils/Icon";
import {__} from "@wordpress/i18n";
import {Button, Modal} from "@wordpress/components";

import {useState} from '@wordpress/element';
import Runner from "./RiskConfiguration/Runner";
import useRunnerData from "./RiskConfiguration/RunnerData";


const VulnerabilitiesIntro = (props) => {
    //first we define a state for the steps


    function goToDashboard() {
        props.onClose();
        //We fire a save event to change an option

    }

    return (
        <>
            <Modal
                title={__('Introducing vulnerabilities', 'really-simple-ssl')}
                className="rsssl-modal"
                onRequestClose={props.onClose}
                shouldCloseOnClickOutside={false}
                shouldCloseOnEsc={false}
                overlayClassName="rsssl-modal-overlay"
            >
                <div className="rsssl-header-extension">
                    <div>
                        <p>
                            {__("orem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.", "really-simple-ssl")}
                        </p>
                        <img className="rsssl-intro-logo"
                             src={'/wp-content/plugins/really-simple-ssl/assets/img/really-simple-ssl-intro.svg'}>

                        </img>
                    </div>
                </div>
                <div className="rsssl-ssl-intro-container">
                    <Runner
                        title={__("Downloading files", "really-simple-ssl")}
                        loading={true}
                        time={1000}
                        delay={1000}
                    />
                    <Runner
                        title={__("Scanning Plugins, themes and core", "really-simple-ssl")}
                        loading={true}
                        time={2000}
                        delay={1000}
                    />
                    <Runner
                        title={__("Scanning Components", "really-simple-ssl")}
                        loading={true}
                        time={1000}
                        delay={2000}
                    />
                    <Runner
                        title={__("Returning results", "really-simple-ssl")}
                        loading={true}
                        time={1000}
                        delay={3000}
                    />
                </div>
                <div className={'rsssl-modal-footer'}>
                    <Button isPrimary onClick={props.onClose}>
                        {__('DashBoard', 'really-simple-ssl')}
                    </Button>
                    <Button isSecondary onClick={props.onClose}>
                        {__('Dismiss', 'really-simple-ssl')}
                    </Button>
                </div>
            </Modal>
        </>
    )
}

export default VulnerabilitiesIntro;
