import {__} from '@wordpress/i18n';
import useRiskData from "./RiskData";
import React, {useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import useFields from "../FieldsData";
import VulnerabilitiesIntro from "./VulnerabilitiesIntro";
import useProgress from "../../Dashboard/Progress/ProgressData";
import {Button} from "@wordpress/components";

const VulnerabilitiesOverview = (props) => {
    const {getProgressData} = useProgress();
    const {
        dataLoaded,
        vulList,
        introCompleted,
        fetchVulnerabilities,
        setDataLoaded,
        fetchFirstRun
    } = useRiskData();
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();
    const [showIntro, setShowIntro] = useState(false);
    const [searchTerm, setSearchTerm] = useState("");
    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;
    let enabled = false;
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
            width: column.width,
            visible: column.visible,
            selector: row => row[column.column],
            searchable: column.searchable,
        };
    }

    let dummyData = [['', '', '', '', ''], ['', '', '', '', ''], ['', '', '', '', '']];
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

    //get data if field was already enabled, so not changed right now.
    useEffect(() => {
        if (fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            if (getFieldValue('vulnerabilities_intro_shown') != 1 && !introCompleted) {
                setShowIntro(true);
            } else {
                //if just enabled, but intro already shown, just get the first run data.
                if (!dataLoaded) {
                    initialize();
                }
            }
        }
    }, [fields, dataLoaded]);

    useEffect(() => {
        //if this value changes, reload vulnerabilities data
        if (getFieldValue('enable_vulnerability_scanner') == 1 && !fieldAlreadyEnabled('enable_vulnerability_scanner')) {
            setDataLoaded(false);
        }
    }, [fields]);

    const initialize = async () => {
        await fetchFirstRun();
        await fetchVulnerabilities();
        await getProgressData();
    }

    fields.forEach(function (item, i) {
        if (item.id === 'enable_vulnerability_scanner') {
            enabled = item.value;
        }
    });

    if (!enabled) {
        return (
            //If there is no data or vulnerabilities scanner is disabled we show some dummy data behind a mask
            <>
                {showIntro && <>
                    <VulnerabilitiesIntro/>
                </>
                }
                <DataTable
                    columns={columns}
                    data={dummyData}
                    dense
                    pagination
                    noDataComponent={__("No results", "really-simple-ssl")}
                    persistTableHead
                    theme="really-simple-plugins"
                    customStyles={customStyles}
                >
                </DataTable>
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate vulnerability detection to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            </>
        )
    }
    let data = vulList.map(item => ({
        ...item,
        risk_name: <span
            className={"rsssl-badge-large rsp-" + item.risk_name.toLowerCase().replace('-risk', '')}>{item.risk_name.replace('-risk', '')}</span>
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
        <>
            {showIntro && <>
                <VulnerabilitiesIntro/>
            </>
            }
            {/* We add a searchbox */}
            <div className="rsssl-container">
                <div>

                </div>
                {/*Display the search bar*/}
                <div className="rsssl-search-bar">
                    <div className="rsssl-search-bar__inner">
                        <div className="rsssl-search-bar__icon"></div>
                        <input
                            type="text"
                            className="rsssl-search-bar__input"
                            placeholder={__("Search", "really-simple-ssl")}
                            onKeyUp={event => {
                                //we get the value from the search bar
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
            >
            </DataTable>


        </>
    )

}

export default VulnerabilitiesOverview;
