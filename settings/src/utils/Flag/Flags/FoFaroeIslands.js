import * as React from "react";
const SvgFoFaroeIslands = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="FO_-_Faroe_Islands_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#FO_-_Faroe_Islands_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="FO_-_Faroe_Islands_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#FO_-_Faroe_Islands_svg__b)">
        <path
          fill="#F50100"
          stroke="#2E42A5"
          d="M5-.5h-.5v5h-5v3h5v5h3v-5h9v-3h-9v-5H5Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgFoFaroeIslands;
