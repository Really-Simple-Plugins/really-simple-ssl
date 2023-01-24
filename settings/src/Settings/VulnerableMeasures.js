import {__} from '@wordpress/i18n';
import {Component,} from '@wordpress/element';
import DataTable, {createTheme} from 'react-data-table-component';
import Icon from "../utils/Icon";
import {SelectControl} from "@wordpress/components";

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
           selector: row => row[column.column],
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
                    paddingLeft: '0',
                    paddingRight: '0',
                }
            }
        }
    }

    conditionalRowStyles() {
        //TODO: make this work
    }

    onChangeHandler( fieldValue, clickedItem, field ) {
        alert('fieldValue');
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

        //and we add the select control to the data
        measures.forEach((item) => {
            item.riskSelection = <SelectControl
                help=''
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
            <div className="rsssl-measures-datatable">
                <DataTable
                    columns={columns}
                    data={measures}
                    dense
                    noDataComponent={__("No data found", "really-simple-ssl")}
                    theme="really-simple-plugins"
                    customStyles={this.customStyles()}
                    conditionalRowStyles={this.conditionalRowStyles()}
                />
            </div>
        );
    }
}

export default VulnerableMeasures;