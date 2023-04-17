import {__} from "@wordpress/i18n";
import {Button, Modal} from "@wordpress/components";
import {useState} from '@wordpress/element';
import Runner from "./RiskConfiguration/Runner";

const VulnerabilitiesIntro = (props) => {
    //first we define a state for the steps
    const [ isOpen, setOpen ] = useState( false );

    //this function closes the modal when onClick is activated
    if(!isOpen) {
        return (
            <>
                <Modal
                    title={__('Introducing vulnerabilities', 'really-simple-ssl')}
                    className="rsssl-modal"
                    onRequestClose={setOpen}
                    shouldCloseOnClickOutside={true}
                    shouldCloseOnEsc={true}
                    overlayClassName="rsssl-modal-overlay"
                >
                    <div className="rsssl-header-extension">
                        <div>
                            <p>
                                {__("You have enabled vulnerability detection! Really Simple SSL will check your plugins, themes and WordPress core daily and report if any known vulnerabilities are fouond.", "really-simple-ssl")}
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
                            time={1000}
                            delay={1000}
                        />
                        <Runner
                            title={__("Collecting plugin, theme and core data", "really-simple-ssl")}
                            name={"second_runner"}
                            loading={true}
                            time={2000}
                            delay={2000}
                        />
                        <Runner
                            title={__("Scanning your WordPress configuration", "really-simple-ssl")}
                            name={"third_runner"}
                            loading={true}
                            time={3000}
                            delay={3000}
                        />
                        <Runner
                            title={__("Reporting enabled", "really-simple-ssl")}
                            name={"fourth_runner"}
                            loading={true}
                            time={4000}
                            delay={4000}
                        />
                    </div>
                    <div className={'rsssl-modal-footer'}>
                        <Button
                            isPrimary
                            onClick={() => {
                                setOpen(true);
                                //we redirect to dashboard
                                window.location.hash = "dashboard";
                            }}
                        >
                            {__('Dashboard', 'really-simple-ssl')}
                        </Button>
                        <Button isSecondary
                                onClick={() => {
                                    setOpen(true);
                                }}
                        >
                            {__('Dismiss', 'really-simple-ssl')}
                        </Button>
                    </div>
                </Modal>
            </>
        )
    }
}

export default VulnerabilitiesIntro;
