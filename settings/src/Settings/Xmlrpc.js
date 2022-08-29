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

class Xmlrpc extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            enforce :0,
            learning_mode :0,
            learning_mode_completed :0,
            filterValue: 0,
        };
    }

    componentDidMount() {
        this.doFilter = this.doFilter.bind(this);
        console.log(this.props.fields);
        let field = this.props.fields.filter(field => field.id === 'xmlrpc_status')[0];
        let enforce = field.value==='enforce';
        let learning_mode = field.value==='learning_mode';
        let learning_mode_completed = field.value==='completed';
        this.setState({
            enforce :enforce,
            learning_mode :learning_mode,
            learning_mode_completed :learning_mode_completed,
        });
    }

    doFilter(e){
        this.setState({
            filterValue :e.target.value,
        });
    }

    toggleEnforce(e, enforce){
        e.preventDefault();
        let fields = this.props.fields;
        let field = fields.filter(field => field.id === 'xmlrpc_status')[0];

        //enforce this setting
        field.value=enforce==1 ? 'enforce' : 'disabled';
        this.setState({
            enforce :enforce,
            learning_mode_completed:0,
        });
        let saveFields = [];
        saveFields.push(field);
        rsssl_api.setFields(saveFields).then(( response ) => {});
    }

    toggleLearningMode(e){
         e.preventDefault();
        let fields = this.props.fields;
        let field = fields.filter(field => field.id === 'xmlrpc_status')[0];
        let learning_mode = field.value === 'learning_mode' ? 1 : 0;
        let learning_mode_completed = field.value === 'completed' ? 1 : 0;

        field.value = learning_mode || learning_mode_completed ? 'disabled' : 'learning_mode';
        if (learning_mode || learning_mode_completed) {
            learning_mode = 0;
        } else {
            learning_mode = 1;
        }
        learning_mode_completed = 0;
        this.setState({
            learning_mode : learning_mode,
            learning_mode_completed : learning_mode_completed,
        });
        let saveFields = [];
        saveFields.push(field);
        rsssl_api.setFields(saveFields).then(( response ) => {});
    }

    render(){
            let field = this.props.field;

            let fieldValue = field.value;
            let options = this.props.options;
            const {
                filterValue,
                enforce,
                learning_mode,
                learning_mode_completed,
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
                item.login_statusControl = item.login_status == 1 ? __("success", "really-simple-ssl") : __("failed", "really-simple-ssl");
                item.statusControl = <ChangeStatus item={item} onChangeHandlerDataTable={this.props.onChangeHandlerDataTable}
                />;
            }
            const conditionalRowStyles = [
              {
                when: row => row.status ==0,
                classNames: ['rsssl-datatables-revoked'],
              },
            ];
             return (
                <>
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

                        { enforce!=1 && <button className="button" onClick={ (e) => this.toggleEnforce(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                        { enforce==1 && <button className="button" onClick={ (e) => this.toggleEnforce(e, false ) }>{__("Disable","really-simple-ssl")}</button> }
                        <label>
                            <input type="checkbox"
                                disabled = {enforce}
                                checked ={learning_mode==1}
                                value = {learning_mode}
                                onChange={ ( e ) => this.toggleLearningMode(e) }
                            />
                            {__("Enable Learning Mode","really-simple-ssl")}
                        </label>
                    </PanelBody>
                    {learning_mode==1 && <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-learning-mode">{__("Learning Mode","really-simple-ssl")}</span>
                            {__("We're configuring your XMLRPC.", "really-simple-ssl")}&nbsp;
                            <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => this.toggleLearningMode(e) }>{__("Disable learning mode and configure manually", "really-simple-ssl") }</a>
                        </div>
                    </div>}
                    {learning_mode_completed==1 && <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-learning-mode-completed">{__("Learning Mode","really-simple-ssl")}</span>
                            {__("We finished the configuration.", "really-simple-ssl")}&nbsp;
                            <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => this.toggleLearningMode(e) }>{__("Review the settings and enforce the policy", "really-simple-ssl") }</a>
                        </div>
                    </div>}
                </>
            )
    }
}


export default Xmlrpc