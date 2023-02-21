import DataTable, {createTheme} from 'react-data-table-component';
import {SelectControl} from "@wordpress/components";
import {UseRiskDetection} from "./VulnerableMeasuresData";

const VulnerableMeasures = (props) => {
    //first we put the data in a state
    const {measuresData} = UseRiskDetection();
    
    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;
    //we loop through the fields
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
    UseMeasuresData().getMeasuresData.forEach((item) => {
        item.riskSelection = <SelectControl
            risk={item.risk}
            value={item.value}
            options={options}
            label=''
            onChange={(fieldValue) => onChangeHandler(fieldValue, item.value, item.risk)
        }
        />
    });

    return (
        <div>
            <DataTable
                columns={columns}
                data={measuresData}
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
        grow: column.grow,
    };
}

function onChangeHandler( fieldValue, clickedItem, risk ) {
    let measuresData = UseMeasuresData().measuresData;
    let index = measuresData.findIndex((i) => i.value === clickedItem);
    console.log(measuresData[index]);

}
export default VulnerableMeasures;