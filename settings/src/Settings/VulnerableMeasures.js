import {
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
    Component,
} from '@wordpress/element';
import ChangeStatus from "./ChangeStatus";
import DataTable, {createTheme} from 'react-data-table-component';
import * as rsssl_api from "../utils/api";
import Icon from "../utils/Icon";


class VulnerableMeasures extends Component {

    constructor() {
        super(...arguments);
        console.log(this.props);
        this.state = {
            measures: [],
            loading: false,
            error: false,
        };
    }

    componentDidMount() {
        this.state.measures = this.props.fields[0].value;
        this.setState(this.state);
    }

    buildColumn(column) {
        return {
           name: column.name,
           sortable: column.sortable,
           width: column.width,
            grow: column.grow,
           selector: row => row[column.column],
            right: !!column.right,
       };
    }

    customStyles() {
        return {
            table: {
                style: {
                    border: 'none',
                    width: '100%',
                }
            },
            headCells: {
                style: {
                    paddingLeft: '0',
                    paddingRight: '0',
                }
            },
            cells: {
                style: {
                    paddingLeft: '10px',
                    paddingRight: '0',
                }
            }
        }
    }

    conditionalRowStyles() {
        //TODO: make this work
    }

    onChangeHandler( fieldValue, clickedItem, field ) {
        alert(fieldValue);
    }

    render() {
        const {measures, loading, error} = this.state;
        let columns = [];

        //we build the columns from the field definition
        this.props.field.columns.forEach((item) => {
            columns.push(this.buildColumn(item));
        });

        //now we get the options for the select control
        let options = this.props.field.options;
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
        createTheme('really-simple-plugins', {
            divider: {
                default: 'transparent',
            },
        }, 'light');
        return (
            <div className={ this.props.highLightClass}>
                <DataTable
                    columns={columns}
                    data={measures}
                    dense
                    striped={true}
                    highlightOnHover={true}
                    noDataComponent={__("No data found", "really-simple-ssl")}
                    conditionalRowStyles={this.conditionalRowStyles()}ß
                    theme="really-simple-plugins"
                />
            </div>
        );
    }
}

export default VulnerableMeasures;