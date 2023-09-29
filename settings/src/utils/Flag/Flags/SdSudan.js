import * as React from "react";
const SvgSdSudan = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SD_-_Sudan_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#SD_-_Sudan_svg__a)">
      <path fill="#F7FCFF" d="M0 0h16v12H0V0Z" />
      <path fill="#E31D1C" d="M0 0v4h16V0H0Z" />
      <path fill="#272727" d="M0 8v4h16V8H0Z" />
      <path fill="#5EAA22" d="m0 0 8 6-8 6V0Z" />
    </g>
  </svg>
);
export default SvgSdSudan;
