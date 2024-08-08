import DataTableStore from "../DataTableStore";
import './Buttons.scss'
import Icon from "../../../utils/Icon";
const ControlButton = ({ controlButton }) => {
    const {
        processing,
    } = DataTableStore();
    return (
        <div className="rsssl-add-button">
            <div className="rsssl-add-button__inner">
                <button
                    className="button button-secondary button-datatable rsssl-add-button__button"
                    onClick={controlButton.onClick}
                    disabled={processing}
                >
                    {processing &&  <Icon name = "loading" color = 'grey' />}
                    {controlButton.label}
                </button>
            </div>
        </div>
    );
};
export default ControlButton;