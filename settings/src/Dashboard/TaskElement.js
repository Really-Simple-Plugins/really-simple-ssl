import { __ } from '@wordpress/i18n';
import Icon from '../utils/Icon'
import {dispatch,} from '@wordpress/data';
import * as rsssl_api from "../utils/api";
import sleeper from "../utils/sleeper";
import useFields from "../Settings/FieldsData";
import useProgress from "./Progress/ProgressData";
import useMenu from "../Menu/MenuData";
import DOMPurify from "dompurify";
import {useState} from "@wordpress/element";

const TaskElement = (props) => {
    const {dismissNotice, getProgressData} = useProgress();
    const {getField, setHighLightField} = useFields();
    const {setSelectedSubMenuItem} = useMenu();
    const [processing, setProcessing] = useState(false);
    const handleClick = async () => {
        setHighLightField(props.notice.output.highlight_field_id);
        let highlightField = getField(props.notice.output.highlight_field_id);
        await setSelectedSubMenuItem(highlightField.menu_id);
    }

    const handleFix = (fix_id) => {
        let data = {};
        data.fix_id = fix_id;
        setProcessing(true);
        rsssl_api.doAction('fix', data).then( ( response ) => {
            setProcessing(false);
            let msg = response.msg;
            const notice = dispatch('core/notices').createNotice(
                'success',
                msg,
                {
                    __unstableHTML: true,
                    id: 'rsssl_fix',
                    type: 'snackbar',
                    isDismissible: true,
                }
            ).then(sleeper(3000)).then(( response ) => {
                dispatch('core/notices').removeNotice('rsssl_clear_cache');
            });
            getProgressData();
        });
    }

    const handleClearCache = (cache_id) => {
        setProcessing(true)
        let data = {};
        data.cache_id = cache_id;
        rsssl_api.doAction('clear_cache', data).then( ( response ) => {
            setProcessing(false)
            const notice = dispatch('core/notices').createNotice(
                'success',
                __( 'Re-started test', 'really-simple-ssl' ),
                {
                    __unstableHTML: true,
                    id: 'rsssl_clear_cache',
                    type: 'snackbar',
                    isDismissible: true,
                }
            ).then(sleeper(3000)).then(( response ) => {
                dispatch('core/notices').removeNotice('rsssl_clear_cache');
            });
            getProgressData();
        });
    }

    let notice = props.notice;
    let premium = notice.output.icon==='premium';
    //treat links to rsssl.com and internal links different.
    let urlIsExternal = notice.output.url && notice.output.url.indexOf('really-simple-ssl.com') !== -1;
    return(
        <div className="rsssl-task-element">
            <span className={'rsssl-task-status rsssl-' + notice.output.icon}>{ notice.output.label }</span>
            <p className="rsssl-task-message" dangerouslySetInnerHTML={{__html: DOMPurify.sanitize( notice.output.msg )}}></p> {/* nosemgrep: react-dangerouslysetinnerhtml */}
            {urlIsExternal && notice.output.url && <a target="_blank" rel="noopener noreferrer" href={notice.output.url}>{__("More info", "really-simple-ssl")}</a> }
            {notice.output.clear_cache_id && <span className="rsssl-task-enable button button-secondary" onClick={ () => handleClearCache(notice.output.clear_cache_id ) }>{__("Re-check", "really-simple-ssl")}</span> }
            {notice.output.fix_id && <span className="rsssl-task-enable button button-secondary" onClick={ () => handleFix(notice.output.fix_id ) }>
                {!processing && __("Fix", "really-simple-ssl")}
                {processing && <Icon name = "loading" color = 'black' size={14} />}
            </span> }
            {!premium && !urlIsExternal && notice.output.url && <a className="rsssl-task-enable button button-secondary" href={notice.output.url}>
                {!processing && __("View", "really-simple-ssl")}
                {processing && <Icon name = "loading" color = 'black' size={14} />}
            </a> }

            {!premium && notice.output.highlight_field_id && <span className="rsssl-task-enable button button-secondary" onClick={() => handleClick()}>{__("View", "really-simple-ssl")}</span> }
            {notice.output.plusone && <span className='rsssl-plusone'>1</span>}
            {notice.output.dismissible && notice.output.status!=='completed' &&
                <div className="rsssl-task-dismiss">
                  <button type='button' onClick={(e) => dismissNotice(notice.id) }>
                         <Icon name='times' />
                  </button>
                </div>
            }
        </div>
    );
}

export default TaskElement;