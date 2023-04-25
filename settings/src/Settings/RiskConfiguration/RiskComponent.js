import React, {useEffect,useState} from 'react';
import UseRiskData from "./RiskData";
import DataTable from 'react-data-table-component';
import {SelectControl} from "@wordpress/components";
import {__} from "@wordpress/i18n";
import useFields from "../FieldsData";

const RiskComponent = (props) => {
    //first we put the data in a state
    const {riskData, processing, vulEnabled, dataLoaded, fetchVulnerabilities, updateRiskData} = UseRiskData();
    const { fields, fieldAlreadyEnabled, getFieldValue, setChangedField, updateField, saveFields} = useFields();
    const [measuresEnabled, setMeasuresEnabled] = useState(false);

    useEffect(() => {
        if ( fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            if (!dataLoaded) {
                fetchVulnerabilities();
            }
        }
    }, [fields]);

    const toggleMeasuresEnabled = () => {
        let newValue = !measuresEnabled;
        setMeasuresEnabled(newValue);
        setChangedField('measures_enabled', newValue);
        updateField('measures_enabled', newValue);
        saveFields(true, false);
    }

    /**
     * Initialize
     */
    useEffect(() => {
        let enabled = getFieldValue('measures_enabled')==1;
        setMeasuresEnabled(enabled);

    }, [] );

    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;
    //we loop through the fields
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });


    //now we get the options for the select control
    let options = props.field.options;

    //we divide the key into label and the value into value
    options = Object.entries(options).map((item) => {
        return {label: item[1], value: item[0]};
    });

    //we only proceed if the data is loaded
    if (!dataLoaded) {
        return ( <><DataTable
            columns={columns}
            data={Object.values([])}
        />
            <div className="rsssl-locked-overlay"><span
            className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate vulnerability detection to enable this block.', 'really-simple-ssl')}</span>
        </div></>)
    }



    //and we add the select control to the data

    let data = [...riskData];
    for (const key in data) {
        let dataItem = {...data[key]}
        dataItem.riskSelection = <SelectControl
            disabled={processing}
            id={dataItem.id}
            name={dataItem.name}
            value={dataItem.value}
            options={options}
            label=''
            onChange={(fieldValue) => onChangeHandler(fieldValue, dataItem)
            }
        />
        data[key] = dataItem;
    }
    let processingClass = processing ? 'rsssl-processing' : '';
    console.log('riskData', riskData);
    return (
        <div className={processingClass}>
            <p>{
                __("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed euismod, nunc sit amet aliquam lacinia, nisl nisl aliquet nisl.","really-simple-ssl")
            }</p>
            <DataTable
                columns={columns}
                data={Object.values(data)}
            />
            { !measuresEnabled && <div className="rsssl-locked">
                <div className="rsssl-locked-overlay">
                    <span className="rsssl-progress-status rsssl-learning-mode">{__("Enable measures","really-simple-ssl")}</span>
                    <label>
                        <input type="checkbox"
                               checked ={measuresEnabled}
                               onChange={ ( e ) => toggleMeasuresEnabled() }
                        />
                        {__("I have read and understood the risk to intervene with these measures","really-simple-ssl")}
                    </label>
                    <a className="rsssl-learning-mode-link" href="https://really-simple-ssl.com/vulnerabilities-measures" target="_blank">{__("Read more", "really-simple-ssl") }</a>
                </div>
            </div> }
            { !getFieldValue('enable_vulnerability_scanner') && <div className="rsssl-locked">
                <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate vulnerability detection to enable this block.', 'really-simple-ssl')}</span>
                </div>
            </div>
            }
        </div>
    )

    function buildColumn(column) {
        return {
            name: column.name,
            sortable: column.sortable,
            width: column.width,
            selector: row => row[column.column],
            grow: column.grow,
        };
    }

    function onChangeHandler(fieldValue, item) {
         updateRiskData(item.id, fieldValue).then(() => {
             alert("saved");
         });
    }

}

export default RiskComponent;