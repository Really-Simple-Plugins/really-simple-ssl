import React, {useEffect,useState} from 'react';
import UseRiskData from "./RiskData";
import DataTable from 'react-data-table-component';
import {SelectControl} from "@wordpress/components";
import {__} from "@wordpress/i18n";
import useFields from "../FieldsData";

const RiskComponent = (props) => {
    //first we put the data in a state
    const {riskData, dummyRiskData, processing, dataLoaded, fetchVulnerabilities, updateRiskData} = UseRiskData();
    const { fields, fieldAlreadyEnabled, getFieldValue, setChangedField, updateField, saveFields} = useFields();
    const [measuresEnabled, setMeasuresEnabled] = useState(false);
    const [vulnerabilityDetectionEnabled, setVulnerabilityDetectionEnabled] = useState(false);
    useEffect(() => {
        if ( fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            if (!dataLoaded) {
                fetchVulnerabilities();
            }
        }
        let vulnerabilitiesEnabled = getFieldValue('enable_vulnerability_scanner')==1;
        setVulnerabilityDetectionEnabled(vulnerabilitiesEnabled);
        let measuresOn = getFieldValue('measures_enabled')==1;
        setMeasuresEnabled(measuresOn);
    }, [fields]);

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

    //and we add the select control to the data
    let data = [...riskData];
    data = data.length===0 ? [...dummyRiskData] : data;
    let disabled = !vulnerabilityDetectionEnabled || !measuresEnabled || processing;
    for (const key of data) {
        let dataItem = {...data[key]}
        dataItem.riskSelection = <SelectControl
            disabled={disabled}
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
    let processingClass = disabled ? 'rsssl-processing' : '';

    return (
        <div className={processingClass}>
            <p>{
                __("Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed euismod, nunc sit amet aliquam lacinia, nisl nisl aliquet nisl.","really-simple-ssl")
            }</p>
            <DataTable
                columns={columns}
                data={Object.values(data)}
            />
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
         updateRiskData(item.id, fieldValue);
    }

}

export default RiskComponent;