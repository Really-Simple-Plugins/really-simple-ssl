import * as React from "react";
const SvgBdBangladesh = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BD_-_Bangladesh_svg__a"
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
    <g mask="url(#BD_-_Bangladesh_svg__a)">
      <path fill="#38A17E" d="M0 0h16v12H0z" />
      <path
        fill="#F72E45"
        fillRule="evenodd"
        d="M6 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgBdBangladesh;
