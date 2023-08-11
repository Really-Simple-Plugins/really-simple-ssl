import * as React from "react";
const SvgGhGhana = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GH_-_Ghana_svg__a"
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
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#GH_-_Ghana_svg__a)">
      <path fill="#5EAA22" d="M0 8h16v4H0V8Z" />
      <path fill="#FECA00" d="M0 4h16v4H0V4Z" />
      <path fill="#E11C1B" d="M0 0h16v4H0V0Z" />
      <path
        fill="#1D1D1D"
        d="M8.038 7.245 6.295 8.454l.557-2.07-1.28-1.323 1.733-.072.733-2.047.733 2.047h1.73L9.223 6.384l.639 1.948-1.825-1.087Z"
        opacity={0.9}
      />
    </g>
  </svg>
);
export default SvgGhGhana;
