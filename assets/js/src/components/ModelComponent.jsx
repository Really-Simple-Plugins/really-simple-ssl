import React, { useEffect, useState } from 'react';

function Modal({ isOpen, onClose, title, children }) {
    if (!isOpen) return null;

    return (
        <div style={overlayStyle}>
            <div style={modalStyle}>
                <h2>{title}</h2>
                <button onClick={onClose}>Close</button>
                {children}
            </div>
        </div>
    );
}

const overlayStyle = {
    position: 'fixed',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1000
};

const modalStyle = {
    backgroundColor: '#fff',
    padding: '20px',
    borderRadius: '5px',
    maxWidth: '500px',
    minHeight: '300px',
    margin: '0 auto',
    zIndex: 1001
};

function RssslModal() {
    const [ isOpen, setOpen ] = useState(false);

    useEffect(() => {
        const showModalListener = () => {
            console.log("showMyPluginModalEvent detected");
            setOpen(true);
        };

        document.addEventListener('showRssslModalEvent', showModalListener);

        // Cleanup the listener on component unmount
        return () => {
            document.removeEventListener('showRssslModalEvent', showModalListener);
        };
    }, []);

    console.log("MyModal component rendered");

    return (
        <Modal
            isOpen={isOpen}
            title={"Are you sure?"}
            onClose={() => setOpen(false)}
        >
            {/* Your modal content here */}
        </Modal>
    );
}

export default RssslModal;
