import React from 'react';

const AddButton = ({ getCurrentFilter, moduleName, handleOpen, processing, blockedText, allowedText }) => {
    return (
        <div className="rsssl-add-button">
                <div className="rsssl-add-button__inner">
                    <button
                        className="button button-secondary button-datatable rsssl-add-button__button"
                        onClick={handleOpen}
                        disabled={processing}
                    >
                        {allowedText}
                    </button>
                </div>
        </div>
    );
};

export default AddButton;