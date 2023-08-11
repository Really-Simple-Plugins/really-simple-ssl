import * as React from "react";
const SvgBlSaintBarthlemy = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BL_-_Saint_Barth\xE9lemy_svg__a"
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
    <g
      fillRule="evenodd"
      clipRule="evenodd"
      mask="url(#BL_-_Saint_Barth\xE9lemy_svg__a)"
    >
      <path fill="#F50100" d="M11 0h5v12h-5V0Z" />
      <path fill="#2E42A5" d="M0 0h6v12H0V0Z" />
      <path fill="#F7FCFF" d="M5 0h6v12H5V0Z" />
    </g>
  </svg>
);
export default SvgBlSaintBarthlemy;
