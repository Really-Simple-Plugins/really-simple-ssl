import * as React from "react";
const SvgLvLatvia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="LV_-_Latvia_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#LV_-_Latvia_svg__a)">
      <path fill="#C51918" d="M0 7h16v5H0V7Z" />
      <path fill="#F7FCFF" d="M0 4h16v3H0V4Z" />
      <path fill="#C51918" d="M0 0h16v5H0V0Z" />
    </g>
  </svg>
);
export default SvgLvLatvia;
