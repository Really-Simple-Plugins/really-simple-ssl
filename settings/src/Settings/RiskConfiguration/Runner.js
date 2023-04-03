import Icon from "../../utils/Icon";
import {useEffect, useState} from "react";

const Runner = (props) => {
    //let us make a state for the loading
    const [loadingState, setLoadingState] = useState(props.loading);
    let title = props.title;
    const [delayState, setDelayState] = useState(true);
    let spin = (loadingState && !delayState)? "icon-spin" : "";

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

    return (
        <div className="rsssl-details">
            <div className={"rsssl-detail-icon " + spin} >
                {delayState?  <Icon name="circle-check" color="red"/> : loadingState? <Icon name="spinner" />:<Icon name="circle-check" color="green"/>}
            </div>
            <div className="rsssl-detail">
                {title}
            </div>
        </div>
    )
}

export default Runner;