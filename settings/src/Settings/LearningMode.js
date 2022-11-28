import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';
import ChangeStatus from "./ChangeStatus";
import DataTable, {createTheme} from 'react-data-table-component';
import * as rsssl_api from "../utils/api";
import Icon from "../utils/Icon";

class Delete extends Component {
    constructor() {
        super( ...arguments );
    }
    render(){
       return (
           <button type="button" className=" rsssl-learning-mode-delete" onClick={ () => this.props.onDeleteHandler( this.props.item ) }>
               <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" height="16" >
                   <path fill="#000000" d="M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"/>
               </svg>
           </button>
        )
    }
}

class LearningMode extends Component {
    constructor() {
        super( ...arguments );
        this.state = {
            enforce :0,
            learning_mode :0,
            lm_enabled_once :0,
            learning_mode_completed :0,
            filterValue: -1,
        };
    }

    componentDidMount() {
        this.doFilter = this.doFilter.bind(this);
        this.onDeleteHandler = this.onDeleteHandler.bind(this);
        let field = this.props.fields.filter(field => field.id === this.props.field.control_field )[0];
        let enforce = field.value === 'enforce';
        let learning_mode = field.value === 'learning_mode';
        let learning_mode_completed = field.value==='completed';

        let lm_enabled_once_field_name = this.props.field.control_field+'_lm_enabled_once';
        let lm_enabled_once_field = this.props.fields.filter(field => field.id === lm_enabled_once_field_name)[0];
        let lm_enabled_once = lm_enabled_once_field.value;

        //we somehow need this to initialize the field. Otherwise it doesn't work on load. need to figure that out.
        this.props.updateField(field.id, field.value);
        this.setState({
            enforce :enforce,
            learning_mode :learning_mode,
            lm_enabled_once :lm_enabled_once,
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
        let field = fields.filter(field => field.id === this.props.field.control_field)[0];

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
        let field = fields.filter(field => field.id === this.props.field.control_field )[0];
        let lm_enabled_once_field_name = this.props.field.control_field+'_lm_enabled_once';
        let lm_enabled_once_field = fields.filter(field => field.id === lm_enabled_once_field_name)[0];
        let learning_mode = field.value === 'learning_mode' ? 1 : 0;
        let learning_mode_completed = field.value === 'completed' ? 1 : 0;

        if ( learning_mode ) {
            lm_enabled_once_field.value = 1;
        }

        field.value = learning_mode || learning_mode_completed ? 'disabled' : 'learning_mode';
        if ( learning_mode || learning_mode_completed ) {
            learning_mode = 0;
        } else {
            learning_mode = 1;
        }
        learning_mode_completed = 0;
        this.setState({
            learning_mode : learning_mode,
            lm_enabled_once : lm_enabled_once_field.value,
            learning_mode_completed : learning_mode_completed,
        });

        let saveFields = [];
        saveFields.push(field);
        saveFields.push(lm_enabled_once_field);
        rsssl_api.setFields(saveFields).then(( response ) => {

        });
    }

    /*
     * Handle data delete
     * @param enabled
     * @param clickedItem
     * @param type
     */
    onDeleteHandler( clickedItem ) {
        let field=this.props.field;
        if (typeof field.value === 'object') {
            field.value = Object.values(field.value);
        }

        //find this item in the field list and remove it.
        field.value.forEach(function(item, i) {
            if (item.id === clickedItem.id) {
                field.value.splice(i, 1);
            }
        });

        //remove objects from values
        for (const item of field.value){
            delete item.valueControl;
            delete item.statusControl;
            delete item.deleteControl;
        }

        //the updateItemId allows us to update one specific item in a field set.
        field.updateItemId = clickedItem.id;
        field.action = 'delete';
        let saveFields = [];

        saveFields.push(field);
        this.props.updateField(field.id, field.value);
        rsssl_api.setFields(saveFields).then(( response ) => {});
    }

    render(){
            let field = this.props.field;
            let fieldValue = field.value;
            let options = this.props.options;
            let configuringString = __("We're configuring your %s. Exit to edit and enforce.", "really-simple-ssl").replace('%s', field.label);
            let disabledString = __("%s has been disabled.", "really-simple-ssl").replace('%s', field.label);
            let enforcedString = __("%s is enforced.", "really-simple-ssl").replace('%s', field.label);
            const {
                filterValue,
                enforce,
                learning_mode,
                lm_enabled_once,
                learning_mode_completed,
            } = this.state;
            let enforceDisabled = !lm_enabled_once;
            const Filter = () => (
              <>
                <select onChange={ ( e ) => this.doFilter(e) } value={filterValue}>
                    <option value="-1" >{__("All", "really-simple-ssl")}</option>
                    <option value="1" >{__("Allowed", "really-simple-ssl")}</option>
                    <option value="0" >{__("Blocked", "really-simple-ssl")}</option>
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
            if ( typeof data === 'object' ) {
                data = Object.values(data);
            }
            if ( !Array.isArray(data) ) {
                data = [];
            }
            data = data.filter(item => item.status<2);
            if (filterValue!=-1) {
                data = data.filter(item => item.status==filterValue);
            }
            for (const item of data){
                if (item.login_status) item.login_statusControl = item.login_status == 1 ? __("success", "really-simple-ssl") : __("failed", "really-simple-ssl");
                item.statusControl = <ChangeStatus item={item} onChangeHandlerDataTableStatus={this.props.onChangeHandlerDataTableStatus} />;
                item.deleteControl = <Delete item={item} onDeleteHandler={this.onDeleteHandler} />;
            }
            const conditionalRowStyles = [
              {
                when: row => row.status ==0,
                classNames: ['rsssl-datatables-revoked'],
              },
            ];

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
                    <div className={ this.highLightClass}>
                        {data.length==0 && <>
                            <div className="rsssl-learningmode-placeholder">
                                <div></div><div></div><div></div><div></div>
                            </div>
                        </>}
                        {data.length>0 && <>
                            <DataTable
                                columns={columns}
                                data={data}
                                dense
                                pagination
                                noDataComponent={__("No results", "really-simple-ssl")}
                                persistTableHead
                                theme="really-simple-plugins"
                                customStyles={customStyles}
                                conditionalRowStyles={conditionalRowStyles}
                            /></>
                        }
                      <div className="rsssl-learning-mode-footer">
                          { enforce!=1 && <button disabled={enforceDisabled} className="button button-primary" onClick={ (e) => this.toggleEnforce(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
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
                        {enforce==1 && <div className="rsssl-locked">
                            <div className="rsssl-shield-overlay">
                                  <Icon name = "shield"  size="80px"/>
                            </div>
                            <div className="rsssl-locked-overlay">
                                <span className="rsssl-progress-status rsssl-learning-mode-enforced">{__("Enforced","really-simple-ssl")}</span>
                                {enforcedString}&nbsp;
                                <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => this.toggleEnforce(e) }>{__("Disable to configure", "really-simple-ssl") }</a>
                            </div>
                        </div>}
                        {learning_mode==1 && <div className="rsssl-locked">
                            <div className="rsssl-locked-overlay">
                                <span className="rsssl-progress-status rsssl-learning-mode">{__("Learning Mode","really-simple-ssl")}</span>
                                {configuringString}&nbsp;
                                <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => this.toggleLearningMode(e) }>{__("Exit learning mode", "really-simple-ssl") }</a>
                            </div>
                        </div>}
                        {learning_mode_completed==1 && <div className="rsssl-locked">
                            <div className="rsssl-locked-overlay">
                                <span className="rsssl-progress-status rsssl-learning-mode-completed">{__("Learning Mode","really-simple-ssl")}</span>
                                {__("We finished the configuration.", "really-simple-ssl")}&nbsp;
                                <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => this.toggleLearningMode(e) }>{__("Review the settings and enforce the policy", "really-simple-ssl") }</a>
                            </div>
                        </div>}
                        { rsssl_settings.pro_plugin_active && this.props.disabled && <div className="rsssl-locked">
                            <div className="rsssl-locked-overlay">
                                <span className="rsssl-progress-status rsssl-disabled">{__("Disabled ","really-simple-ssl")}</span>
                                {disabledString}
                            </div>
                        </div>}
                        <Filter />
                    </div>
                    </div>
                </>
            )
    }
}

export default LearningMode
