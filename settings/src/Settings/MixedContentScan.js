import {useState, useEffect} from "@wordpress/element";
import DataTable, { createTheme }  from "react-data-table-component";
import {ToggleControl} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../utils/api";
import ModalControl from "../Modal/ModalControl";
import Icon from "../utils/Icon";
import useModal from "../Modal/ModalData";
const MixedContentScan = (props) => {
    const {dropItemFromModal} = useModal;
    const [data, setData] = useState(false);
    const [progress, setProgress] = useState(0);
    const [action, setAction] = useState('');
    const [state, setState] = useState('stop');
    const [paused, setPaused] = useState(false);
    const [showIgnoredUrls, setShowIgnoredUrls] = useState(false);
    const [resetPaginationToggle, setResetPaginationToggle] = useState(false);
    const [completedStatus, setCompletedStatus] = useState(false);
    const [nonce, setNonce] = useState('');

    useEffect(async () => {
        let data = props.field.value.data ? props.field.value.data : [];
        let progress = props.field.value.progress ? props.field.value.progress : 0;
        if (!rsssl_settings.pro_plugin_active) progress=80;

        let action = props.field.value.action ? props.field.value.action : '';
        let nonce = props.field.value.nonce ? props.field.value.nonce : '';
        let state = props.field.value.state ? props.field.value.state : 'stop';
        let completedStatus = props.field.value.completed_status ? props.field.value.completed_status.toLowerCase() : 'never';

        setData(data);
        setProgress(progress);
        setAction(action);
        setNonce(nonce);
        setState(state);
        setCompletedStatus(completedStatus);

    }, [] );


    const start = (e) => {
        //add start_full option
        let state = 'start';
        if ( paused ) {
            state = 'running';
        }
        setState('running');
        setPaused(false);
        rsssl_api.runTest('mixed_content_scan', state ).then( ( response ) => {
            setData(response.data);
            setProgress(response.progress);
            setState(response.state);
            if ( response.state==='running' ){
                run();
            }
        });
    }

    const run = (e) => {
        if ( paused ) {
            return;
        }
        rsssl_api.runTest('mixed_content_scan', 'running' ).then( ( response ) => {
            setCompletedStatus(response.completed_status);
            setData(response.data);
            setProgress(response.progress);
            setAction(response.action);
            setState(response.state);
            //if scan was stopped while running, set it to stopped now.
            if ( paused ) {
                stop();
            } else if ( response.state==='running' ) {
                run();
            }
        });
    }

    const toggleIgnoredUrls = (e) => {
        setShowIgnoredUrls(!showIgnoredUrls);
    }

    const stop = (e) => {
        setState('stop');
        setPaused(true);
        rsssl_api.runTest('mixed_content_scan', 'stop' ).then( ( response ) => {
            setCompletedStatus(response.completed_status);
            setData(response.data);
            setProgress(response.progress);
            setAction(response.action);
        });
    }

    /**
     * After an update, remove an item from the data array
     * @param removeItem
     */
    const removeDataItem = (removeItem) => {
        const updatedData = data.filter(
            item => item.id === removeItem.id,
        );
        setData(updatedData);
    }

    let field = props.field;
    let columns = [];
    field.columns.forEach(function(item, i) {
        let newItem = {
            name: item.name,
            sortable: item.sortable,
            grow: item.grow,
            selector: row => row[item.column],
            right: !!item.right,
        }
        columns.push(newItem);
    });

    let dataTable = data;
    if (typeof dataTable === 'object') {
        dataTable = Object.values(dataTable);
    }
    if (!Array.isArray(dataTable) ) {
        dataTable = [];
    }
    let dropItem = dropItemFromModal;
    for (const item of dataTable) {
        item.warningControl = <span className="rsssl-task-status rsssl-warning">{__("Warning", "really-simple-ssl")}</span>
        //@todo check action for correct filter or drop action.
        if ( dropItem && dropItem.url === item.blocked_url ) {
            if (dropItem.action==='ignore_url'){
                item.ignored = true;
            } else {
                item.fixed = true;
            }
        }
        //give fix and details the url as prop
        if (item.fix) {
            item.fix.url = item.blocked_url;
            item.fix.nonce = nonce;
        }
        if (item.details) {
            item.details.url = item.blocked_url;
            item.details.nonce = nonce;
            item.details.ignored = item.ignored;
        }
        if (item.location.length > 0) {
            if (item.location.indexOf('http://') !== -1 || item.location.indexOf('https://') !== -1) {
                item.locationControl =
                    <a href={item.location} target="_blank">{__("View", "really-simple-ssl")}</a>
            } else {
                item.locationControl = item.location;
            }
        }
        item.detailsControl = item.details && <ModalControl removeDataItem={removeDataItem}
                                                            handleModal={props.handleModal}
                                                            item={item}
                                                            btnText={__("Details", "really-simple-ssl")}
                                                            btnStyle={"secondary"}
                                                            modalData={item.details}/>;
        item.fixControl = item.fix && <ModalControl className={"button button-primary"}
                                                    removeDataItem={removeDataItem}
                                                    handleModal={props.handleModal}
                                                    item={item}
                                                    btnText={__("Fix", "really-simple-ssl")}
                                                    btnStyle={"primary"}
                                                    modalData={item.fix}/>;
    }

    if ( !showIgnoredUrls ) {
        dataTable = dataTable.filter(
            item => !item.ignored,
        );
    }

    //filter also recently fixed items
    dataTable = dataTable.filter(
        item => !item.fixed,
    );

    let progressOutput =progress+'%';
    let startDisabled = state === 'running';
    let stopDisabled = state !== 'running';

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


    return (
        <>
            <div className="rsssl-progress-container">
                <div className="rsssl-progress-bar" style={{width: progressOutput}} ></div>
            </div>
            {state==='running' && <div className="rsssl-current-scan-action">{action}</div>}
                {dataTable.length===0 && <>
                    <div className="rsssl-mixed-content-description">
                        {state!=='running' && completedStatus==='never' && __("No results. Start your first scan","really-simple-ssl")}
                        {state!=='running' && completedStatus==='completed' && __("Everything is now served over SSL","really-simple-ssl")}
                    </div>
                    { (state ==='running' || completedStatus!=='completed') && <div className="rsssl-mixed-content-placeholder">
                             <div></div><div></div><div></div>
                    </div>
                    }
                    { state!=='running' && completedStatus==='completed' && <div className="rsssl-shield-overlay">
                          <Icon name = "shield"  size="80px"/>
                    </div> }
                    </>}
                { dataTable.length>0 && <div className={'rsssl-mixed-content-datatable'}><DataTable
                    columns={columns}
                    data={dataTable}
                    dense
                    pagination
                    paginationResetDefaultPage={resetPaginationToggle} // optionally, a hook to reset pagination to page 1
                    noDataComponent={__("No results", "really-simple-ssl")} //or your component
                    theme="really-simple-plugins"
                    customStyles={customStyles}
                /></div>  }
            <div className="rsssl-grid-item-content-footer">
                <button className="button" disabled={startDisabled} onClick={ (e) => start(e) }>{__("Start scan","really-simple-ssl")}</button>
                <button className="button" disabled={stopDisabled} onClick={ (e) => stop(e) }>{__("Stop","really-simple-ssl")}</button>
                <ToggleControl
                    checked= { showIgnoredUrls==1 }
                    onChange={ (e) => toggleIgnoredUrls(e) }
                />
                <label>{__('Show ignored URLs', 'really-simple-ssl')}</label>
            </div>

        </>
    )

}

export default MixedContentScan;
