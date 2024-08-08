import {useEffect, useState , memo } from "@wordpress/element";
import DataTable, { createTheme } from "react-data-table-component";

import DataTableStore from "../DataTable/DataTableStore";
import { __ } from '@wordpress/i18n';
import ControlButton from "../DataTable/Buttons/ControlButton";
import RowButton from "../DataTable/Buttons/RowButton";
import SearchBar from "../DataTable/SearchBar/SearchBar";
import SelectedRowsControl from "../DataTable/SelectedRowsControl/SelectedRowsControl";
import './DataTable.scss';
import './Checkboxes.scss';
import useFields from "../FieldsData";
import useMenu from "../../Menu/MenuData";

const DataTableWrapper = ({field, controlButton, enabled}) => {
    const {
        filteredData,
        handleSearch,
        dataLoaded,
        fetchData,
        reloadFields,
    } = DataTableStore();
    const {fetchFieldsData} = useFields();
    const {selectedSubMenuItem} = useMenu();
    const [rowsSelected, setRowsSelected] = useState([]);
    const [currentPage, setCurrentPage] = useState(1);
    const [rowsPerPage, setRowsPerPage] = useState(10);

    useEffect(() => {
        if ( !dataLoaded) {
            fetchData(field.action, {});
        }
    }, [dataLoaded] );

    useEffect(() => {
        if ( reloadFields ) {
            fetchFieldsData(selectedSubMenuItem);
        }
    }, [reloadFields]);

    /**
     * Build a column configuration object.
     *
     * @param {object} column - The column object.
     * @param {string} column.name - The name of the column.
     * @param {boolean} column.sortable - Whether the column is sortable.
     * @param {boolean} column.searchable - Whether the column is searchable.
     * @param {number} column.width - The width of the column.
     * @param {boolean} column.visible - Whether the column is visible.
     * @param {string} column.column - The column identifier.
     *
     * @returns {object} The column configuration object.
     */
    const buildColumn = ({reloadFields, name, isButton, action, label, className, sortable, searchable, width, visible, column}) => ({
        reloadFields, name, isButton, action, label, className, sortable, searchable, width, visible, column, selector: row => row[column],
    });
    const columns = field.columns.map(buildColumn);
    const buttonColumns = columns.filter(column => column.isButton);
    const hasSelectableRows = buttonColumns.length>0;
    const searchableColumns = columns.filter(column => column.searchable).map(column => column.column);

    const customgitStyles = {
        headCells: {
            style: {
                paddingLeft: '0',
                paddingRight: '0',
            },
        },
        cells: {
            style: {
                paddingLeft: '0',
                paddingRight: '0',
            },
        },
    };

    createTheme('really-simple-plugins', {
        divider: {
            default: 'transparent',
        },
    }, 'light');

    const handleSelection = ({selectedCount, selectedRows}) => {
        // based on the current page and the rows per page we get the rows that are selected
        let actualRows = rowsPerPage;
        //in case not all selected, get the rows that are selected from the current page.
        //the datatable component selects 'all' rows, but we only want the rows from the current page.
        let rows = [];
        if ( selectedCount < rowsPerPage ) {
            rows = selectedRows;
            setRowsSelected(selectedRows);
        } else if ( selectedCount >= rowsPerPage ) {
            //previously all rows were selected, but now some were unselected.
            //in the latter case we need to get the rows that are selected from the current page.
            //remove the rows from all pages after the current page
            let diff = filteredData.length - selectedRows.length;
            rows = selectedRows.slice( 0, (currentPage * rowsPerPage) - diff );
            if ( currentPage > 1 ) {
                //remove the rows from all pages before the current page from the selected rows
                rows = rows.slice( (currentPage - 1) * rowsPerPage);
            }
            setRowsSelected(rows);
        }
    }

    const data= dataLoaded && filteredData.length>0 ? {...filteredData} : [];
    for (const key in data) {
        const dataItem = {...data[key]};
        //check if there exists a column with column = 'actionButton'
        if ( buttonColumns.length > 0 ) {
            for (const buttonColumn of buttonColumns) {
                dataItem[buttonColumn.column] = <RowButton id={dataItem.id} buttonData={buttonColumn}/>
            }
        }
        data[key] = dataItem;
    }
    let selectAllRowsClass = "";
    if ( rowsSelected.length>0 && rowsSelected.length < rowsPerPage) {
        selectAllRowsClass = "rsssl-indeterminate";
    }
    if ( rowsSelected.length === rowsPerPage ) {
        selectAllRowsClass = "rsssl-all-selected";
    }
    return (
        <div className={"rsssl-datatable-component"}>
            <div className="rsssl-container">
                {controlButton.show && <ControlButton controlButton={controlButton}/> }
                {/*Ensure that positioning also works without the addButton, by adding a div */}
                { !controlButton.show && <div></div>}
                <SearchBar
                    handleSearch={handleSearch}
                    searchableColumns={searchableColumns}
                />
            </div>

            { field.multiselect_buttons && rowsSelected.length > 0 && (
                <SelectedRowsControl rowsSelected={rowsSelected} buttonData = {field.multiselect_buttons} />
            )}

            <DataTable
                className={ selectAllRowsClass }
                columns={columns}
                data={Object.values(data)}
                dense
                pagination={true}
                paginationComponentOptions={{
                    rowsPerPageText: __('Rows per page:', 'really-simple-ssl'),
                    rangeSeparatorText: __('of', 'really-simple-ssl'),
                    noRowsPerPage: false,
                    selectAllRowsItem: false,
                    selectAllRowsItemText: __('All', 'really-simple-ssl'),
                }}
                noDataComponent={__("No results", "really-simple-ssl")}
                persistTableHead
                selectableRows={hasSelectableRows}
                //clearSelectedRows={() => setRowsSelected([])}
                paginationPerPage={rowsPerPage}
                onChangePage={setCurrentPage}
                onChangeRowsPerPage={setRowsPerPage}
                onSelectedRowsChange={handleSelection}
                theme="really-simple-plugins"
                // customStyles={customStyles}
            />
            {!enabled && (
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay">
                        <span className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span>
                        <span>{__('Here you can add IP addresses that should never be blocked by region restrictions.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            )}
        </div>
    );
}

export default memo(DataTableWrapper);