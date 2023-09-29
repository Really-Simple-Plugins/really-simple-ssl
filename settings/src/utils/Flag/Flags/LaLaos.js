import * as React from "react";
const SvgLaLaos = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="LA_-_Laos_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#LA_-_Laos_svg__a)">
      <path fill="#E31D1C" d="M0 8h16v4H0V8Z" />
      <path fill="#2E42A5" d="M0 4h16v4H0V4Z" />
      <path fill="#E31D1C" d="M0 0h16v4H0V0Z" />
      <path
        fill="#F7FCFF"
        d="M8 7.87a1.875 1.875 0 1 0 0-3.75 1.875 1.875 0 0 0 0 3.75Z"
      />
    </g>
  </svg>
);
export default SvgLaLaos;
