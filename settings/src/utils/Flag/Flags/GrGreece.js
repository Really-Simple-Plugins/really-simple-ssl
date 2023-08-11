import * as React from "react";
const SvgGrGreece = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GR_-_Greece_svg__a"
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
    <g mask="url(#GR_-_Greece_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path fill="#4564F9" d="M.014 2.75h16v1.5h-16z" />
      <path
        fill="#4564F9"
        fillRule="evenodd"
        d="M0 0h16v1.5H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#4564F9"
        d="M-.029 5.5h16V7h-16zM.056 8.2h16v1.5h-16zM.051 10.75h16v1.5h-16z"
      />
      <path
        fill="#4564F9"
        fillRule="evenodd"
        d="M0 0h8v7H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M3.236 0h1.582v2.75H8v1.893H4.818V7.5H3.236V4.643H0V2.75h3.236V0Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgGrGreece;
