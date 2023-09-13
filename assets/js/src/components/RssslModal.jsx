/** @jsx wp.element.createElement */
const { Modal, Button } = wp.components;
const { useState, useEffect } = wp.element;

function RssslModal() {
    const [isOpen, setOpen] = useState(false);

    useEffect(() => {
        const showModalListener = () => {
            console.log("showRssslModalEvent detected we should open the modal");
            setOpen(true);
        };

        document.addEventListener('showRssslModalEvent', showModalListener);

        // Cleanup the listener on component unmount
        return () => {
            document.removeEventListener('showRssslModalEvent', showModalListener);
        };
    }, []);  // Removed [isOpen] to avoid unnecessary re-registrations of the event listener

    return (
        <div>
            {isOpen && (
                <Modal
                    title="My Modal Title"
                    onRequestClose={() => setOpen(false)}
                >
                    <p>This is the modal content.</p>
                    <Button onClick={() => setOpen(false)}>Close Modal</Button>
                </Modal>
            )}
        </div>
    );
}

export default RssslModal;
