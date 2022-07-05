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
        };
    }

    componentDidMount() {
        let data = this.props.field.value;
        if (!Array.isArray(data) ) {
            data = [];
        }
        this.setState({
            data:data,
        });
    }

    startScan(e){
        rsssl_api.runTest('mixed_content_scan', 'refresh' ).then( ( response ) => {
            this.setState({
                data:response.data.data,
            });
        });
    }

    render(){
        let {
            data,
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


        for (const item of data){
            if ( item.location.length>0 ) {
                if (item.location.indexOf('http://')!==-1 || item.location.indexOf('https://')!==-1) {
                    item.locationControl = <a href={item.location} target="_blank">{__("View", "really-simple-ssl")}</a>
                } else {
                    item.locationControl = item.location;
                }
            }
            item.detailsControl = item.details && <ModalControl handleModal={this.props.handleModal} item={item} btnText={__("Details", "really-simple-ssl")} modalData={item.details} />;
            item.fixControl = item.fix && <ModalControl handleModal={this.props.handleModal} item={item} btnText={__("Fix", "really-simple-ssl")} modalData={item.fix}/>;
        }

        return (
            <div>
                <PanelBody>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                    />
                </PanelBody>
                <button onClick={ (e) => this.startScan(e) }>{__("Scan","really-simple-ssl-pro")}</button>
            </div>
        )
    }
}

export default MixedContentScan;