import { __ } from '@wordpress/i18n';
import {useState,useEffect} from '@wordpress/element';
import ChangeStatus from "./ChangeStatus";
import Delete from "./Delete";
import Icon from "../../utils/Icon";
import useFields from "./../FieldsData";
import useLearningMode from "./LearningModeData";
import {Button} from "@wordpress/components";
import React from "react";

const LearningMode = (props) => {
    const {updateField, getFieldValue, getField, setChangedField, highLightField, saveFields} = useFields();
    const {fetchLearningModeData, learningModeData, dataLoaded, updateStatus, deleteData } = useLearningMode();

    //used to show if a feature is already enforced by a third party
    const [enforcedByThirdparty, setEnforcedByThirdparty] = useState(0);
    //toggle from enforced to not enforced
    const [enforce, setEnforce] = useState(0);
    //toggle from learning mode to not learning mode
    const [learningMode, setLearningMode] = useState(0);
    //set learning mode to completed
    const [learningModeCompleted, setLearningModeCompleted] = useState(0);
    const [hasError, setHasError] = useState(false);
    //check if learningmode has been enabled at least once
    const [lmEnabledOnce, setLmEnabledOnce] = useState(0);
    //filter the data
    const [filterValue, setFilterValue] = useState(-1);
    //the value that is used to enable or disable this feature. On or of.
    const [controlField, setControlField] = useState(false);
    // the value that is used to select and deselect rows
    const [rowsSelected, setRowsSelected] = useState([]);
    const [rowCleared, setRowCleared] = useState(false);

    const [DataTable, setDataTable] = useState(null);
    const [theme, setTheme] = useState(null);
    useEffect( () => {
        import('react-data-table-component').then(({ default: DataTable, createTheme }) => {
            setDataTable(() => DataTable);
            setTheme(() => createTheme('really-simple-plugins', {
                divider: {
                    default: 'transparent',
                },
            }, 'light'));
        });

    }, []);


    /**
     * Styling
     */
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


;

    /**
     * Initialize
     */
    useEffect(() => {
        const run = async () => {
            await fetchLearningModeData(props.field.id);
            let controlField = getField(props.field.control_field );
            let enforced_by_thirdparty = controlField.value === 'enforced-by-thirdparty';
            let enforce = enforced_by_thirdparty || controlField.value === 'enforce';

            setControlField(controlField);
            setEnforcedByThirdparty(enforced_by_thirdparty);
            setLearningModeCompleted(controlField.value==='completed');
            setHasError(controlField.value==='error');
            setLmEnabledOnce(getFieldValue(props.field.control_field+'_lm_enabled_once'))
            setEnforce(enforce);
            setLearningMode(controlField.value === 'learning_mode');
        }
        run();
    }, [enforce, learningMode] );

    const toggleEnforce = async (e, enforceValue) => {
        e.preventDefault();
        //enforce this setting
        let controlFieldValue = enforceValue==1 ? 'enforce' : 'disabled';
        setEnforce(enforceValue);
        setLearningModeCompleted(0);
        setLearningMode(0);
        setChangedField(controlField.id, controlFieldValue);
        updateField(controlField.id, controlFieldValue);
        await saveFields(true, false);
        //await fetchLearningModeData();
    }


    const toggleLearningMode = async (e) => {
         e.preventDefault();
        let lmEnabledOnceField = getField(props.field.control_field+'_lm_enabled_once');
        if ( learningMode ) {
            setLmEnabledOnce(1);
            updateField(lmEnabledOnceField.id, 1);
        }

        let controlFieldValue;
        if ( learningMode || learningModeCompleted ) {
            setLearningMode(0);
            controlFieldValue = 'disabled';
        } else {
            setLearningMode(1);
            controlFieldValue = 'learning_mode';
        }
        setLearningModeCompleted(0);
        setChangedField(controlField.id, controlFieldValue);
        updateField(controlField.id, controlFieldValue);
        setChangedField(lmEnabledOnceField.id, lmEnabledOnceField.value);
        updateField(lmEnabledOnceField, lmEnabledOnceField.value);
        await saveFields(true, false);
    }

    const Filter = () => (
        <>
            <select onChange={ ( e ) => setFilterValue(e.target.value) } value={filterValue}>
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

    let data = learningModeData;
    data = data.filter(item => item.status<2);
    if (filterValue!=-1) {
        data = data.filter(item => item.status==filterValue);
    }
    for (const item of data){
        if (item.login_status) item.login_statusControl = item.login_status == 1 ? __("success", "really-simple-ssl") : __("failed", "really-simple-ssl");
        item.statusControl = <ChangeStatus item={item} field={props.field} />;
        item.deleteControl = <Delete item={item} field={props.field}/>;
        item.grouped = <div className="rsssl-action-buttons">
            <ChangeStatus item={item} field={props.field} />
            <Delete item={item} field={props.field}/>
        </div>
    }

    const handleMultiRowStatus = (status, selectedRows, type) => {
        selectedRows.forEach(row => {
            //the updateItemId allows us to update one specific item in a field set.
            updateStatus(status, row, type);
        });
        setRowCleared(true);
        setRowsSelected([]);
        // Reset rowCleared back to false after the DataTable has re-rendered
        setTimeout(() => setRowCleared(false), 0);
    }

    const handleMultiRowDelete = (  selectedRows, type) => {
        selectedRows.forEach(row => {
            //the updateItemId allows us to update one specific item in a field set.
            deleteData( row, type );
        });
        setRowCleared(true);
        setRowsSelected([]);
        // Reset rowCleared back to false after the DataTable has re-rendered
        setTimeout(() => setRowCleared(false), 0);
    }
    function handleSelection(state) {
        setRowCleared(false);
        setRowsSelected(state.selectedRows);
    }

    if (!DataTable || !theme) return null;
    return (
        <>
            <div>
                { !dataLoaded && <>
                    <div className="rsssl-learningmode-placeholder">
                        <div></div><div></div><div></div><div></div>
                    </div>
                </>}
                {rowsSelected.length > 0 && (
                    <div
                        style={{
                            marginTop: '1em',
                            marginBottom: '1em',
                        }}
                    >
                        <div
                            className={"rsssl-multiselect-datatable-form rsssl-primary"}
                        >
                            <div>
                                {__("You have selected", "really-simple-ssl")} {rowsSelected.length} {__("rows", "really-simple-ssl")}
                            </div>

                            <div className="rsssl-action-buttons">
                                {(Number(filterValue) === -1 || Number(filterValue) === 0 ) &&
                                <div className="rsssl-action-buttons__inner">
                                    <Button
                                        // className={"button button-red rsssl-action-buttons__button"}
                                        className={"button button-secondary rsssl-status-allowed rsssl-action-buttons__button"}
                                        onClick={ () => handleMultiRowStatus( 0, rowsSelected, props.field.id ) }
                                    >
                                        {__('Allow', 'really-simple-ssl')}
                                    </Button>
                                </div>
                                }
                                {(Number(filterValue) === -1 || Number(filterValue) === 1 ) &&
                                    <div className="rsssl-action-buttons__inner">
                                        <Button
                                            // className={"button button-red rsssl-action-buttons__button"}
                                            className={"button button-primary rsssl-action-buttons__button"}
                                            onClick={ () => handleMultiRowStatus( 1, rowsSelected, props.field.id ) }
                                        >
                                            {__('Revoke', 'really-simple-ssl')}
                                        </Button>
                                    </div>
                                }
                                <div className="rsssl-action-buttons__inner">
                                    <Button
                                        // className={"button button-red rsssl-action-buttons__button"}
                                        className={"button button-red rsssl-action-buttons__button"}
                                        onClick={ () => handleMultiRowDelete( rowsSelected, props.field.id ) }
                                    >
                                        {__('Remove', 'really-simple-ssl')}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
                {dataLoaded && <>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                        noDataComponent={__("No results", "really-simple-ssl")}
                        persistTableHead
                        theme={theme}
                        customStyles={customStyles}
                        conditionalRowStyles={conditionalRowStyles}
                        selectableRows
                        selectableRowsHighlight={true}
                        onSelectedRowsChange={handleSelection}
                        clearSelectedRows={rowCleared}
                    /></>
                }
              <div className={"rsssl-learning-mode-footer "}>
                  {hasError && <div className="rsssl-locked">
                          <div className="rsssl-locked-overlay">
                              <span className="rsssl-progress-status rsssl-learning-mode-error">{__("Error detected","really-simple-ssl")}</span>
                              {__("%s cannot be implemented due to server limitations. Check your notices for the detected issue.", "really-simple-ssl").replace('%s', field.label)}&nbsp;
                              <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => toggleEnforce(e, false ) }>{__("Disable", "really-simple-ssl") }</a>
                          </div>
                      </div>
                  }
                  {!hasError && <>
                      { enforce!=1 && <button disabled={enforceDisabled} className="button button-primary" onClick={ (e) => toggleEnforce(e, true ) }>{__("Enforce","really-simple-ssl")}</button> }
                      { !enforcedByThirdparty && enforce==1 && <button className="button" onClick={ (e) => toggleEnforce(e, false ) }>{__("Disable","really-simple-ssl")}</button> }
                      <label>
                          <input type="checkbox"
                              disabled = {enforce}
                              checked ={learningMode==1}
                              value = {learningMode}
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
                    {learningMode==1 && <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-learning-mode">{__("Learning Mode","really-simple-ssl")}</span>
                            {configuringString}&nbsp;
                            <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => toggleLearningMode(e) }>{__("Exit", "really-simple-ssl") }</a>
                        </div>
                    </div>}
                    { learningModeCompleted==1 && <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-progress-status rsssl-learning-mode-completed">{__("Learning Mode","really-simple-ssl")}</span>
                            {__("We finished the configuration.", "really-simple-ssl")}&nbsp;
                            <a className="rsssl-learning-mode-link" href="#" onClick={ (e) => toggleLearningMode(e) }>{__("Review the settings and enforce the policy", "really-simple-ssl") }</a>
                        </div>
                    </div> }
                    { rsssl_settings.pro_plugin_active && props.disabled && <div className="rsssl-locked ">
                        <div className="rsssl-locked-overlay">
                            { !enforcedByThirdparty && <span className="rsssl-progress-status rsssl-disabled">{__("Disabled","really-simple-ssl")}</span> }
                            { enforcedByThirdparty && <span className="rsssl-progress-status rsssl-learning-mode-enforced">{__("Enforced","really-simple-ssl")}</span> }
                            { disabledString }
                        </div>
                    </div>}
                  </>
                  }
                <Filter />
            </div>
            </div>
        </>
    )
}

export default LearningMode
