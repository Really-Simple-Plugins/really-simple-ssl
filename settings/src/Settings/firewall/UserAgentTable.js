import { useEffect, useState, useCallback } from '@wordpress/element';
import DataTable, { createTheme } from "react-data-table-component";
import FieldsData from "../FieldsData";
import { __ } from '@wordpress/i18n';
import useFields from "../FieldsData";
import useMenu from "../../Menu/MenuData";
import AddButton from "../GeoBlockList/AddButton";
import UserAgentModal from "./UserAgentModal";
import UserAgentStore from "./UserAgentStore";
import { in_array } from "../../utils/lib";
import FilterData from "../FilterData";

const UserAgentTable = (props) => {
    const {
        data,
        processing,
        dataLoaded,
        fetchData,
        user_agent,
        note,
        deleteValue,
        setDataLoaded,
    } = UserAgentStore();

    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter,
        setProcessingFilter,
    } = FilterData();

    const moduleName = 'rsssl-group-filter-user_agents';
    const { fields, fieldAlreadyEnabled, getFieldValue, setHighLightField, getField } = useFields();
    const [modalOpen, setModalOpen] = useState(false);
    const [rowsSelected, setRowsSelected] = useState([]);
    const [rowCleared, setRowCleared] = useState(false);
    const [columns, setColumns] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [filteredData, setFilteredData] = useState([]);
    const { showSavedSettingsNotice, saveFields } = FieldsData();
    const [currentPage, setCurrentPage] = useState(1);
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const [filter, setFilter] = useState(getCurrentFilter(moduleName));

    let enabled = getFieldValue('enable_firewall');
    const IsNull = (value) => value === null;

    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);
        if (typeof currentFilter === 'undefined') {
            setFilter('blocked');
        } else {
            setFilter(currentFilter);
        }
        setRowsSelected([]);
    }, [getCurrentFilter(moduleName)]);

    useEffect(() => {
        if (filter !== undefined) {
            const fetchData = async () => {
                setDataLoaded(false);
            }
            fetchData();
            setRowsSelected([]);
        }

    }, [filter]);

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };
    const handlePerRowsChange = (newRowsPerPage) => {
        setRowsPerPage(newRowsPerPage);
    };

    useEffect(() => {
        if (props.field) {
            const buildColumn = (column) => ({
                name: column.name,
                sortable: column.sortable,
                searchable: column.searchable,
                width: column.width,
                visible: column.visible,
                column: column.column,
                selector: row => row[column.column],
            });
            setColumns(props.field.columns.map(buildColumn));
        }
    }, [props.field]);

    useEffect(() => {
        const fetchUserAgentList = async () => {
            if (!dataLoaded && enabled ) {
                await fetchData('rsssl_user_agent_list', filter);
            }
        };

        fetchUserAgentList();
    }, [dataLoaded, enabled]);

    useEffect(() => {
            saveFields(false, false, true);
            setDataLoaded(false);
    },[enabled]);

    const handleClose = () => {
        setModalOpen(false);
    }

    const handleOpen = () => {
        setModalOpen(true);
    }

    const ActionButton = ({ onClick, children, className }) => (
        <button
            className={`button ${className} rsssl-action-buttons__button`}
            onClick={onClick}
            disabled={false}
        >
            {children}
        </button>
    );

    const softDelete = useCallback((id) => {
        if (Array.isArray(id)) {
            const ids = id.map(item => ({ id: item.id }));
            deleteValue(ids).then((result) => {
                showSavedSettingsNotice(result.message);
                setRowsSelected([]);
            });
        } else {
            deleteValue(id).then((result) => {
                showSavedSettingsNotice(result.message);
            });
        }
    }, [deleteValue, rowsSelected, showSavedSettingsNotice]);

    const generateActionButtons = useCallback((id, deleted) => (
        <div className="rsssl-action-buttons">
            <ActionButton
                onClick={() => softDelete(id)}
                className={deleted ? "button-primary" : "button-red"}
            >
                {deleted ? __("Block", "really-simple-ssl") : __("Delete", "really-simple-ssl")}
            </ActionButton>
        </div>
    ), [softDelete]);

    const customStyles = {
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

    useEffect(() => {
        if (data) {  // Add this check to ensure data is not undefined or null
            const filtered = Object.entries(data)
                .filter(([_, dataItem]) => {
                    return Object.values(dataItem).some(val => ((val ?? '').toString().toLowerCase().includes(searchTerm.toLowerCase())));
                })
                .map(([key, dataItem]) => {
                    const newItem = { ...dataItem,
                        action: generateActionButtons(dataItem.id, !IsNull(dataItem.deleted_at) ) };
                    return [key, newItem];
                })
                .reduce((obj, [key, val]) => ({ ...obj, [key]: val }), {});
            setFilteredData(filtered);
        }
    }, [searchTerm, data, generateActionButtons]);

    const handleSelection = useCallback((state) => {
        //based on the current page and the rows per page we get the rows that are selected
        const {selectedCount, selectedRows, allSelected, allRowsSelected} = state;
        let rows = [];
        if (allSelected) {
            rows = selectedRows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage);
            setRowsSelected(rows);
        } else {
            setRowsSelected(selectedRows);
        }
    }, [currentPage, rowsPerPage]);

    useEffect(() => {
        if (rowsSelected.length === 0) {
            setRowCleared(!rowCleared);
        }
    }, [rowsSelected]);

    return (
        <>
            <UserAgentModal
                isOpen={modalOpen}
                onRequestClose={handleClose}
                value={user_agent}
                status={'blocked'}
            />
            <div className="rsssl-container">
                <AddButton
                    handleOpen={handleOpen}
                    processing={processing}
                    allowedText={__("Block User-Agent", "really-simple-ssl")}
                    disabled={!dataLoaded}
                />
                <div className="rsssl-search-bar">
                    <div className="rsssl-search-bar__inner">
                        <div className="rsssl-search-bar__icon"></div>
                        <input
                            type="text"
                            className="rsssl-search-bar__input"
                            placeholder={__("Search", "really-simple-ssl")}
                            onChange={e => setSearchTerm(e.target.value)}
                        />
                    </div>
                </div>
            </div>
            {rowsSelected.length > 0 && (
                <div
                    style={{
                        marginTop: '1em',
                        marginBottom: '1em',
                    }}
                >
                    <div className={"rsssl-multiselect-datatable-form rsssl-primary"}>
                        <div>
                            {__("You have selected %s rows", "really-simple-ssl").replace('%s', rowsSelected.length)}
                        </div>
                        <div className="rsssl-action-buttons">
                            <ActionButton
                                onClick={() => softDelete(rowsSelected)}
                                className="button-red"
                            >
                                {__("Delete", "really-simple-ssl")}
                            </ActionButton>
                        </div>
                    </div>
                </div>
            )}
            <DataTable
                columns={columns}
                data={Object.values(filteredData)}
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
                onSelectedRowsChange={handleSelection}
                clearSelectedRows={rowCleared}
                paginationPerPage={rowsPerPage}
                onChangePage={handlePageChange}
                onChangeRowsPerPage={handlePerRowsChange}
                theme="really-simple-plugins"
                customStyles={customStyles}
                selectableRows={true}
            />
            {!getFieldValue('enable_firewall') && (
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay">
                        <span className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span>
                        <span>
                            {__('Restrict access from specific countries or continents. You can also allow only specific countries.', 'really-simple-ssl')}
                        </span>
                    </div>
                </div>
            )}
        </>
    );
};

export default UserAgentTable;