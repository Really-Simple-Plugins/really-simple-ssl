import {__, _n} from "@wordpress/i18n";
import DataTableStore from "../DataTableStore";
import MultiSelectButton from "../Buttons/MultiSelectButton";
import './SelectedRowsControl.scss'
import {memo} from "@wordpress/element";
import MenuItem from "../../../Menu/MenuItem";

const SelectedRowsControl = ({ rowsSelected, buttonData }) => {
    const {
        processing,
        filteredData,
    } = DataTableStore();
    //ensure that all items in the rowsSelected array still exist in the filteredData array
    //after a delete this might not be the case
    let rowsSelectedFiltered = rowsSelected.filter(selectedRow =>
        filteredData.some(filteredRow => filteredRow.id === selectedRow.id)
    );

    if ( rowsSelectedFiltered.length === 0 ) {
        return null;
    }

    //parse ids from rowsSelected into array
    const ids = rowsSelectedFiltered.map((row) => row.id);
    return (
        <div className="rsssl-selected-rows-control">
            <div className={"rsssl-multiselect-datatable-form rsssl-primary"}>
                <div>
                    {_n( "You have selected %d row", "You have selected %d rows", rowsSelectedFiltered.length, 'really-simple-ssl'  ).replace('%d', rowsSelectedFiltered.length )}
                </div>
                <div className="rsssl-action-buttons">
                    <>
                        { buttonData.map((buttonItem, i) => <MultiSelectButton key={"multiselectButton-"+i} ids={ids} buttonData={buttonItem} /> ) }
                    </>
                </div>
            </div>
        </div>
    )
}

export default memo(SelectedRowsControl);
