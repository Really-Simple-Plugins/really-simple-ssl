import DataTableStore from "../DataTableStore";
import './Buttons.scss'
import Icon from "../../../utils/Icon";
import {memo} from "@wordpress/element";

const RowButton = ({id, buttonData}) => {
    const {
        processing,
        rowAction,
    } = DataTableStore();
    return (
        <div className={`rsssl-action-buttons__inner`}>
            <button
                className={`button ${buttonData.className} rsssl-action-buttons__button`}
                onClick={(e) => rowAction([id], buttonData.action, buttonData.type, buttonData.reloadFields) }
                disabled={processing}
            >
                {buttonData.label}
            </button>
        </div>
    );
};
export default memo(RowButton);