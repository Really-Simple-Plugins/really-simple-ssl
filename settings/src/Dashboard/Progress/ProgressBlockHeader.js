import { __ } from '@wordpress/i18n';
import {
    useEffect,
} from '@wordpress/element';
import useProgress from "./ProgressData";

const ProgressHeader = () => {
    const {setFilter, filter, fetchFilter, notices, error } = useProgress();

    useEffect( () => {
        fetchFilter();
    }, [] );

    const onClickHandler = (e) => {
        let filter = e.target.getAttribute('data-filter');
        if (filter==='all' || filter==='remaining') {
            setFilter(filter);
        }
    }

    if  (error ) {
        return (
            <></>
        );
    }

    let all_task_count = 0;
    let open_task_count = 0;
    all_task_count = notices.length;
    let openNotices = notices.filter(function (notice) {
        return notice.output.status==='open' || notice.output.status==='warning';
    });
    open_task_count = openNotices.length;

    return (
        <>
            <h3 className="rsssl-grid-title rsssl-h4">{  __( "Progress", 'really-simple-ssl' ) }</h3>
            <div className="rsssl-grid-item-controls">
                <div className={"rsssl-task-switcher-container rsssl-active-filter-"+filter}>
                    <span className="rsssl-task-switcher rsssl-all-tasks" onClick={onClickHandler} htmlFor="rsssl-all-tasks" data-filter="all">
                            { __( "All tasks", "really-simple-ssl" ) }
                        <span className="rsssl_task_count">({all_task_count})</span>
                    </span>
                            <span className="rsssl-task-switcher rsssl-remaining-tasks" onClick={onClickHandler} htmlFor="rsssl-remaining-tasks" data-filter="remaining">
                            { __( "Remaining tasks", "really-simple-ssl" )}
                                <span className="rsssl_task_count">({open_task_count})</span>
                    </span>
                </div>
            </div>
        </>

    );

}
export default ProgressHeader;
