import {Component} from "@wordpress/element";
import DataTable from "react-data-table-component";
import {
    PanelBody,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {runTest} from "./utils/api";
import * as rsssl_api from "./utils/api";
import ModalControl from "./ModalControl";

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

    componentDidMount() {
        let data = [];
        let progress = 0;
        let action = '';
        let state = 'stop';
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
        if (this.props.field.value.nonce ){
            this.nonce = this.props.field.value.nonce;
        }
        this.setState({
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

        columns = [];
        field.columns.forEach(function(item, i) {
            let newItem = {
                name: item.name,
                width: item.width,
                sortable: item.sortable,
                selector: row => row[item.column],
            }
            columns.push(newItem);
        });

        if (typeof data === 'object') {
            data = Object.values(data);
        }
        if (!Array.isArray(data) ) {
            data = [];
        }
        let dropItem = this.props.dropItemFromModal;
        for (const item of data) {
            item.warningControl = <span className="rsssl-warning">{__("Warning", "really-simple-ssl")}</span>
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
                                                                modalData={item.details}/>;
            item.fixControl = item.fix && <ModalControl removeDataItem={this.removeDataItem}
                                                        handleModal={this.props.handleModal} item={item}
                                                        btnText={__("Fix", "really-simple-ssl")}
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
        return (
            <div>
                <div className="rsssl-progress-container">
                    <div className="rsssl-progress-bar" style={{width: progress}} ></div>
                </div>
                <span className="rsssl-current-scan-action">{state==='running' && action}</span>
                <PanelBody>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                        paginationResetDefaultPage={resetPaginationToggle} // optionally, a hook to reset pagination to page 1
                        // subHeader
                        // subHeaderComponent=<subHeaderComponentMemo/>
                    />
                </PanelBody>
                <button className="button" disabled={startDisabled} onClick={ (e) => this.start(e) }>{__("Scan","really-simple-ssl-pro")}</button>
                <button className="button" disabled={stopDisabled} onClick={ (e) => this.stop(e) }>{__("Pause","really-simple-ssl-pro")}</button>
                <label>{__("Show ignored URLs")}
                    <input value={showIgnoredUrls} type="checkbox" id="rsssl_show_ignored_urls" onClick={ (e) => this.toggleIgnoredUrls(e) } />
                </label>

            </div>
        )
    }
}

export default MixedContentScan;