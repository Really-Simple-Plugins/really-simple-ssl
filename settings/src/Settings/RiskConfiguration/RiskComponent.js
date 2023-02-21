import React, {useEffect} from 'react';
import UseRiskData from "./RiskData";
import DataTable from 'react-data-table-component';
import {SelectControl} from "@wordpress/components";

const RiskComponent = (props) => {
    //first we put the data in a state
    const {riskData, dataLoaded, fetchRiskData, setData, updateRiskData} = UseRiskData();

    useEffect(() => {
        fetchRiskData();
    }, []);

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

    //we check if the property request_success exists if so we remove it
    if (riskData.hasOwnProperty('request_success')) {
        delete riskData.request_success;
    }

    //and we add the select control to the data
    Object.keys(riskData).forEach((item) => {
        riskData[item].riskSelection = <SelectControl
            id={riskData[item].id}
            value={riskData[item].value}
            options={options}
            label=''
            onChange={(fieldValue) => onChangeHandler(fieldValue, riskData[item])
            }
        />
    });

    return (
        <div>
            <DataTable
                columns={columns}
                data={Object.values(riskData)}
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