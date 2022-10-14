import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';
import Icon from '../utils/Icon'

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
        let premium = notice.output.icon==='premium';
        //treat links to rsssl.com and internal links different.
        let urlIsExternal = notice.output.url && notice.output.url.indexOf('really-simple-ssl.com') !== -1;
        return(
            <div className="rsssl-task-element">
                <span className={'rsssl-task-status rsssl-' + notice.output.icon}>{ notice.output.label }</span>
                <p className="rsssl-task-message" dangerouslySetInnerHTML={{__html: notice.output.msg}}></p>
                {urlIsExternal && notice.output.url && <a target="_blank" href={notice.output.url}>{__("More info", "really-simple-ssl")}</a> }
                {!premium && !urlIsExternal && notice.output.url && <a className="rsssl-task-enable button button-secondary" href={notice.output.url}>{__("Fix", "really-simple-ssl")}</a> }
                {!premium && notice.output.highlight_field_id && <span className="rsssl-task-enable button button-secondary" onClick={this.handleClick}>{__("Fix", "really-simple-ssl")}</span> }
                {notice.output.plusone && <span className='rsssl-plusone'>1</span>}
                {notice.output.dismissible && notice.output.status!=='completed' &&
                    <div className="rsssl-task-dismiss">
                      <button type='button' data-id={notice.id} onClick={this.props.onCloseTaskHandler}>
                             <Icon name='times' />
                      </button>
                    </div>
                }
            </div>
        );
    }
}

export default TaskElement;