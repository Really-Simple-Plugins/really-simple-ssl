import {__} from '@wordpress/i18n';
import React, {useEffect, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import LimitLoginAttemptsData from "./LimitLoginAttemptsData";
import useFields from "../FieldsData";
import {Button} from "@wordpress/components";

const IpAddressModule = (props) => {

    const { selectedFilter } = props;
    const { EventLog, dataLoaded, fetchEventLog } = LimitLoginAttemptsData();
    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    let field = props.field;
    let columns = [];

    useEffect(() => {
        if (selectedFilter) {
            console.log("selectedFilter", selectedFilter);
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
    if (!dataLoaded && !selectedFilter && columns.length === 0) {
        return (
            <div className="rsssl-spinner">
                <div className="rsssl-spinner__inner">
                    <div className="rsssl-spinner__icon"></div>
                    <div className="rsssl-spinner__text">{__("Loading...", "really-simple-ssl")}</div>
                </div>
            </div>
        );
    }

    let dummyData = [['127.0.0.1','testuser1','','',''],['','','','',''],['','','','','']];


    return (
        <>
            {/*Display the add row button */}
            <div className="rsssl-add-row">
                <Button isSecondary onClick={() => console.log("add row")}>{__("Add row", "really-simple-ssl")}</Button>
            </div>
            {/*Display the datatable*/}
            <DataTable
            columns={columns}
            data={EventLog}
            dense
            pagination
            noDataComponent={__("No results", "really-simple-ssl")}
            persistTableHead
            theme="really-simple-plugins"
            customStyles={customStyles}
        ></DataTable>
        </>

    );
}

export default IpAddressModule;