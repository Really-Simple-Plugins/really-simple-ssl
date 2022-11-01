import {Component} from "@wordpress/element";
import DataTable, { createTheme }  from "react-data-table-component";
import {
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import ModalControl from "../Modal/ModalControl";
import Placeholder from "../Placeholder/Placeholder";
import Icon from "../utils/Icon";

class subHeaderComponentMemo extends Component {
    constructor() {
        super( ...arguments );
    }
    render() {
        return (
            <select>
                <option>{__("All results", "really-simple-ssl")}</option>
                <option>{__("Show", "really-simple-ssl")}</option>
                <option>{__("All results", "really-simple-ssl")}</option>
            </select>
        );
    }
}
class MixedContentScan extends Component {
    constructor() {
        super( ...arguments );
        this.nonce='';
        this.state = {
            data:[],
            progress:0,
            action:'',
            state:'stop',
            paused:false,
            showIgnoredUrls:false,
            resetPaginationToggle:false,
        };
    }

    getScanStatus(){
        return rsssl_api.runTest('scan_status', 'refresh').then( ( response ) => {
            return response.data;
        });
    }

    componentDidMount() {
        let data = [];
        let progress = 0;
        let action = '';
        let state = 'stop';
        let completedStatus = 'never';

        if (this.props.field.value.data ){
            data = this.props.field.value.data;
        }
        if (this.props.field.value.progress ){
            progress = this.props.field.value.progress;
        }
        if (this.props.field.value.action ){
            action = this.props.field.value.action;
        }
        if (this.props.field.value.state ){
            state = this.props.field.value.state;
        }
        if (this.props.field.value.completed_status ){
            completedStatus = this.props.field.value.completed_status;
        }
        if (this.props.field.value.nonce ){
            this.nonce = this.props.field.value.nonce;
        }
        this.setState({
            completedStatus:completedStatus,
            data:data,
            progress:progress,
            action:action,
            state:state
        });
    }

    start(e){
        //add start_full option
        let state = 'start';
        if ( this.state.paused ) {
            state = 'running';
        }
        this.setState({
            state:'running',
            paused:false,
        });
        rsssl_api.runTest('mixed_content_scan', state ).then( ( response ) => {
            this.setState({
                data:response.data.data,
                progress:response.data.progress,
                action:response.data.action,
                state:response.data.state,
            });
            if ( response.data.state==='running' ){
                this.run();
            }
        });
    }

    run(e){
        if ( this.state.paused ) {
            return;
        }
        rsssl_api.runTest('mixed_content_scan', 'running' ).then( ( response ) => {
            this.setState({
                completedStatus:response.data.completed_status,
                data:response.data.data,
                progress:response.data.progress,
                action:response.data.action,
                state:response.data.state,
            });
            //if scan was stopped while running, set it to stopped now.
            if ( this.state.paused ) {
                this.stop();
            } else if ( response.data.state==='running' ) {
                this.run();
            }

        });
    }
    toggleIgnoredUrls(e){
        let {
            showIgnoredUrls
        } = this.state;
        this.setState({
            showIgnoredUrls: !showIgnoredUrls,
        });
    }

    stop(e){
        this.setState({
            state: 'stop',
            paused: true,
        });
        rsssl_api.runTest('mixed_content_scan', 'stop' ).then( ( response ) => {
            this.setState({
                completedStatus:response.data.completed_status,
                data:response.data.data,
                progress:response.data.progress,
                action:response.data.action,
            });
        });
    }

    /**
     * After an update, remove an item from the data array
     * @param removeItem
     */
    removeDataItem(removeItem){
        const updatedData = this.state.data.filter(
            item => item.id === removeItem.id,
        );
        this.setState({
            data:updatedData,
        });
    }

    render(){
        let {
            completedStatus,
            data,
            action,
            progress,
            state,
            showIgnoredUrls,
            resetPaginationToggle,
        } = this.state;
        let field = this.props.field;
        let fieldValue = field.value;
        let fields = this.props.fields;
        if (!rsssl_settings.pro_plugin_active) progress=80;
        columns = [];

        field.columns.forEach(function(item, i) {
            let newItem = {
                name: item.name,
                sortable: item.sortable,
                grow: item.grow,
                selector: row => row[item.column],
                right: !!item.right,
            }
            columns.push(newItem);
        });

        if (typeof data === 'object') {
            data = Object.values(data);
        }
        if (!Array.isArray(data) ) {
            data = [];
        }
        completedStatus = completedStatus ? completedStatus.toLowerCase() : 'never';
        let dropItem = this.props.dropItemFromModal;
        for (const item of data) {
            item.warningControl = <span className="rsssl-task-status rsssl-warning">{__("Warning", "really-simple-ssl")}</span>
            //@todo check action for correct filter or drop action.
            if ( dropItem && dropItem.url === item.blocked_url ) {
                if (dropItem.action==='ignore_url'){
                    item.ignored = true;
                } else {
                    item.fixed = true;
                }
            }
            //give fix and details the url as prop
            if (item.fix) {
                item.fix.url = item.blocked_url;
                item.fix.nonce = this.nonce;
            }
            if (item.details) {
                item.details.url = item.blocked_url;
                item.details.nonce = this.nonce;
                item.details.ignored = item.ignored;
            }
            if (item.location.length > 0) {
                if (item.location.indexOf('http://') !== -1 || item.location.indexOf('https://') !== -1) {
                    item.locationControl =
                        <a href={item.location} target="_blank">{__("View", "really-simple-ssl")}</a>
                } else {
                    item.locationControl = item.location;
                }
            }
            item.detailsControl = item.details && <ModalControl removeDataItem={this.removeDataItem}
                                                                handleModal={this.props.handleModal} item={item}
                                                                btnText={__("Details", "really-simple-ssl")}
                                                                btnStyle={"secondary"}
                                                                modalData={item.details}/>;
            item.fixControl = item.fix && <ModalControl className={"button button-primary"}
                                                        removeDataItem={this.removeDataItem}
                                                        handleModal={this.props.handleModal} item={item}
                                                        btnText={__("Fix", "really-simple-ssl")}
                                                        btnStyle={"primary"}
                                                        modalData={item.fix}/>;
        }

        if ( !showIgnoredUrls ) {
            data = data.filter(
                item => !item.ignored,
            );
        }

        //filter also recently fixed items
        data = data.filter(
            item => !item.fixed,
        );

        progress+='%';
        let startDisabled = state === 'running';
        let stopDisabled = state !== 'running';
        let label = __("Show ignored URLs", 'burst-statistics')

        const customStyles = {
            headCells: {
                style: {
                    paddingLeft: '0', // override the cell padding for head cells
                    paddingRight: '0',
                },
            },
            cells: {
                style: {
                    paddingLeft: '0', // override the cell padding for data cells
                    paddingRight: '0',
                },
            },
        };

        createTheme('really-simple-plugins', {
            divider: {
                default: 'transparent',
            },
        }, 'light');


        return (
            <>
                <div className="rsssl-progress-container">
                    <div className="rsssl-progress-bar" style={{width: progress}} ></div>
                </div>
                {state==='running' && <div className="rsssl-current-scan-action">{action}</div>}
                    {data.length==0 && <>
                        <div className="rsssl-mixed-content-description">
                            {state!=='running' && completedStatus==='never' && __("No results. Start your first scan","really-simple-ssl")}
                            {state!=='running' && completedStatus==='completed' && __("Everything is now served over SSL","really-simple-ssl")}
                        </div>
                        { (state ==='running' || completedStatus!=='completed') && <div className="rsssl-mixed-content-placeholder">
                                 <div></div><div></div><div></div>
                        </div>
                        }
                        { state!=='running' && completedStatus==='completed' && <div className="rsssl-shield-overlay">
                              <Icon name = "shield"  size="80px"/>
                        </div> }
                        </>}
                    { data.length>0 && <div className={'rsssl-mixed-content-datatable'}><DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                        paginationResetDefaultPage={resetPaginationToggle} // optionally, a hook to reset pagination to page 1
                        noDataComponent={__("No results", "really-simple-ssl")} //or your component
                        theme="really-simple-plugins"
                        customStyles={customStyles}

                        // subHeader
                        // subHeaderComponent=<subHeaderComponentMemo/>
                    /></div>  }
                <div className="rsssl-grid-item-content-footer">
                    <button className="button" disabled={startDisabled} onClick={ (e) => this.start(e) }>{__("Start scan","really-simple-ssl-pro")}</button>
                    <button className="button" disabled={stopDisabled} onClick={ (e) => this.stop(e) }>{__("Stop","really-simple-ssl-pro")}</button>
                    <ToggleControl
                        checked= { showIgnoredUrls==1 }
                        onChange={ (e) => this.toggleIgnoredUrls(e) }
                    />
                    <label>{__('Show ignored URLs', 'burst-statistics')}</label>
                </div>

            </>
        )
    }
}

export default MixedContentScan;
