import * as React from "react";
const SvgMmMyanmar = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MM_-_Myanmar_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#MM_-_Myanmar_svg__a)">
      <path fill="#E31D1C" d="M0 8h16v4H0V8Z" />
      <path fill="#5EAA22" d="M0 4h16v4H0V4Z" />
      <path fill="#FFD018" d="M0 0h16v4H0V0Z" />
      <path
        fill="#F7FCFF"
        d="M8.031 7.988 5.456 9.625l.863-2.866-1.837-1.873 2.533-.055L8.135 2l1.022 2.867 2.526.044-1.899 1.907.887 2.727-2.64-1.558Z"
      />
    </g>
  </svg>
);
export default SvgMmMyanmar;
