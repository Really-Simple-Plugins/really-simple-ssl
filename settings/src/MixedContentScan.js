import {Component} from "@wordpress/element";
import DataTable from "react-data-table-component";
import {
    PanelBody,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {runTest} from "./utils/api";
import * as rsssl_api from "./utils/api";
import ModalControl from "./ModalControl";


class MixedContentScan extends Component {
    constructor() {
        super( ...arguments );

        this.state = {
            data:[],
            progress:0,
            action:'',
            state:'stop',
            paused:false,
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
                console.log("finished start, execute run. ");
                this.run();
            }
        });
    }

    run(e){
        if ( this.state.paused ) return;

        console.log("Run function, setting state to running");
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
                console.log("finished run step, next run");
                console.log(response.data.state);
                this.run();
            }

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

    render(){
        let {
            data,
            action,
            progress,
            state,
        } = this.state;
        let field = this.props.field;
        let fieldValue = field.value;
        let fields = this.props.fields;

        columns = [];
        field.columns.forEach(function(item, i) {
            let newItem = {
                name: item.name,
                sortable: item.sortable,
                selector: row => row[item.column],
            }
            columns.push(newItem);
        });

        if (!Array.isArray(data) ) {
            data = [];
        }

        for (const item of data) {
            if (item.location.length > 0) {
                if (item.location.indexOf('http://') !== -1 || item.location.indexOf('https://') !== -1) {
                    item.locationControl =
                        <a href={item.location} target="_blank">{__("View", "really-simple-ssl")}</a>
                } else {
                    item.locationControl = item.location;
                }
            }
            item.detailsControl = item.details && <ModalControl handleModal={this.props.handleModal} item={item}
                                                                btnText={__("Details", "really-simple-ssl")}
                                                                modalData={item.details}/>;
            item.fixControl = item.fix && <ModalControl handleModal={this.props.handleModal} item={item}
                                                        btnText={__("Fix", "really-simple-ssl")}
                                                        modalData={item.fix}/>;
        }

        progress+='%';
        console.log("Actual state "+state);

        let startDisabled = state === 'running';
        let stopDisabled = state !== 'running';
        return (
            <div>
                <div className="rsssl-progress-container">
                    <div className="rsssl-progress-bar" style={{width: progress}} ></div>
                </div>
                <PanelBody>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                    />
                </PanelBody>
                <button className="button" disabled={startDisabled} onClick={ (e) => this.start(e) }>{__("Scan","really-simple-ssl-pro")}</button>
                <button className="button" disabled={stopDisabled} onClick={ (e) => this.stop(e) }>{__("Pause","really-simple-ssl-pro")}</button>
                <span className="rsssl-current-scan-action">{action}</span>
            </div>
        )
    }
}

export default MixedContentScan;