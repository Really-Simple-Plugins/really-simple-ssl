import {useEffect,useState} from '@wordpress/element';
import UseRiskData from "./RiskData";
import useFields from "../FieldsData";
import {__} from "@wordpress/i18n";

const RiskComponent = (props) => {
    //first we put the data in a state
    const {riskData, dummyRiskData, processing, dataLoaded, fetchVulnerabilities, updateRiskData} = UseRiskData();
    const { fields, fieldAlreadyEnabled, getFieldValue, setChangedField, updateField, saveFields} = useFields();
    const [measuresEnabled, setMeasuresEnabled] = useState(false);
    const [vulnerabilityDetectionEnabled, setVulnerabilityDetectionEnabled] = useState(false);
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

    useEffect(() => {
        if ( fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            if (!dataLoaded) {
                fetchVulnerabilities();
            }
        }
        let vulnerabilitiesEnabled = fieldAlreadyEnabled('enable_vulnerability_scanner' );
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
    let data = Array.isArray(riskData) ? [...riskData] : [];
    data = data.length===0 ? [...dummyRiskData] : data;
    let disabled = !vulnerabilityDetectionEnabled || !measuresEnabled;
    for (const key in data) {
        let dataItem = {...data[key]}
        dataItem.riskSelection = <select disabled={processing || disabled} value={dataItem.value} onChange={(e) => onChangeHandler(e.target.value, dataItem)}>
            {options.map((option,i) => <option key={'risk-'+i} value={option.value} disabled={ dataItem.disabledRiskLevels &&  dataItem.disabledRiskLevels.includes(option.value)} >{option.label}</option>) }
        </select>
        data[key] = dataItem;
    }
    let processingClass = disabled ? 'rsssl-processing' : '';

    return (
        <div>
            {DataTable && <DataTable
                columns={columns}
                data={Object.values(data)}
                dense
                pagination={false}
                persistTableHead
                noDataComponent={__("No vulnerabilities found", "really-simple-ssl")}
                theme={theme}
            /> }
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
