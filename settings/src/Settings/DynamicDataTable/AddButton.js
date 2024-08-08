const AddButton = ({ getCurrentFilter, moduleName, handleOpen, processing, blockedText, allowedText }) => {
    let buttonText = getCurrentFilter(moduleName) === 'blocked' ?  blockedText : allowedText;

    return (
        <div className="rsssl-add-button">
            {(getCurrentFilter(moduleName) === 'blocked' || getCurrentFilter(moduleName) === 'allowed') && (
                <div className="rsssl-add-button__inner">
                    <button
                        className="button button-secondary button-datatable rsssl-add-button__button"
                        onClick={handleOpen}
                        disabled={processing}
                    >
                        {buttonText}
                    </button>
                </div>
            )}
        </div>
    );
};

export default AddButton;