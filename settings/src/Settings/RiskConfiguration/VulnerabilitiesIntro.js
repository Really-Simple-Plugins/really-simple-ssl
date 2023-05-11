import {__} from "@wordpress/i18n";
import {Button, Modal} from "@wordpress/components";
import {useState,useEffect} from '@wordpress/element';
import Runner from "./Runner";
import useRunnerData from "./RunnerData";

const VulnerabilitiesIntro = () => {
    //first we define a state for the steps
    const [ isClosed, setClosed ] = useState( false );
    const [ disabled, setDisabled ] = useState( true );
    const {step} = useRunnerData();

    useEffect (() => {
        if (step===4) {
            setDisabled(false);
        }
    },[step])

    const closeOnX = () => {
        if (!disabled) {
            setClosed(true);
        }
    }
    //this function closes the modal when onClick is activated
    if(!isClosed) {
        return (
            <>
                <Modal
                    title={__('Introducing vulnerabilities', 'really-simple-ssl')}
                    className="rsssl-modal"
                    onRequestClose={() => closeOnX()}
                    shouldCloseOnClickOutside={true}
                    shouldCloseOnEsc={true}
                    overlayClassName="rsssl-modal-overlay"
                >
                    <div className="rsssl-header-extension">
                        <div>
                            <p>
                                {__("You have enabled vulnerability detection! Really Simple SSL will check your plugins, themes and WordPress core daily and report if any known vulnerabilities are found.", "really-simple-ssl")}
                            </p>
                            <img className="rsssl-intro-logo"
                                 src={rsssl_settings.plugin_url+'/assets/img/really-simple-ssl-intro.svg'}>

                            </img>
                        </div>
                    </div>
                    <div className="rsssl-ssl-intro-container">
                        <Runner
                            title={__("Preparing vulnerability detection", "really-simple-ssl")}
                            name={"first_runner"}
                            loading={true}
                            currentStep={1}
                        />
                        <Runner
                            title={__("Collecting plugin, theme and core data", "really-simple-ssl")}
                            name={"second_runner"}
                            loading={true}
                            currentStep={2}
                        />
                        <Runner
                            title={__("Scanning your WordPress configuration", "really-simple-ssl")}
                            name={"third_runner"}
                            loading={true}
                            currentStep={3}
                        />
                        <Runner
                            title={__("Reporting enabled", "really-simple-ssl")}
                            name={"fourth_runner"}
                            loading={true}
                            currentStep={4}
                        />
                    </div>
                    <div className={'rsssl-modal-footer'}>
                        <Button disabled={disabled}
                            isPrimary
                            onClick={() => {
                                setClosed(true);
                                //we redirect to dashboard
                                window.location.hash = "dashboard";
                            }}
                        >
                            {__('Dashboard', 'really-simple-ssl')}
                        </Button>
                        <Button disabled={disabled} isSecondary
                                onClick={() => {
                                    setClosed(true);
                                }}
                        >
                            {__('Dismiss', 'really-simple-ssl')}
                        </Button>
                    </div>
                </Modal>
            </>
        )
    }

    //in case the modal is closed we return null
    return null;
}

export default VulnerabilitiesIntro;
