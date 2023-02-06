import {
    useState,
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

    componentDidMount() {
        this.getProgressData();
    }

    componentDidUpdate() {
        //if a field has changed, we update the progress data as well.
        if ( this.fields !== this.props.fields ) {
            this.fields = this.props.fields;
            this.getProgressData();
        }
    }
    getStyles() {
        return Object.assign(
            {},
            {width: this.percentageCompleted+"%"},
        );
    }

    onCloseTaskHandler(e){
        let button = e.target.closest('button');
        let notice_id = button.getAttribute('data-id');
        let container = button.closest('.rsssl-task-element');
        container.animate({
            marginLeft: ["0px", "-1000px"]
        }, {
            duration: 500,
            easing: "linear",
            iterations: 1,
            fill: "both"
        }).onfinish = function() {
            container.parentElement.removeChild(container);
        }

        let notices = this.props.blockProps.notices;
        notices = notices.filter(function (notice) {
            return notice.id !== notice_id;
        });

        this.props.updateBlockProps('notices', notices);
        return rsssl_api.runTest('dismiss_task', notice_id).then(( response ) => {
            if ( response.error ) {
                this.setState({
                    error: response.error,
                });
            } else {
                this.percentageCompleted = response.percentage;
                this.setState({
                    percentageCompleted: this.percentageCompleted
                })
            }
        });
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
