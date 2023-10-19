import Icon from "../../utils/Icon";
import {useState,useEffect} from '@wordpress/element';
import useRiskData from "./RiskData";
import useRunnerData from "./RunnerData";
import useFields from "../FieldsData";
import useProgress from "../../Dashboard/Progress/ProgressData";

const Runner = (props) => {
    //let us make a state for the loading
    const [loadingState, setLoadingState] = useState(true);
    const {setChangedField, updateField, saveFields} = useFields();
    const {getProgressData} = useProgress();
    const {step, setStep} = useRunnerData();
    const {
        fetchFirstRun,
        fetchVulnerabilities,
        setIntroCompleted
    } = useRiskData();
    let spin = (loadingState)? "icon-spin" : "";

    //first step
    useEffect(() => {
        if (step===0 && props.currentStep===1) {
            firstRunner();
        }else if (step===1 && props.currentStep===2) {
            secondRunner();
        }else if (step===2 && props.currentStep===3) {
            thirdRunner();
        }else if (step===3 && props.currentStep===4) {
            fourthRunner();
        }

    }, [step]);

    const firstRunner = async () => {
        await fetchFirstRun();
        completeCurrentRun();
    }

    const secondRunner = async () => {
        await fetchVulnerabilities();
        completeCurrentRun();
    }

    const thirdRunner = async () => {
        //after the first run is complete, and vulnerabilities data is loaded,
        //we reload the progress now to ensure we have all the vulnerabilities loaded on the dashboard.
        await getProgressData();
        completeCurrentRun();
    }

    const fourthRunner = async () => {
        //last run, store as completed
        setIntroCompleted(true);
        setChangedField('vulnerabilities_intro_shown', true);
        updateField('vulnerabilities_intro_shown', true);
        await saveFields(true, false);
        completeCurrentRun();
    }

    const completeCurrentRun = () => {
        setTimeout(function () {
            setLoadingState(false);
            setStep(step+1);
        }, 1000 );
    }

    return (
        <div className="rsssl-details">
            <span className={"rsssl-detail-icon " + spin} >
                {loadingState ? <Icon name="spinner" />:<Icon name="circle-check" color="green"/>}
            </span>
            <div className="rsssl-detail">
                <div className="rsssl-detail-title">
                    {props.title}
                </div>
            </div>
        </div>
    )
}

export default Runner;