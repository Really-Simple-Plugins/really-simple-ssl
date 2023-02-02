import { __ } from '@wordpress/i18n';
import {useState,useEffect} from '@wordpress/element';
import ChangeStatus from "./ChangeStatus";
import DataTable, {createTheme} from 'react-data-table-component';
import Icon from "../utils/Icon";
import useFields from "./FieldsData";

const Delete = () => {
   return (
       <button type="button" className=" rsssl-learning-mode-delete" onClick={ () => props.onDeleteHandler( props.item ) }>
           <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" height="16" >
               <path fill="#000000" d="M310.6 361.4c12.5 12.5 12.5 32.75 0 45.25C304.4 412.9 296.2 416 288 416s-16.38-3.125-22.62-9.375L160 301.3L54.63 406.6C48.38 412.9 40.19 416 32 416S15.63 412.9 9.375 406.6c-12.5-12.5-12.5-32.75 0-45.25l105.4-105.4L9.375 150.6c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L160 210.8l105.4-105.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-105.4 105.4L310.6 361.4z"/>
           </svg>
       </button>
    )
}

const LearningMode = () => {
    const {fields, updateField, getFieldValue, getField, setChangedField, highLightField, saveFields} = useFields();
    const [enforcedByThirdparty, setEnforcedByThirdparty] = useState(0);
    const [enforce, setEnforce] = useState(0);
    const [learningMode, setLearningMode] = useState(0);
    const [learningModeCompleted, setLearningModeCompleted] = useState(0);
    const [lmEnabledOnce, setLmEnabledOnce] = useState(0);
    const [filterValue, setFilterValue] = useState(-1);
    const [controlField, setControlField] = useState(false);

    useEffect(async () => {
        let controlField = getField(props.field.control_field );
        let enforced_by_thirdparty = controlField.value === 'enforced-by-thirdparty';
        let enforce = enforced_by_thirdparty || controlField.value === 'enforce';

        //we somehow need this to initialize the field. Otherwise it doesn't work on load. need to figure that out.
        updateField(controlField.id, controlField.value);
        setControlField(controlField);
        setEnforcedByThirdparty(enforced_by_thirdparty);
        setLearningModeCompleted(controlField.value==='completed');
        setLmEnabledOnce(getFieldValue(props.field.control_field+'_lm_enabled_once'))
        setEnforce(enforce);
        setLearningMode(controlField.value === 'learning_mode');
    }, [] );

    const doFilter = (e) => {
        setFilterValue(e.target.value)
    }

    const toggleEnforce = (e, enforce) => {
        e.preventDefault();
        //enforce this setting
        controlField.value = enforce==1 ? 'enforce' : 'disabled';
        setEnforce(enforce);
        setLearningModeCompleted(0);
        setChangedField(controlField);
        updateField(controlField);
        saveFields(true, false);
    }

    const toggleLearningMode = (e) => {
         e.preventDefault();
        let lmEnabledOnceField = getField(props.field.control_field+'_lm_enabled_once')[0];
        let copyControlfield = {...controlField};
        let copyLmEnabledOnceField = {...lmEnabledOnceField};
        if ( learningMode ) {
            copyLmEnabledOnceField.value = 1;
            setLmEnabledOnce(1);
        }

        if ( learningMode || learningModeCompleted ) {
            setLearningMode(0);
            copyControlfield.value = 'disabled';
        } else {
            setLearningMode(1);
            copyControlfield.value = 'learning_mode';
        }

        setLearningModeCompleted(0);
        setChangedField(copyControlfield);
        updateField(copyControlfield);

        setChangedField(copyLmEnabledOnceField);
        updateField(copyLmEnabledOnceField);

        saveFields(true, false);
    }

    /*
     * Handle data delete
     * @param enabled
     * @param clickedItem
     * @param type
     */
    const onDeleteHandler = ( clickedItem ) => {
        let field = props.field;
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
        setChangedField(field.id, field.value);
        updateField(field.id, field.value);
        saveFields(true, false);
    }

    const Filter = () => (
        <>
            <select onChange={ ( e ) => doFilter(e) } value={filterValue}>
                <option value="-1" >{__("All", "really-simple-ssl")}</option>
                <option value="1" >{__("Allowed", "really-simple-ssl")}</option>
                <option value="0" >{__("Blocked", "really-simple-ssl")}</option>
            </select>
        </>
    );

    let field = props.field;
    let configuringString = __(" The %s is now in report-only mode and will collect directives. This might take a while. Afterwards you can Exit, Edit and Enforce these Directives.", "really-simple-ssl").replace('%s', field.label);
    let disabledString = __("%s has been disabled.", "really-simple-ssl").replace('%s', field.label);
    let enforcedString = __("%s is enforced.", "really-simple-ssl").replace('%s', field.label);
    let enforceDisabled = !lmEnabledOnce;
    if (enforcedByThirdparty) disabledString = __("%s is already set outside Really Simple SSL.", "really-simple-ssl").replace('%s', field.label);
    let highLightClass = 'rsssl-field-wrap';
    if ( highLightField===props.field.id ) {
        highLightClass = 'rsssl-field-wrap rsssl-highlight';
    }
    //build our header
    let columns = [];
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
        item.statusControl = <ChangeStatus item={item} field={props.field} />;
        item.deleteControl = <Delete item={item} onDeleteHandler={() => onDeleteHandler() } />;
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
            <div className={ highLightClass}>
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
                  { enforce!=1 && <button disabled={enforceDisabled} className="button button-primary" onClick={ (e) => toggleEnforce(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                  { !enforced_by_thirdparty && enforce==1 && <button className="button" onClick={ (e) => toggleEnforce(e, false ) }>{__("Disable","really-simple-ssl")}</button> }
                  <label>
                      <input type="checkbox"
                          disabled = {enforce}
                          checked ={learning_mode==1}
                          value = {learning_mode}
                          onChange={ ( e ) => toggleLearningMode(e) }
                      />
                      {__("Enable Learning Mode to configure automatically","really-simple-ssl")}
                  </label>
                { enforce==1 && <div className="rsssl-locked">
                    <div className="rsssl-shield-overlay">
                          <Icon name = "shield"  size="80px"/>
                    </div>
                    <div className="rsssl-locked-overlay">
                        <span className="rsssl-progress-status rsssl-learning-mode-enforced">{__("Enforced","really-simple-ssl")}</span>
                        {enforcedString}&nbsp;
                        <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => toggleEnforce(e) }>{__("Disable to configure", "really-simple-ssl") }</a>
                    </div>
                </div>}
                {learning_mode==1 && <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay">
                        <span className="rsssl-progress-status rsssl-learning-mode">{__("Learning Mode","really-simple-ssl")}</span>
                        {configuringString}&nbsp;
                        <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => toggleLearningMode(e) }>{__("Exit", "really-simple-ssl") }</a>
                    </div>
                </div>}
                { learning_mode_completed==1 && <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay">
                        <span className="rsssl-progress-status rsssl-learning-mode-completed">{__("Learning Mode","really-simple-ssl")}</span>
                        {__("We finished the configuration.", "really-simple-ssl")}&nbsp;
                        <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => toggleLearningMode(e) }>{__("Review the settings and enforce the policy", "really-simple-ssl") }</a>
                    </div>
                </div> }
                { rsssl_settings.pro_plugin_active && props.disabled && <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay">
                        { !enforced_by_thirdparty && <span className="rsssl-progress-status rsssl-disabled">{__("Disabled ","really-simple-ssl")}</span> }
                        { enforced_by_thirdparty && <span className="rsssl-progress-status rsssl-learning-mode-enforced">{__("Enforced","really-simple-ssl")}</span> }
                        { disabledString }
                    </div>
                </div>}
                <Filter />
            </div>
            </div>
        </>
    )

}

export default LearningMode
