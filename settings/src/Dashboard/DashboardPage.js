import GridBlock from "./GridBlock";

const DashboardPage = (props) => {
    let blocks = rsssl_settings.blocks;
    return (
        <>
            {blocks.map((block, i) => <GridBlock key={i}
                                                 block={block}
                                                 setShowOnBoardingModal={this.props.setShowOnBoardingModal}
                                                 isApiLoaded={this.props.isAPILoaded}
                                                 fields={this.props.fields}
                                                 highLightField={this.props.highLightField}
                                                 selectMainMenu={this.props.selectMainMenu}
                                                 getFields={this.props.getFields}
            />)}
        </>
    );

}
export default DashboardPage