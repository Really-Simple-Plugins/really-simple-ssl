import RssslModal from "../DeactivationModal/DeactivationModal";
const { __ } = wp.i18n;
const { useState, useEffect } = wp.element;

const DeactivationModal = () => {
    const [isOpen, setOpen] = useState(false);
    useEffect(() => {
        // Add an event listener to elements with the "my-link" class
        const targetPluginLink = document.getElementById('deactivate-really-simple-ssl');

        const handleClick = (event) => {
            event.preventDefault();
            setOpen(true);
        };

        // Attach the click event listener to each link element
        targetPluginLink.addEventListener('click', handleClick);

        // Clean up the event listeners when the component unmounts
        return () => {
            targetPluginLink.removeEventListener('click', handleClick);
        };
    }, []);

    return (
        <>
            <RssslModal title={__("Are you sure?", "really-simple-ssl")} isOpen={isOpen}/>
        </>
    );
}
export default DeactivationModal;