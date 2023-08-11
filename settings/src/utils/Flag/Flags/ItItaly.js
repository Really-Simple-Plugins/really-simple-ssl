import * as React from "react";
const SvgItItaly = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="IT_-_Italy_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#IT_-_Italy_svg__a)">
      <path fill="#C51918" d="M11 0h5v12h-5V0Z" />
      <path fill="#5EAA22" d="M0 0h6v12H0V0Z" />
      <path fill="#fff" d="M5 0h6v12H5V0Z" />
    </g>
  </svg>
);
export default SvgItItaly;
