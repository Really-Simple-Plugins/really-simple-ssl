import {__} from '@wordpress/i18n';
import React, {useEffect, Countryef, useState} from 'react';
import DataTable, {createTheme} from "react-data-table-component";
import CountryDataTableStore from "./CountryDataTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import {Button} from "@wordpress/components";
import {produce} from "immer";
import Icon from "../../utils/Icon";

const CountryDatatable = (props) => {
    const {
        CountryDataTable,
        dataLoaded,
        pagination,
        dataActions,
        handleCountryTableRowsChange,
        fetchCountryData,
        handleCountryTableSort,
        handleCountryTablePageChange,
        handleCountryTableSearch,
        handleCountryTableFilter
    } = CountryDataTableStore()

    //here we set the selectedFilter from the Settings group
    const {selectedFilter, setSelectedFilter, activeGroupId, getCurrentFilter} = FilterData();
    const moduleName = 'rsssl-group-filter-limit_login_attempts_Countrys';


    //we create the columns
    let columns = [];
    //getting the fields from the props
    let field = props.field;
    //we loop through the fields
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);

        if (!currentFilter) {
            setSelectedFilter('all', moduleName);
        }
        handleCountryTableFilter('region', currentFilter);
    }, [selectedFilter, moduleName]);

    useEffect(() => {
        if (!dataLoaded) {
            fetchCountryData(field.action);
        }
    });


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
    if (!dataLoaded && columns.length === 0 && CountryDataTable.length === 0) {
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

    //now we get the options for the select control
    let options = props.field.options;
    //we divide the key into label and the value into value
    options = Object.entries(options).map((item) => {
        return {label: item[1], value: item[0]};
    });

    function handleStatusChange(value, id) {

    }

    //we convert the data to an array
    let data = {...CountryDataTable.data};

    function generateOptions(status, id, name = '', region = '') {
        return (
            <div>
                <select
                    className="rsssl-select"
                    value={status}
                    onChange={(event) => handleStatusChange(event.target.value, id)}
                    style={{
                        width: '100%!important',
                    }}
                >
                    {options.map((item, i) => {
                        let disabled = false;
                        if (item.value === 'locked') {
                            disabled = true;
                        }
                        let displayValue = name;
                        if (item.value.startsWith('region')) {
                            displayValue = region;
                        }
                        return (
                            <option key={i} value={item.value} disabled={disabled}>
                                {item.label} {displayValue}
                            </option>
                        );
                    })}
                </select>
            </div>

        );
    }

    function generateFlag(flag, title) {
        return (
            <>
                <Flag
                    countryCode={flag}
                    style={{
                        fontSize: '2em',
                    }}
                    title={title}
                ></Flag>
            </>

        )
    }

    function generateGoodBad(value) {
        ``
        if (value > 0) {
            return (
                <Icon name="circle-check" color='green'/>
            )
        } else {
            return (
                <Icon name="circle-times" color='red'/>
            )
        }
    }

    for (const key in data) {
        let dataItem = {...data[key]}
        dataItem.action = generateOptions(dataItem.action, dataItem.id, dataItem.country_name, dataItem.region);
        dataItem.iso2_code = generateFlag(dataItem.iso2_code, dataItem.country_name);
        dataItem.users = generateGoodBad(dataItem.users);
        dataItem.api = generateGoodBad(dataItem.api);

        data[key] = dataItem;
    }

    return (
        <>
            <div className="rsssl-container">
                <div></div>
                <div className="rsssl-search-bar">
                    <div className="rsssl-search-bar__inner">
                        <div className="rsssl-search-bar__icon"></div>
                        <input
                            type="text"
                            className="rsssl-search-bar__input"
                            placeholder={__("Search", "really-simple-ssl")}
                            onChange={event => handleCountryTableSearch(event.target.value, searchableColumns)}
                        />
                    </div>
                </div>
            </div>

            {/*Display the datatable*/}
            <DataTable
                columns={columns}
                data={Object.values(data)}
                dense
                pagination
                paginationServer
                paginationTotalRows={pagination.totalRows ?? 0}
                onChangeRowsPerPage={handleCountryTableRowsChange}
                onChangePage={handleCountryTablePageChange}
                sortServer
                onSort={handleCountryTableSort}
                paginationRowsPerPageOptions={[10, 25, 50, 100]}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                theme="really-simple-plugins"
                customStyles={customStyles}
            ></DataTable>
        </>
    );

}
export default CountryDatatable;

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

