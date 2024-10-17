import {useEffect, useState, useCallback} from '@wordpress/element';
import FieldsData from "../FieldsData";
import GeoDataTableStore from "./GeoDataTableStore";
import EventLogDataTableStore from "../EventLog/EventLogDataTableStore";
import FilterData from "../FilterData";
import Flag from "../../utils/Flag/Flag";
import {__} from '@wordpress/i18n';
import useFields from "../FieldsData";
import useMenu from "../../Menu/MenuData";

/**
 * A component for displaying a geo datatable.
 *
 * @param {Object} props - The component props.
 * @param {string} props.field - The field to display.
 *
 * @returns {JSX.Element} The rendered component.
 */
const GeoDatatable = (props) => {
    const {
        CountryDataTable,
        dataLoaded,
        fetchCountryData,
        addRow,
        addMultiRow,
        removeRegion,
        removeRegionMulti,
        addRegion,
        addRegionsMulti,
        removeRow,
        removeRowMulti,
        rowCleared,
        resetRowSelection,
    } = GeoDataTableStore();

    const moduleName = 'rsssl-group-filter-firewall_list_listing';
    const [localData, setLocalData] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [visualData, setVisualData] = useState([]);
    const {showSavedSettingsNotice, saveFields} = FieldsData();
    const [rowsSelected, setRowsSelected] = useState([]);
    const [columns, setColumns] = useState([]);
    const {fields, fieldAlreadyEnabled, getFieldValue, setHighLightField, getField} = useFields();
    const [currentPage, setCurrentPage] = useState(1);
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const {setSelectedSubMenuItem} = useMenu();
    const [DataTable, setDataTable] = useState(null);
    const [theme, setTheme] = useState(null);

    useEffect( () => {
        import('react-data-table-component').then(({ default: DataTable, createTheme }) => {
            setDataTable(() => DataTable);
            setTheme(() => createTheme('really-simple-plugins', {
                divider: {
                    default: 'transparent',
                },
            }, 'light'));
        });

    }, []);

    let enabled = getFieldValue('enable_firewall');

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };
    const handlePerRowsChange = (newRowsPerPage) => {
        setRowsPerPage(newRowsPerPage);
    };

    const {
        selectedFilter,
        setSelectedFilter,
        activeGroupId,
        getCurrentFilter,
        setProcessingFilter,
    } = FilterData();

    const [filter, setFilter] = useState(getCurrentFilter(moduleName));

    const buildColumn = useCallback((column) => ({
        //if the filter is set to region and the columns = status we do not want to show the column
        omit: filter === 'regions' && (column.column === 'country_name' || column.column === 'flag'),
        name: (column.column === 'action' && 'regions' === filter) ? __('Block / Allow All', 'really-simple-ssl') : column.name,
        sortable: column.sortable,
        searchable: column.searchable,
        width: column.width,
        visible: column.visible,
        column: column.column,
        selector: row => row[column.column],
    }), [filter]);
    let field = props.field;

    useEffect(() => {
        const element = document.getElementById('set_to_captcha_configuration');
        const clickListener = async event => {
            event.preventDefault();
            if (element) {
                await redirectToAddCaptcha(element);
            }
        };

        if (element) {
            element.addEventListener('click', clickListener);
        }

        return () => {
            if (element) {
                element.removeEventListener('click', clickListener);
            }
        };
    }, []);

    const redirectToAddCaptcha = async (element) => {
        // We fetch the props from the menu item
        let menuItem = getField('enabled_captcha_provider');

        // Create a new object based on the menuItem, including the new property
        let highlightingMenuItem = {
            ...menuItem,
            highlight_field_id: 'enabled_captcha_provider',
        };

        setHighLightField(highlightingMenuItem.highlight_field_id);
        let highlightField = getField(highlightingMenuItem.highlight_field_id);
        await setSelectedSubMenuItem(highlightField.menu_id);
    }

    const blockCountryByCode = useCallback(async (code, name) => {
        if (Array.isArray(code)) {
            //We get all the iso2 codes and names from the array
            const ids = code.map(item => ({
                country_code: item.iso2_code,
                country_name: item.country_name
            }));
            //we loop through the ids and block them one by one
            await addMultiRow(ids).then(
                (response) => {
                    if (response.success) {
                        showSavedSettingsNotice(response.message);
                    } else {
                        showSavedSettingsNotice(response.message, 'error');
                    }
                }
            );
            await fetchCountryData(field.action, filter);
            setRowsSelected([]);
        } else {
            await addRow(code, name).then((result) => {
                showSavedSettingsNotice(result.message);
                if (result.success) {
                    fetchCountryData(field.action, filter);
                }
            });
        }
    }, [addRow, filter, localData, enabled]);

    const allowRegionByCode = useCallback(async (code, regionName = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            await removeRegionMulti(ids).then(
                (response) => {
                    if (response.success) {
                        showSavedSettingsNotice(response.message);
                        if (response.success) {
                            fetchCountryData(field.action, filter);
                        }
                    } else {
                        showSavedSettingsNotice(response.message, 'error');
                    }
                }
            );
            setRowsSelected([]);
        } else {
            await removeRegion(code).then((result) => {
                showSavedSettingsNotice(result.message);
                if (result.success) {
                    fetchCountryData(field.action, filter);
                }
            });
        }
    }, [removeRegion, filter]);

    const blockRegionByCode = useCallback(async (code, region = '') => {
        if (Array.isArray(code)) {
            const ids = code.map(item => ({
                iso2_code: item.iso2_code,
                country_name: item.country_name
            }));
            await addRegionsMulti(ids).then(
                (response) => {
                    if (response.success) {
                        showSavedSettingsNotice(response.message);
                    } else {
                        showSavedSettingsNotice(response.message, 'error');
                    }
                }
            );
            await fetchCountryData(field.action, filter);
            setRowsSelected([]);
        } else {
            await addRegion(code).then((result) => {
                if (result.success) {
                    showSavedSettingsNotice(result.message);
                } else {
                    showSavedSettingsNotice(result.message, 'error');
                }
            });
            await fetchCountryData(field.action, filter);
        }

    }, [addRegion, filter]);

    const allowCountryByCode = useCallback(async (code) => {
        if (Array.isArray(code)) {
            const ids = code.map(item => ({
                country_code: item.iso2_code,
                country_name: item.country_name
            }));
            //we loop through the ids and allow them one by one
            await removeRowMulti(ids).then(
                (response) => {
                    if (response.success) {
                        showSavedSettingsNotice(response.message);
                    } else {
                        showSavedSettingsNotice(response.message, 'error');
                    }
                }
            );

            setRowsSelected([]);
            await fetchCountryData(field.action, filter);
        } else {
            await removeRow(code).then((result) => {
                showSavedSettingsNotice(result.message);
            });
            await fetchCountryData(field.action, filter);
        }

    }, [removeRow, filter]);

    const ActionButton = ({onClick, children, className, disabled = false}) => (
        // <div className={`rsssl-action-buttons__inner`}>
        <button
            className={`button ${className} rsssl-action-buttons__button`}
            onClick={onClick}
            disabled={disabled}
        >
            {children}
        </button>
        // </div>
    );

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

    const generateActionButtons = useCallback((code, name, region_name, showBlockButton = true, showAllowButton = true) => {
        return (<div className="rsssl-action-buttons">
            {filter === 'blocked' && (
                <ActionButton
                    onClick={() => allowCountryByCode(code)}
                    className="button-secondary">
                    {__("Allow", "really-simple-ssl")}
                </ActionButton>
            )}
            {filter === 'regions' && (
                <>
                    <ActionButton
                        onClick={() => blockRegionByCode(code, region_name)}
                        className="button-primary"
                        disabled={!showBlockButton}
                    >
                        {__("Block", "really-simple-ssl")}
                    </ActionButton>
                    <ActionButton
                        onClick={() => allowRegionByCode(code, region_name)}
                        className="button-secondary"
                        disabled={!showAllowButton}
                    >
                        {__("Allow", "really-simple-ssl")}
                    </ActionButton>
                </>
            )}
            {filter === 'countries' && (
                <ActionButton
                    onClick={() => blockCountryByCode(code, name)}
                    className="button-primary">
                    {__("Block", "really-simple-ssl")}
                </ActionButton>
            )}
        </div>)
    }, [filter]);

    const generateFlag = useCallback((flag, title) => {
        return (
            <>
                <Flag
                    countryCode={flag}
                    style={{
                        fontSize: '2em',
                    }}
                    title={title}
                    continent={(getCurrentFilter(moduleName) === 'regions')}
                />
            </>
        )
    }, [filter]);


    useEffect(() => {
        const currentFilter = getCurrentFilter(moduleName);
        if (typeof currentFilter === 'undefined') {
            setFilter('regions');
            setSelectedFilter('regions', moduleName);
        } else {
            setFilter(currentFilter);
        }
        setRowsSelected([]);
        resetRowSelection(true);
        resetRowSelection(false);
    }, [getCurrentFilter(moduleName)]);

    useEffect(() => {
        if (filter !== undefined) {
            const fetchData = async () => {
                await fetchCountryData(field.action, filter);
            }
            fetchData();
        }

    }, [filter]);

    useEffect(() => {
        if (dataLoaded && CountryDataTable.data !== undefined) {
            setLocalData(CountryDataTable.data);
        }
    }, [dataLoaded]);

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
    }, [currentPage, rowsPerPage, visualData]);

    useEffect(() => {
        let FilterColumns = field.columns.map(buildColumn);
        // Find the index of the 'action' column
        const actionIndex = FilterColumns.findIndex(column => column.column === 'action');


        // If 'filter' equals 'regions' and 'action' column exists, then do the rearrangement
        if (filter === 'regions' && actionIndex !== -1) {
            const actionColumn = FilterColumns[actionIndex];

            // Remove 'action' column from its current place
            FilterColumns.splice(actionIndex, 1);
            const emptyColumn = {
                name: '',
                selector: '',
                sortable: false,
                omit: false,
                searchable: false,
            };
            // Push 'action' column to the end of the array
            FilterColumns.push(emptyColumn, actionColumn);
        }
        setColumns(FilterColumns);
        const generatedVisualData = (localData || [])
            .filter((row) => {
                return Object.values(row).some((val) => ((val ?? '').toString().toLowerCase()).includes(searchTerm.toLowerCase()));
            }).map((row) => {
                const newRow = {...row};
                columns.forEach((column) => {
                    newRow[column.column] = row[column.column];
                });
                if (filter === 'regions') {
                    let showBlockButton = (newRow.region_count - newRow.blocked_count) > 0;
                    let showAllowButton = (newRow.blocked_count > 0);
                    newRow.action = generateActionButtons(newRow.iso2_code, newRow.country_name, newRow.region, showBlockButton, showAllowButton);
                } else if (filter === 'countries') {
                    newRow.action = generateActionButtons(newRow.iso2_code, newRow.country_name, newRow.region);
                } else {
                    newRow.action = generateActionButtons(newRow.iso2_code, newRow.status, newRow.region);
                }
                newRow.flag = generateFlag(newRow.iso2_code, newRow.country_name);
                if (newRow.status) {
                    newRow.status = __(newRow.status.charAt(0).toUpperCase() + newRow.status.slice(1), 'really-simple-ssl');
                    if ('regions' === filter) {
                        // So i all is blocked we don't want to show the count also if all are allowed we don't want to show the count
                        if (newRow.blocked_count === newRow.region_count || newRow.blocked_count === 0) {
                            newRow.status = newRow.status;

                        } else {
                            newRow.status = newRow.status + ' (' + newRow.blocked_count + '/ ' + newRow.region_count + ')';
                        }
                    }

                }
                return newRow;
            });
        setVisualData(generatedVisualData);
    }, [localData, searchTerm]);

    useEffect(() => {
        if ( rowsSelected.length === 0 ) {
            resetRowSelection
        }
    }, [rowsSelected]);

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
                            {filter === 'countries' && (
                                <>
                                    <ActionButton
                                        onClick={() => blockCountryByCode(rowsSelected)} className="button-primary">
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                </>
                            )}
                            {filter === 'regions' && (
                                <>
                                    <ActionButton
                                        onClick={() => allowRegionByCode(rowsSelected)} className="button-secondary">
                                        {__("Allow", "really-simple-ssl")}
                                    </ActionButton>
                                    <ActionButton
                                        onClick={() => blockRegionByCode(rowsSelected)} className="button-primary">
                                        {__("Block", "really-simple-ssl")}
                                    </ActionButton>
                                </>
                            )}
                            {filter === 'blocked' && (
                                <ActionButton
                                    onClick={() => allowCountryByCode(rowsSelected)}>
                                    {__("Allow", "really-simple-ssl")}
                                </ActionButton>
                            )}
                        </div>
                    </div>
                </div>
            )}
            {DataTable &&
            <DataTable
                columns={columns}
                data={visualData}
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
                selectableRows={true}
                paginationPerPage={rowsPerPage}
                onChangePage={handlePageChange}
                onChangeRowsPerPage={handlePerRowsChange}
                onSelectedRowsChange={handleSelection}
                clearSelectedRows={rowCleared}
                theme="really-simple-plugins"
                customStyles={customStyles}
            >
            </DataTable> }
            {!getFieldValue('enable_firewall') && (
                <div className="rsssl-locked">
                    <div className="rsssl-locked-overlay"><span
                        className="rsssl-task-status rsssl-open">{__('Disabled', 'really-simple-ssl')}</span><span>{__('Restrict access from specific countries or continents. You can also allow only specific countries.', 'really-simple-ssl')}</span>
                    </div>
                </div>
            )}
        </>
    )
}

export default GeoDatatable;
