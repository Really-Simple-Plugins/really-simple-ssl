import Icon from "../../utils/Icon";
import {useEffect, useState} from "react";
import * as rsssl_api from "../../utils/api";
import useRiskData from "./RiskData";
import useFields from "../FieldsData";


const Runner = (props) => {
    //let us make a state for the loading
    const [loadingState, setLoadingState] = useState(props.loading);
    const {setChangedField, updateField, saveFields} = useFields();
    const {
        fetchVulnerabilities,
        setIntroCompleted
    } = useRiskData();
    let title = props.title;
    let spin = (loadingState)? "icon-spin" : "";
    let name = props.name;
    if(props.name === "first_runner") {
        useEffect(() => {
            const firstRunner = async () => {
                setLoadingState(true);
                let response = await rsssl_api.doAction('vulnerabilities_scan_files');
                if (response.request_success) {
                    setLoadingState(false);
                    spin = "";
                }
                setTimeout(function () {
                    //we set the loading state to true
                    setLoadingState(false);
                }, props.time);
            }
            firstRunner();
        }, []);
    } else if(props.name === "fourth_runner") {
        useEffect(() => {
            const fourthRunner = async () => {
                await fetchVulnerabilities();
                setIntroCompleted(true);
                setChangedField('vulnerabilities_intro_shown', true);
                updateField('vulnerabilities_intro_shown', true);
                await saveFields(true, false);
                setTimeout(function () {
                    //we set the loading state to true
                    setLoadingState(false);
                }, props.time);
            }
            fourthRunner();
        }, []);
    } else {
        useEffect(() => {
            setTimeout(function () {
                //we set the loading state to true
                setLoadingState(false);
            }, props.time);

       }, []);
    }

    function displayTitle(name) {
        return (
            <div className="rsssl-detail-title">
                {title}
            </div>
        )
    }

    return (
        <div className="rsssl-details">
            <div className={"rsssl-detail-icon " + spin} >
                {loadingState ? <Icon name="spinner" />:<Icon name="circle-check" color="green"/>}
            </div>
            <div className="rsssl-detail">
                {displayTitle(name)}
            </div>
        </div>
    )
}

export default Runner;