import {__} from '@wordpress/i18n';
import useRiskData from "./RiskData";
import {useEffect, useState} from '@wordpress/element';
import DataTable, {createTheme} from "react-data-table-component";
import useFields from "../FieldsData";
import useProgress from "../../Dashboard/Progress/ProgressData";
import useRunnerData from "./RunnerData";
import './datatable.scss';

const VulnerabilitiesOverview = (props) => {
    const {getProgressData} = useProgress();
    const [enabled, setEnabled] = useState(false);

    const {
        dataLoaded,
        vulList,
        fetchVulnerabilities,
        setDataLoaded,
        fetchFirstRun
    } = useRiskData();
    const {getFieldValue, handleNextButtonDisabled, fieldAlreadyEnabled, fieldsLoaded} = useFields();
    const [searchTerm, setSearchTerm] = useState("");
    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;
    const customStyles = {
        headCells: {
            style: {
                paddingLeft: '0', // override the cell padding for head cells
                paddingRight: '0',
            },
        },
        cells: {
            style: {
                paddingLeft: '0', // override the cell padding for data cells
                paddingRight: '0',
            },
        },
    };
    createTheme('really-simple-plugins', {
        divider: {
            default: 'transparent',
        },
    }, 'light');

    function buildColumn(column) {
        return {
            name: column.name,
            sortable: column.sortable,
            visible: column.visible,
            selector: row => row[column.column],
            searchable: column.searchable,
            grow:column.grow,
            width: column.width,
        };
    }

    useEffect(() => {
        if (!fieldsLoaded) return;
        setEnabled(getFieldValue('enable_vulnerability_scanner') == 1);
    }, [getFieldValue('enable_vulnerability_scanner')])

    let dummyData = [['', '', '', '', ''], ['', '', '', '', ''], ['', '', '', '', '']];
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

    //get data if field was already enabled, so not changed right now.
    useEffect(() => {
        let vulnerabilityDetectionEnabledAndSaved = fieldAlreadyEnabled('enable_vulnerability_scanner');

        // let introShown = getFieldValue('vulnerabilities_intro_shown') == 1;
        if ( !vulnerabilityDetectionEnabledAndSaved ) {
            return;
        }
        setDataLoaded(false);

    }, [ getFieldValue('enable_vulnerability_scanner') ]);

    useEffect(() => {
        if ( dataLoaded ) {
            return;
        }

        let vulnerabilityDetectionEnabledAndSaved = fieldAlreadyEnabled('enable_vulnerability_scanner');
        if ( vulnerabilityDetectionEnabledAndSaved ) {
            //if just enabled, but intro already shown, just get the first run data.
            initialize();
        }

    }, [ dataLoaded ]);

    const initialize = async () => {
        await fetchFirstRun();
        await fetchVulnerabilities();
        await getProgressData();
    }

    let data = vulList.map(item => ({
        ...item,
        risk_name: <span className={"rsssl-badge-large rsp-risk-level-" + item.risk_level}>
        {/* Convert the first character to uppercase and append the rest of the string */}
            {item.risk_name.charAt(0).toUpperCase() + item.risk_name.slice(1).replace('-risk', '')}
    </span>
    }));
    if (searchTerm.length > 0) {
        data = data.filter(function (item) {
            //we check if the search value is in the name or the risk name
            if (item.Name.toLowerCase().includes(searchTerm.toLowerCase())) {
                return item;
            }
        });
    }

    return (
        <div style={{marginTop: '5px'}}>
            {!enabled ? (
                <>
                    <DataTable
                        columns={columns}
                        data={dummyData}
                        dense
                        pagination
                        noDataComponent={__("No results", "really-simple-ssl")}
                        persistTableHead
                        theme="really-simple-plugins"
                        customStyles={customStyles}
                    />
                    <div className="rsssl-locked">
                        <div className="rsssl-locked-overlay">
                            <span className="rsssl-task-status rsssl-open">
                                {__('Disabled', 'really-simple-ssl')}
                            </span>
                            <span>
                                {__('Activate vulnerability detection to enable this block.', 'really-simple-ssl')}
                            </span>
                        </div>
                    </div>
                </>
            ) : (
                <>
                    <div className="rsssl-container">
                        <div className="rsssl-search-bar">
                            <div className="rsssl-search-bar__inner">
                                <div className="rsssl-search-bar__icon"></div>
                                <input
                                    type="text"
                                    className="rsssl-search-bar__input"
                                    placeholder={__("Search", "really-simple-ssl")}
                                    onKeyUp={event => {
                                        setSearchTerm(event.target.value);
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                    <DataTable
                        columns={columns}
                        data={data}
                        dense
                        pagination
                        persistTableHead
                        noDataComponent={__("No vulnerabilities found", "really-simple-ssl")}
                        theme="really-simple-plugins"
                        customStyles={customStyles}
                    />
                </>
            )}
        </div>
    );

}

export default VulnerabilitiesOverview;
