import {__} from '@wordpress/i18n';
import React, {useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import LimitLoginAttemptsData from "./LimitLoginAttemptsData";
import useFields from "../FieldsData";

const IpAddressModule = (props) => {

    const { selectedFilter } = props;
    const { EventLog, dataLoaded, fetchEventLog } = LimitLoginAttemptsData();
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    let field = props.field;
    let columns = [];

    useEffect(() => {
        if (selectedFilter) {
            if (!dataLoaded) {
                fetchEventLog(selectedFilter).then(r => console.log(r));
            }
        }
    });

    function buildColumn(column) {
        return {
            name: column.name,
            sortable: column.sortable,
            width: column.width,
            visible: column.visible,
            selector: row => row[column.column],
        };
    }

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

    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });



    //only show the datatable if the data is loaded
    if (!dataLoaded) {
        return null;
    }
    console.log(EventLog, columns)
    return (
        <DataTable
            dense
            pagination
            noDataComponent={__("No results", "really-simple-ssl")}
            persistTableHead
            theme="really-simple-plugins"
            customStyles={customStyles}
        ></DataTable>
    );
}

export default IpAddressModule;