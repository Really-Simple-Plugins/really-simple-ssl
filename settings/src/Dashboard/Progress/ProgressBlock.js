import {
    useState, useEffect
} from '@wordpress/element';

import TaskElement from "./../TaskElement";
import Placeholder from '../../Placeholder/Placeholder';
import useProgress from "./ProgressData";

const ProgressBlock = (props) => {
    const {percentageCompleted, progressText, filter, notices, progressLoaded, getProgressData, error} = useProgress();

    useEffect( () => {
        const run = async () => {
            await getProgressData();
        }
        run();
    }, [] );

    const getStyles = () => {
        return Object.assign(
            {},
            {width: percentageCompleted+"%"},
        );
    }

    let progressBarColor = '';
    if ( percentageCompleted<80 ) {
        progressBarColor += 'rsssl-orange';
    }
    if ( !progressLoaded || error ) {
        return (
            <Placeholder lines='9' error={error}></Placeholder>
        );
    }
    let noticesOutput = notices;
    if ( filter==='remaining' ) {
        noticesOutput = noticesOutput.filter(function (notice) {
            return notice.output.status==='open';
        });
    }

    return (
        <div className="rsssl-progress-block">
            <div className="rsssl-progress-bar">
                <div className="rsssl-progress">
                    <div className={'rsssl-bar ' + progressBarColor} style={getStyles()}></div>
                </div>
            </div>

            <div className="rsssl-progress-text">
                <h1 className="rsssl-progress-percentage">
                    {percentageCompleted}%
                </h1>
                <h5 className="rsssl-progress-text-span">
                    {progressText}
                </h5>
            </div>

            <div className="rsssl-scroll-container">
                {noticesOutput.map((notice, i) => <TaskElement key={i} notice={notice}/>)}
            </div>
        </div>
    );

}
export default ProgressBlock;
