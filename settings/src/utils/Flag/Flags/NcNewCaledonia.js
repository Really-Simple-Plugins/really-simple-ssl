import * as React from "react";
const SvgNcNewCaledonia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="NC_-_New_Caledonia_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <rect width={16} height={12} fill="#fff" rx={0} />
    </mask>
    <g
      fillRule="evenodd"
      clipRule="evenodd"
      mask="url(#NC_-_New_Caledonia_svg__a)"
    >
      <path fill="#F50100" d="M11 0h5v12h-5V0Z" />
      <path fill="#2E42A5" d="M0 0h6v12H0V0Z" />
      <path fill="#F7FCFF" d="M5 0h6v12H5V0Z" />
    </g>
  </svg>
);
export default SvgNcNewCaledonia;
