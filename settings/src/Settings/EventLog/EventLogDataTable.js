import {__} from '@wordpress/i18n';
import React, {useEffect, useState, useRef} from 'react';
import DataTable, {createTheme, ExpanderComponentProps} from "react-data-table-component";
import EventLogDataTableStore from "./EventLogDataTableStore";
import FilterData from "../FilterData";
import * as rsssl_api from "../../utils/api";
import useMenu from "../../Menu/MenuData";
import Flag from "../../utils/Flag/Flag";
import Icon from "../../utils/Icon";
import useFields from "../FieldsData";

const EventLogDataTable = (props) => {
    const {
        DynamicDataTable,
        dataLoaded,
        pagination,
        dataActions,
        handleEventTableRowsChange,
        fetchDynamicData,
        handleEventTableSort,
        handleEventTablePageChange,
        handleEventTableSearch,
        handleEventTableFilter,
        processing,
        rowCleared,
    } = EventLogDataTableStore()
    //here we set the selectedFilter from the Settings group
    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter,
        setProcessingFilter,
    } = FilterData();

    const moduleName = 'rsssl-group-filter-limit_login_attempts_event_log';

    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);

        if (!currentFilter) {
            setSelectedFilter('all', moduleName);
        }
        setProcessingFilter(processing);
        handleEventTableFilter('severity', currentFilter);
    }, [moduleName, handleEventTableFilter, getCurrentFilter(moduleName), setSelectedFilter, moduleName, DynamicDataTable, processing]);


    //if the dataActions are changed, we fetch the data
    useEffect(() => {
        //we make sure the dataActions are changed in the store before we fetch the data
        if (dataActions) {
            fetchDynamicData(field.action, dataActions)
        }
    }, [dataActions.sortDirection, dataActions.filterValue, dataActions.search, dataActions.page, dataActions.currentRowsPerPage]);



    //we create the columns
    let columns = [];
    //getting the fields from the propsß
    let field = props.field;
    //we loop through the fields
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });


    let enabled = getFieldValue('enable_limited_login_attempts');

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

    //only show the datatable if the data is loaded
    if (!dataLoaded && columns.length === 0 && DynamicDataTable.length === 0) {
        return (
            <div className="rsssl-spinner">
                <div className="rsssl-spinner__inner">
                    <div className="rsssl-spinner__icon"></div>
                    <div className="rsssl-spinner__text">{__("Loading...", "really-simple-ssl")}</div>
                </div>
            </div>
        );
    }

    let searchableColumns = [];
    //setting the searchable columns
    columns.map(column => {
        if (column.searchable) {
            searchableColumns.push(column.column);
        }
    });
    let data = [];

    if (DynamicDataTable.data) {
        data = DynamicDataTable.data.map((dataItem) => {
            let newItem = {...dataItem};
            newItem.iso2_code = generateFlag(newItem.iso2_code, newItem.country_name);
            newItem.expandableRows = true;
            return newItem;
        });
    }

    //we generate an expandable row
    const ExpandableRow = ({data}) => {
        let code, icon, color = '';
        switch (data.severity) {
            case 'warning':
                code = 'rsssl-warning';
                icon = 'circle-times';
                color = 'red';
                break;
            case 'informational':
                code = 'rsssl-primary';
                icon = 'info';
                color = 'black';
                break;


            default:
                code = 'rsssl-primary';
        }

        return (
            <div className={"rsssl-wizard-help-notice " + code}
                 style={{padding: '1em', borderRadius: '5px'}}>
                {/*now we place a block to the rightcorner with the severity*/}
                <div style={{float: 'right'}}>
                    <Icon name={icon} color={color}/>
                </div>
                <div style={{fontSize: '1em', fontWeight: 'bold'}}>
                    {data.severity.charAt(0).toUpperCase() + data.severity.slice(1)}
                </div>
                <div>{data.description}</div>
            </div>
        );
    };


    function generateFlag(flag, title) {
        return (
            <>
                <Flag
                    countryCode={flag}
                    style={{
                        fontSize: '2em',
                        marginLeft: '0.3em',
                    }}
                    title={title}
                ></Flag>
            </>

        )
    }

    let paginationSet = true;
    if (typeof pagination === 'undefined') {
        paginationSet = false;
    }

    return (
        <>
            <div className="rsssl-container">
                <div></div>
                {/*Display the search bar*/}
                <div className="rsssl-search-bar">
                    <div className="rsssl-search-bar__inner">
                        <div className="rsssl-search-bar__icon"></div>
                        <input
                            type="text"
                            className="rsssl-search-bar__input"
                            placeholder={__("Search", "really-simple-ssl")}
                            disabled={processing}
                            onChange={event => handleEventTableSearch(event.target.value, searchableColumns)}
                        />
                    </div>
                </div>
            </div>
            {/*Display the datatable*/}
            <DataTable
                columns={columns}
                data={processing? [] : data}
                dense
                pagination={!processing}
                paginationServer
                paginationTotalRows={paginationSet? pagination.totalRows: 10}
                paginationPerPage={paginationSet? pagination.perPage: 10}
                paginationDefaultPage={paginationSet?pagination.currentPage: 1}
                paginationComponentOptions={{
                    rowsPerPageText: __('Rows per page:', 'really-simple-ssl'),
                    rangeSeparatorText: __('of', 'really-simple-ssl'),
                    noRowsPerPage: false,
                    selectAllRowsItem: false,
                    selectAllRowsItemText: __('All', 'really-simple-ssl'),

                }}
                onChangeRowsPerPage={handleEventTableRowsChange}
                onChangePage={handleEventTablePageChange}
                expandableRows={!processing}
                expandableRowsComponent={ExpandableRow}
                loading={dataLoaded}
                onSort={handleEventTableSort}
                sortServer={!processing}
                paginationRowsPerPageOptions={[5, 10, 25, 50, 100]}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                theme="really-simple-plugins"
                customStyles={customStyles}
            ></DataTable>
            {!enabled && (
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Activate Limit login attempts to enable this block.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            )}
        </>
    );

}
export default EventLogDataTable;

function buildColumn(column) {
    return {
        name: column.name,
        sortable: column.sortable,
        searchable: column.searchable,
        width: column.width,
        visible: column.visible,
        column: column.column,
        selector: row => row[column.column],
    };
}

