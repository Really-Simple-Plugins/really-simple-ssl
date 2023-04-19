import React, {useEffect} from 'react';
import UseRiskData from "./RiskData";
import DataTable from 'react-data-table-component';
import {SelectControl} from "@wordpress/components";
import sleeper from "../../utils/sleeper";
import {dispatch} from '@wordpress/data';
import {__} from "@wordpress/i18n";
import useFields from "../FieldsData";

const RiskComponent = (props) => {
    //first we put the data in a state
    const {riskData, processing, dataLoaded, fetchVulnerabilities, updateRiskData} = UseRiskData();
    const { fields, fieldAlreadyEnabled} = useFields();

    useEffect(() => {
        if ( fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            if (!dataLoaded) {
                fetchVulnerabilities();
            }
        }
    }, [fields]);

    //we only proceed if the data is loaded
    if (!dataLoaded) {
        return null;
    }

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
    return (
        <div className={processingClass}>
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