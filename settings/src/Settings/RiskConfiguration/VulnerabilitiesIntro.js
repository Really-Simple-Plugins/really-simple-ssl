import {__} from "@wordpress/i18n";
import {Button} from "@wordpress/components";
import {useState,useEffect} from '@wordpress/element';
import useRunnerData from "./RunnerData";
import RssslModal from "../../../../modal/src/components/Modal/RssslModal";
import useRiskData from "./RiskData";
import useProgress from "../../Dashboard/Progress/ProgressData";
import useFields from "../FieldsData";
import sleeper from "../../utils/sleeper";
import './modal.scss';

const VulnerabilitiesIntro = () => {
    const {
        fetchFirstRun,
        fetchVulnerabilities,
    } = useRiskData();
    const {getProgressData} = useProgress();
    const {handleNextButtonDisabled, setChangedField, updateField, saveFields} = useFields();
    //first we define a state for the steps
    const [ isClosed, setClosed ] = useState( false );
    const {list, disabled,setDisabled, setItemCompleted, setIntroCompleted} = useRunnerData();

    const setOpen = () => {
        if (!disabled) {
            setClosed(true);
        }
    }

    useEffect(() => {
        if ( !isClosed ) {
            initializeVulnerabilities();
        }
    },[isClosed]);

    const initializeVulnerabilities = async () => {
        await sleeper(1000);

        await fetchFirstRun();
        await setItemCompleted('initialize');
        await sleeper(1000);

        await fetchVulnerabilities();
        await setItemCompleted('fetchVulnerabilities');
        await sleeper(1000);

        await getProgressData();
        await setItemCompleted('scan');
        await sleeper(1000);

        setChangedField('vulnerabilities_intro_shown', true);
        updateField('vulnerabilities_intro_shown', true);
        setIntroCompleted(true);

        await saveFields(true, false);
        setDisabled(false);
        await setItemCompleted('enabled');
        handleNextButtonDisabled(false);
    }

    const Controls = () => {
        return (
            <>
                <Button disabled={disabled}
                    onClick={() => {
                        setClosed(true);
                    }}
                >
                    {__('Dismiss', 'really-simple-ssl')}
                </Button>
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
                </>
        )
    }

    //this function closes the modal when onClick is activated
    if(!isClosed) {
        return (
            <>
                <RssslModal
                    className={"rsssl-vulnerabilities-modal"}
                    title={__('Introducing vulnerabilities', 'really-simple-ssl')}
                    setOpen={() => setOpen()}
                    content={__("You have enabled vulnerability detection! Really Simple SSL will check your plugins, themes and WordPress core daily and report if any known vulnerabilities are found.", "really-simple-ssl")}
                    isOpen={!isClosed}
                    buttons={Controls()}
                    list = {list}
                />
            </>
        );
    }

    //in case the modal is closed we return null
    return null;
}

export default VulnerabilitiesIntro;
