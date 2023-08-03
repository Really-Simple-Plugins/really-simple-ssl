import * as React from "react";
const SvgGyGuyana = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GY_-_Guyana_svg__a"
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
    <g mask="url(#GY_-_Guyana_svg__a)">
      <path
        fill="#5EAA22"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#FECA00"
        stroke="#F7FCFF"
        d="M.5 11.293V.707L15.498 6 .5 11.293Z"
      />
      <path
        fill="#E11C1B"
        stroke="#272727"
        d="M-.5 11.978V.022L7.186 6-.5 11.978Z"
      />
    </g>
  </svg>
);
export default SvgGyGuyana;
