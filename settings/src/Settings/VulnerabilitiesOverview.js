import {__} from '@wordpress/i18n';
import useVulnerabilityData from "../Dashboard/Vulnerabilities/VulnerabilityData";
import React, {useEffect} from 'react';
import DataTable from "react-data-table-component";
import Icon from "../utils/Icon";
import {Button} from "@wordpress/components";

const VulnerabilitiesOverview = (props) => {
    const {
        dataLoaded,
        vulList,
        fetchVulnerabilities
    } = useVulnerabilityData();

    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;

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
        fetchVulnerabilities();
    }, []);

    if(!dataLoaded || vulList.length === 0 ) {
        return (
            <>
                <div className="rsssl-shield-overlay">
                    <Icon name = "shield"  size="80px"/>
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
        item.vulnerability_action = <div className="rsssl-vulnerability-action">
            <Button variant="secondary">{ __("Details", "really-simple-ssl") }</Button>
            <Button variant="primary" style={btnStyle}>{ __("View", "really-simple-ssl") }</Button>
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