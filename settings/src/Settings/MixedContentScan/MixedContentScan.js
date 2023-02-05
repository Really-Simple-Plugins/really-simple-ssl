import {useState, useEffect} from "@wordpress/element";
import DataTable, { createTheme }  from "react-data-table-component";
import {ToggleControl} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as rsssl_api from "../../utils/api";
import ModalControl from "../../Modal/ModalControl";
import Icon from "../../utils/Icon";
import UseMixedContent from "./MixedContentData";
import useModal from "../../Modal/ModalData";

const MixedContentScan = (props) => {
    const {fixedItems, ignoredItems} = useModal();
    const {fetchMixedContentData, mixedContentData, runScanIteration, start, stop, dataLoaded, action, scanStatus, progress, completedStatus, nonce, removeDataItem, ignoreDataItem} = UseMixedContent();
    const [showIgnoredUrls, setShowIgnoredUrls] = useState(false);
    const [resetPaginationToggle, setResetPaginationToggle] = useState(false);

    useEffect( () => {
        fetchMixedContentData();
    }, [] );

    useEffect( () => {
        if (scanStatus==='running') {
            runScanIteration()
        }
    }, [progress, scanStatus] );

    const toggleIgnoredUrls = (e) => {
        setShowIgnoredUrls(!showIgnoredUrls);
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

    let dataTable = dataLoaded ? mixedContentData : [];

    for (const item of dataTable) {
        item.warningControl = <span className="rsssl-task-status rsssl-warning">{__("Warning", "really-simple-ssl")}</span>

        //check if an item was recently fixed or ignored, and update the table
        if (fixedItems.includes(item.id)) {
            item.fixed = true;
        }
        if (ignoredItems.includes(item.id)) {
            item.ignored = true;
        }
        //give fix and details the url as prop
        if ( item.fix ) {
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
        item.detailsControl = item.details &&
            <ModalControl
                            handleModal={props.handleModal}
                            item={item}
                            id={item.id}
                            btnText={__("Details", "really-simple-ssl")}
                            btnStyle={"secondary"}
                            modalData={item.details}/>;
        item.fixControl = item.fix &&
            <ModalControl className={"button button-primary"}
                            handleModal={props.handleModal}
                            item={item}
                          id={item.id}
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
    let startDisabled = scanStatus === 'running';
    let stopDisabled = scanStatus !== 'running';

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
            {scanStatus==='running' && <div className="rsssl-current-scan-action">{action}</div>}
                {dataTable.length===0 && <>
                    <div className="rsssl-mixed-content-description">
                        {scanStatus!=='running' && completedStatus==='never' && __("No results. Start your first scan","really-simple-ssl")}
                        {scanStatus!=='running' && completedStatus==='completed' && __("Everything is now served over SSL","really-simple-ssl")}
                    </div>
                    { (scanStatus ==='running' || completedStatus!=='completed') && <div className="rsssl-mixed-content-placeholder">
                             <div></div><div></div><div></div>
                    </div>
                    }
                    { scanStatus!=='running' && completedStatus==='completed' && <div className="rsssl-shield-overlay">
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
                <button className="button" disabled={startDisabled} onClick={ () => start() }>{__("Start scan","really-simple-ssl")}</button>
                <button className="button" disabled={stopDisabled} onClick={ () => stop() }>{__("Stop","really-simple-ssl")}</button>
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
