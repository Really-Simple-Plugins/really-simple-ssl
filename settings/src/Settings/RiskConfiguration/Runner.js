import Icon from "../../utils/Icon";
import {useEffect, useState} from "react";
import * as rsssl_api from "../../utils/api";


const Runner = (props) => {
    //let us make a state for the loading
    const [loadingState, setLoadingState] = useState(props.loading);
    let title = props.title;
    const [delayState, setDelayState] = useState(true);
    let spin = (loadingState && !delayState)? "icon-spin" : "";
    let name = props.name;
    if(props.name === "first_runner") {
        useEffect(() => {
            const firstRunner = async () => {
                setDelayState(false);
                setLoadingState(true);
                let response = await rsssl_api.doAction('rsssl_scan_files');
                if (response.request_success) {
                    setLoadingState(false);
                    spin = "";
                }
            }
            firstRunner();
        }, []);
    } else {
        useEffect(() => {
            const run = async () => {
                setTimeout(function () {
                    setTimeout(function () {
                        //we set the loading state to true
                        setLoadingState(false);
                    }, props.time);
                    setDelayState(false);
                }, props.delay);
            }
            run();
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
                {delayState?  <Icon name="circle-check" color="red"/> : loadingState? <Icon name="spinner" />:<Icon name="circle-check" color="green"/>}
            </div>
            <div className="rsssl-detail">
                {displayTitle(name)}
            </div>
        </div>
    )
}

export default Runner;