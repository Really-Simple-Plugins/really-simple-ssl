import {__} from '@wordpress/i18n';
import {useEffect, useState} from '@wordpress/element';
import DataTable, {createTheme} from "react-data-table-component";
import EventLogDataTableStore from "./EventLogDataTableStore";
import FilterData from "../FilterData";
import * as rsssl_api from "../../utils/api";
import useMenu from "../../Menu/MenuData";
import Flag from "../../utils/Flag/Flag";
import Icon from "../../utils/Icon";
import useFields from "../FieldsData";
import SearchBar from "../DynamicDataTable/SearchBar";

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



    const {fields, fieldAlreadyEnabled, getFieldValue} = useFields();
    const [tableHeight, setTableHeight] = useState(600);  // Starting height
    const rowHeight = 50; // Height of each row.
    const moduleName = 'rsssl-group-filter-' + props.field.id;
    let field = props.field;
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
            fetchDynamicData(field.action, field.event_type, dataActions)
        }
    }, [dataActions.sortDirection, dataActions.filterValue, dataActions.search, dataActions.page, dataActions.currentRowsPerPage]);



    //we create the columns
    let columns = [];
    //getting the fields from the props

    //we loop through the fields
    field.columns.forEach(function (item, i) {
        let newItem = buildColumn(item)
        columns.push(newItem);
    });

    let enabled = fieldAlreadyEnabled('event_log_enabled');

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

    let paginationSet;
    paginationSet = typeof pagination !== 'undefined';

    useEffect(() => {
        if (Object.keys(data).length === 0 ) {
            setTableHeight(100); // Adjust depending on your UI measurements
        } else {
            setTableHeight(rowHeight * (paginationSet ? pagination.perPage + 2 : 12)); // Adjust depending on your UI measurements
        }

    }, [paginationSet, pagination?.perPage, data]);

    return (
        <>
            <div className="rsssl-container">
                <div></div>
                {/*Display the search bar*/}
                <SearchBar handleSearch={handleEventTableSearch} searchableColumns={searchableColumns}/>
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

