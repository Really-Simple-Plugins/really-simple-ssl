import {
    PanelBody,
    SelectControl,
    ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';
import ChangeStatus from "./ChangeStatus";
import DataTable from "react-data-table-component";
import * as rsssl_api from "../utils/api";

class ContentSecurityPolicy extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            csp_enforce :0,
            csp_learning_mode :0,
            filterValue: 0,
        };
    }

    componentDidMount() {
        this.doFilter = this.doFilter.bind(this);
        let enforce_field = this.props.fields.filter(field => field.id === 'csp_enforce')[0];
        let learning_mode_field = this.props.fields.filter(field => field.id === 'csp_learning_mode')[0];
        this.setState({
            csp_enforce :enforce_field.value,
            csp_learning_mode :learning_mode_field.value
        });
    }

    doFilter(e){
        this.setState({
            filterValue :e.target.value,
        });
    }


    toggleEnforce(e, enforce){

        let fields = this.props.fields;
        //look up permissions policy enable field //enable_permissions_policy
        let field = fields.filter(field => field.id === 'csp_enforce')[0];
        let learning_mode_field = fields.filter(field => field.id === 'csp_learning_mode')[0];
        //disable learning mode if enforced
        if (enforce==1){
            learning_mode_field.value=0;
        }
        //enforce this setting
        field.value=enforce;
        this.setState({
            csp_enforce :enforce
        });
        let saveFields = [];
        saveFields.push(field);
        saveFields.push(learning_mode_field);
        this.props.updateField(field);
        this.props.updateField(learning_mode_field);
        rsssl_api.setFields(saveFields).then(( response ) => {
            //this.props.showSavedSettingsNotice();
        });
    }

    toggleLearningMode(e, enforce){

        let fields = this.props.fields;
        //look up permissions policy enable field //enable_permissions_policy
        let field = fields.filter(field => field.id === 'csp_learning_mode')[0];
        //enforce this setting
        enforce = field.value==1 ? 0 : 1;
        field.value=enforce;
        this.setState({
            csp_learning_mode :enforce
        });
        let saveFields = [];
        saveFields.push(field);
        this.props.updateField(field);
        rsssl_api.setFields(saveFields).then(( response ) => {
            //this.props.showSavedSettingsNotice();
        });
    }



    render(){
            let field = this.props.field;
            let fieldValue = field.value;
            let options = this.props.options;


            const {
                filterValue,
                csp_enforce,
                csp_learning_mode,
            } = this.state;

            const Filter = () => (
              <>
                <select onChange={ ( e ) => this.doFilter(e) }>
                    <option value="-1" selected={filterValue==-1}>{__("All", "really-simple-ssl")}</option>
                    <option value="1" selected={filterValue==1}>{__("Allowed", "really-simple-ssl")}</option>
                    <option value="0" selected={filterValue==0}>{__("Blocked", "really-simple-ssl")}</option>
                </select>
              </>
            );

            //build our header
            columns = [];
            field.columns.forEach(function(item, i) {
                let newItem = {
                    name: item.name,
                    sortable: item.sortable,
                    width: item.width,
                    selector: row => row[item.column],
                }
                columns.push(newItem);
            });
            let data = field.value;

            if (typeof data === 'object') {
                data = Object.values(data);
            }
            if (!Array.isArray(data) ) {
                data = [];
            }
            if (filterValue!=-1) {
                data = data.filter(item => item.status==filterValue);
            }
            for (const item of data){
                item.statusControl = <ChangeStatus item={item} onChangeHandlerDataTable={this.props.onChangeHandlerDataTable}
                />;
            }
            const conditionalRowStyles = [
              {
                when: row => row.status ==0,
                classNames: ['rsssl-csp-revoked'],
              },
            ];
             return (
                <PanelBody className={ this.highLightClass}>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                        noDataComponent={__("No results", "really-simple-ssl")}
                        persistTableHead
                        subHeader
                        subHeaderComponent={<Filter />}
                        conditionalRowStyles={conditionalRowStyles}
                    />

                    { csp_enforce!=1 && <button className="button" onClick={ (e) => this.toggleEnforce(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                    { csp_enforce==1 && <button className="button" onClick={ (e) => this.toggleEnforce(e, false ) }>{__("Disable","really-simple-ssl")}</button> }
                    <label>
                        <input type="checkbox"
                            disabled = {csp_enforce}
                            checked ={csp_learning_mode==1}
                            value = {csp_learning_mode}
                            onChange={ ( fieldValue ) => this.toggleLearningMode() }
                        />
                        {__("Enable Learning Mode","really-simple-ssl")}
                    </label>
                </PanelBody>
            )
    }
}


export default ContentSecurityPolicy