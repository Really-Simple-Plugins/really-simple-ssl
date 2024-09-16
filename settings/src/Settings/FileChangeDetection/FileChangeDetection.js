
import DataTableWrapper from "../DataTable/DataTableWrapper";
import {__} from "@wordpress/i18n";
import DataTableStore from "../DataTable/DataTableStore";
import * as rsssl_api from "../../utils/api";
import useFields from "../FieldsData";
import useMenu from "../../Menu/MenuData";
import {toast} from "react-toastify";

const FileChangeDetection = ({field}) => {
    const {
        clearAllData,
        setProcessing,
    } = DataTableStore();
    const { updateFieldsData, showSavedSettingsNotice } = useFields();
    const { selectedSubMenuItem} = useMenu();
    const enabled = true;
    const handleClick = async () => {
        setProcessing(true);
        try {
            const response = await rsssl_api.doAction(
                'reset_changed_files',
                {}
            );

        } catch (e) {
            console.log(e);
        } finally {
            showSavedSettingsNotice(__('File changes have been been reset', 'really-simple-ssl') );
            clearAllData();
            setProcessing(false);
            //field now should be disabled, as it's now processing
            updateFieldsData(selectedSubMenuItem);
        }
    }

    let controlButton = {
        show:true,
        onClick:handleClick,
        label:__("Reset changed files", "really-simple-ssl")
    };

    return (
        <>
            <DataTableWrapper
                field={field}
                controlButton={controlButton}
                enabled={true}
            />
        </>
    )
}

export default FileChangeDetection;