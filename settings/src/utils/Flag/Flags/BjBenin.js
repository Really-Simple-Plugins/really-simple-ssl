import * as React from "react";
const SvgBjBenin = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BJ_-_Benin_svg__a"
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
    <g mask="url(#BJ_-_Benin_svg__a)">
      <path
        fill="#DD2C2B"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="M0 0v7h16V0H0Z"
        clipRule="evenodd"
      />
      <path fill="#5EAA22" d="M0 0h7v12H0z" />
    </g>
  </svg>
);
export default SvgBjBenin;
