import * as React from "react";
const SvgGmGambia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GM_-_Gambia_svg__a"
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
    <g mask="url(#GM_-_Gambia_svg__a)">
      <path
        fill="#5EAA22"
        fillRule="evenodd"
        d="M0 8h16v4H0V8Z"
        clipRule="evenodd"
      />
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0h16v4H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#3D58DB"
        stroke="#fff"
        strokeWidth={1.5}
        d="M0 4.25h-.75v3.5h17.5v-3.5H0Z"
      />
    </g>
  </svg>
);
export default SvgGmGambia;
