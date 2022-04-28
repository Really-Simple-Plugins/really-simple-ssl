import { __ } from '@wordpress/i18n';
import {
    Placeholder,
} from '@wordpress/components';
import {
    Component,
} from '@wordpress/element';

import * as rsssl_api from "./utils/api";
class TaskElement extends Component {
    constructor() {
        super( ...arguments);
    }

    handleClick(){
        this.props.highLightField(this.props.notice.output.highlight_field_id);
    }

    componentDidMount() {
        this.handleClick = this.handleClick.bind(this);
    }

    render(){

        let notice = this.props.notice;

        return(
            <div className="rsssl-task-element">
                <div className="rsssl-task-icon"><span className={'rsssl-progress-status rsssl-' + notice.output.icon}>{ notice.output.label }</span></div>
                <div className="rsssl-task-message" dangerouslySetInnerHTML={{__html: notice.output.msg}}></div>
                {notice.output.url && <a target="_blank" href={notice.output.url}>{__("More info", "really-simple-ssl")}</a> }
                {notice.output.highlight_field_id && <span onClick={this.handleClick}>{__("Enable", "really-simple-ssl")}</span> }
                {notice.output.plusone && <span className='rsssl-dashboard-plusone update-plugins rsssl-update-count'><span className='update-count'>1</span></span>}
                <div className="rsssl-task-dismiss">
                    {notice.output.dismissible &&
                        <button type='button' data-id={notice.id} onClick={this.props.onCloseTaskHandler}>
                            <span className='rsssl-close-warning-x'>
                                <svg width="20" height="20" viewBox="0, 0, 400,400">
                                    <path id="path0" d="M55.692 37.024 C 43.555 40.991,36.316 50.669,36.344 62.891 C 36.369 73.778,33.418 70.354,101.822 138.867 L 162.858 200.000 101.822 261.133 C 33.434 329.630,36.445 326.135,36.370 337.109 C 36.270 351.953,47.790 363.672,62.483 363.672 C 73.957 363.672,68.975 367.937,138.084 298.940 L 199.995 237.127 261.912 298.936 C 331.022 367.926,326.053 363.672,337.517 363.672 C 351.804 363.672,363.610 352.027,363.655 337.891 C 363.689 326.943,367.629 331.524,299.116 262.841 C 265.227 228.868,237.500 200.586,237.500 199.991 C 237.500 199.395,265.228 171.117,299.117 137.150 C 367.625 68.484,363.672 73.081,363.672 62.092 C 363.672 48.021,351.832 36.371,337.500 36.341 C 326.067 36.316,331.025 32.070,261.909 101.066 L 199.990 162.877 138.472 101.388 C 87.108 50.048,76.310 39.616,73.059 38.191 C 68.251 36.083,60.222 35.543,55.692 37.024 " stroke="none" fill="#000000">
                                    </path>
                                </svg>
                            </span>
                        </button>
                    }
                </div>
            </div>
        );
    }
}

class ProgressBlock extends Component {
    constructor() {
        super( ...arguments);
        this.percentageCompleted = 0;
        this.progressText = '';
        this.filter = 'all';
        this.notices = null;
        this.progressLoaded = false;
        this.state = {
            progressText:'',
            filter:'all',
            notices:null,
            percentageCompleted:0,
            progressLoaded: false,
        };

        this.getProgressData().then(( response ) => {
            this.progressText = response.text;
            this.filter = response.filter;
            this.percentageCompleted = response.percentage;
            this.notices = response.notices;
            this.progressLoaded = true;
            this.setState({
                progressLoaded: this.progressLoaded,
                progressText: this.progressText,
                filter: this.filter,
                notices: this.notices,
                percentageCompleted: this.percentageCompleted,
            });
            this.props.setBlockProps('notices', this.notices);
        });
    }
    componentDidMount() {
        this.getProgressData = this.getProgressData.bind(this);
        this.onCloseTaskHandler = this.onCloseTaskHandler.bind(this);
    }
    getStyles() {
        return Object.assign(
            {},
            {width: this.percentageCompleted+"%"},
        );
    }
    getProgressData(){
        return rsssl_api.runTest('progressData', 'refresh').then( ( response ) => {
            return response.data;
        });
    }
    onCloseTaskHandler(e){
        let button = e.target.closest('button');
        let type = button.getAttribute('data-id');
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

        let notices = this.props.BlockProps.notices;
        notices = notices.filter(function (notice) {
            return notice.id !== type;
        });

        this.props.setBlockProps('notices', notices);
        return rsssl_api.runTest('dismiss_task', type).then(( response ) => {
            this.percentageCompleted = response.data.percentage;
            this.setState({
                percentageCompleted:this.percentageCompleted
            })
        });
    }
    render(){
        let progressBarColor = '';
        if ( this.percentageCompleted<80 ) {
            progressBarColor += 'rsssl-orange';
        }
        if ( !this.progressLoaded ) {
            return (
                <div></div>
            );
        }
        let filter = 'all';
        if ( this.props.BlockProps && this.props.BlockProps.filterStatus ) {
            filter = this.props.BlockProps.filterStatus;
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
                    <span className="rsssl-progress-percentage">
                        {this.percentageCompleted}%
                    </span>
                    <span className="rsssl-progress-text">
                        {this.progressText}
                    </span>
                </div>

                <div className="rsssl-scroll-container">
                    {notices.map((notice, i) => <TaskElement key={i} index={i} notice={notice} onCloseTaskHandler={this.onCloseTaskHandler} highLightField={this.props.highLightField}/>)}
                </div>

            </div>
        );
    }
}
export default ProgressBlock;
