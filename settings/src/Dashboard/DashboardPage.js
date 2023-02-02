import GridBlock from "./GridBlock";

const DashboardPage = (props) => {
    let blocks = rsssl_settings.blocks;
    return (
        <>
            {blocks.map((block, i) => <GridBlock key={i}
                                                 block={block}
                                                 setShowOnBoardingModal={props.setShowOnBoardingModal}
                                                 isApiLoaded={props.isAPILoaded}
                                                 fields={props.fields}
                                                 highLightField={props.highLightField}
                                                 selectMainMenu={props.selectMainMenu}
                                                 getFields={props.getFields}
            />)}
        </>
    );

}
export default DashboardPage