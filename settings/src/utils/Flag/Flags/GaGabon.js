import * as React from "react";
const SvgGaGabon = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GA_-_Gabon_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#GA_-_Gabon_svg__a)">
      <path fill="#40A8FF" d="M0 8h16v4H0V8Z" />
      <path fill="#FECA00" d="M0 4h16v4H0V4Z" />
      <path fill="#73BE4A" d="M0 0h16v4H0V0Z" />
    </g>
  </svg>
);
export default SvgGaGabon;
