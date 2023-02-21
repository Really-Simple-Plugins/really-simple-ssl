import React from 'react';
import UseRiskData from "./RiskData";
import DataTable from 'react-data-table-component';
import {SelectControl} from "@wordpress/components";

const RiskComponent = (props) => {
    let {riskData, dataLoaded, fetchRiskData} = UseRiskData();
    if(riskData.length === 0) {
        if (!dataLoaded) {
            fetchRiskData().then((response) => {
                dataLoaded = true;
            }).catch((error) => {
                console.log('error', error);
            });
        }
    }
    let field = props.field;
    let columns = [];
    field.columns.forEach(function(item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

    let options = props.field.options;
    //and we add the select control to the data
    riskData.forEach((item) => {
        //only when the item is an object
        if (typeof item === 'object') {
            item.riskSelection = <SelectControl
                value={item.value}
                options={options}
                label=''
                onChange={(fieldValue) => onChangeHandler(fieldValue)}
            />
        }
    });
    function buildColumn(column) {
        return {
            name: column.name,
            sortable: column.sortable,
            width: column.width,
            selector: row => row[column.column],
            grow: column.grow,
        };
    }

    function onChangeHandler( fieldValue ) {
        alert('i have come this far');
    }

    console.log('riskData', riskData);
    if(riskData.length !== 0) {
        return (

            <div>
                <DataTable
                    columns={columns}
                    data={riskData}
                />
            </div>
        )
    } else {
        return (
            <div>
                <p>Loading...</p>
            </div>
        )
    }
}

export default RiskComponent;