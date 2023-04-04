import {__} from '@wordpress/i18n';
import useVulnerabilityData from "../Dashboard/Vulnerabilities/VulnerabilityData";
import React, {useEffect} from 'react';
import DataTable from "react-data-table-component";
import Icon from "../utils/Icon";
import useFields from "./FieldsData";
import {Button} from "@wordpress/components";
import VulnerabilitiesIntro from "./VulnerabilitiesIntro";

const VulnerabilitiesOverview = (props) => {
    const {
        dataLoaded,
        vulList,
        firstRun,
        fetchVulnerabilities
    } = useVulnerabilityData();

    const {
        changedFields,
        fields,
    } = useFields();

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

    useEffect(() => {
        const run = async () => {
            await fetchVulnerabilities();
        }
        run();
    }, []);

    fields.forEach(function (item, i) {
        if (item.id === 'enable_vulnerability_scanner') {
            enabled = item.value;
        }
    });

    //we run this only once
    if (dataLoaded && !firstRun && enabled) {
        //we display the wow factor
        return (<VulnerabilitiesIntro/>);
    }

    if (!dataLoaded || vulList.length === 0 || !enabled) {
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

    /**
     * Styling
     */
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

    let data = vulList;

    //we need to add a key to the data called action wich produces the action buttons


    if (typeof data === 'object') {
        //we make it an array
        data = Object.values(data);
    }
    const btnStyle = {
        marginLeft: '10px'
    }
    data.forEach(function (item, i) {
        let rsssid = item.rss_identifier;
        item.vulnerability_action = <div className="rsssl-vulnerability-action">
            <a className="button" href={"https://really-simple-ssl.com/vulnerabilities/" + rsssid}
               target={"_blank"}>{__("Details", "really-simple-ssl")}</a>
            <a target={"_blank"} href="/wp-admin/plugins.php?plugin_status=upgrade" className="button button-primary"
               style={btnStyle}>{__("View", "really-simple-ssl")}</a>
        </div>

    });
    return (
        <DataTable
            columns={columns}
            data={data}
            dense
            pagination
            noDataComponent={__("No results", "really-simple-ssl")}
            persistTableHead
            customStyles={customStyles}
        >
        </DataTable>
    )
}

export default VulnerabilitiesOverview;