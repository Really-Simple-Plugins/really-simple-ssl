import { __ } from '@wordpress/i18n';
import {useState,useEffect} from '@wordpress/element';
import DataTable, {createTheme} from 'react-data-table-component';
import UseMeasuresData from "./VulnerableMeasuresData";
import {SelectControl} from "@wordpress/components";
const VulnerableMeasures = (props) => {
    const {fetchMeasuresData, measuresData, dataLoaded} = UseMeasuresData();
    const measures = props.field.value;
    let field = props.field;
    let columns = [];
    field.columns.forEach(function(item, i) {
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
    measures.forEach((item) => {
        item.riskSelection = <SelectControl
            value={item.value}
            options={options}
            label=''
            onChange={ ( fieldValue ) => this.onChangeHandler( fieldValue, item, 'value' ) }
        />
    });
    console.log(columns);
    return (
        <div>
            <DataTable
                columns={columns}
                data={measures}
            />
        </div>
    )
}

function buildColumn(column) {
    return {
        name: column.name,
        sortable: column.sortable,
        width: column.width,
        selector: row => row[column.column],
    };
}
export default VulnerableMeasures;