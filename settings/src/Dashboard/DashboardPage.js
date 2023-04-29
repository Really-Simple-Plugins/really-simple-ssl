import GridBlock from "./GridBlock";

const DashboardPage = () => {
    let blocks = rsssl_settings.blocks;
    return (
        <>
            {blocks.map((block, i) => <GridBlock key={"grid_"+i} block={block}/>)}
        </>
    );

}
export default DashboardPage