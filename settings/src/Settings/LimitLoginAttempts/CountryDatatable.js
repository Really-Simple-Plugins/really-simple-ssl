import React, { useEffect, useState, useCallback } from 'react';
import DataTable, { createTheme } from "react-data-table-component";
import CountryDataTableStore from "./CountryDataTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import { Button } from "@wordpress/components";
import { __ } from '@wordpress/i18n';

const CountryDatatable = (props) => {
    const {
        CountryDataTable,
        dataLoaded,
        fetchCountryData,
        handleCountryTableFilter,
        addRow,
        removeRow,
        pagination,
        handleCountryTablePageChange,
        handleCountryTableRowsChange,
        handleCountryTableSort,
        handleCountryTableSearch,
        addRegion,
        removeRegion,
        addRowMultiple,
        removeRowMultiple,
        resetRow,
        resetMultiRow,
    } = CountryDataTableStore();

    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter
    } = FilterData();

    //here we set the selectedFilter from the Settings group
    const [rowsSelected, setRowsSelected] = useState([]);
    const [rowCleared, setRowCleared] = useState(false);

    const moduleName = 'rsssl-group-filter-limit_login_attempts_country';


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
            setSelectedFilter('blocked', moduleName);
        }
        handleCountryTableFilter('status', currentFilter);
    }, [selectedFilter, moduleName]);

    useEffect(() => {
        if (!dataLoaded) {
            fetchCountryData(props.field.action);
        }
    }, [dataLoaded, props.field.action]);


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

    function handleSelection(state) {
        console.log(state.selectedRows);
        setRowsSelected(state.selectedRows);
    }

    function allowRegionByCode(code) {
        if (Array.isArray(code)) {
            //some multi action
        } else {
            removeRegion(code, 'blocked');
        }
        setRowCleared(false);
    }

    function allowMultiple (rows) {
        let ids = [];
        rows.map(item => {
            ids.push(item.id);
        });
        resetMultiRow(ids, 'blocked');
    }

    function allowById(id) {
        resetRow(id, 'blocked');
    }

    function blockRegionByCode(code) {
        if (Array.isArray(code)) {
            //some multi action
            code.forEach(function (item, i) {
                addRegion(item, 'blocked');
            });
            //we clear the selected rows
            setRowCleared(true);
            setRowsSelected([]);
        } else {
            addRegion(code, 'blocked');
        }
        setRowCleared(false);
        // fetchDynamicData('event_log');
    }

    function allowCountryByCode(code) {
        //we check if the id is an array
        if (Array.isArray(code)) {
            let ids = [];
            code.map(item => {
                ids.push(item.iso2_code);
            });
            removeRowMultiple(ids, 'blocked');

            //we clear the selected rows
            setRowCleared(true);
            setRowsSelected([]);
        } else {
            removeRow(code, 'blocked');
        }
        setRowCleared(false);
        // fetchDynamicData('event_log');
    }

    function blockCountryByCode(code) {
        console.log(code);
        //we check if the id is an array
        if (Array.isArray(code)) {
            let ids = [];
            code.map(item => {
                ids.push(item.iso2_code);
            });
            addRowMultiple(ids, 'blocked');
            //we clear the selected rows
            setRowCleared(true);
            setRowsSelected([]);
        } else {
            addRow(code, 'blocked');
        }
        setRowCleared(false);
        // fetchDynamicData('event_log');
    }

    //we convert the data to an array
    let data = {...CountryDataTable.data};


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

        //based on the correct filter we set the correct action buttons
        if (getCurrentFilter(moduleName) === 'regions' || getCurrentFilter(moduleName) === 'countries') {
            dataItem.action = generateActionButtons(dataItem.attempt_value);
        } else {
            dataItem.action = generateActionButtons(dataItem.id);
        }
        dataItem.attempt_value = generateFlag(dataItem.attempt_value, dataItem.country_name);
        data[key] = dataItem;
    }

    function generateActionButtons(id) {
        return (
            <>
                <div className="rsssl-action-buttons">

                    {/* if the id is new we show the Allow button */}
                    {getCurrentFilter(moduleName) === 'blocked' && (
                        <div className="rsssl-action-buttons__inner">
                            <Button
                                className="button button-secondary rsssl-action-buttons__button"
                                onClick={() => {
                                    allowById(id);
                                }}
                            >
                                {__("Allow", "really-simple-ssl")}
                            </Button>
                        </div>
                    )}
                    {getCurrentFilter(moduleName) === 'regions' && (
                        <>
                            <div className="rsssl-action-buttons__inner">
                                <Button
                                    className="button button-primary rsssl-action-buttons__button"
                                    onClick={() => {
                                        blockRegionByCode(id);
                                    }}
                                >
                                    {__("Block", "really-simple-ssl")}
                                </Button>
                            </div>
                            <div className="rsssl-action-buttons__inner">
                                <Button
                                    className="button button-secondary rsssl-action-buttons__button"
                                    onClick={() => {
                                        allowRegionByCode(id);
                                    }}
                                >
                                    {__("Allow", "really-simple-ssl")}
                                </Button>
                            </div>
                        </>
                    )}
                    {getCurrentFilter(moduleName) === 'countries' && (
                        <>
                            <div className="rsssl-action-buttons__inner">
                                <Button
                                    className="button button-primary rsssl-action-buttons__button"
                                    onClick={() => {
                                        blockCountryByCode(id);
                                    }}
                                >
                                    {__("Block", "really-simple-ssl")}
                                </Button>
                            </div>
                            <div className="rsssl-action-buttons__inner">
                                <Button
                                    className="button button-secondary rsssl-action-buttons__button"
                                    onClick={() => {
                                        allowCountryByCode(id);
                                    }}
                                >
                                    {__("Allow", "really-simple-ssl")}
                                </Button>
                            </div>
                        </>
                    )}
                    {/* if the id is new we show the Reset button */}
                    {/*{(getCurrentFilter(moduleName) !== 'regions' && getCurrentFilter(moduleName) !== 'countries') && (*/}
                    {/*    <div className="rsssl-action-buttons__inner">*/}
                    {/*        <Button*/}
                    {/*            className="button button-red rsssl-action-buttons__button"*/}
                    {/*            onClick={() => {*/}
                    {/*                allowCountryByCode(id);*/}
                    {/*            }*/}
                    {/*            }*/}
                    {/*        >*/}
                    {/*            {__("Reset", "really-simple-ssl")}*/}
                    {/*        </Button>*/}
                    {/*    </div>*/}
                    {/*)}*/}
                </div>
            </>
        );
    }

    return (
        <>
            <div className="rsssl-container">
                <div>
                    {/* reserved for left side buttons */}
                </div>
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
            { /*Display the action form what to do with the selected*/}
            {rowsSelected.length > 0 && (
                <div
                    style={{
                        marginTop: '1em',
                        marginBottom: '1em',
                    }}>
                    <div className={"rsssl-multiselect-datatable-form rsssl-primary"}
                    >
                        <div>
                            {__("You have selected", "really-simple-ssl")} {rowsSelected.length} {__("rows", "really-simple-ssl")}
                        </div>

                        <div className="rsssl-action-buttons">
                            {getCurrentFilter(moduleName) === 'countries' && (
                                <>
                                    {/* if the id is new we show the Allow button */}
                                        <div className="rsssl-action-buttons__inner">
                                            <Button
                                                className="button button-secondary rsssl-action-buttons__button"
                                                onClick={() => {
                                                    allowCountryByCode(rowsSelected);
                                                }}
                                            >
                                                {__("Allow", "really-simple-ssl")}
                                            </Button>
                                        </div>

                                    {/* if the id is new we show the Block button */}
                                        <div className="rsssl-action-buttons__inner">
                                            <Button
                                                className="button button-primary rsssl-action-buttons__button"
                                                onClick={() => {
                                                    blockCountryByCode(rowsSelected);
                                                }}
                                            >
                                                {__("Block", "really-simple-ssl")}
                                            </Button>
                                        </div>
                                </>
                            )}
                            {getCurrentFilter(moduleName) === 'blocked' && (
                                <>
                                    {/* if the id is new we show the Allow button */}
                                    <div className="rsssl-action-buttons__inner">
                                        <Button
                                            className="button button-secondary rsssl-action-buttons__button"
                                            onClick={() => {
                                                allowMultiple(rowsSelected);
                                            }}
                                        >
                                            {__("Allow", "really-simple-ssl")}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}

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
                selectableRows
                selectableRowsHighlight={true}
                clearSelectedRows={rowCleared}
                onSelectedRowsChange={handleSelection}
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

