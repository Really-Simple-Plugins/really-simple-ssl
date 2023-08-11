import * as React from "react";
const SvgClChile = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="CL_-_Chile_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#CL_-_Chile_svg__a)">
      <path fill="#3D58DB" d="M0 0h7v7H0V0Z" />
      <path fill="#F7FCFF" d="M7-1h9v8H7v-8Z" />
      <path fill="#E31D1C" d="M0 7h16v5H0V7Z" />
      <path
        fill="#F7FCFF"
        d="M3.507 4.892 1.605 6.027l.939-1.932L.882 2.84 2.8 2.82l.723-1.714.467 1.713 1.816.009-1.382 1.227.718 1.972-1.636-1.135Z"
      />
    </g>
  </svg>
);
export default SvgClChile;
