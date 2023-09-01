import * as React from "react";
const SvgBsBahamas = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BS_-_Bahamas_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#BS_-_Bahamas_svg__a)">
      <path fill="#FECA00" d="M0 0h16v12H0V0Z" />
      <path fill="#3CB1CF" d="M0 0v4h16V0H0ZM0 8v4h16V8H0Z" />
      <path fill="#272727" d="m0 0 8 6-8 6V0Z" />
    </g>
  </svg>
);
export default SvgBsBahamas;
