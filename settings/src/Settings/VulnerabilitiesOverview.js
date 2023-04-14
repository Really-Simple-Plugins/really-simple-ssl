import {__} from '@wordpress/i18n';
import useRiskData from "./RiskConfiguration/RiskData";
import React, {useEffect, useState} from 'react';
import DataTable from "react-data-table-component";
import useFields from "./FieldsData";
import VulnerabilitiesIntro from "./VulnerabilitiesIntro";

const VulnerabilitiesOverview = (props) => {
    const {
        dataLoaded,
        vulList,
        introCompleted,
        fetchVulnerabilities,
        setDataLoaded
    } = useRiskData();
    const {fields, getField, fieldAlreadyEnabled, getFieldValue} = useFields();
    const [showIntro, setShowIntro] = useState(false);

    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;
    let enabled = false;

    function buildColumn(column) {
        return {
            name: column.name,
            sortable: column.sortable,
            width: column.width,
            visible: column.visible,
            selector: row => row[column.column],
        };
    }

    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

    //get data if field was already enabled, so not changed right now.
    useEffect(() => {
        if ( fieldAlreadyEnabled('enable_vulnerability_scanner') ) {
            if (getFieldValue('vulnerabilities_intro_shown')!=1 ) {
                if (!introCompleted) setShowIntro(true);
            } else {
                if (!dataLoaded) {
                    fetchVulnerabilities();
                }
            }
        }

        if ( getFieldValue('enable_vulnerability_scanner')==1 && !fieldAlreadyEnabled('enable_vulnerability_scanner') ) {
            setDataLoaded(false);
        }
    }, [fields]);

    fields.forEach(function (item, i) {
        if (item.id === 'enable_vulnerability_scanner') {
            enabled = item.value;
        }
    });

    if (!enabled) {
        return (
            //If there is no data or vulnerabilities scanner is disabled we show some dummy data behind a mask
            <>
                <DataTable
                    columns={columns}
                    //  data={dummyData}
                    dense
                    pagination
                    noDataComponent={__("No results", "really-simple-ssl")}
                    persistTableHead
                    //     customStyles={customStyles}
                >
                </DataTable>
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate vulnerability scanning to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            </>
        )
    }

    let data = vulList;
    //we need to add a key to the data called action wich produces the action buttons
    return (
        <>
            {data.length>0 &&
                <DataTable
                    columns={columns}
                    data={data}
                    dense
                    pagination
                    noDataComponent={__("No results", "really-simple-ssl")}
                    persistTableHead
                >
                </DataTable>
            }

            {showIntro && <>
                <VulnerabilitiesIntro/>
            </>
            }
        </>
    )

}

export default VulnerabilitiesOverview;