import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';

class ProgressHeader extends Component {
    constructor()
    {
        super(...arguments);
        this.filter = 'all';
    }

    componentDidMount() {
        this.onClickHandler = this.onClickHandler.bind(this);
        this.setState({
            filter: this.filter,
        })
    }

    onClickHandler(e){
        let filter = e.target.getAttribute('data-filter');
        if (filter==='all' || filter==='remaining') {
            this.filter = filter;
            this.setState({
                filter: this.filter,
            })
            this.props.setBlockProps('filterStatus', filter);
            sessionStorage.rsssl_task_filter = filter;
        }
    }

    render(){
        if ( typeof (Storage) !== "undefined" && sessionStorage.rsssl_task_filter  ) {
            this.filter = sessionStorage.rsssl_task_filter;
        }
        let all_task_count = 0;
        let open_task_count = 0;
        let notices =[];
        if ( this.props.BlockProps && this.props.BlockProps.notices ){
            notices = this.props.BlockProps.notices;
            all_task_count = notices.length;
            let openNotices = notices.filter(function (notice) {
                return notice.output.status==='open';
            });
            open_task_count = openNotices.length;
        }

        return (
            <div className={"rsssl-task-switcher-container rsssl-active-filter-"+this.filter}>
                <span className="rsssl-task-switcher rsssl-all-tasks" onClick={this.onClickHandler} htmlFor="rsssl-all-tasks" data-filter="all">
                        { __( "All tasks", "really-simple-ssl" ) }
                        <span className="rsssl_task_count">({all_task_count})</span>
                </span>
                <span className="rsssl-task-switcher rsssl-remaining-tasks" onClick={this.onClickHandler} htmlFor="rsssl-remaining-tasks" data-filter="remaining">
                        { __( "Remaining tasks", "really-simple-ssl" )}
                        <span className="rsssl_task_count">({open_task_count})</span>
                </span>
            </div>
        );
    }
}
export default ProgressHeader;
