import {
    useState, useEffect
} from '@wordpress/element';

import * as rsssl_api from "../../utils/api";
import TaskElement from "./../TaskElement";
import Placeholder from '../../Placeholder/Placeholder';
import useProgress from "./ProgressData";
import useFields from "../../Settings/FieldsData";

const ProgressBlock = (props) => {
    const {setFilter, filter, fetchFilter, notices, progressLoaded, getProgressData, error} = useProgress();
    const {fields} = useFields();
    const [percentageCompleted, setPercentageCompleted] = useState(0);
    const [progressText, setProgressText] = useState('');

    useEffect(async () => {
        getProgressData();
    }, [] );

    const getStyles = () => {
        return Object.assign(
            {},
            {width: this.percentageCompleted+"%"},
        );
    }



    render(){
        const {
            error,
        } = this.state;
        let progressBarColor = '';
        if ( this.percentageCompleted<80 ) {
            progressBarColor += 'rsssl-orange';
        }
        if ( !this.progressLoaded || error ) {
            return (
                <Placeholder lines='9' error={error}></Placeholder>
            );
        }
        let filter = 'all';
        if ( this.props.blockProps && this.props.blockProps.filterStatus ) {
            filter = this.props.blockProps.filterStatus;
        }
        let notices = this.notices;
        if ( filter==='remaining' ) {
            notices = notices.filter(function (notice) {
                return notice.output.status==='open';
            });
        }

        return (
            <div className="rsssl-progress-block">
                <div className="rsssl-progress-bar">
                    <div className="rsssl-progress">
                        <div className={'rsssl-bar ' + progressBarColor} style={this.getStyles()}></div>
                    </div>
                </div>

                <div className="rsssl-progress-text">
                    <h1 className="rsssl-progress-percentage">
                        {this.percentageCompleted}%
                    </h1>
                    <h5 className="rsssl-progress-text-span">
                        {this.progressText}
                    </h5>
                </div>

                <div className="rsssl-scroll-container">
                    {notices.map((notice, i) => <TaskElement key={i} index={i} notice={notice} getFields={this.props.getFields} onCloseTaskHandler={this.onCloseTaskHandler} doHighlightField={this.props.doHighlightField}/>)}
                </div>

            </div>
        );
    }
}
export default ProgressBlock;
