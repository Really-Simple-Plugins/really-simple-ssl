import * as React from "react";
const SvgAeUnitedArabEmirates = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="AE_-_United_Arab_Emirates_svg__a"
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
    <g mask="url(#AE_-_United_Arab_Emirates_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#5EAA22"
        fillRule="evenodd"
        d="M0 0v4h16V0H0Z"
        clipRule="evenodd"
      />
      <path
        fill="#272727"
        fillRule="evenodd"
        d="M0 8v4h16V8H0Z"
        clipRule="evenodd"
      />
      <path fill="#E31D1C" d="M0 0h5v12H0z" />
    </g>
  </svg>
);
export default SvgAeUnitedArabEmirates;
