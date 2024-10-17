import Icon from "../../utils/Icon";

const AddButton = ({ getCurrentFilter, moduleName, handleOpen, processing, blockedText, allowedText, disabled }) => {
    return (
        <div className="rsssl-add-button">
                <div className="rsssl-add-button__inner">
                    <button
                        className="button button-secondary button-datatable rsssl-add-button__button"
                        onClick={handleOpen}
                        disabled={disabled}
                    >
                        {allowedText}{processing && <Icon name = "loading" color = 'grey' />}
                    </button>
                </div>
        </div>
    );
};

export default AddButton;