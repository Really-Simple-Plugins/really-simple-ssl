import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';

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
                <span className={'rsssl-task-status rsssl-' + notice.output.icon}>{ notice.output.label }</span>
                <p className="rsssl-task-message" dangerouslySetInnerHTML={{__html: notice.output.msg}}></p>
                {notice.output.url && <a target="_blank" href={notice.output.url}>{__("More info", "really-simple-ssl")}</a> }
                {notice.output.highlight_field_id && <span className="rsssl-task-enable" onClick={this.handleClick}>{__("Fix", "really-simple-ssl")}</span> }
                {notice.output.plusone && <span className='rsssl-plusone'>1</span>}
                {notice.output.dismissible && notice.output.status!=='completed' &&
                    <div className="rsssl-task-dismiss">
                      <button type='button' data-id={notice.id} onClick={this.props.onCloseTaskHandler}>
                              <span className='rsssl-close-warning-x'>
                                  <svg width="20" height="20" viewBox="0, 0, 400,400">
                                      <path id="path0" d="M55.692 37.024 C 43.555 40.991,36.316 50.669,36.344 62.891 C 36.369 73.778,33.418 70.354,101.822 138.867 L 162.858 200.000 101.822 261.133 C 33.434 329.630,36.445 326.135,36.370 337.109 C 36.270 351.953,47.790 363.672,62.483 363.672 C 73.957 363.672,68.975 367.937,138.084 298.940 L 199.995 237.127 261.912 298.936 C 331.022 367.926,326.053 363.672,337.517 363.672 C 351.804 363.672,363.610 352.027,363.655 337.891 C 363.689 326.943,367.629 331.524,299.116 262.841 C 265.227 228.868,237.500 200.586,237.500 199.991 C 237.500 199.395,265.228 171.117,299.117 137.150 C 367.625 68.484,363.672 73.081,363.672 62.092 C 363.672 48.021,351.832 36.371,337.500 36.341 C 326.067 36.316,331.025 32.070,261.909 101.066 L 199.990 162.877 138.472 101.388 C 87.108 50.048,76.310 39.616,73.059 38.191 C 68.251 36.083,60.222 35.543,55.692 37.024 " stroke="none" fill="#000000">
                                      </path>
                                  </svg>
                              </span>
                      </button>
                    </div>
                }
            </div>
        );
    }
}

export default TaskElement;