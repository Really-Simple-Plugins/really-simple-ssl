import * as React from "react";
const SvgBeBelgium = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BE_-_Belgium_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#BE_-_Belgium_svg__a)">
      <path fill="#FECA00" d="M5 0h5.5v12H5V0Z" />
      <path fill="#E31D1C" d="M10.5 0H16v12h-5.5V0Z" />
      <path fill="#1D1D1D" d="M0 0h5.5v12H0V0Z" />
    </g>
  </svg>
);
export default SvgBeBelgium;
