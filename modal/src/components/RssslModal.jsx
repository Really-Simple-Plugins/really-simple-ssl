/** @jsx wp.element.createElement */
const { Modal, Button } = wp.components;
const { useState, useEffect } = wp.element;

function RssslModal() {
    const [isOpen, setOpen] = useState(false);

    useEffect(() => {
        const showModalListener = () => {
            setOpen(true);
        };

        document.addEventListener('showRssslModalEvent', showModalListener);

        // Cleanup the listener on component unmount
        return () => {
            document.removeEventListener('showRssslModalEvent', showModalListener);
        };
    }, [isOpen]); // Add isOpen as a dependency

    return (
        <div>
            {isOpen && (
                <Modal
                    title="My Modal Title"
                    onRequestClose={() => setOpen(false)}
                >
                    <p>This is the modal content.</p>
                </Modal>
            )}
        </div>
    );
}

export default RssslModal;